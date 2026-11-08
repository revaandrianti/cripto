<?php

namespace Tests\Unit\Crypto;

use App\Crypto\StreamCipherFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StreamCipherFactoryTest extends TestCase
{
    /**
     * Rantai XOR Bab V makalah: P = "KRIPTO" dengan keystream yang
     * tercantum di makalah (Z_LFSR, Z_RC4, Z_ChaCha) harus menghasilkan
     * ciphertext "hFv+b+".
     */
    #[Test]
    public function rantai_xor_makalah_menghasilkan_hfv_b_plus(): void
    {
        $plaintext = 'KRIPTO';
        $zLfsr = [108, 21, 60, 0, 59, 98];
        $zRc4 = [122, 88, 0, 79, 12, 84];
        $zChacha = [53, 89, 3, 52, 1, 82];

        $cipher = '';

        foreach (str_split($plaintext) as $n => $char) {
            $stage1 = ord($char) ^ $zLfsr[$n];
            $stage2 = $stage1 ^ $zRc4[$n];
            $cipher .= chr($stage2 ^ $zChacha[$n]);
        }

        $this->assertSame('hFv+b+', $cipher);
    }

    #[Test]
    public function trace_ascii7_konsisten_dengan_rantai_xor(): void
    {
        $trace = StreamCipherFactory::traceAscii7('KRIPTO', 'KUNCI');

        $this->assertCount(6, $trace['rows']);

        foreach ($trace['rows'] as $row) {
            $this->assertSame(
                $row['plain'] ^ $row['z_lfsr'] ^ $row['z_rc4'] ^ $row['z_chacha'],
                $row['cipher'],
                'Setiap baris trace harus memenuhi C = P xor Z_total.'
            );
            $this->assertLessThan(128, $row['cipher'], 'Keluaran mode ASCII 7-bit harus di bawah 128.');
        }
    }

    #[Test]
    public function enkripsi_lalu_dekripsi_mengembalikan_plaintext(): void
    {
        $passphrase = 'frasa-sandi-induk';
        $plaintext = 'Pesan uji: Hybrid Trio dengan data biner '."\xC3\xA9\xFF\x00".' dan panjang bebas.';

        $bundle = StreamCipherFactory::enkripsi($plaintext, $passphrase);
        $recovered = StreamCipherFactory::dekripsi($bundle, $passphrase);

        $this->assertSame($plaintext, $recovered);
        $this->assertSame(16, strlen($bundle['salt']));
        $this->assertSame(12, strlen($bundle['nonce']));
        $this->assertSame(32, strlen($bundle['tag']));
    }

    #[Test]
    public function setiap_enkripsi_menghasilkan_salt_dan_nonce_berbeda(): void
    {
        $a = StreamCipherFactory::enkripsi('pesan sama', 'passphrase sama');
        $b = StreamCipherFactory::enkripsi('pesan sama', 'passphrase sama');

        $this->assertNotSame($a['salt'], $b['salt']);
        $this->assertNotSame($a['nonce'], $b['nonce']);
        $this->assertNotSame($a['ciphertext'], $b['ciphertext']);
    }

    #[Test]
    public function satu_bit_ciphertext_diubah_membuat_verifikasi_gagal(): void
    {
        $bundle = StreamCipherFactory::enkripsi('integritas penting', 'passphrase');

        $bundle['ciphertext'][0] = $bundle['ciphertext'][0] ^ "\x01";

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Verifikasi tag HMAC gagal');

        StreamCipherFactory::dekripsi($bundle, 'passphrase');
    }

    #[Test]
    public function passphrase_salah_ditolak(): void
    {
        $bundle = StreamCipherFactory::enkripsi('rahasia', 'passphrase-benar');

        $this->expectException(\RuntimeException::class);

        StreamCipherFactory::dekripsi($bundle, 'passphrase-salah');
    }

    #[Test]
    public function subkunci_dari_passphrase_sama_berbeda_antar_label(): void
    {
        $keys = StreamCipherFactory::turunkanKunci('passphrase', str_repeat('s', 16));

        $this->assertSame(8, strlen($keys['lfsr']));
        $this->assertSame(16, strlen($keys['rc4']));
        $this->assertSame(32, strlen($keys['chacha']));
        $this->assertSame(32, strlen($keys['mac']));

        $this->assertCount(4, array_unique([$keys['lfsr'], $keys['rc4'], $keys['chacha'], $keys['mac']]));
    }
}
