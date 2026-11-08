<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CryptoAlgorithmSeeder extends Seeder
{
    /**
     * Pola dedup kanonik: satu baris algoritma per mesin/persamaan
     * matematis. Sudut pandang lain (mis. "Enkripsi AES" di Fungsi
     * Utama) diwakili oleh materi yang merujuk baris algoritma yang
     * sama, bukan dengan menduplikasi data.
     */
    public function run(): void
    {
        $data = [
            'block-cipher' => [
                ['DES', 'des', 'Block Cipher', 'Algoritma block cipher klasik.', 'historical'],
                ['3DES', '3des', 'Block Cipher', 'Pengembangan DES dengan tiga tahap operasi.', 'historical'],
                ['AES', 'aes', 'Block Cipher', 'Advanced Encryption Standard.', 'recommended'],
                ['Blowfish', 'blowfish', 'Block Cipher', 'Algoritma block cipher berbasis Feistel.', 'educational'],
                ['ECB', 'ecb', 'Mode Operasi', 'Electronic Codebook.', 'educational'],
                ['CBC', 'cbc', 'Mode Operasi', 'Cipher Block Chaining.', 'educational'],
                ['CFB', 'cfb', 'Mode Operasi', 'Cipher Feedback.', 'educational'],
                ['OFB', 'ofb', 'Mode Operasi', 'Output Feedback.', 'educational'],
                ['CTR', 'ctr', 'Mode Operasi', 'Counter Mode.', 'educational'],
                ['IDEA', 'idea', 'Block Cipher', 'Cipher blok 64-bit dengan kunci 128-bit, memakai XOR, penjumlahan, dan perkalian modulo 65537.', 'educational'],
                ['RC5', 'rc5', 'Block Cipher', 'Cipher blok dengan rotasi bergantung data (RC5-32/12/16).', 'educational'],
                ['RC6', 'rc6', 'Block Cipher', 'Pengembangan RC5 untuk blok 128-bit (RC6-32/20/16).', 'educational'],
                ['Twofish', 'twofish', 'Block Cipher', 'Cipher blok 128-bit berstruktur Feistel, finalis seleksi AES.', 'recommended'],
                ['CAST-128', 'cast-128', 'Block Cipher', 'Cipher blok 128-bit berstruktur Feistel dengan 16 putaran.', 'educational'],
                ['Skipjack', 'skipjack', 'Block Cipher', 'Cipher blok 64-bit dengan 32 putaran, dikembangkan NSA.', 'historical'],
                ['Serpent', 'serpent', 'Block Cipher', 'Cipher blok 128-bit dengan 32 putaran, finalis seleksi AES.', 'recommended'],
                ['Camellia', 'camellia', 'Block Cipher', 'Cipher blok 128-bit berstruktur Feistel dengan 18 putaran dan fungsi FL.', 'recommended'],
                ['SEED', 'seed', 'Block Cipher', 'Cipher blok 128-bit berstruktur Feistel dengan 16 putaran, standar Korea.', 'recommended'],
            ],

            'stream-cipher' => [
                ['RC4', 'rc4', 'Stream Cipher', 'Stream cipher dengan array state 256 byte (KSA dan PRGA). Sudah tidak dianggap aman, dipakai untuk belajar.', 'historical'],
                ['A5/1', 'a5-1', 'Stream Cipher', 'Stream cipher berbasis tiga LFSR, dipakai pada GSM.', 'historical'],
                ['LFSR', 'lfsr', 'Stream Cipher', 'Linear Feedback Shift Register.', 'educational'],
                ['OTP', 'otp', 'Stream Cipher', 'One-Time Pad.', 'educational'],
                ['Salsa20', 'salsa20', 'Stream Cipher', 'Stream cipher berbasis operasi ARX pada blok 64 byte, pendahulu ChaCha20.', 'recommended'],
                ['ChaCha20', 'chacha20', 'Stream Cipher', 'Stream cipher berbasis operasi add-rotate-xor.', 'recommended'],
                ['A5/2', 'a5-2', 'Stream Cipher', 'Versi lemah A5/1 untuk ekspor GSM, memakai empat LFSR dengan R4 sebagai pengatur clock.', 'historical'],
                ['Rabbit', 'rabbit', 'Stream Cipher', 'Stream cipher berkunci 128-bit dan IV 64-bit, memakai delapan variabel state dan delapan counter (RFC 4503).', 'educational'],
                ['Trivium', 'trivium', 'Stream Cipher', 'Stream cipher hardware ringan berkunci 80-bit dan IV 80-bit dengan state 288 bit, finalis eSTREAM.', 'educational'],
                ['Hybrid Trio', 'hybrid-trio', 'Kaskade Stream Cipher', 'Kaskade tiga stream cipher (A5/1, RC4, ChaCha20) dengan derivasi kunci HKDF dan Encrypt-then-MAC.', 'educational'],
            ],

            'fungsi-utama' => [
                ['SHA-256', 'sha-256', 'Hashing', 'Fungsi hash SHA-256.', 'recommended'],
                ['SHA-512', 'sha-512', 'Hashing', 'Fungsi hash SHA-512.', 'recommended'],
                ['HMAC', 'hmac', 'Authentication', 'Message Authentication Code.', 'recommended'],
                ['RSA Signature', 'rsa-signature', 'Digital Signature', 'Tanda tangan digital RSA.', 'recommended'],
                ['Diffie-Hellman', 'diffie-hellman', 'Key Exchange', 'Pertukaran kunci rahasia.', 'educational'],
                ['AES Encryption', 'aes-encryption', 'Confidentiality', 'Menjaga kerahasiaan informasi.', 'recommended'],
                ['MD5', 'md5', 'Hashing', 'Hash 128-bit. Tabrakan bisa dibuat, sudah tidak aman untuk keamanan.', 'historical'],
                ['SHA-1', 'sha-1', 'Hashing', 'Hash 160-bit. Tabrakan sudah ditemukan, tidak dianjurkan lagi.', 'historical'],
                ['SHA-3', 'sha-3', 'Hashing', 'SHA3-256 berbasis konstruksi sponge Keccak.', 'recommended'],
                ['CMAC-AES', 'cmac-aes', 'Authentication', 'Kode autentikasi pesan berbasis cipher blok AES-128 (RFC 4493).', 'recommended'],
                ['RSA-OAEP', 'rsa-oaep', 'Enkripsi Asimetris', 'Enkripsi kunci publik RSA dengan padding OAEP dan SHA-256.', 'recommended'],
                ['RSA-PSS', 'rsa-pss', 'Digital Signature', 'Tanda tangan RSA dengan padding PSS dan SHA-256.', 'recommended'],
                ['ElGamal', 'elgamal', 'Enkripsi Asimetris', 'Enkripsi kunci publik berbasis logaritma diskrit.', 'educational'],
                ['ECC', 'ecc', 'Enkripsi Asimetris', 'Enkripsi kunci publik di kurva eliptik (ECDH dan AES-GCM).', 'recommended'],
                ['ECDH', 'ecdh', 'Key Exchange', 'Pertukaran kunci Diffie-Hellman di kurva eliptik P-256.', 'recommended'],
                ['ECDSA', 'ecdsa', 'Digital Signature', 'Tanda tangan digital di kurva eliptik P-256 dengan SHA-256.', 'recommended'],
            ],

            'dasar-matematika' => [
                ['Aritmetika Modulo', 'modulo', 'Matematika', 'Operasi sisa pembagian.', 'educational'],
                ['Bilangan Prima', 'bilangan-prima', 'Matematika', 'Pemeriksaan bilangan prima.', 'educational'],
                ['Faktorisasi', 'faktorisasi', 'Matematika', 'Faktorisasi bilangan.', 'educational'],
                ['GCD', 'gcd', 'Matematika', 'Greatest Common Divisor.', 'educational'],
                ['Extended Euclidean', 'extended-euclidean', 'Matematika', 'Mencari invers modulo.', 'educational'],
                ['Teorema Fermat', 'fermat', 'Matematika', 'Fermat Little Theorem.', 'educational'],
                ['Teorema Euler', 'euler', 'Matematika', 'Euler Theorem.', 'educational'],
                ['Fungsi Euler', 'phi-euler', 'Matematika', 'Euler Totient Function.', 'educational'],
                ['Finite Field', 'finite-field', 'Matematika', 'Operasi pada medan hingga.', 'educational'],
                ['Discrete Logarithm', 'discrete-logarithm', 'Matematika', 'Permasalahan logaritma diskrit.', 'educational'],
                ['Elliptic Curve', 'elliptic-curve', 'Matematika', 'Konsep kurva eliptik.', 'educational'],
                ['Faktorisasi RSA', 'faktorisasi-rsa', 'Faktorisasi Bilangan', 'Keamanan RSA bertumpu pada sulitnya memfaktorkan bilangan besar.', 'educational'],
                ['Rabin', 'rabin', 'Faktorisasi Bilangan', 'Enkripsi dengan mengkuadratkan pesan modulo n = p kali q.', 'educational'],
                ['DSA', 'dsa', 'Logaritma Diskrit', 'Tanda tangan digital berbasis logaritma diskrit.', 'educational'],
                ['NTRU', 'ntru', 'Lattice', 'Enkripsi berbasis kisi (lattice), kandidat kriptografi pasca-kuantum.', 'educational'],
                ['Knapsack Merkle-Hellman', 'knapsack-merkle-hellman', 'Knapsack', 'Enkripsi berbasis masalah knapsack, dipatahkan oleh Shamir pada 1982.', 'historical'],
            ],
        ];

        foreach ($data as $categorySlug => $algorithms) {
            $category = DB::table('crypto_categories')
                ->where('slug', $categorySlug)
                ->first();

            if (! $category) {
                continue;
            }

            foreach ($algorithms as $algorithm) {
                DB::table('crypto_algorithms')->updateOrInsert(
                    ['slug' => $algorithm[1]],
                    [
                        'category_id' => $category->id,
                        'name' => $algorithm[0],
                        'slug' => $algorithm[1],
                        'type' => $algorithm[2],
                        'description' => $algorithm[3],
                        'security_status' => $algorithm[4],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
