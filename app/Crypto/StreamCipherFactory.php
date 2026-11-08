<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * Pabrik cipher aliran Hybrid Trio (A5/1 + RC4-128 + ChaCha20).
 *
 * Perbaikan terhadap makalah sumber:
 *  - HKDF lengkap RFC 5869 (Extract dengan salt acak, Expand dengan
 *    counter dan info), bukan sekadar HMAC per label.
 *  - K_MAC ikut diturunkan dari HKDF sehingga Encrypt-then-MAC
 *    benar-benar terpisah dari subkunci cipher.
 *  - A5/1 menjalankan 100 clock pemanasan seperti GSM nyata.
 *  - ChaCha20 RFC 8439 mengeluarkan keystream byte penuh sehingga
 *    data biner apa pun bisa diproses, bukan hanya ASCII 7-bit.
 *  - Kelemahan nonce reuse tetap ada (tidak bisa ditutup kaskade
 *    XOR); di sini nonce dibangkitkan acak per pesan dan tag HMAC
 *    diverifikasi (hash_equals, waktu konstan) sebelum dekripsi.
 */
final class StreamCipherFactory
{
    public const CIPHER_TRIPLE = 'TRIPLE_STREAM';

    public const INFO_LFSR = 'TRIO-LFSR-KEY-V1';

    public const INFO_RC4 = 'TRIO-RC4-KEY-V1';

    public const INFO_CHACHA = 'TRIO-CHACHA-KEY-V1';

    public const INFO_MAC = 'TRIO-MAC-KEY-V1';

    private const SALT_BYTES = 16;

    private const NONCE_BYTES = 12;

    /**
     * Turunkan keempat subkunci dari satu passphrase + salt.
     *
     * @return array{lfsr: string, rc4: string, chacha: string, mac: string, prk: string}
     */
    public static function turunkanKunci(string $passphrase, string $salt): array
    {
        $prk = Hkdf::extract($salt, $passphrase);

        return [
            'lfsr' => Hkdf::expand($prk, self::INFO_LFSR, 8),
            'rc4' => Hkdf::expand($prk, self::INFO_RC4, 16),
            'chacha' => Hkdf::expand($prk, self::INFO_CHACHA, 32),
            'mac' => Hkdf::expand($prk, self::INFO_MAC, 32),
            'prk' => $prk,
        ];
    }

    /**
     * Rangkai tiga engine kaskade dalam urutan tetap:
     * LFSR -> RC4 -> ChaCha20. crypt() bersifat involutori.
     */
    public static function rangkaiTrio(string $passphrase, string $salt, string $nonce): object
    {
        $keys = self::turunkanKunci($passphrase, $salt);

        $lfsr = new A51($keys['lfsr']);
        $rc4 = new Rc4($keys['rc4'], 128);
        $chacha = new ChaCha20($keys['chacha'], $nonce);

        return new class($lfsr, $rc4, $chacha)
        {
            public function __construct(
                private readonly A51 $lfsr,
                private readonly Rc4 $rc4,
                private readonly ChaCha20 $chacha,
            ) {}

            public function crypt(string $data): string
            {
                return $this->chacha->crypt($this->rc4->crypt($this->lfsr->crypt($data)));
            }
        };
    }

    /**
     * Enkripsi lengkap dengan Encrypt-then-MAC.
     *
     * @return array{salt: string, nonce: string, ciphertext: string, tag: string}
     */
    public static function enkripsi(string $plaintext, string $passphrase): array
    {
        $salt = random_bytes(self::SALT_BYTES);
        $nonce = random_bytes(self::NONCE_BYTES);

        $trio = self::rangkaiTrio($passphrase, $salt, $nonce);
        $ciphertext = $trio->crypt($plaintext);

        $keys = self::turunkanKunci($passphrase, $salt);
        $tag = hash_hmac('sha256', $salt.$nonce.$ciphertext, $keys['mac'], true);

        return [
            'salt' => $salt,
            'nonce' => $nonce,
            'ciphertext' => $ciphertext,
            'tag' => $tag,
        ];
    }

    /**
     * Verifikasi tag (waktu konstan) lalu dekripsi.
     *
     * @throws \RuntimeException bila tag tidak cocok atau paket tak lengkap
     */
    public static function dekripsi(array $bundle, string $passphrase): string
    {
        foreach (['salt', 'nonce', 'ciphertext', 'tag'] as $field) {
            if (! isset($bundle[$field])) {
                throw new \RuntimeException("Paket dekripsi tidak lengkap: {$field} hilang.");
            }
        }

        $keys = self::turunkanKunci($passphrase, $bundle['salt']);
        $expected = hash_hmac('sha256', $bundle['salt'].$bundle['nonce'].$bundle['ciphertext'], $keys['mac'], true);

        if (! hash_equals($expected, $bundle['tag'])) {
            throw new \RuntimeException('Verifikasi tag HMAC gagal: data termodifikasi atau passphrase salah.');
        }

        return self::rangkaiTrio($passphrase, $bundle['salt'], $bundle['nonce'])->crypt($bundle['ciphertext']);
    }

    /**
     * Trace per karakter untuk lab, mode ASCII 7-bit seperti makalah:
     * A5/1 memakai 7 detak per karakter, RC4-128 dilingkup mod 128,
     * dan byte pertama blok ChaCha20 diproyeksikan AND 0x7F.
     *
     * @return array{rows: list<array{index: int, char: string, plain: int, z_lfsr: int, z_rc4: int, z_chacha: int, stage1: int, stage2: int, cipher: int}>, cipher: string}
     */
    public static function traceAscii7(string $plaintext, string $passphrase, string $salt = '', string $nonce = ''): array
    {
        $salt = $salt !== '' ? $salt : str_repeat("\x01", self::SALT_BYTES);
        $nonce = $nonce !== '' ? $nonce : str_repeat("\x01", self::NONCE_BYTES);

        $keys = self::turunkanKunci($passphrase, $salt);

        $lfsr = new A51($keys['lfsr']);
        $rc4 = new Rc4($keys['rc4'], 128);
        $chacha = new ChaCha20($keys['chacha'], $nonce);

        // Keystream ChaCha20 diperoleh dari XOR nol (byte pertama
        // diproyeksikan ke 7-bit dengan AND 0x7F seperti makalah).
        $length = strlen($plaintext);
        $zChachaRaw = $chacha->crypt(str_repeat("\0", $length));

        $rows = [];
        $cipher = '';

        foreach (str_split($plaintext) as $n => $char) {
            $plain = ord($char);

            $zLfsr = $lfsr->keystreamByte(7);
            $zRc4 = $rc4->nextByte() % 128;
            $zChacha = ord($zChachaRaw[$n]) & 0x7F;

            $stage1 = $plain ^ $zLfsr;
            $stage2 = $stage1 ^ $zRc4;
            $stage3 = $stage2 ^ $zChacha;

            $rows[] = [
                'index' => $n,
                'char' => $char,
                'plain' => $plain,
                'z_lfsr' => $zLfsr,
                'z_rc4' => $zRc4,
                'z_chacha' => $zChacha,
                'stage1' => $stage1,
                'stage2' => $stage2,
                'cipher' => $stage3,
            ];

            $cipher .= chr($stage3);
        }

        return ['rows' => $rows, 'cipher' => $cipher];
    }
}
