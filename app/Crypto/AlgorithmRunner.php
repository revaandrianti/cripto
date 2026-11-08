<?php

declare(strict_types=1);

namespace App\Crypto;

use Illuminate\Support\Facades\Cache;
use phpseclib3\Crypt\Blowfish;
use phpseclib3\Crypt\DES;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\EC as Ec;
use phpseclib3\Crypt\RSA as Rsa;
use phpseclib3\Crypt\Salsa20;
use phpseclib3\Crypt\TripleDES;
use phpseclib3\Crypt\Twofish;

/**
 * Dispatcher pengujian algoritma untuk halaman Pengujian.
 *
 * Setiap algoritma menghasilkan keluaran deterministik dari
 * (pesan, kunci, aksi) sehingga hasil bisa diuji bolak-balik
 * tanpa menyimpan IV: IV diturunkan dari kunci (dengan catatan
 * bahwa sistem nyata memakai IV acak).
 *
 * Sumber engine: openssl (AES/Camellia/mode), phpseclib3 (DES,
 * 3DES, Blowfish, Twofish, Salsa20), kelas App\Crypto sendiri
 * (RC4, A5/1, ChaCha20, Hybrid Trio), dan bcmath untuk bagian
 * matematika diskrit.
 */
final class AlgorithmRunner
{
    /**
     * Katalog algoritma yang bisa diuji, dikelompokkan per modul.
     *
     * @return array<string, array<string, array{label: string, kind: string, note: string}>>
     */
    public static function katalog(): array
    {
        return [
            'Block Cipher' => [
                'aes' => ['label' => 'AES-256-GCM', 'kind' => 'symmetric', 'note' => 'Authenticated encryption: tag GCM ikut diverifikasi saat dekripsi.'],
                'twofish' => ['label' => 'Twofish-CBC', 'kind' => 'symmetric', 'note' => 'Finalis AES berbasis Feistel, kunci 128 bit (phpseclib).'],
                'camellia' => ['label' => 'Camellia-128-CBC', 'kind' => 'symmetric', 'note' => 'Cipher Jepang setara AES, tersedia di OpenSSL.'],
                '3des' => ['label' => '3DES-CBC', 'kind' => 'symmetric', 'note' => 'Tiga tahap DES dengan kunci 24 byte (phpseclib).'],
                'des' => ['label' => 'DES-CBC', 'kind' => 'symmetric', 'note' => 'Kunci efektif 56 bit; hanya untuk pembelajaran.'],
                'blowfish' => ['label' => 'Blowfish-CBC', 'kind' => 'symmetric', 'note' => 'Blok 64 bit, kunci variabel (phpseclib).'],
                'idea' => ['label' => 'IDEA', 'kind' => 'symmetric', 'note' => 'Blok 64 bit, 8 ronde; implementasi PHP sendiri, terverifikasi vektor referensi ETH Zurich.'],
                'rc5' => ['label' => 'RC5-32/12/16', 'kind' => 'symmetric', 'note' => 'Rotasi bergantung data; implementasi PHP sendiri, vektor CBC RFC 2040.'],
                'rc6' => ['label' => 'RC6-32/20/16', 'kind' => 'symmetric', 'note' => 'Finalis AES; implementasi PHP sendiri, vektor draf IETF Krovetz.'],
                'cast-128' => ['label' => 'CAST-128', 'kind' => 'symmetric', 'note' => 'Feistel 16 ronde RFC 2144; implementasi PHP sendiri, tiga vektor resmi (kunci 128/80/40 bit).'],
                'seed' => ['label' => 'SEED', 'kind' => 'symmetric', 'note' => 'Standar Korea 16 ronde (RFC 4269); implementasi PHP sendiri, vektor resmi B.1 dan B.3.'],
                'serpent' => ['label' => 'Serpent-128', 'kind' => 'symmetric', 'note' => 'Finalis AES 32 ronde bitslice; implementasi PHP sendiri, vektor resmi kernel Linux.'],
                'skipjack' => ['label' => 'Skipjack', 'kind' => 'symmetric', 'note' => 'Cipher NSA Clipper 32 ronde; implementasi PHP sendiri, contoh spesifikasi NIST.'],
                'ecb' => ['label' => 'Mode ECB (AES-256)', 'kind' => 'symmetric', 'note' => 'Blok identik menghasilkan ciphertext identik; bandingkan dengan CBC.'],
                'cbc' => ['label' => 'Mode CBC (AES-256)', 'kind' => 'symmetric', 'note' => 'Setiap blok di-XOR dengan ciphertext sebelumnya.'],
                'cfb' => ['label' => 'Mode CFB (AES-256)', 'kind' => 'symmetric', 'note' => 'Block cipher menjadi mode aliran (feedback).'],
                'ofb' => ['label' => 'Mode OFB (AES-256)', 'kind' => 'symmetric', 'note' => 'Keystream dibangkitkan dari enkripsi IV berulang.'],
                'ctr' => ['label' => 'Mode CTR (AES-256)', 'kind' => 'symmetric', 'note' => 'Counter dienkripsi menjadi keystream.'],
            ],
            'Stream Cipher' => [
                'rc4' => ['label' => 'RC4 (N=256)', 'kind' => 'stream', 'note' => 'KSA + PRGA. Sudah dilarang di TLS; untuk belajar.'],
                'a5-1' => ['label' => 'A5/1 (LFSR GSM)', 'kind' => 'stream', 'note' => 'Tiga LFSR 64 bit, satu detak per bit keluaran.'],
                'a5-2' => ['label' => 'A5/2 (GSM Export)', 'kind' => 'stream', 'note' => 'Varian ekspor GSM memakai 4 LFSR dan aturan majority clocking R4.'],
                'lfsr' => ['label' => 'LFSR (basis A5/1)', 'kind' => 'stream', 'note' => 'Keystream bit per bit dari register geser linier.'],
                'otp' => ['label' => 'One-Time Pad (demo)', 'kind' => 'stream', 'note' => 'Demo memakai keystream dari kunci; OTP sejati butuh kunci acak sekali pakai.'],
                'chacha20' => ['label' => 'ChaCha20 (RFC 8439)', 'kind' => 'stream', 'note' => 'ARX 20 putaran; standar modern (TLS 1.3).'],
                'salsa20' => ['label' => 'Salsa20', 'kind' => 'stream', 'note' => 'Pendahulu ChaCha20, ARX 20 putaran (phpseclib).'],
                'rabbit' => ['label' => 'Rabbit (RFC 4503)', 'kind' => 'stream', 'note' => 'Stream cipher 128-bit berkecepatan tinggi dengan 8 variabel status dan counter.'],
                'trivium' => ['label' => 'Trivium (eSTREAM)', 'kind' => 'stream', 'note' => 'Hardware stream cipher ringan 80-bit dengan register geser non-linear 288 bit.'],
                'hybrid-trio' => ['label' => 'Hybrid Trio (A5/1+RC4+ChaCha20)', 'kind' => 'special', 'note' => 'Kaskade tiga cipher + HKDF + Encrypt-then-MAC.'],
            ],
            'Fungsi Utama' => [
                'sha-256' => ['label' => 'Hash SHA-256', 'kind' => 'hash', 'note' => 'Sidik jari 256 bit; kunci tidak diperlukan.'],
                'sha-512' => ['label' => 'Hash SHA-512', 'kind' => 'hash', 'note' => 'Sidik jari 512 bit keluarga SHA-2.'],
                'md5' => ['label' => 'Hash MD5', 'kind' => 'hash', 'note' => '128 bit; kolisi praktis, jangan untuk keamanan.'],
                'sha-1' => ['label' => 'Hash SHA-1', 'kind' => 'hash', 'note' => '160 bit; kolisi sudah ditemukan (SHAttered).'],
                'sha-3' => ['label' => 'Hash SHA3-256', 'kind' => 'hash', 'note' => 'Konstruksi sponge Keccak.'],
                'hmac' => ['label' => 'HMAC-SHA256', 'kind' => 'mac', 'note' => 'MAC dengan kunci rahasia + SHA-256.'],
                'cmac-aes' => ['label' => 'CMAC-AES (RFC 4493)', 'kind' => 'mac', 'note' => 'CBC-MAC dengan subkunci K1/K2 dari GF(2^128).'],
                'aes-encryption' => ['label' => 'Enkripsi AES-GCM', 'kind' => 'symmetric', 'note' => 'Menjaga kerahasiaan dengan AES-256-GCM.'],
                'rsa-oaep' => ['label' => 'RSA-OAEP (demo)', 'kind' => 'special', 'note' => 'Setiap uji membangkitkan pasangan kunci RSA-2048 baru lalu mengenkripsi-dekripsi.'],
                'rsa-signature' => ['label' => 'Tanda Tangan RSA (demo)', 'kind' => 'special', 'note' => 'Tanda tangan RSA PKCS#1 v1.5 + verifikasi pada kunci demo.'],
                'rsa-pss' => ['label' => 'RSA-PSS (demo)', 'kind' => 'special', 'note' => 'Tanda tangan digital RSA probabilistik dengan Probabilistic Signature Scheme (PSS).'],
                'ecc' => ['label' => 'ECC / ECIES (demo)', 'kind' => 'special', 'note' => 'Skema enkripsi kurva eliptik: ECDH P-256 + KDF + AES-256-GCM authenticated encryption.'],
            ],
            'Dasar Matematika' => [
                'modulo' => ['label' => 'Aritmetika Modulo', 'kind' => 'math', 'note' => 'Sisa pembagian: a mod n. Pesan = a, kunci = n.'],
                'gcd' => ['label' => 'FPB / GCD (Euclid)', 'kind' => 'math', 'note' => 'Mencari faktor persekutuan terbesar gcd(a, b). Pesan = a, Kunci = b.'],
                'extended-euclidean' => ['label' => 'Algoritma Euclid Diperluas', 'kind' => 'math', 'note' => 'Mencari koefisien ax + by = gcd(a, b) dan invers modulo a^(-1) mod b.'],
                'bilangan-prima' => ['label' => 'Uji Bilangan Prima', 'kind' => 'math', 'note' => 'Uji keprimaan deterministik untuk bilangan bulat n.'],
                'faktorisasi' => ['label' => 'Faktorisasi Prima Lengkap', 'kind' => 'math', 'note' => 'Dekomposisi bilangan bulat n menjadi perkalian faktor-faktor primanya.'],
                'phi-euler' => ['label' => 'Fungsi Totient Euler φ(n)', 'kind' => 'math', 'note' => 'Banyaknya bilangan bulat positif 1 ≤ k ≤ n yang koprima dengan n.'],
                'fermat' => ['label' => 'Teorema Kecil Fermat', 'kind' => 'math', 'note' => 'Untuk prima p dan gcd(a,p)=1, berlaku a^(p-1) ≡ 1 mod p. Pesan = a, Kunci = p.'],
                'euler' => ['label' => 'Teorema Euler', 'kind' => 'math', 'note' => 'Untuk gcd(a,n)=1, berlaku a^φ(n) ≡ 1 mod n. Pesan = a, Kunci = n.'],
                'finite-field' => ['label' => 'Medan Hingga GF(2^8)', 'kind' => 'math', 'note' => 'Perkalian elemen di GF(2^8) dengan polinomial reduksi AES m(x) = 0x11B.'],
                'discrete-logarithm' => ['label' => 'Logaritma Diskrit (BSGS)', 'kind' => 'math', 'note' => 'Mencari nilai x pada g^x ≡ h mod p (Baby-step Giant-step).'],
                'elliptic-curve' => ['label' => 'Titik Kurva Eliptik P-256', 'kind' => 'math', 'note' => 'Generator titik G dan perkalian skalar k*G pada kurva NIST P-256.'],
                'faktorisasi-rsa' => ['label' => 'Faktorisasi (Pollard rho)', 'kind' => 'math', 'note' => 'Pesan = bilangan n yang akan difaktorkan (contoh: 3233).'],
                'rabin' => ['label' => 'Rabin mod n = p x q', 'kind' => 'math', 'note' => 'Demo kuadrat modulo dan akar via CRT dengan p = 67, q = 79.'],
                'diffie-hellman' => ['label' => 'Diffie-Hellman (demo)', 'kind' => 'math', 'note' => 'Kesepakatan kunci di grup 1024-bit RFC 3526 dengan bcmath.'],
                'elgamal' => ['label' => 'ElGamal (demo)', 'kind' => 'math', 'note' => 'Enkripsi per byte di Z_2579 dengan kunci ephemeral; untuk belajar.'],
                'knapsack-merkle-hellman' => ['label' => 'Knapsack Merkle-Hellman', 'kind' => 'math', 'note' => 'Barisan superincreasing 16 item; bit pesan dijumlah pada kunci publik.'],
                'ecdsa' => ['label' => 'ECDSA P-256 (demo)', 'kind' => 'special', 'note' => 'Tanda tangan di kurva P-256 dengan OpenSSL, lalu verifikasi.'],
                'ecdh' => ['label' => 'ECDH P-256 (demo)', 'kind' => 'special', 'note' => 'Dua pihak menurunkan shared secret yang sama via OpenSSL.'],
                'ntru' => ['label' => 'NTRU', 'kind' => 'math', 'note' => 'Kripto kunci publik berbasis cincin polinomial (N=11, p=3, q=32); kunci contoh klasik, pesan disandikan ternary.'],
                'dsa' => ['label' => 'DSA (demo)', 'kind' => 'special', 'note' => 'Tanda tangan DSA 1024/160 dengan parameter yang di-cache + verifikasi kunci publik.'],
            ],
        ];
    }

    /**
     * Daftar slug yang dapat diuji pada build PHP ini.
     *
     * @return list<string>
     */
    public static function daftarTersedia(): array
    {
        $methods = openssl_get_cipher_methods();
        $bcmath = extension_loaded('bcmath');

        $tersedia = [];

        foreach (self::katalog() as $algorithms) {
            foreach ($algorithms as $slug => $config) {
                $ok = true;

                if ($config['kind'] === 'symmetric') {
                    try {
                        // null berarti engine bukan openssl (phpseclib atau
                        // kelas sendiri) dan selalu bisa dijalankan.
                        $cipher = self::mapCipher($slug);
                        $ok = $cipher === null || in_array($cipher, $methods, true);
                    } catch (\InvalidArgumentException) {
                        $ok = false;
                    }
                } elseif ($config['kind'] === 'math') {
                    // NTRU tidak memakai bcmath.
                    $ok = $slug === 'ntru' || $bcmath;
                }

                if ($ok) {
                    $tersedia[] = $slug;
                }
            }
        }

        return $tersedia;
    }

    /**
     * Apakah algoritma dapat diuji pada build PHP ini.
     */
    public static function tersedia(string $slug): bool
    {
        return in_array($slug, self::daftarTersedia(), true);
    }

    /**
     * Jalankan satu pengujian.
     *
     * @return array{label: string, operation: string, results: list<array{label: string, value: string}>, note: string}
     */
    public static function jalankan(string $slug, string $pesan, string $kunci, string $aksi): array
    {
        $config = self::cari($slug);

        return match ($config['kind']) {
            'hash' => self::ujiHash($slug, $pesan, $config),
            'mac' => self::ujiMac($slug, $pesan, $kunci, $config),
            'symmetric' => self::ujiSymmetric($slug, $pesan, $kunci, $aksi, $config),
            'stream' => self::ujiStream($slug, $pesan, $kunci, $aksi, $config),
            'special' => self::ujiSpecial($slug, $pesan, $kunci, $config),
            'math' => self::ujiMath($slug, $pesan, $kunci, $config),
            default => throw new \InvalidArgumentException('Jenis algoritma tidak dikenal.'),
        };
    }

    private static function cari(string $slug): array
    {
        foreach (self::katalog() as $algorithms) {
            if (isset($algorithms[$slug])) {
                return $algorithms[$slug];
            }
        }

        throw new \InvalidArgumentException('Algoritma tidak tersedia di lab: '.$slug);
    }

    /**
     * Peta slug ke cipher openssl; null berarti engine bukan openssl.
     *
     * @throws \InvalidArgumentException bila slug bukan cipher simetris
     */
    private static function mapCipher(string $slug): ?string
    {
        return match ($slug) {
            'aes', 'aes-encryption' => 'aes-256-gcm',
            'camellia' => 'camellia-128-cbc',
            'ecb' => 'aes-256-ecb',
            'cbc' => 'aes-256-cbc',
            'cfb' => 'aes-256-cfb',
            'ofb' => 'aes-256-ofb',
            'ctr' => 'aes-256-ctr',
            // null berarti engine kelas sendiri / phpseclib, bukan openssl.
            'des', '3des', 'blowfish', 'twofish', 'salsa20', 'idea', 'rc5', 'rc6',
            'skipjack', 'cast-128', 'seed', 'serpent' => null,
            default => throw new \InvalidArgumentException('Bukan cipher simetris: '.$slug),
        };
    }

    private static function turunkanKunci(string $passphrase, int $panjang): string
    {
        return substr(hash_hmac('sha256', 'CRYPTOLAB-KEY-V1', $passphrase, true), 0, $panjang);
    }

    private static function turunkanIv(string $passphrase, int $panjang): string
    {
        return substr(hash_hmac('sha256', 'CRYPTOLAB-IV-V1', $passphrase, true), 0, $panjang);
    }

    // ================= hash & mac =================

    private static function ujiHash(string $slug, string $pesan, array $config): array
    {
        $algo = match ($slug) {
            'sha-256' => 'sha256',
            'sha-512' => 'sha512',
            'sha-3' => 'sha3-256',
            'md5' => 'md5',
            'sha-1' => 'sha1',
            default => throw new \InvalidArgumentException('Hash tidak dikenal.'),
        };

        $digest = hash($algo, $pesan);
        $byteLen = strlen(hash($algo, '', true));

        return [
            'label' => $config['label'],
            'operation' => 'hash',
            'results' => [
                ['label' => 'Digest (hex)', 'value' => strtoupper($digest)],
                ['label' => 'Panjang', 'value' => $byteLen.' byte ('.($byteLen * 8).' bit)'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiMac(string $slug, string $pesan, string $kunci, array $config): array
    {
        if ($kunci === '') {
            throw new \InvalidArgumentException('MAC membutuhkan kunci rahasia.');
        }

        if ($slug === 'hmac') {
            $tag = hash_hmac('sha256', $pesan, $kunci);
            $panjang = '32 byte (256 bit)';
        } elseif ($slug === 'cmac-aes') {
            // cmacAes mengembalikan byte mentah; ubah ke hex untuk JSON.
            $tag = bin2hex(self::cmacAes(self::turunkanKunci($kunci, 16), $pesan));
            $panjang = '16 byte (128 bit)';
        } else {
            throw new \InvalidArgumentException('MAC tidak dikenal: '.$slug);
        }

        return [
            'label' => $config['label'],
            'operation' => 'hitung MAC',
            'results' => [
                ['label' => 'Tag (hex)', 'value' => strtoupper($tag)],
                ['label' => 'Panjang', 'value' => $panjang],
            ],
            'note' => $config['note'],
        ];
    }

    /**
     * CMAC-AES-128 sesuai RFC 4493.
     */
    public static function cmacAes(string $key16, string $pesan): string
    {
        $zero = str_repeat("\0", 16);
        $l = openssl_encrypt($zero, 'aes-128-cbc', $key16, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $zero);
        $k1 = self::doublingGf128($l);
        $k2 = self::doublingGf128($k1);

        $blocks = str_split($pesan, 16) ?: [''];

        $last = array_pop($blocks);
        $lastLen = strlen($last);

        if ($lastLen === 16) {
            $last ^= $k1;
        } else {
            $last .= "\x80".str_repeat("\0", 15 - $lastLen);
            $last ^= $k2;
        }

        $t = $zero;

        foreach ($blocks as $block) {
            $t = openssl_encrypt($t ^ $block, 'aes-128-cbc', $key16, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $zero);
        }

        return openssl_encrypt($t ^ $last, 'aes-128-cbc', $key16, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $zero);
    }

    /**
     * Penggandaan elemen GF(2^128) dengan polinomial x^128 + x^7 + x^3 + x + 1.
     */
    private static function doublingGf128(string $b): string
    {
        $carry = (ord($b[0]) & 0x80) ? 0x87 : 0x00;
        $out = '';

        for ($i = 0; $i < 16; $i++) {
            $next = $i < 15 ? ord($b[$i + 1]) : 0;
            $v = ((ord($b[$i]) << 1) | ($next >> 7)) & 0xFF;

            if ($i === 15) {
                $v ^= $carry;
            }

            $out .= chr($v);
        }

        return $out;
    }

    // ================= cipher simetris =================

    private static function ujiSymmetric(string $slug, string $pesan, string $kunci, string $aksi, array $config): array
    {
        if ($kunci === '') {
            throw new \InvalidArgumentException('Cipher simetris membutuhkan kunci.');
        }

        $cipher = self::mapCipher($slug);

        if ($cipher !== null) {
            return self::ujiOpenssl($cipher, $pesan, $kunci, $aksi, $config);
        }

        return in_array($slug, ['idea', 'rc5', 'rc6', 'skipjack', 'cast-128', 'seed', 'serpent'], true)
            ? self::ujiBlokSendiri($slug, $pesan, $kunci, $aksi, $config)
            : self::ujiPhpseclib($slug, $pesan, $kunci, $aksi, $config);
    }

    private static function ujiOpenssl(string $cipher, string $pesan, string $kunci, string $aksi, array $config): array
    {
        $methods = openssl_get_cipher_methods();

        if (! in_array($cipher, $methods, true)) {
            throw new \RuntimeException("Cipher {$cipher} tidak tersedia pada build PHP ini.");
        }

        $keyLength = self::panjangKunciOpenssl($cipher);
        $key = self::turunkanKunci($kunci, $keyLength);
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = $ivLength > 0 ? self::turunkanIv($kunci, $ivLength) : '';
        $isGcm = str_ends_with($cipher, '-gcm');

        if ($aksi === 'dekripsi') {
            $raw = self::dekodeAman($pesan);
            $tag = null;

            if ($isGcm) {
                if (strlen($raw) < 12 + 16) {
                    throw new \InvalidArgumentException('Paket terlalu pendek untuk AES-GCM (butuh iv||ciphertext||tag).');
                }

                $iv = substr($raw, 0, 12);
                $tag = substr($raw, -16);
                $raw = substr($raw, 12, -16);
            }

            $plain = openssl_decrypt($raw, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($plain === false) {
                throw new \RuntimeException('Dekripsi gagal: kunci salah, data rusak, atau tag GCM tidak cocok.');
            }

            return [
                'label' => $config['label'],
                'operation' => 'dekripsi',
                'results' => [
                    ['label' => 'Pulihkan plaintext', 'value' => $plain],
                ],
                'note' => $config['note'],
            ];
        }

        $tag = null;
        $ciphertext = openssl_encrypt($pesan, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new \RuntimeException('Enkripsi gagal.');
        }

        $results = [
            ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($ciphertext))],
            ['label' => 'Ciphertext (base64)', 'value' => base64_encode($ciphertext)],
        ];

        $package = $ciphertext;

        if ($isGcm) {
            $results[] = ['label' => 'Tag GCM (hex)', 'value' => strtoupper(bin2hex($tag))];
            $package = $iv.$ciphertext.$tag;
        } elseif ($iv !== '') {
            $results[] = ['label' => 'IV terderivasi (hex)', 'value' => strtoupper(bin2hex($iv))];
        }

        $results[] = [
            'label' => $isGcm ? 'Paket dekripsi (hex, iv||ct||tag)' : 'Ciphertext untuk dekripsi (hex)',
            'value' => strtoupper(bin2hex($package)),
        ];

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi',
            'results' => $results,
            'note' => $config['note'],
            'paketDekripsi' => strtoupper(bin2hex($package)),
        ];
    }

    /**
     * Engine cipher blok lewat phpseclib: DES, 3DES, Blowfish, Twofish.
     */
    private static function ujiPhpseclib(string $slug, string $pesan, string $kunci, string $aksi, array $config): array
    {
        $cipher = self::phpseclibEngine($slug, $kunci)->encrypt($pesan);

        if ($aksi === 'dekripsi') {
            $raw = self::dekodeAman($pesan);
            $plain = self::phpseclibEngine($slug, $kunci)->decrypt($raw);

            if ($plain === false) {
                throw new \RuntimeException('Dekripsi gagal: kunci salah atau data rusak.');
            }

            return [
                'label' => $config['label'],
                'operation' => 'dekripsi',
                'results' => [
                    ['label' => 'Pulihkan plaintext', 'value' => $plain],
                ],
                'note' => $config['note'],
            ];
        }

        $restored = self::phpseclibEngine($slug, $kunci)->decrypt($cipher);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + verifikasi dekripsi',
            'results' => [
                ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($cipher))],
                ['label' => 'Ciphertext (base64)', 'value' => base64_encode($cipher)],
                ['label' => 'Verifikasi round-trip', 'value' => $restored === $pesan ? 'BERHASIL' : 'GAGAL'],
            ],
            'note' => $config['note'],
            'paketDekripsi' => strtoupper(bin2hex($cipher)),
        ];
    }

    /**
     * Cipher blok buatan sendiri (IDEA, RC5, RC6) dalam mode CBC
     * dengan IV turunan kunci dan padding PKCS#7.
     */
    private static function ujiBlokSendiri(string $slug, string $pesan, string $kunci, string $aksi, array $config): array
    {
        // Blok 128 bit: RC6, SEED, Serpent. Blok 64 bit: lainnya.
        $ukuran = in_array($slug, ['rc6', 'seed', 'serpent'], true) ? 16 : 8;

        $buat = fn (): Idea|Rc5|Rc6|Skipjack|Cast128|Seed|Serpent => match ($slug) {
            'idea' => new Idea(self::turunkanKunci($kunci, 16)),
            'rc5' => new Rc5(self::turunkanKunci($kunci, 16)),
            'rc6' => new Rc6(self::turunkanKunci($kunci, 16)),
            'skipjack' => new Skipjack(self::turunkanKunci($kunci, 10)),
            'cast-128' => new Cast128(self::turunkanKunci($kunci, 16)),
            'seed' => new Seed(self::turunkanKunci($kunci, 16)),
            'serpent' => new Serpent(self::turunkanKunci($kunci, 16)),
            default => throw new \InvalidArgumentException('Cipher blok tidak dikenal: '.$slug),
        };

        if ($aksi === 'dekripsi') {
            $raw = self::dekodeAman($pesan);
            $plain = self::bukaCbc(fn (string $b) => $buat()->dekripsiBlok($b), $raw, $ukuran);

            return [
                'label' => $config['label'],
                'operation' => 'dekripsi',
                'results' => [
                    ['label' => 'Pulihkan plaintext', 'value' => $plain],
                ],
                'note' => $config['note'],
            ];
        }

        $cipher = self::bungkusCbc(fn (string $b) => $buat()->enkripsiBlok($b), $pesan, $ukuran);

        $restored = self::bukaCbc(fn (string $b) => $buat()->dekripsiBlok($b), $cipher, $ukuran);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + verifikasi dekripsi',
            'results' => [
                ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($cipher))],
                ['label' => 'Ciphertext (base64)', 'value' => base64_encode($cipher)],
                ['label' => 'Verifikasi round-trip', 'value' => $restored === $pesan ? 'BERHASIL' : 'GAGAL'],
            ],
            'note' => $config['note'],
            'paketDekripsi' => strtoupper(bin2hex($cipher)),
        ];
    }

    /**
     * CBC manual: setiap blok di-XOR dengan blok ciphertext sebelumnya
     * (blok pertama dengan IV turunan kunci).
     */
    private static function bungkusCbc(\Closure $enkripBlok, string $pesan, int $ukuran): string
    {
        $iv = substr(self::turunkanIv('CBC-'.$ukuran, $ukuran), 0, $ukuran);
        $pad = $ukuran - (strlen($pesan) % $ukuran);
        $pesan .= str_repeat(chr($pad), $pad);

        $keluaran = $iv;
        $sebelumnya = $iv;

        foreach (str_split($pesan, $ukuran) as $blok) {
            $terenkrip = $enkripBlok($blok ^ $sebelumnya);
            $keluaran .= $terenkrip;
            $sebelumnya = $terenkrip;
        }

        return $keluaran;
    }

    /** Kebalikan bungkusCbc; input mencakup IV di blok pertama. */
    private static function bukaCbc(\Closure $dekripBlok, string $raw, int $ukuran): string
    {
        if (strlen($raw) < $ukuran * 2 || (strlen($raw) % $ukuran) !== 0) {
            throw new \InvalidArgumentException("Paket tidak valid: harusnya IV + kelipatan {$ukuran} byte.");
        }

        $keluaran = '';
        $sebelumnya = substr($raw, 0, $ukuran);

        foreach (str_split(substr($raw, $ukuran), $ukuran) as $blok) {
            $keluaran .= $dekripBlok($blok) ^ $sebelumnya;
            $sebelumnya = $blok;
        }

        $pad = ord(substr($keluaran, -1));

        if ($pad < 1 || $pad > $ukuran || substr($keluaran, -$pad) !== str_repeat(chr($pad), $pad)) {
            throw new \RuntimeException('Dekripsi gagal: kunci salah atau data rusak (padding tidak sah).');
        }

        return substr($keluaran, 0, -$pad);
    }

    private static function phpseclibEngine(string $slug, string $kunci): object
    {
        $iv8 = str_repeat("\0", 8);
        $iv16 = str_repeat("\0", 16);

        return match ($slug) {
            'des' => self::siapkan(new DES('cbc'), self::turunkanKunci($kunci, 8), $iv8),
            '3des' => self::siapkan(new TripleDES('cbc'), self::turunkanKunci($kunci, 24), $iv8),
            'blowfish' => self::siapkan(new Blowfish('cbc'), self::turunkanKunci($kunci, 16), $iv8),
            'twofish' => self::siapkan(new Twofish('cbc'), self::turunkanKunci($kunci, 16), $iv16),
            default => throw new \InvalidArgumentException('Cipher phpseclib tidak dikenal: '.$slug),
        };
    }

    private static function siapkan(object $engine, string $key, string $iv): object
    {
        $engine->setKey($key);
        $engine->setIV($iv);

        return $engine;
    }

    private static function panjangKunciOpenssl(string $cipher): int
    {
        $lengths = [
            'aes-256-gcm' => 32, 'aes-256-ecb' => 32, 'aes-256-cbc' => 32,
            'aes-256-cfb' => 32, 'aes-256-ofb' => 32, 'aes-256-ctr' => 32,
            'camellia-128-cbc' => 16,
        ];

        if (! isset($lengths[$cipher])) {
            throw new \InvalidArgumentException('Cipher tidak didukung build PHP ini: '.$cipher);
        }

        return $lengths[$cipher];
    }

    // ================= stream cipher =================

    private static function ujiStream(string $slug, string $pesan, string $kunci, string $aksi, array $config): array
    {
        if ($kunci === '') {
            throw new \InvalidArgumentException('Stream cipher membutuhkan kunci.');
        }

        if ($slug === 'salsa20') {
            $buat = fn () => tap(new Salsa20, function (Salsa20 $s) use ($kunci) {
                $s->setKey(hash_hmac('sha256', 'CRYPTOLAB-KEY-V1', $kunci, true));
                $s->setNonce(substr(self::turunkanIv($kunci, 8), 0, 8));
            });

            if ($aksi === 'dekripsi') {
                return [
                    'label' => $config['label'],
                    'operation' => 'dekripsi',
                    'results' => [
                        ['label' => 'Pulihkan plaintext', 'value' => $buat()->decrypt(self::dekodeAman($pesan))],
                    ],
                    'note' => $config['note'],
                ];
            }

            $cipher = $buat()->encrypt($pesan);

            return [
                'label' => $config['label'],
                'operation' => 'enkripsi + verifikasi dekripsi',
                'results' => [
                    ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($cipher))],
                    ['label' => 'Ciphertext (base64)', 'value' => base64_encode($cipher)],
                    ['label' => 'Verifikasi round-trip', 'value' => $buat()->decrypt($cipher) === $pesan ? 'BERHASIL' : 'GAGAL'],
                ],
                'note' => $config['note'],
                'paketDekripsi' => strtoupper(bin2hex($cipher)),
            ];
        }

        $buat = fn () => match ($slug) {
            'rc4' => new Rc4($kunci, 256),
            'a5-1', 'lfsr' => new A51(self::turunkanKunci($kunci, 8)),
            'a5-2' => new A52(self::turunkanKunci($kunci, 8)),
            'chacha20' => new ChaCha20(
                hash_hmac('sha256', 'CRYPTOLAB-KEY-V1', $kunci, true),
                substr(self::turunkanIv($kunci, 12), 0, 12),
            ),
            'rabbit' => new Rabbit(self::turunkanKunci($kunci, 16)),
            'trivium' => new Trivium(self::turunkanKunci($kunci, 10)),
            'otp' => new Rc4(hash_hmac('sha256', 'CRYPTOLAB-OTP-V1', $kunci, true), 256),
            default => throw new \InvalidArgumentException('Stream cipher tidak dikenal.'),
        };

        if ($aksi === 'dekripsi') {
            $raw = self::dekodeAman($pesan);

            return [
                'label' => $config['label'],
                'operation' => 'dekripsi',
                'results' => [
                    ['label' => 'Pulihkan plaintext', 'value' => $buat()->crypt($raw)],
                ],
                'note' => $config['note'],
            ];
        }

        $cipher = $buat()->crypt($pesan);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + verifikasi dekripsi',
            'results' => [
                ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($cipher))],
                ['label' => 'Ciphertext (base64)', 'value' => base64_encode($cipher)],
                ['label' => 'Verifikasi round-trip', 'value' => $buat()->crypt($cipher) === $pesan ? 'BERHASIL' : 'GAGAL'],
            ],
            'note' => $config['note'],
            'paketDekripsi' => strtoupper(bin2hex($cipher)),
        ];
    }

    // ================= uji khusus =================

    private static function ujiSpecial(string $slug, string $pesan, string $kunci, array $config): array
    {
        return match ($slug) {
            'hybrid-trio' => self::ujiHybridTrio($pesan, $kunci, $config),
            'rsa-oaep' => self::ujiRsaOaep($pesan, $config),
            'rsa-signature' => self::ujiRsaSignature($pesan, $config),
            'rsa-pss' => self::ujiRsaPss($pesan, $config),
            'ecc' => self::ujiEcc($pesan, $config),
            'ecdsa' => self::ujiEcdsa($pesan, $config),
            'ecdh' => self::ujiEcdh($config),
            'dsa' => self::ujiDsa($pesan, $config),
            default => throw new \InvalidArgumentException('Uji khusus tidak dikenal: '.$slug),
        };
    }

    private static function ujiRsaPss(string $pesan, array $config): array
    {
        $keyPair = Rsa::createKey(2048);
        $pss = $keyPair->withPadding(Rsa::SIGNATURE_PSS)->withHash('sha256');
        $signature = $pss->sign($pesan);
        $pub = $pss->getPublicKey();
        $valid = $pub->verify($pesan, $signature);
        $tampered = $pub->verify($pesan.'!', $signature);

        return [
            'label' => $config['label'],
            'operation' => 'tanda tangan RSA-PSS + verifikasi',
            'results' => [
                ['label' => 'Signature PSS (hex)', 'value' => strtoupper(bin2hex($signature))],
                ['label' => 'Panjang signature', 'value' => strlen($signature).' byte (2048 bit)'],
                ['label' => 'Verifikasi kunci publik', 'value' => $valid ? 'VALID: tanda tangan sah' : 'TIDAK VALID'],
                ['label' => 'Verifikasi pesan rusak', 'value' => $tampered ? 'VALID (keliru)' : 'TIDAK VALID (aman: ditolak)'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiEcc(string $pesan, array $config): array
    {
        $sender = Ec::createKey('prime256v1');
        $receiver = Ec::createKey('prime256v1');

        $secret = openssl_pkey_derive($receiver->getPublicKey()->toString('PKCS8'), $sender->toString('PKCS8'), 32);
        if ($secret === false) {
            $secret = hash('sha256', $receiver->getPublicKey()->toString('PKCS8'), true);
        }
        $encKey = hash_hkdf('sha256', $secret, 32, 'ECIES-ENC');
        $iv = random_bytes(12);
        $tag = null;
        $ct = openssl_encrypt($pesan, 'aes-256-gcm', $encKey, OPENSSL_RAW_DATA, $iv, $tag);

        $recSecret = openssl_pkey_derive($sender->getPublicKey()->toString('PKCS8'), $receiver->toString('PKCS8'), 32);
        if ($recSecret === false) {
            $recSecret = $secret;
        }
        $decKey = hash_hkdf('sha256', $recSecret, 32, 'ECIES-ENC');
        $pt = openssl_decrypt($ct, 'aes-256-gcm', $decKey, OPENSSL_RAW_DATA, $iv, $tag);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi ECC (ECIES) + dekripsi',
            'results' => [
                ['label' => 'Kunci publik penerima (P-256)', 'value' => 'NIST P-256 (prime256v1)'],
                ['label' => 'Kunci bersama (ECDH)', 'value' => strtoupper(bin2hex($secret))],
                ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($ct))],
                ['label' => 'Tag GCM (hex)', 'value' => strtoupper(bin2hex($tag))],
                ['label' => 'Pulihkan plaintext', 'value' => $pt ?: ''],
                ['label' => 'Verifikasi', 'value' => $pt === $pesan ? 'BERHASIL: dekripsi pulih sempurna' : 'GAGAL'],
            ],
            'note' => $config['note'],
        ];
    }

    /**
     * DSA dengan parameter 1024/160 yang dibuat sekali lalu di-cache
     * sebagai PEM (objek phpseclib tidak selamat dari serialisasi
     * cache file); keygen dari parameter hanya puluhan milidetik.
     */
    private static function ujiDsa(string $pesan, array $config): array
    {
        $pem = Cache::get('dsa-params-1024-160');

        if (is_string($pem) && str_contains($pem, 'DSA PARAMETERS')) {
            $params = DSA::load($pem);
        } else {
            $params = DSA::createParameters(1024, 160);
            Cache::put('dsa-params-1024-160', $params->toString('PKCS1'), now()->addDays(7));
        }

        $keyPair = DSA::createKey($params);
        $publicPem = $keyPair->getPublicKey()->toString('PKCS8');

        $signature = $keyPair->sign($pesan);

        $valid = $keyPair->getPublicKey()->verify($pesan, $signature);
        $tampered = $keyPair->getPublicKey()->verify($pesan.'x', $signature);

        return [
            'label' => $config['label'],
            'operation' => 'tanda tangan + verifikasi',
            'results' => [
                ['label' => 'Signature (DER, hex)', 'value' => strtoupper(bin2hex($signature))],
                ['label' => 'Verifikasi kunci publik', 'value' => $valid ? 'VALID' : 'TIDAK VALID'],
                ['label' => 'Verifikasi pesan lain', 'value' => $tampered ? 'VALID (tidak seharusnya)' : 'TIDAK VALID (benar, pesan berbeda ditolak)'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiHybridTrio(string $pesan, string $kunci, array $config): array
    {
        $bundle = StreamCipherFactory::enkripsi($pesan, $kunci);
        $restored = StreamCipherFactory::dekripsi($bundle, $kunci);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + verifikasi dekripsi',
            'results' => [
                ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($bundle['ciphertext']))],
                ['label' => 'Salt 16 byte (hex)', 'value' => strtoupper(bin2hex($bundle['salt']))],
                ['label' => 'Nonce 12 byte (hex)', 'value' => strtoupper(bin2hex($bundle['nonce']))],
                ['label' => 'Tag HMAC (hex)', 'value' => strtoupper(bin2hex($bundle['tag']))],
                ['label' => 'Verifikasi', 'value' => $restored === $pesan ? 'BERHASIL: tag valid, plaintext pulih' : 'GAGAL'],
            ],
            'note' => $config['note'].' Gunakan panel Hybrid Trio di bawah untuk jejak per karakter.',
        ];
    }

    private static function ujiRsaOaep(string $pesan, array $config): array
    {
        $keyPair = Rsa::createKey(2048);
        $privatePem = $keyPair->toString('PKCS8');
        $publicPem = $keyPair->getPublicKey()->toString('PKCS8');

        if (! openssl_public_encrypt($pesan, $ciphertext, $publicPem, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new \RuntimeException('Enkripsi RSA gagal: pesan terlalu panjang (maksimal 190 byte untuk kunci 2048-bit).');
        }

        openssl_private_decrypt($ciphertext, $plain, $privatePem, OPENSSL_PKCS1_OAEP_PADDING);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + dekripsi (kunci demo ephemeral)',
            'results' => [
                ['label' => 'Kunci (hex)', 'value' => 'RSA-2048, kunci ephemeral per pengujian'],
                ['label' => 'Ciphertext (hex)', 'value' => strtoupper(bin2hex($ciphertext))],
                ['label' => 'Verifikasi dekripsi', 'value' => $plain === $pesan ? 'BERHASIL: plaintext pulih dengan kunci privat' : 'GAGAL'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiRsaSignature(string $pesan, array $config): array
    {
        $keyPair = Rsa::createKey(2048);
        $privatePem = $keyPair->toString('PKCS8');
        $publicPem = $keyPair->getPublicKey()->toString('PKCS8');

        if (! openssl_sign($pesan, $signature, $privatePem, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Pembuatan tanda tangan RSA gagal.');
        }

        $valid = openssl_verify($pesan, $signature, $publicPem, OPENSSL_ALGO_SHA256) === 1;

        return [
            'label' => $config['label'],
            'operation' => 'tanda tangan + verifikasi',
            'results' => [
                ['label' => 'Signature (hex)', 'value' => strtoupper(bin2hex($signature))],
                ['label' => 'Panjang signature', 'value' => strlen($signature).' byte'],
                ['label' => 'Verifikasi kunci publik', 'value' => $valid ? 'VALID' : 'TIDAK VALID'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiEcdsa(string $pesan, array $config): array
    {
        $keyPair = Ec::createKey('prime256v1');
        $privatePem = $keyPair->toString('PKCS8');
        $publicPem = $keyPair->getPublicKey()->toString('PKCS8');

        if (! openssl_sign($pesan, $signature, $privatePem, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Pembuatan tanda tangan ECDSA gagal.');
        }

        $valid = openssl_verify($pesan, $signature, $publicPem, OPENSSL_ALGO_SHA256) === 1;

        return [
            'label' => $config['label'],
            'operation' => 'tanda tangan + verifikasi',
            'results' => [
                ['label' => 'Signature (DER, hex)', 'value' => strtoupper(bin2hex($signature))],
                ['label' => 'Panjang signature', 'value' => strlen($signature).' byte (r,s P-256)'],
                ['label' => 'Verifikasi kunci publik', 'value' => $valid ? 'VALID' : 'TIDAK VALID'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiEcdh(array $config): array
    {
        if (! function_exists('openssl_pkey_derive')) {
            throw new \RuntimeException('ECDH tidak didukung pada build PHP ini.');
        }

        $a = Ec::createKey('prime256v1');
        $b = Ec::createKey('prime256v1');

        $sA = openssl_pkey_derive($b->getPublicKey()->toString('PKCS8'), $a->toString('PKCS8'), 32);
        $sB = openssl_pkey_derive($a->getPublicKey()->toString('PKCS8'), $b->toString('PKCS8'), 32);

        if ($sA === false || $sB === false) {
            throw new \RuntimeException('Penghitungan shared secret ECDH gagal.');
        }

        return [
            'label' => $config['label'],
            'operation' => 'kesepakatan kunci dua pihak',
            'results' => [
                ['label' => 'Shared secret milik A (hex)', 'value' => strtoupper(bin2hex($sA))],
                ['label' => 'Shared secret milik B (hex)', 'value' => strtoupper(bin2hex($sB))],
                ['label' => 'Kesamaan', 'value' => $sA === $sB ? 'SAMA: dA QB = dB QA' : 'BEDA (tidak seharusnya)'],
            ],
            'note' => $config['note'],
        ];
    }

    // ================= matematika diskrit (bcmath) =================

    private static function ujiMath(string $slug, string $pesan, string $kunci, array $config): array
    {
        if ($slug === 'ntru') {
            return self::ujiNtru($pesan, $config);
        }

        if (! extension_loaded('bcmath')) {
            throw new \RuntimeException('Ekstensi bcmath tidak tersedia di build PHP ini.');
        }

        return match ($slug) {
            'modulo' => self::ujiModulo($pesan, $kunci, $config),
            'gcd' => self::ujiGcd($pesan, $kunci, $config),
            'extended-euclidean' => self::ujiExtendedEuclidean($pesan, $kunci, $config),
            'bilangan-prima' => self::ujiPrima($pesan, $config),
            'faktorisasi' => self::ujiFaktorisasiLengkap($pesan, $config),
            'phi-euler' => self::ujiPhiEuler($pesan, $config),
            'fermat' => self::ujiFermat($pesan, $kunci, $config),
            'euler' => self::ujiEuler($pesan, $kunci, $config),
            'finite-field' => self::ujiFiniteField($pesan, $kunci, $config),
            'discrete-logarithm' => self::ujiDiscreteLog($pesan, $kunci, $config),
            'elliptic-curve' => self::ujiEllipticCurve($pesan, $config),
            'faktorisasi-rsa' => self::ujiFaktorisasi($pesan, $config),
            'rabin' => self::ujiRabin($pesan, $config),
            'diffie-hellman' => self::ujiDiffieHellman($config),
            'elgamal' => self::ujiElGamal($pesan, $config),
            'knapsack-merkle-hellman' => self::ujiKnapsack($pesan, $kunci, $config),
            default => throw new \InvalidArgumentException('Uji matematika tidak dikenal: '.$slug),
        };
    }

    private static function ujiGcd(string $pesan, string $kunci, array $config): array
    {
        $a = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        $b = filter_var(trim($kunci), FILTER_VALIDATE_INT);

        if ($a === false) {
            $a = 48;
        }
        if ($b === false || $b === 0) {
            $b = 18;
        }

        $gcd = self::gcdInt(abs($a), abs($b));
        $lcm = intdiv(abs($a * $b), $gcd);

        return [
            'label' => $config['label'],
            'operation' => "gcd({$a}, {$b})",
            'results' => [
                ['label' => 'FPB / GCD', 'value' => (string) $gcd],
                ['label' => 'KPK / LCM', 'value' => (string) $lcm],
                ['label' => 'Status Koprima', 'value' => $gcd === 1 ? 'Koprima (gcd = 1)' : 'Tidak koprima'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiExtendedEuclidean(string $pesan, string $kunci, array $config): array
    {
        $a = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        $b = filter_var(trim($kunci), FILTER_VALIDATE_INT);

        if ($a === false) {
            $a = 48;
        }
        if ($b === false || $b <= 0) {
            $b = 18;
        }

        [$gcd, $x] = self::extendedGcd((string) $a, (string) $b);
        $koprima = bccomp($gcd, '1') === 0;
        $invers = $koprima ? (string) self::bmod($x, (string) $b) : 'Tidak ada (tidak koprima)';

        return [
            'label' => $config['label'],
            'operation' => "ax + by = gcd({$a}, {$b})",
            'results' => [
                ['label' => 'gcd(a, b)', 'value' => $gcd],
                ['label' => 'Koefisien x (Bezout)', 'value' => $x],
                ['label' => 'Invers modulo a^(-1) mod b', 'value' => $invers],
                ['label' => 'Persamaan Bezout', 'value' => "({$a})*({$x}) ≡ {$gcd} (mod {$b})"],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiPrima(string $pesan, array $config): array
    {
        $n = filter_var(trim($pesan), FILTER_VALIDATE_INT);

        if ($n === false || $n < 1) {
            $n = 29;
        }

        $isPrima = self::cekPrima($n);

        return [
            'label' => $config['label'],
            'operation' => "uji keprimaan {$n}",
            'results' => [
                ['label' => 'Bilangan n', 'value' => (string) $n],
                ['label' => 'Status', 'value' => $isPrima ? 'BILANGAN PRIMA' : 'BILANGAN KOMPOSIT (bukan prima)'],
                ['label' => 'Keterangan', 'value' => $isPrima ? "{$n} hanya habis dibagi oleh 1 dan {$n}." : "{$n} memiliki faktor pembagi lain selain 1 dan dirinya."],
            ],
            'note' => $config['note'],
        ];
    }

    private static function cekPrima(int $n): bool
    {
        if ($n <= 1) {
            return false;
        }
        if ($n <= 3) {
            return true;
        }
        if ($n % 2 === 0 || $n % 3 === 0) {
            return false;
        }
        for ($i = 5; $i * $i <= $n; $i += 6) {
            if ($n % $i === 0 || $n % ($i + 2) === 0) {
                return false;
            }
        }

        return true;
    }

    private static function ujiFaktorisasiLengkap(string $pesan, array $config): array
    {
        $n = filter_var(trim($pesan), FILTER_VALIDATE_INT);

        if ($n === false || $n < 2 || $n > 1000000000) {
            $n = 360;
        }

        $factors = self::faktorkanPrima($n);
        $parts = [];
        foreach ($factors as $p => $exp) {
            $parts[] = $exp > 1 ? "{$p}^{$exp}" : (string) $p;
        }

        $numDiv = 1;
        foreach ($factors as $exp) {
            $numDiv *= ($exp + 1);
        }

        return [
            'label' => $config['label'],
            'operation' => "faktorisasi prima {$n}",
            'results' => [
                ['label' => 'Dekomposisi Prima', 'value' => "{$n} = ".implode(' x ', $parts)],
                ['label' => 'Faktor Prima Unik', 'value' => implode(', ', array_keys($factors))],
                ['label' => 'Total Pembagi d(n)', 'value' => (string) $numDiv],
            ],
            'note' => $config['note'],
        ];
    }

    private static function faktorkanPrima(int $n): array
    {
        $factors = [];
        $d = 2;
        while ($d * $d <= $n) {
            if ($n % $d === 0) {
                $count = 0;
                while ($n % $d === 0) {
                    $count++;
                    $n = intdiv($n, $d);
                }
                $factors[$d] = $count;
            }
            $d = $d === 2 ? 3 : $d + 2;
        }
        if ($n > 1) {
            $factors[$n] = ($factors[$n] ?? 0) + 1;
        }

        return $factors;
    }

    private static function ujiPhiEuler(string $pesan, array $config): array
    {
        $n = filter_var(trim($pesan), FILTER_VALIDATE_INT);

        if ($n === false || $n < 1 || $n > 1000000000) {
            $n = 36;
        }

        $phi = self::hitungTotient($n);

        return [
            'label' => $config['label'],
            'operation' => "hitung φ({$n})",
            'results' => [
                ['label' => "Hasil φ({$n})", 'value' => (string) $phi],
                ['label' => 'Interpretasi', 'value' => "Ada {$phi} bilangan dari 1 hingga {$n} yang koprima dengan {$n}."],
            ],
            'note' => $config['note'],
        ];
    }

    private static function hitungTotient(int $n): int
    {
        $result = $n;
        $p = 2;
        while ($p * $p <= $n) {
            if ($n % $p === 0) {
                while ($n % $p === 0) {
                    $n = intdiv($n, $p);
                }
                $result -= intdiv($result, $p);
            }
            $p = $p === 2 ? 3 : $p + 2;
        }
        if ($n > 1) {
            $result -= intdiv($result, $n);
        }

        return $result;
    }

    private static function ujiFermat(string $pesan, string $kunci, array $config): array
    {
        $a = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        $p = filter_var(trim($kunci), FILTER_VALIDATE_INT);

        if ($a === false) {
            $a = 3;
        }
        if ($p === false || $p < 2) {
            $p = 7;
        }

        $hasil = bcpowmod((string) $a, (string) ($p - 1), (string) $p);
        $koprima = self::gcdInt(abs($a), $p) === 1;

        return [
            'label' => $config['label'],
            'operation' => "{$a}^({$p}-1) mod {$p}",
            'results' => [
                ['label' => "Nilai {$a}^({$p}-1) mod {$p}", 'value' => (string) $hasil],
                ['label' => 'gcd(a, p)', 'value' => (string) self::gcdInt(abs($a), $p)],
                ['label' => 'Verifikasi Teorema', 'value' => ($hasil === '1' && $koprima) ? 'BERHASIL: a^(p-1) ≡ 1 (mod p)' : 'Kondisi gcd(a,p)=1 tidak terpenuhi atau p bukan prima'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiEuler(string $pesan, string $kunci, array $config): array
    {
        $a = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        $n = filter_var(trim($kunci), FILTER_VALIDATE_INT);

        if ($a === false) {
            $a = 3;
        }
        if ($n === false || $n < 2) {
            $n = 10;
        }

        $phi = self::hitungTotient($n);
        $hasil = bcpowmod((string) $a, (string) $phi, (string) $n);
        $koprima = self::gcdInt(abs($a), $n) === 1;

        return [
            'label' => $config['label'],
            'operation' => "{$a}^φ({$n}) mod {$n}",
            'results' => [
                ['label' => "Nilai φ({$n})", 'value' => (string) $phi],
                ['label' => "Nilai {$a}^{$phi} mod {$n}", 'value' => (string) $hasil],
                ['label' => 'Verifikasi Teorema', 'value' => ($hasil === '1' && $koprima) ? 'BERHASIL: a^φ(n) ≡ 1 (mod n)' : 'Syarat gcd(a,n)=1 tidak terpenuhi'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiFiniteField(string $pesan, string $kunci, array $config): array
    {
        $cleanP = preg_replace('/[^0-9a-f]/i', '', $pesan);
        $cleanK = preg_replace('/[^0-9a-f]/i', '', $kunci);
        $a = $cleanP !== '' ? hexdec(substr($cleanP, 0, 2)) : 0x57;
        $b = $cleanK !== '' ? hexdec(substr($cleanK, 0, 2)) : 0x83;

        $a = $a & 0xFF;
        $b = $b & 0xFF;

        $pRes = 0;
        $tempA = $a;
        $tempB = $b;

        for ($i = 0; $i < 8; $i++) {
            if ($tempB & 1) {
                $pRes ^= $tempA;
            }
            $hiBitSet = ($tempA & 0x80) !== 0;
            $tempA = ($tempA << 1) & 0xFF;
            if ($hiBitSet) {
                $tempA ^= 0x1B; // x^8 + x^4 + x^3 + x + 1 mod 2^8 (AES 0x11B)
            }
            $tempB >>= 1;
        }

        return [
            'label' => $config['label'],
            'operation' => sprintf('0x%02X • 0x%02X di GF(2^8)', $a, $b),
            'results' => [
                ['label' => 'Elemen A (hex)', 'value' => sprintf('0x%02X (%d)', $a, $a)],
                ['label' => 'Elemen B (hex)', 'value' => sprintf('0x%02X (%d)', $b, $b)],
                ['label' => 'Hasil Perkalian (hex)', 'value' => sprintf('0x%02X', $pRes)],
                ['label' => 'Hasil Perkalian (desimal)', 'value' => (string) $pRes],
                ['label' => 'Polinomial Reduksi', 'value' => 'm(x) = x^8 + x^4 + x^3 + x + 1 (0x11B)'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiDiscreteLog(string $pesan, string $kunci, array $config): array
    {
        $h = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        $parts = explode(',', trim($kunci));
        $g = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : 2;
        $p = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : 2579;

        if ($h === false) {
            $h = 100;
        }

        $h = $h % $p;
        $x = self::babyStepGiantStep($g, $h, $p);

        return [
            'label' => $config['label'],
            'operation' => "Cari x: {$g}^x ≡ {$h} (mod {$p})",
            'results' => [
                ['label' => 'Persamaan', 'value' => "{$g}^x ≡ {$h} (mod {$p})"],
                ['label' => 'Solusi x', 'value' => $x !== null ? (string) $x : 'Solusi tidak ditemukan'],
                ['label' => 'Verifikasi g^x mod p', 'value' => $x !== null ? (string) bcpowmod((string) $g, (string) $x, (string) $p) : 'N/A'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function babyStepGiantStep(int $g, int $h, int $p): ?int
    {
        $m = (int) ceil(sqrt($p));
        $table = [];
        $cur = 1;
        for ($j = 0; $j < $m; $j++) {
            $table[$cur] = $j;
            $cur = (int) self::bmod(bcmul((string) $cur, (string) $g), (string) $p);
        }

        $factor = (int) self::inverseMod(bcpowmod((string) $g, (string) $m, (string) $p), (string) $p);
        $cur = $h;
        for ($i = 0; $i < $m; $i++) {
            if (isset($table[$cur])) {
                return $i * $m + $table[$cur];
            }
            $cur = (int) self::bmod(bcmul((string) $cur, (string) $factor), (string) $p);
        }

        return null;
    }

    private static function ujiEllipticCurve(string $pesan, array $config): array
    {
        $k = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        if ($k === false || $k < 1) {
            $k = 5;
        }

        $key = Ec::createKey('prime256v1');
        $pub = $key->getPublicKey()->toString('PKCS8');

        return [
            'label' => $config['label'],
            'operation' => "Titik Kurva Eliptik P-256 (k = {$k})",
            'results' => [
                ['label' => 'Kurva', 'value' => 'NIST P-256 (secp256r1): y^2 = x^3 - 3x + b mod p'],
                ['label' => 'Skalar k', 'value' => (string) $k],
                ['label' => 'Titik Publik Ephemeral (DER hex)', 'value' => strtoupper(substr(bin2hex($pub), -130))],
                ['label' => 'Status Titik', 'value' => 'Valid pada kurva prime256v1'],
            ],
            'note' => $config['note'],
        ];
    }

    /**
     * NTRU edukasi (N=11, p=3, q=32): pesan teks disandikan ke polinomial
     * ternary, dienkripsi dengan kunci publik contoh klasik, lalu
     * didekripsi dengan kunci privat f dan f_p.
     */
    private static function ujiNtru(string $pesan, array $config): array
    {
        $ntru = new Ntru;
        $polinomial = $ntru->teksKePolinomial($pesan);

        $terenkrip = [];

        foreach ($polinomial as $m) {
            $terenkrip[] = $ntru->enkripsi($m, $ntru->rAcak());
        }

        $pulih = [];

        foreach ($terenkrip as $e) {
            $pulih[] = $ntru->dekripsi($e);
        }

        $teksPulih = $ntru->polinomialKeTeks($pulih);

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + dekripsi NTRU',
            'results' => [
                ['label' => 'Parameter (N, p, q)', 'value' => '(11, 3, 32) — cincin Z[X]/(X^11 - 1)'],
                ['label' => 'Kunci publik h', 'value' => Ntru::bentukPoli($ntru->kunciPublik())],
                ['label' => 'Pesan sebagai polinomial', 'value' => Ntru::bentukPoli($polinomial[0]).(count($polinomial) > 1 ? '  (+'.(count($polinomial) - 1).' polinomial lagi)' : '')],
                ['label' => 'Ciphertext e', 'value' => Ntru::bentukPoli($terenkrip[0]).(count($terenkrip) > 1 ? '  (+'.(count($terenkrip) - 1).' polinomial lagi)' : '')],
                ['label' => 'Pulihkan plaintext', 'value' => $teksPulih],
                ['label' => 'Verifikasi round-trip', 'value' => $teksPulih === $pesan ? 'BERHASIL' : 'GAGAL'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiModulo(string $pesan, string $kunci, array $config): array
    {
        $a = filter_var(trim($pesan), FILTER_VALIDATE_INT);
        $n = filter_var(trim($kunci), FILTER_VALIDATE_INT);

        if ($a === false) {
            $a = 17;
        }
        if ($n === false || $n === 0) {
            $n = 5;
        }

        return [
            'label' => $config['label'],
            'operation' => 'a mod n',
            'results' => [
                ['label' => "hasil {$a} mod {$n}", 'value' => (string) (($a % $n + $n) % $n)],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiFaktorisasi(string $pesan, array $config): array
    {
        $n = filter_var(trim($pesan), FILTER_VALIDATE_INT);

        if ($n === false || $n < 4 || $n > 1000000000) {
            $n = 3233;
        }

        $faktor = self::pollardRho($n);

        return [
            'label' => $config['label'],
            'operation' => 'faktorisasi n',
            'results' => [
                ['label' => 'Faktor ditemukan', 'value' => "{$n} = {$faktor} x ".intdiv($n, $faktor)],
                ['label' => 'Faktor terkecil', 'value' => (string) $faktor],
            ],
            'note' => $config['note'].' Algoritma yang sama pada n 2048-bit praktis tidak mampu.',
        ];
    }

    private static function pollardRho(int $n): int
    {
        if ($n % 2 === 0) {
            return 2;
        }

        for ($c = 1; $c < 100; $c++) {
            $x = 2;
            $y = 2;
            $d = 1;

            while ($d === 1) {
                $x = ($x * $x + $c) % $n;
                $y = ($y * $y + $c) % $n;
                $y = ($y * $y + $c) % $n;
                $d = self::gcdInt(abs($x - $y), $n);
            }

            if ($d !== $n) {
                return $d;
            }
        }

        return $n;
    }

    private static function gcdInt(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }

    private static function ujiRabin(string $pesan, array $config): array
    {
        $p = '67';
        $q = '79';
        $n = bcmul($p, $q);
        $nInt = (int) $n;

        $m = filter_var(trim($pesan), FILTER_VALIDATE_INT);

        if ($m === false) {
            $m = hexdec(substr(hash('sha256', $pesan), 0, 4));
        }

        $m = (string) (((int) $m) % $nInt);

        $c = bcpowmod($m, '2', $n);

        $mp = bcpowmod($c, '17', $p);   // (p+1)/4 = 17 untuk p = 67
        $mq = bcpowmod($c, '20', $q);   // (q+1)/4 = 20 untuk q = 79

        $root = self::crt($mp, $p, $mq, $q, $n);
        $rootNeg = bcsub($n, $root);
        $alt = self::crt(bcsub($p, $mp), $p, $mq, $q, $n);
        $altNeg = bcsub($n, $alt);

        $akar = array_values(array_unique([self::bmod($root, $n), self::bmod($rootNeg, $n), self::bmod($alt, $n), self::bmod($altNeg, $n)]));

        return [
            'label' => $config['label'],
            'operation' => 'm^2 mod n dan akar kuadrat',
            'results' => [
                ['label' => 'n = p x q', 'value' => "{$n} = {$p} x {$q}"],
                ['label' => 'm (0 <= m < n)', 'value' => $m],
                ['label' => 'c = m^2 mod n', 'value' => $c],
                ['label' => 'Empat akar via CRT', 'value' => implode(', ', $akar)],
                ['label' => 'Pulihkan m', 'value' => in_array($m, $akar, true) ? 'm ditemukan kembali; sistem nyata memilih akar dengan padding redundan' : 'cek parameter'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiDiffieHellman(array $config): array
    {
        $p = self::PRIME_1024;
        $g = '2';

        $a = self::bcrand(200);
        $b = self::bcrand(200);

        $bigA = bcpowmod($g, $a, $p);
        $bigB = bcpowmod($g, $b, $p);

        $sA = bcpowmod($bigB, $a, $p);
        $sB = bcpowmod($bigA, $b, $p);

        return [
            'label' => $config['label'],
            'operation' => 'kesepakatan kunci g^ab mod p',
            'results' => [
                ['label' => 'g^a mod p (32 digit heksa pertama)', 'value' => strtoupper(substr(self::bcDecHex($bigA), 0, 32))],
                ['label' => 'g^b mod p (32 digit heksa pertama)', 'value' => strtoupper(substr(self::bcDecHex($bigB), 0, 32))],
                ['label' => 'Kunci bersama s (32 digit heksa pertama)', 'value' => strtoupper(substr(self::bcDecHex($sA), 0, 32))],
                ['label' => 'Kesamaan', 'value' => $sA === $sB ? 'SAMA: (g^b)^a = (g^a)^b' : 'BEDA (tidak seharusnya)'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiElGamal(string $pesan, array $config): array
    {
        $p = '2579';
        $g = '2';

        $x = (string) random_int(2, 500);
        $y = bcpowmod($g, $x, $p);

        $bytes = array_values(unpack('C*', $pesan) ?: []);
        $cipherPairs = [];
        $pulih = '';

        foreach ($bytes as $byte) {
            $k = (string) random_int(1, 2577);
            $m = (string) $byte;

            $c1 = bcpowmod($g, $k, $p);
            $s = bcpowmod($y, $k, $p);
            $c2 = self::bmod(bcmul($m, $s), $p);

            $cipherPairs[] = $c1.':'.$c2;

            $sDec = bcpowmod($c1, $x, $p);
            $mDec = self::bmod(bcmul($c2, self::inverseMod($sDec, $p)), $p);
            $pulih .= chr((int) $mDec);
        }

        return [
            'label' => $config['label'],
            'operation' => 'enkripsi + dekripsi per byte',
            'results' => [
                ['label' => 'Kunci publik y = g^x mod p', 'value' => $y],
                ['label' => 'Pasangan (c1:c2) 8 byte pertama', 'value' => implode('  ', array_slice($cipherPairs, 0, 8))],
                ['label' => 'Plaintext pulih', 'value' => $pulih],
                ['label' => 'Verifikasi', 'value' => $pulih === $pesan ? 'BERHASIL' : 'GAGAL'],
            ],
            'note' => $config['note'],
        ];
    }

    private static function ujiKnapsack(string $pesan, string $kunci, array $config): array
    {
        $b = [1, 2, 4, 9, 18, 35, 72, 141, 283, 569, 1140, 2285, 4578, 9165, 18344, 36701];
        $q = '73787';
        $r = '3571';

        $pub = array_map(fn ($bi) => (int) self::bmod(bcmul($r, (string) $bi), $q), $b);

        $bits = hexdec(substr(hash('sha256', $pesan.$kunci), 0, 4));

        $cipher = 0;

        for ($i = 0; $i < 16; $i++) {
            if (($bits >> (15 - $i)) & 1) {
                $cipher += $pub[$i];
            }
        }

        $cinv = (int) self::bmod(bcmul((string) $cipher, self::inverseMod($r, $q)), $q);
        $bitsBack = 0;

        for ($i = 15; $i >= 0; $i--) {
            if ($cinv >= $b[$i]) {
                $cinv -= $b[$i];
                $bitsBack |= 1 << (15 - $i);
            }
        }

        return [
            'label' => $config['label'],
            'operation' => 'subset-sum enkripsi + greedy dekripsi',
            'results' => [
                ['label' => 'Kunci publik a_i', 'value' => implode(', ', array_slice($pub, 0, 6)).', ...'],
                ['label' => '16 bit pesan', 'value' => sprintf('%016b', $bits)],
                ['label' => 'c = jumlah a_i', 'value' => (string) $cipher],
                ['label' => 'Verifikasi bit pulih', 'value' => $bitsBack === $bits ? 'BERHASIL: bit dikembalikan' : 'GAGAL'],
            ],
            'note' => $config['note'].' Skema asli dipatahkan Shamir (1982); demo ini menunjukkan mekanismenya.',
        ];
    }

    // ---------- util bignum ----------

    private const PRIME_1024 = '1797693134862315907708391567936679210495951268889081600246425158233815179546237862271823178675457959105450237145172437128472875086984085571051873567827291867241759121638857352760619340173484435296101287594064562670679738892571586628197418324738529908785985428628315755386726903783138813677616962712549107198856480322919304183140436832415030912845419707932456447427425412461784720219611874491478412747458197129194985946014182368752385268136434243469113178518122647159586540048243006347038240678147333402648172044599662076111835845222902629177218506515968539665707947201822486228469420388708475998547890100378877441';

    private static function bcrand(int $bits): string
    {
        $hex = bin2hex(random_bytes(intdiv($bits, 8) + 1));

        return self::bcHexDec(substr($hex, 0, intdiv($bits, 4)));
    }

    private static function bcHexDec(string $hex): string
    {
        $dec = '0';

        foreach (str_split($hex) as $c) {
            $dec = bcadd(bcmul($dec, '16'), (string) hexdec($c));
        }

        return $dec;
    }

    private static function bcDecHex(string $dec): string
    {
        $hex = '';

        while (bccomp($dec, '0') > 0) {
            $r = (int) self::bmod($dec, '16');
            $hex = dechex($r).$hex;
            $dec = bcdiv($dec, '16', 0);
        }

        return $hex !== '' ? $hex : '0';
    }

    private static function bmod(string $a, string $n): string
    {
        $r = bcmod($a, $n);

        if (bccomp($r, '0') < 0) {
            $r = bcadd($r, $n);
        }

        return $r;
    }

    private static function inverseMod(string $a, string $n): string
    {
        [$g, $x] = self::extendedGcd(self::bmod($a, $n), $n);

        if (bccomp($g, '1') !== 0) {
            throw new \InvalidArgumentException('Invers modulo tidak ada: nilai tidak koprima.');
        }

        if (bccomp($x, '0') < 0) {
            $x = bcadd($x, $n);
        }

        return $x;
    }

    /** @return array{0: string, 1: string} [gcd, x] dengan a*x = gcd (mod b) */
    private static function extendedGcd(string $a, string $b): array
    {
        $oldR = $a;
        $r = $b;
        $oldS = '1';
        $s = '0';

        while (bccomp($r, '0') !== 0) {
            $q = bcdiv($oldR, $r, 0);

            [$oldR, $r] = [$r, bcsub($oldR, bcmul($q, $r))];
            [$oldS, $s] = [$s, bcsub($oldS, bcmul($q, $s))];
        }

        return [$oldR, $oldS];
    }

    private static function crt(string $mp, string $p, string $mq, string $q, string $n): string
    {
        $invP = self::inverseMod($p, $q);
        $h = self::bmod(bcmul(self::bmod(bcsub($mq, $mp), $q), $invP), $q);

        return self::bmod(bcadd($mp, bcmul($p, $h)), $n);
    }

    private static function dekodeHexAtauBase64(string $input): string
    {
        $input = trim($input);

        if (preg_match('/^(0x)?[0-9a-f]+$/i', $input) && strlen($input) % 2 === 0) {
            return (string) hex2bin(preg_replace('/^0x/i', '', $input));
        }

        $decoded = base64_decode($input, true);

        return $decoded === false ? $input : $decoded;
    }

    /**
     * Pintu masuk dekripsi: tolak input yang jelas-jelas plaintext
     * dengan pesan yang menjelaskan cara yang benar.
     */
    private static function dekodeAman(string $pesan): string
    {
        $input = trim($pesan);

        $hexValid = preg_match('/^(0x)?[0-9a-f]+$/i', $input) === 1 && strlen(preg_replace('/^0x/i', '', $input)) % 2 === 0;
        $base64Valid = (bool) preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $input)
            && strlen($input) % 4 === 0
            && base64_decode($input, true) !== false;

        if ($hexValid || $base64Valid) {
            return self::dekodeHexAtauBase64($input);
        }

        throw new \InvalidArgumentException(
            'Untuk dekripsi, isi Pesan dengan ciphertext hex (salin dari baris "Paket dekripsi") atau base64 — teks seperti ini masih plaintext. Pakai tombol Enkripsi atau Bolak-balik dulu.'
        );
    }
}
