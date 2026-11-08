<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CryptoMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [

            // ==========================================
            // 1. BLOCK CIPHER
            // ==========================================

            'des' => [
                'category' => 'block-cipher',
                'title' => 'Data Encryption Standard (DES)',
                'intro' => 'DES adalah algoritma kriptografi simetris yang memproses data dalam blok berukuran 64 bit menggunakan kunci efektif 56 bit.',
                'concept' => 'DES menggunakan struktur Feistel Network yang terdiri dari 16 putaran. Setiap putaran melakukan ekspansi, XOR dengan subkey, substitusi melalui S-Box, dan permutasi.',
                'steps' => [
                    'Plaintext 64 bit mengalami Initial Permutation.',
                    'Data dibagi menjadi bagian kiri (L) dan kanan (R), masing-masing 32 bit.',
                    'Dilakukan 16 putaran Feistel dengan subkey berbeda.',
                    'Dilakukan pertukaran bagian dan Final Permutation.',
                    'Hasilnya adalah ciphertext 64 bit.',
                ],
                'formula' => 'L_i = R_{i-1}; R_i = L_{i-1} XOR F(R_{i-1}, K_i)',
                'example' => 'DES memiliki ukuran blok 64 bit dan menghasilkan 16 subkey. Contoh pengujian dapat menggunakan plaintext ASCII sepanjang 8 karakter agar sesuai ukuran satu blok.',
                'note' => 'DES sudah tidak aman untuk penggunaan modern karena ukuran kuncinya terlalu pendek. Materi ini digunakan untuk pembelajaran.',
            ],

            '3des' => [
                'category' => 'block-cipher',
                'title' => 'Triple DES (3DES)',
                'intro' => '3DES merupakan pengembangan DES yang menjalankan proses DES sebanyak tiga tahap untuk meningkatkan keamanan dibanding DES tunggal.',
                'concept' => '3DES umumnya menggunakan pola Encrypt-Decrypt-Encrypt (EDE). Terdapat konfigurasi dua kunci dan tiga kunci.',
                'steps' => [
                    'Plaintext dienkripsi menggunakan kunci K1.',
                    'Hasil tahap pertama didekripsi menggunakan K2.',
                    'Hasil tahap kedua dienkripsi menggunakan K3.',
                    'Hasil akhir menjadi ciphertext.',
                ],
                'formula' => 'C = E(K3, D(K2, E(K1, P)))',
                'example' => 'Misalkan P merupakan plaintext. Proses pertama menghasilkan C1 = E(K1,P), proses kedua menghasilkan C2 = D(K2,C1), dan proses ketiga menghasilkan C = E(K3,C2).',
                'note' => '3DES sudah tidak direkomendasikan untuk sistem baru. Gunakan AES untuk kebutuhan keamanan modern.',
            ],

            'aes' => [
                'category' => 'block-cipher',
                'title' => 'Advanced Encryption Standard (AES)',
                'intro' => 'AES adalah algoritma enkripsi simetris berbasis substitution-permutation network dengan ukuran blok 128 bit.',
                'concept' => 'AES mendukung kunci 128, 192, dan 256 bit. Jumlah putaran masing-masing adalah 10, 12, dan 14.',
                'steps' => [
                    'AddRoundKey: melakukan XOR state dengan round key.',
                    'SubBytes: mengganti byte menggunakan S-Box.',
                    'ShiftRows: menggeser baris state.',
                    'MixColumns: melakukan transformasi pada kolom.',
                    'AddRoundKey: menggabungkan state dengan round key.',
                    'Putaran terakhir tidak menggunakan MixColumns.',
                ],
                'formula' => 'State_{baru} = State XOR RoundKey',
                'example' => 'AES-128 menggunakan blok 128 bit dan kunci 128 bit. Prosesnya terdiri dari initial AddRoundKey dan 10 putaran.',
                'note' => 'Untuk aplikasi nyata, gunakan mode authenticated encryption seperti AES-GCM dan pengelolaan nonce yang benar.',
            ],

            'blowfish' => [
                'category' => 'block-cipher',
                'title' => 'Algoritma Blowfish',
                'intro' => 'Blowfish merupakan algoritma enkripsi simetris yang menggunakan blok 64 bit dan panjang kunci variabel.',
                'concept' => 'Blowfish menggunakan Feistel Network dengan 16 putaran, P-array, dan empat S-Box.',
                'steps' => [
                    'Inisialisasi P-array dan S-Box.',
                    'Lakukan key expansion menggunakan kunci.',
                    'Bagi blok menjadi bagian kiri dan kanan.',
                    'Jalankan 16 putaran Feistel.',
                    'Gabungkan hasil untuk menghasilkan ciphertext.',
                ],
                'formula' => 'L_i = R_{i-1}; R_i = L_{i-1} XOR F(R_{i-1})',
                'example' => 'Blowfish memproses satu blok data sebesar 64 bit. Pengujian dilakukan dengan membandingkan plaintext dan hasil dekripsi.',
                'note' => 'Blowfish memiliki blok 64 bit sehingga kurang ideal untuk mengenkripsi data dalam jumlah besar pada sistem modern.',
            ],

            'ecb' => [
                'category' => 'block-cipher',
                'title' => 'Electronic Codebook (ECB)',
                'intro' => 'ECB merupakan mode operasi block cipher yang mengenkripsi setiap blok plaintext secara terpisah.',
                'concept' => 'Setiap blok menggunakan kunci yang sama dan tidak bergantung pada blok sebelumnya.',
                'steps' => [
                    'Bagi plaintext menjadi beberapa blok.',
                    'Tambahkan padding jika diperlukan.',
                    'Enkripsi setiap blok menggunakan kunci yang sama.',
                    'Gabungkan seluruh ciphertext.',
                ],
                'formula' => 'C_i = E_K(P_i)',
                'example' => 'Jika plaintext terdiri dari P1 dan P2, maka C1 = E(K,P1) dan C2 = E(K,P2).',
                'note' => 'ECB dapat memperlihatkan pola data yang sama. Jangan digunakan untuk melindungi data nyata.',
            ],

            'cbc' => [
                'category' => 'block-cipher',
                'title' => 'Cipher Block Chaining (CBC)',
                'intro' => 'CBC merupakan mode operasi block cipher yang menghubungkan setiap blok plaintext dengan ciphertext sebelumnya.',
                'concept' => 'CBC menggunakan Initialization Vector (IV) pada blok pertama.',
                'steps' => [
                    'Siapkan plaintext dan IV.',
                    'XOR blok plaintext pertama dengan IV.',
                    'Enkripsi menggunakan kunci.',
                    'Gunakan ciphertext sebelumnya untuk blok berikutnya.',
                    'Ulangi sampai seluruh blok selesai.',
                ],
                'formula' => 'C_i = E_K(P_i XOR C_{i-1}), C_0 = IV',
                'example' => 'Untuk blok pertama, C1 = E(K, P1 XOR IV). Untuk blok kedua, C2 = E(K, P2 XOR C1).',
                'note' => 'CBC membutuhkan padding dan autentikasi tambahan. Jangan menggunakan IV yang berulang dengan kunci yang sama.',
            ],

            'cfb' => [
                'category' => 'block-cipher',
                'title' => 'Cipher Feedback (CFB)',
                'intro' => 'CFB adalah mode operasi yang mengubah block cipher menjadi proses seperti stream cipher.',
                'concept' => 'CFB mengenkripsi IV atau ciphertext sebelumnya untuk menghasilkan keystream yang kemudian di-XOR dengan plaintext.',
                'steps' => [
                    'Siapkan IV.',
                    'Enkripsi IV menggunakan kunci.',
                    'XOR hasil enkripsi dengan plaintext.',
                    'Gunakan ciphertext sebagai input blok berikutnya.',
                ],
                'formula' => 'C_i = P_i XOR E_K(C_{i-1})',
                'example' => 'Pada blok pertama, ciphertext diperoleh dari XOR plaintext dengan hasil enkripsi IV.',
                'note' => 'CFB tidak menyediakan autentikasi data dengan sendirinya.',
            ],

            'ofb' => [
                'category' => 'block-cipher',
                'title' => 'Output Feedback (OFB)',
                'intro' => 'OFB adalah mode operasi block cipher yang menghasilkan keystream secara berulang dari hasil enkripsi sebelumnya.',
                'concept' => 'Keystream dibentuk dari enkripsi IV dan hasil enkripsi sebelumnya, bukan dari ciphertext.',
                'steps' => [
                    'Siapkan IV.',
                    'Enkripsi IV untuk memperoleh keystream pertama.',
                    'XOR keystream dengan plaintext.',
                    'Enkripsi kembali hasil internal sebelumnya.',
                    'Ulangi untuk blok berikutnya.',
                ],
                'formula' => 'O_i = E_K(O_{i-1}); C_i = P_i XOR O_i',
                'example' => 'Keystream pertama adalah E(K,IV). Ciphertext pertama merupakan XOR antara plaintext pertama dan keystream tersebut.',
                'note' => 'IV tidak boleh digunakan kembali dengan kunci yang sama.',
            ],

            'ctr' => [
                'category' => 'block-cipher',
                'title' => 'Counter Mode (CTR)',
                'intro' => 'CTR adalah mode operasi yang mengenkripsi nilai counter untuk menghasilkan keystream.',
                'concept' => 'Setiap blok menggunakan counter yang berbeda. Hasil enkripsi counter di-XOR dengan plaintext.',
                'steps' => [
                    'Tentukan nonce dan counter awal.',
                    'Gabungkan nonce dengan counter.',
                    'Enkripsi nilai tersebut.',
                    'XOR hasil enkripsi dengan plaintext.',
                    'Naikkan counter untuk blok berikutnya.',
                ],
                'formula' => 'C_i = P_i XOR E_K(Nonce || Counter_i)',
                'example' => 'Counter 0 menghasilkan keystream pertama. Counter 1 menghasilkan keystream kedua, dan seterusnya.',
                'note' => 'Nonce dan counter tidak boleh berulang pada kunci yang sama. CTR tidak memberikan autentikasi.',
            ],

            // ==========================================
            // 2. STREAM CIPHER
            // ==========================================

            'rc4' => [
                'category' => 'stream-cipher',
                'title' => 'RC4 Stream Cipher',
                'intro' => 'RC4 merupakan algoritma stream cipher yang menghasilkan keystream berdasarkan permutasi array 256 elemen.',
                'concept' => 'RC4 menggunakan Key Scheduling Algorithm (KSA) dan Pseudo-Random Generation Algorithm (PRGA).',
                'steps' => [
                    'Inisialisasi array S berisi nilai 0 sampai 255.',
                    'Lakukan KSA menggunakan kunci.',
                    'Lakukan PRGA untuk menghasilkan keystream.',
                    'XOR keystream dengan plaintext.',
                ],
                'formula' => 'C_i = P_i XOR K_i',
                'example' => 'Jika byte plaintext adalah 65 dan keystream 23, ciphertext = 65 XOR 23 = 86.',
                'note' => 'RC4 sudah tidak aman dan hanya digunakan untuk pembelajaran.',
            ],

            'a5-1' => [
                'category' => 'stream-cipher',
                'title' => 'A5/1 Stream Cipher',
                'intro' => 'A5/1 adalah stream cipher yang pernah digunakan dalam sistem komunikasi GSM.',
                'concept' => 'A5/1 menggunakan tiga Linear Feedback Shift Register (LFSR) dengan panjang 19, 22, dan 23 bit.',
                'steps' => [
                    'Inisialisasi tiga register.',
                    'Masukkan kunci dan frame number.',
                    'Lakukan clocking berdasarkan mekanisme majority.',
                    'Gabungkan bit keluaran register.',
                    'Gunakan keystream untuk proses XOR.',
                ],
                'formula' => 'C_i = P_i XOR K_i',
                'example' => 'Tiga register menghasilkan bit keluaran yang digabungkan untuk membentuk keystream.',
                'note' => 'A5/1 telah memiliki kelemahan keamanan dan tidak direkomendasikan untuk komunikasi modern.',
            ],

            'lfsr' => [
                'category' => 'stream-cipher',
                'title' => 'Linear Feedback Shift Register',
                'intro' => 'LFSR merupakan rangkaian register geser yang menghasilkan urutan bit melalui operasi XOR pada posisi tertentu.',
                'concept' => 'LFSR menggunakan initial state dan tap position untuk menentukan bit feedback.',
                'steps' => [
                    'Tentukan panjang register.',
                    'Masukkan initial state yang tidak semuanya nol.',
                    'Ambil bit pada posisi tap.',
                    'Hitung feedback menggunakan XOR.',
                    'Geser register dan masukkan feedback.',
                    'Ulangi untuk membentuk keystream.',
                ],
                'formula' => 'b_{baru} = b_1 XOR b_3 XOR b_4',
                'example' => 'Jika bit tap adalah 1, 0, dan 1, maka feedback = 1 XOR 0 XOR 1 = 0.',
                'note' => 'LFSR sederhana tidak aman sebagai stream cipher modern karena sifat linear dan periodisitasnya.',
            ],

            'otp' => [
                'category' => 'stream-cipher',
                'title' => 'One-Time Pad (OTP)',
                'intro' => 'One-Time Pad adalah metode enkripsi yang menggunakan kunci acak dengan panjang sama seperti pesan.',
                'concept' => 'OTP dapat memberikan kerahasiaan sempurna jika kunci benar-benar acak, rahasia, hanya digunakan sekali, dan sepanjang pesan.',
                'steps' => [
                    'Ubah plaintext menjadi representasi bit.',
                    'Siapkan kunci acak dengan panjang sama.',
                    'Lakukan XOR plaintext dan kunci.',
                    'Kirim ciphertext.',
                    'Dekripsi menggunakan kunci yang sama.',
                ],
                'formula' => 'C = P XOR K; P = C XOR K',
                'example' => 'P = 1010 dan K = 1100. Maka C = 0110. Dekripsi: 0110 XOR 1100 = 1010.',
                'note' => 'Kunci tidak boleh digunakan kembali dan distribusinya harus aman.',
            ],

            'salsa20' => [
                'category' => 'stream-cipher',
                'title' => 'Salsa20',
                'intro' => 'Salsa20 merupakan stream cipher yang dirancang oleh Daniel J. Bernstein.',
                'concept' => 'Salsa20 menggunakan operasi penjumlahan modulo 2^32, XOR, dan rotasi bit.',
                'steps' => [
                    'Bentuk state dari konstanta, kunci, nonce, dan counter.',
                    'Lakukan operasi quarter-round.',
                    'Jalankan putaran transformasi.',
                    'Tambahkan state awal ke state hasil.',
                    'Ubah hasil menjadi keystream.',
                ],
                'formula' => 'x = (x + y) mod 2^32',
                'example' => 'Operasi dasarnya melibatkan penjumlahan modulo 2^32, XOR, dan rotasi bit.',
                'note' => 'Gunakan implementasi pustaka yang teruji dan jangan menggunakan nonce yang sama berulang kali dengan kunci yang sama.',
            ],

            'chacha20' => [
                'category' => 'stream-cipher',
                'title' => 'ChaCha20',
                'intro' => 'ChaCha20 adalah stream cipher modern yang dirancang oleh Daniel J. Bernstein sebagai pengembangan Salsa20.',
                'concept' => 'ChaCha20 menggunakan state 512 bit yang terdiri dari konstanta, kunci, counter, dan nonce.',
                'steps' => [
                    'Susun 16 word state, masing-masing 32 bit.',
                    'Jalankan operasi quarter-round.',
                    'Lakukan 20 rounds melalui column rounds dan diagonal rounds.',
                    'Tambahkan hasil ke state awal.',
                    'Hasilkan blok keystream.',
                ],
                'formula' => 'a = a + b; d = ROTL(d XOR a, 16)',
                'example' => 'Quarter-round menggunakan operasi ADD, XOR, dan ROTL dengan rotasi 16, 12, 8, dan 7 bit.',
                'note' => 'ChaCha20 merupakan algoritma modern. Gunakan nonce unik dan implementasi standar yang telah teruji.',
            ],

            // ==========================================
            // 3. FUNGSI UTAMA KRIPTOGRAFI
            // ==========================================

            'sha-256' => [
                'category' => 'fungsi-utama',
                'title' => 'Secure Hash Algorithm 256 (SHA-256)',
                'intro' => 'SHA-256 adalah fungsi hash kriptografi yang menghasilkan digest sepanjang 256 bit atau 32 byte.',
                'concept' => 'SHA-256 memproses pesan melalui padding, pembagian blok, message schedule, dan 64 putaran kompresi.',
                'steps' => [
                    'Tambahkan padding pada pesan.',
                    'Tambahkan panjang pesan dalam format 64 bit.',
                    'Bagi pesan menjadi blok 512 bit.',
                    'Bentuk message schedule 64 word.',
                    'Lakukan 64 putaran kompresi.',
                    'Gabungkan hasil menjadi hash 256 bit.',
                ],
                'formula' => 'H = SHA256(M)',
                'example' => 'Teks "abc" menghasilkan digest SHA-256: ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad.',
                'note' => 'SHA-256 merupakan fungsi hash satu arah, bukan algoritma enkripsi yang dapat didekripsi.',
            ],

            'sha-512' => [
                'category' => 'fungsi-utama',
                'title' => 'Secure Hash Algorithm 512 (SHA-512)',
                'intro' => 'SHA-512 merupakan fungsi hash dari keluarga SHA-2 yang menghasilkan digest 512 bit.',
                'concept' => 'SHA-512 menggunakan ukuran blok 1024 bit, word 64 bit, dan 80 putaran kompresi.',
                'steps' => [
                    'Lakukan padding pesan.',
                    'Bagi pesan menjadi blok 1024 bit.',
                    'Buat message schedule.',
                    'Jalankan 80 putaran kompresi.',
                    'Gabungkan delapan word hasil menjadi digest.',
                ],
                'formula' => 'H = SHA512(M)',
                'example' => 'Satu pesan akan menghasilkan digest sepanjang 512 bit atau 128 karakter heksadesimal.',
                'note' => 'SHA-512 tidak dapat digunakan untuk mengembalikan pesan asli dari digest.',
            ],

            'hmac' => [
                'category' => 'fungsi-utama',
                'title' => 'Hash-based Message Authentication Code',
                'intro' => 'HMAC adalah mekanisme autentikasi pesan yang menggabungkan fungsi hash dengan secret key.',
                'concept' => 'HMAC menggunakan inner hash dan outer hash untuk menghasilkan kode autentikasi.',
                'steps' => [
                    'Siapkan secret key.',
                    'Sesuaikan panjang key dengan block size hash.',
                    'XOR key dengan ipad.',
                    'Hitung inner hash.',
                    'XOR key dengan opad.',
                    'Hitung outer hash.',
                ],
                'formula' => 'HMAC(K,m) = H((K XOR opad) || H((K XOR ipad) || m))',
                'example' => 'Pengirim dan penerima menggunakan secret key yang sama untuk menghitung dan memverifikasi HMAC.',
                'note' => 'Secret key harus disimpan dengan aman. HMAC tidak menyediakan kerahasiaan pesan.',
            ],

            'rsa-signature' => [
                'category' => 'fungsi-utama',
                'title' => 'RSA Digital Signature',
                'intro' => 'RSA Digital Signature adalah mekanisme tanda tangan digital menggunakan pasangan kunci privat dan publik.',
                'concept' => 'Pesan di-hash terlebih dahulu. Hash kemudian ditandatangani menggunakan private key dan diverifikasi menggunakan public key.',
                'steps' => [
                    'Buat pasangan private key dan public key.',
                    'Hitung hash pesan.',
                    'Buat tanda tangan menggunakan private key.',
                    'Kirim pesan dan tanda tangan.',
                    'Verifikasi tanda tangan dengan public key.',
                ],
                'formula' => 'Signature = Sign(PrivateKey, Hash(M))',
                'example' => 'Penerima menghitung ulang hash pesan dan memverifikasi tanda tangan menggunakan public key.',
                'note' => 'Implementasi nyata harus menggunakan skema modern seperti RSA-PSS, bukan operasi RSA mentah.',
            ],

            'diffie-hellman' => [
                'category' => 'fungsi-utama',
                'title' => 'Diffie-Hellman Key Exchange',
                'intro' => 'Diffie-Hellman merupakan metode pertukaran kunci yang memungkinkan dua pihak menghasilkan shared secret melalui kanal publik.',
                'concept' => 'Metode ini menggunakan bilangan prima p, generator g, dan operasi perpangkatan modular.',
                'steps' => [
                    'Sepakati p dan g.',
                    'Alice memilih private value a.',
                    'Bob memilih private value b.',
                    'Alice mengirim A = g^a mod p.',
                    'Bob mengirim B = g^b mod p.',
                    'Keduanya menghitung shared secret.',
                ],
                'formula' => 'K = B^a mod p = A^b mod p',
                'example' => 'Gunakan p = 23 dan g = 5. Alice memilih a = 6, Bob memilih b = 15. Keduanya dapat memperoleh shared secret yang sama.',
                'note' => 'Diffie-Hellman harus diautentikasi untuk mencegah serangan man-in-the-middle.',
            ],

            'aes-encryption' => [
                'category' => 'fungsi-utama',
                'title' => 'Implementasi AES Encryption',
                'intro' => 'Materi ini membahas penggunaan AES sebagai fungsi enkripsi dan dekripsi data dalam aplikasi.',
                'concept' => 'Enkripsi mengubah plaintext menjadi ciphertext menggunakan kunci. Dekripsi mengembalikan ciphertext menjadi plaintext menggunakan kunci yang sesuai.',
                'steps' => [
                    'Masukkan plaintext.',
                    'Pilih panjang kunci AES.',
                    'Buat nonce atau IV sesuai mode.',
                    'Lakukan enkripsi.',
                    'Simpan ciphertext dan parameter yang diperlukan.',
                    'Lakukan dekripsi untuk menguji hasil.',
                ],
                'formula' => 'C = AES_Encrypt(K,P)',
                'example' => 'Plaintext "HELLO" dienkripsi menggunakan AES-GCM. Ciphertext kemudian didekripsi menggunakan kunci dan nonce yang sama.',
                'note' => 'Gunakan AES-GCM dengan nonce unik dan autentikasi tag untuk implementasi aplikasi.',
            ],

            // ==========================================
            // 4. DASAR MATEMATIKA ALGORITMA
            // ==========================================

            'modulo' => [
                'category' => 'dasar-matematika',
                'title' => 'Aritmetika Modulo',
                'intro' => 'Aritmetika modulo adalah operasi matematika yang menghasilkan sisa pembagian suatu bilangan.',
                'concept' => 'Operasi modulo banyak digunakan dalam algoritma RSA, Diffie-Hellman, dan kriptografi kunci publik lainnya.',
                'steps' => [
                    'Tentukan bilangan a.',
                    'Tentukan modulus n.',
                    'Bagi a dengan n.',
                    'Ambil sisa pembagiannya.',
                ],
                'formula' => 'a mod n = r',
                'example' => '17 mod 5 = 2 karena 17 = 3 x 5 + 2.',
                'note' => 'Operasi modulo menjadi dasar perhitungan perpangkatan modular.',
            ],

            'bilangan-prima' => [
                'category' => 'dasar-matematika',
                'title' => 'Bilangan Prima',
                'intro' => 'Bilangan prima adalah bilangan bulat positif lebih besar dari satu yang hanya memiliki dua faktor positif, yaitu satu dan dirinya sendiri.',
                'concept' => 'Bilangan prima memiliki peranan penting dalam pembentukan kunci RSA.',
                'steps' => [
                    'Ambil bilangan n lebih besar dari satu.',
                    'Periksa pembagi dari 2 sampai akar kuadrat n.',
                    'Jika ditemukan pembagi selain satu dan n, bilangan bukan prima.',
                    'Jika tidak ditemukan, bilangan tersebut prima.',
                ],
                'formula' => 'n prima jika faktor positifnya hanya 1 dan n.',
                'example' => 'Bilangan 7 adalah prima karena hanya dapat dibagi oleh 1 dan 7.',
                'note' => 'Pemeriksaan prima sederhana cocok untuk demonstrasi bilangan kecil, bukan pembangkitan kunci produksi.',
            ],

            'faktorisasi' => [
                'category' => 'dasar-matematika',
                'title' => 'Faktorisasi Bilangan',
                'intro' => 'Faktorisasi adalah proses menyatakan suatu bilangan sebagai hasil perkalian faktor-faktor yang lebih kecil.',
                'concept' => 'Faktorisasi prima menyatakan bilangan sebagai perkalian bilangan-bilangan prima.',
                'steps' => [
                    'Ambil bilangan yang akan difaktorkan.',
                    'Cari faktor prima terkecil.',
                    'Bagi bilangan dengan faktor tersebut.',
                    'Ulangi sampai hasil pembagian menjadi satu.',
                ],
                'formula' => 'n = p_1^{a_1} x p_2^{a_2} x ... x p_k^{a_k}',
                'example' => '60 = 2 x 2 x 3 x 5 = 2^2 x 3 x 5.',
                'note' => 'Kesulitan faktorisasi bilangan besar menjadi salah satu dasar keamanan RSA klasik.',
            ],

            'gcd' => [
                'category' => 'dasar-matematika',
                'title' => 'Greatest Common Divisor (GCD)',
                'intro' => 'GCD adalah faktor persekutuan terbesar dari dua bilangan bulat.',
                'concept' => 'Algoritma Euclidean digunakan untuk mencari GCD secara efisien.',
                'steps' => [
                    'Ambil dua bilangan a dan b.',
                    'Hitung sisa pembagian a mod b.',
                    'Ganti a dengan b dan b dengan sisanya.',
                    'Ulangi sampai sisa bernilai nol.',
                    'Nilai terakhir yang bukan nol adalah GCD.',
                ],
                'formula' => 'gcd(a,b) = gcd(b, a mod b)',
                'example' => 'gcd(48,18): 48 mod 18 = 12; 18 mod 12 = 6; 12 mod 6 = 0. Jadi GCD = 6.',
                'note' => 'GCD digunakan untuk memeriksa apakah dua bilangan relatif prima.',
            ],

            'extended-euclidean' => [
                'category' => 'dasar-matematika',
                'title' => 'Extended Euclidean Algorithm',
                'intro' => 'Extended Euclidean Algorithm adalah pengembangan algoritma Euclidean yang mencari koefisien x dan y sehingga ax + by = gcd(a,b).',
                'concept' => 'Algoritma ini dapat digunakan untuk menghitung invers modular ketika GCD bernilai satu.',
                'steps' => [
                    'Hitung GCD menggunakan Euclidean Algorithm.',
                    'Catat persamaan pembagian setiap tahap.',
                    'Lakukan substitusi balik.',
                    'Dapatkan koefisien x dan y.',
                    'Jika GCD satu, x merupakan invers modular a.',
                ],
                'formula' => 'ax + my = 1; maka x mod m = a^{-1} mod m',
                'example' => 'Invers 3 modulo 11 adalah 4 karena 3 x 4 = 12 dan 12 mod 11 = 1.',
                'note' => 'Invers modular hanya ada jika gcd(a,m) = 1.',
            ],

            'fermat' => [
                'category' => 'dasar-matematika',
                'title' => 'Teorema Kecil Fermat',
                'intro' => 'Teorema Kecil Fermat menjelaskan hubungan perpangkatan modular dengan bilangan prima.',
                'concept' => 'Jika p bilangan prima dan p tidak membagi a, maka perpangkatan a^(p-1) memiliki sisa satu modulo p.',
                'steps' => [
                    'Tentukan bilangan prima p.',
                    'Tentukan a yang tidak habis dibagi p.',
                    'Hitung a pangkat p-1.',
                    'Ambil hasil modulo p.',
                ],
                'formula' => 'a^(p-1) = 1 mod p',
                'example' => '2^4 mod 5 = 16 mod 5 = 1.',
                'note' => 'Teorema ini menjadi dasar beberapa teknik perhitungan modular.',
            ],

            'euler' => [
                'category' => 'dasar-matematika',
                'title' => 'Teorema Euler',
                'intro' => 'Teorema Euler merupakan generalisasi Teorema Kecil Fermat.',
                'concept' => 'Jika a dan n relatif prima, maka perpangkatan a dengan eksponen fungsi Euler phi(n) menghasilkan sisa satu modulo n.',
                'steps' => [
                    'Tentukan a dan n.',
                    'Periksa gcd(a,n) = 1.',
                    'Hitung phi(n).',
                    'Hitung a pangkat phi(n) modulo n.',
                ],
                'formula' => 'a^phi(n) = 1 mod n',
                'example' => 'Untuk a = 3 dan n = 10, phi(10) = 4. Maka 3^4 mod 10 = 81 mod 10 = 1.',
                'note' => 'Teorema Euler merupakan dasar matematis dalam RSA.',
            ],

            'phi-euler' => [
                'category' => 'dasar-matematika',
                'title' => 'Fungsi Totient Euler',
                'intro' => 'Fungsi Euler phi(n) menghitung banyaknya bilangan positif yang lebih kecil atau sama dengan n dan relatif prima terhadap n.',
                'concept' => 'Untuk dua bilangan prima berbeda p dan q, phi(pq) = (p-1)(q-1).',
                'steps' => [
                    'Tentukan bilangan n.',
                    'Cari bilangan dari 1 sampai n yang relatif prima dengan n.',
                    'Hitung jumlah bilangan tersebut.',
                    'Hasilnya merupakan phi(n).',
                ],
                'formula' => 'phi(pq) = (p-1)(q-1), untuk p dan q prima berbeda.',
                'example' => 'phi(10) = 4 karena bilangan 1, 3, 7, dan 9 relatif prima dengan 10.',
                'note' => 'Fungsi Euler digunakan dalam pembentukan parameter RSA.',
            ],

            'finite-field' => [
                'category' => 'dasar-matematika',
                'title' => 'Finite Field',
                'intro' => 'Finite Field atau medan hingga adalah struktur aljabar yang memiliki sejumlah elemen terbatas dan operasi penjumlahan serta perkalian.',
                'concept' => 'AES menggunakan operasi dalam GF(2^8) untuk transformasi MixColumns dan pembentukan S-Box.',
                'steps' => [
                    'Representasikan elemen sebagai polinomial biner.',
                    'Lakukan penjumlahan menggunakan XOR.',
                    'Lakukan perkalian polinomial.',
                    'Reduksi hasil menggunakan polinomial irreducible.',
                ],
                'formula' => 'a + b = a XOR b',
                'example' => 'Dalam GF(2), 1 + 1 = 0 karena operasi penjumlahan dilakukan modulo 2.',
                'note' => 'AES menggunakan GF(2^8) dengan polinomial irreducible x^8 + x^4 + x^3 + x + 1.',
            ],

            'discrete-logarithm' => [
                'category' => 'dasar-matematika',
                'title' => 'Discrete Logarithm',
                'intro' => 'Discrete logarithm adalah masalah mencari eksponen x yang memenuhi g^x mod p = y.',
                'concept' => 'Masalah discrete logarithm menjadi dasar keamanan beberapa sistem kriptografi kunci publik.',
                'steps' => [
                    'Tentukan basis g.',
                    'Tentukan modulus p.',
                    'Tentukan hasil y.',
                    'Cari nilai x yang memenuhi persamaan modular.',
                ],
                'formula' => 'g^x mod p = y',
                'example' => '2^3 mod 5 = 8 mod 5 = 3. Dengan demikian, untuk g=2, p=5, dan y=3, salah satu nilai x adalah 3.',
                'note' => 'Contoh bilangan kecil hanya untuk pembelajaran karena mudah dicari secara brute force.',
            ],

            'elliptic-curve' => [
                'category' => 'dasar-matematika',
                'title' => 'Elliptic Curve Cryptography',
                'intro' => 'Elliptic Curve Cryptography (ECC) adalah pendekatan kriptografi kunci publik yang menggunakan operasi titik pada kurva eliptik.',
                'concept' => 'ECC menggunakan persamaan kurva dan operasi penjumlahan titik untuk membentuk sistem kunci publik.',
                'steps' => [
                    'Tentukan parameter kurva.',
                    'Tentukan titik generator G.',
                    'Pilih private key d.',
                    'Hitung public key Q = dG.',
                    'Gunakan operasi titik sesuai protokol kriptografi.',
                ],
                'formula' => 'Q = dG',
                'example' => 'Public key dibentuk dengan mengalikan titik generator G dengan private key d melalui penjumlahan titik berulang.',
                'note' => 'Implementasi nyata harus menggunakan kurva dan parameter standar yang telah teruji.',
            ],

            // ==========================================
            // BLOCK CIPHER (tambahan, mengikuti situs referensi)
            // ==========================================

            'idea' => [
                'category' => 'block-cipher',
                'title' => 'International Data Encryption Algorithm (IDEA)',
                'intro' => 'IDEA adalah cipher blok 64-bit dengan kunci 128-bit yang menggabungkan tiga operasi aljabar: XOR, penjumlahan modulo 2^16, dan perkalian modulo 65537.',
                'concept' => 'IDEA menggunakan struktur 8 putaran ditambah transformasi keluaran. Blok 64-bit dibagi menjadi empat subblok 16-bit yang diolah secara campuran.',
                'steps' => [
                    'Key schedule menghasilkan 52 subkunci 16-bit dari kunci 128-bit.',
                    'Blok 64-bit dibagi menjadi empat subblok 16-bit (X1 sampai X4).',
                    'Delapan putaran memadukan subblok dengan perkalian, penjumlahan, dan XOR.',
                    'Transformasi keluaran diterapkan setelah putaran terakhir.',
                    'Empat subblok hasil digabungkan menjadi ciphertext 64-bit.',
                ],
                'formula' => "Perkalian grup: a (bulat) mod 65537, dengan 0 diperlakukan sebagai 65536\nContoh putaran: X1' = X1 . K1; X2' = X2 + K2; X3' = X3 + K3; X4' = X4 . K4",
                'example' => 'Konstanta 65537 = 2^16 + 1 dipilih agar perkalian memiliki invers modulo 2^16. Misal 3 . 5 = 15; operasi dibalik dengan invers perkalian untuk dekripsi.',
                'note' => 'IDEA dipakai pada versi awal PGP. Patennya sudah berakhir sehingga bebas digunakan, tetapi blok 64-bit membuatnya rentan terhadap batas birthday attack.',
            ],

            'rc5' => [
                'category' => 'block-cipher',
                'title' => 'RC5 (RC5-32/12/16)',
                'intro' => 'RC5 adalah cipher blok parametrik yang terkenal karena rotasi bergantung data (data-dependent rotation), ditulis RC5-w/r/b.',
                'concept' => 'Parameter w menentukan ukuran kata (bit), r jumlah putaran, dan b panjang kunci. Pada RC5-32/12/16: kata 32-bit, 12 putaran, kunci 16 byte.',
                'steps' => [
                    'Key expansion menghasilkan 2r+2 subkunci kata dari kunci b byte.',
                    'Blok dibagi menjadi dua register A dan B.',
                    'A = A + S[0]; B = B + S[1].',
                    'Selama r putaran: A = ((A xor B) <<< B) + S[2i]; B = ((B xor A) <<< A) + S[2i+1].',
                    'Gabungan A dan B menjadi ciphertext.',
                ],
                'formula' => "A = ((A xor B) <<< B) + S[2i]\nB = ((B xor A) <<< A) + S[2i+1]",
                'example' => "Pada 32-bit, rotasi '<<<' bergeser sebanyak (nilai B mod 32). Jika B = 37 maka geser 5 bit. Jumlah pergeseran berubah mengikuti isi data.",
                'note' => 'Rotasi bergantung data membuat analisis differensial menjadi rumit. Serangan paling efektif pada RC5-32/12/16 memerlukan plaintext terpilih dalam jumlah besar.',
            ],

            'rc6' => [
                'category' => 'block-cipher',
                'title' => 'RC6 (RC6-32/20/16)',
                'intro' => 'RC6 adalah pengembangan RC5 untuk blok 128-bit sebagai kandidat AES, memakai empat register 32-bit dan perkalian modulo 2^32.',
                'concept' => 'RC6-32/20/16 memakai kata 32-bit, 20 putaran, dan kunci 16 byte. Perkalian kuadrat B(2B+1) mod 2^32 menjadi sumber non-linearitas.',
                'steps' => [
                    'Key expansion seperti RC5 menghasilkan 2r+4 subkunci.',
                    'Blok 128-bit dibagi menjadi register A, B, C, D.',
                    'B = B + S[0]; D = D + S[1].',
                    'Selama 20 putaran, tiap register dirotasi dengan nilai fungsi kuadrat register lain dan di-XOR dengan subkunci.',
                    'C = C + S[2r+2]; A = A + S[2r+3] sebagai transformasi akhir.',
                ],
                'formula' => "t = (B(2B+1)) <<< log2(w)\nu = (D(2D+1)) <<< log2(w)\nA = ((A xor t) <<< u) + S[2i]",
                'example' => 'Jika B = 5, maka B(2B+1) = 55. Pada 32-bit, 55 dirotasi kiri sebanyak 55 mod 32 = 23 bit sebelum dipakai merotasi register lain.',
                'note' => 'RC6 gagal terpilih menjadi AES (kalah dari Rijndael) namun tetap dianggap aman dan menjadi kandidat historis IEEE 802.11i.',
            ],

            'twofish' => [
                'category' => 'block-cipher',
                'title' => 'Twofish',
                'intro' => 'Twofish adalah cipher blok 128-bit berstruktur Feistel dengan 16 putaran, finalis seleksi AES yang mendukung kunci 128, 192, dan 256 bit.',
                'concept' => 'Twofish memadukan S-Box bergantung kunci, matriks MDS (Maximum Distance Separable), whitening kunci sebelum dan sesudah putaran, serta jaringan Feistel tanpa jeda.',
                'steps' => [
                    'Key schedule membangun S-Box bergantung kunci dan subkunci whitening.',
                    'Blok 128-bit dibagi dua bagian 64-bit (L dan R).',
                    'Whitening awal: L dan R di-XOR dengan subkunci.',
                    '16 putaran Feistel: fungsi F menggunakan matriks MDS 4x4 dan S-Box 8-bit.',
                    'Whitening akhir menghasilkan ciphertext.',
                ],
                'formula' => "F(R) = MDS . (S-Box kunci (q0, q1)(R))\nL_i = R_{i-1}; R_i = L_{i-1} xor F(R_{i-1}, K_i)",
                'example' => 'Pada kunci 256-bit, key schedule memanfaatkan tiga keluarga S-Box berbeda sehingga substitusi tidak bisa diprediksi tanpa kunci.',
                'note' => 'Twofish dianggap sangat aman secara desain, tetapi implementasinya lebih lambat dibanding AES sehingga jarang dipakai di sistem modern.',
            ],

            'cast-128' => [
                'category' => 'block-cipher',
                'title' => 'CAST-128',
                'intro' => 'CAST-128 adalah cipher blok 64-bit berstruktur Feistel dengan 16 putaran dan kunci 128-bit, menggunakan delapan S-Box berukuran besar.',
                'concept' => 'CAST-128 memakai tiga jenis fungsi putaran (f1, f2, f3) yang bergantian: kombinasi rotasi, penjumlahan mod 2^32, XOR, dan substitusi S-Box.',
                'steps' => [
                    'Key schedule menghasilkan 32 subkunci (16 pasang Km, Kr) dari kunci 128-bit.',
                    'Blok 64-bit dibagi menjadi L dan R 32-bit.',
                    'Setiap putaran memakai f1, f2, atau f3 bergantian dengan subkunci dan jumlah rotasi masing-masing.',
                    'Setelah 12 putaran pertama, putaran ke-13 sampai 16 memakai kunci yang sama.',
                    'L dan R digabungkan menjadi ciphertext 64-bit.',
                ],
                'formula' => 'f1(D, Km, Kr) = I((I((D + Km) <<< Kr) . S1), S2, S3, S4) + Km',
                'example' => 'Blok D = 0x12345678 dijumlahkan modulo 2^32 dengan Km, dirotasi kiri sebanyak Kr mod 32, lalu melewati empat S-Box berturut-turut.',
                'note' => 'CAST-128 merupakan cipher default yang lebih lama pada OpenPGP dan masih dipakai untuk kompatibilitas, tetapi AES-GCM lebih direkomendasikan.',
            ],

            'skipjack' => [
                'category' => 'block-cipher',
                'title' => 'Skipjack',
                'intro' => 'Skipjack adalah cipher blok 64-bit dengan kunci 80-bit dan 32 putaran, dikembangkan NSA untuk perangkat Clipper chip.',
                'concept' => 'Skipjack memakai struktur Feistel tidak seimbang dengan dua jenis putaran yang disebut Rule A dan Rule B, masing-masing dijalankan 8 dan 24 kali secara bergantian.',
                'steps' => [
                    'Key schedule membagi kunci 80-bit menjadi empat subkunci 10 byte.',
                    'Blok 64-bit dibagi menjadi empat kata 16-bit (w1 sampai w4).',
                    'Rule A (8 putaran): register digeser dengan F di ujung.',
                    'Rule B (24 putaran): urutan geser berbeda dengan F tetap di ujung.',
                    'Setelah 32 putaran, empat kata digabungkan menjadi ciphertext.',
                ],
                'formula' => 'F adalah G permutation berbasis Feistel 4 putaran dengan S-Box F-table\nRule A: (w1 w2 w3 w4) -> (F(w1) xor w4, w1, w2, w3)',
                'example' => 'Dengan kunci 80-bit, G permutation memakai counter j = 1..4 untuk memilih subkunci bergantian pada setiap putaran internalnya.',
                'note' => 'Kunci 80-bit terlalu pendek untuk standar modern. Skipjack diklasifikasikan ke publik pada 1998 dan tidak direkomendasikan untuk sistem baru.',
            ],

            'serpent' => [
                'category' => 'block-cipher',
                'title' => 'Serpent',
                'intro' => 'Serpent adalah cipher blok 128-bit dengan 32 putaran dan struktur substitution-permutation network, finalis seleksi AES yang paling konservatif.',
                'concept' => 'Serpent memakai 8 S-Box 4-bit yang berbeda pada tiap putaran, transformasi linier XOR dan pergeseran, serta bitslice implementation agar cepat di prosesor modern.',
                'steps' => [
                    'Key schedule menurunkan 33 subkunci 128-bit dari kunci (pad ke 256 bit).',
                    'Initial permutation diterapkan pada plaintext.',
                    '32 putaran: XOR subkunci, substitusi 8 S-Box 4-bit paralel, transformasi linier.',
                    'Putaran terakhir memakai S-Box tambahan tanpa transformasi linier.',
                    'Final permutation menghasilkan ciphertext.',
                ],
                'formula' => 'B_i = LT(S_{i mod 8}(B_{i-1} xor K_i)), untuk i = 1..31',
                'example' => 'Pada satu putaran, 16 nibble (64 S-Box lookup paralel) diproses sekaligus; bitslice memakai operasi logika 32-bit sehingga S-Box dievaluasi tanpa tabel lookup.',
                'note' => 'Serpent memiliki margin keamanan terbesar di antara finalis AES, tetapi paling lambat. Cocok untuk studi ketahanan jangka panjang.',
            ],

            'camellia' => [
                'category' => 'block-cipher',
                'title' => 'Camellia',
                'intro' => 'Camellia adalah cipher blok 128-bit berstruktur Feistel dengan 18 putaran (kunci 128-bit) plus fungsi FL setelah putaran ke-6 dan ke-12.',
                'concept' => 'Camellia dikembangkan NTT dan Mitsubishi, memakai S-Box yang sebagian identik dengan AES, lapisan P-function, dan subkunci whitening.',
                'steps' => [
                    'Key schedule menghasilkan subkunci whitening dan subkunci putaran dari kunci.',
                    'Whitening awal di-XOR ke blok 128-bit.',
                    'Enam putaran Feistel, lalu fungsi FL/FL-invers diterapkan pada separuh kiri dan kanan.',
                    'Enam putaran berikutnya, FL lagi, lalu enam putaran terakhir (total 18).',
                    'Whitening akhir menghasilkan ciphertext.',
                ],
                'formula' => "L_i = R_{i-1}; R_i = L_{i-1} xor P(S(R_{i-1} xor k_i))\nFL/FL^-1 disisipkan setelah putaran 6 dan 12",
                'example' => 'Lapisan P adalah permutasi linier XOR dari delapan byte di GF(2), sebagaimana MixColumns pada AES.',
                'note' => 'Camellia tersertifikasi NESSIE, CRYPTREC, dan ISO/IEC 18033-3. Kinerjanya setara AES dan tersedia di TLS melalui RFC 6367.',
            ],

            'seed' => [
                'category' => 'block-cipher',
                'title' => 'SEED',
                'intro' => 'SEED adalah cipher blok 128-bit berstruktur Feistel dengan 16 putaran, standar enkripsi Korea Selatan yang dikembangkan KISA pada 1998.',
                'concept' => 'SEED memakai fungsi putaran F dengan S-Box 8x8 (dua buah) dan matriks campuran 16x16, kunci 128-bit dibagi dua dan dijadwalkan dengan rotasi 9 dan 17 bit.',
                'steps' => [
                    'Key schedule membagi kunci menjadi KI0 dan KI1, memutar 9 atau 17 bit bergantian per putaran.',
                    'Blok 128-bit dibagi menjadi L dan R 64-bit.',
                    'Setiap putaran: L_i = R_{i-1}; R_i = L_{i-1} xor F(R_{i-1}, KI0, KI1).',
                    'F memadukan dua S-Box dan penjumlahan modulo 2^32.',
                    'Setelah 16 putaran, L dan R ditukar dan digabungkan menjadi ciphertext.',
                ],
                'formula' => 'F(R, KI0, KI1) = M16x16( S3,S4(R xor KI1) ) + M16x16( S1,S2(R xor KI0) )',
                'example' => 'Subkunci putaran pertama dihasilkan dengan memutar kunci 9 bit ke kanan; putaran kedua 17 bit; pola berulang sampai putaran ke-16.',
                'note' => 'SEED wajib untuk layanan keuangan Korea dan tersertifikasi ISO/IEC 18033-3. Blok 128-bit membuatnya aman dari birthday attack.',
            ],

            // ==========================================
            // STREAM CIPHER (tambahan)
            // ==========================================

            'a5-2' => [
                'category' => 'stream-cipher',
                'title' => 'A5/2 Stream Cipher',
                'intro' => 'A5/2 adalah versi ekspor GSM yang diperlemah dari A5/1, memakai empat LFSR dengan R4 sebagai pengatur clock.',
                'concept' => 'Strukturnya sama dengan A5/1 (R1 19 bit, R2 22 bit, R3 23 bit) tetapi keputusan clocking ditentukan mayoritas dari tiga bit R4 (17 bit), dan keluarannya digabung dari fungsi mayoritas tambahan.',
                'steps' => [
                    'Empat register diinisialisasi dari kunci dan frame number.',
                    'R4 mengatur register mana yang bergeser dengan fungsi maj(R4[10], R4[3], R4[7]).',
                    'R4 bergeser pada setiap clock.',
                    'Keluaran z = R1[18] xor R2[21] xor R3[22] xor maj1 xor maj2 xor maj3.',
                    'Keystream di-XOR dengan data suara digital.',
                ],
                'formula' => "maj(a,b,c) = (a AND b) xor (a AND c) xor (b AND c)\nz = R1[18] xor R2[21] xor R3[22] xor maj1 xor maj2 xor maj3",
                'example' => 'Bit clocking R4[10], R4[3], R4[7] menentukan apakah R1, R2, atau R3 bergeser pada satu clock; polanya berbeda dari mayoritas A5/1 sehingga keamanannya jauh lebih lemah.',
                'note' => 'A5/2 dipatahkan secara real-time pada 1998 (serangan dalam hitungan detik). GSM memutuskannya sekitar tahun 2000-an dan beralih ke A5/3.',
            ],

            'rabbit' => [
                'category' => 'stream-cipher',
                'title' => 'Rabbit Stream Cipher',
                'intro' => 'Rabbit adalah stream cipher berkunci 128-bit dan IV 64-bit yang memakai delapan variabel state 32-bit dan delapan counter, distandarkan sebagai RFC 4503.',
                'concept' => 'Fungsi iterasi menghitung g_j = kuadrat bawah-xor-atas dari (x_j + c_j), lalu menyilangkan hasil antar variabel melalui penjumlahan dan rotasi 16 dan 8 bit.',
                'steps' => [
                    'Key setup membangun state x_j dan counter c_j dari kunci 128-bit melalui iterasi tertentu.',
                    'IV setup meng-XOR IV 64-bit ke counter dan menjalankan empat iterasi.',
                    'Setiap iterasi next_state memperbarui counter c_j dengan konstanta a_j dan carry.',
                    'Delapan g_j dihitung lalu disilangkan menjadi x_0 sampai x_7 baru.',
                    'Keystream 128-bit diambil dari gabungan x_j dan di-XOR dengan plaintext.',
                ],
                'formula' => "g_j = ((x_j + c_j)^2 xor ((x_j + c_j)^2 >> 32)) mod 2^32\nx_0 = g_0 + (g_7 <<< 16) + (g_6 <<< 16)",
                'example' => 'Untuk kunci 128-bit, x_0 sampai x_7 diisi dari empat kata kunci dan penjumlahan silang awal, kemudian diiterasi empat kali sebelum keystream diambil.',
                'note' => 'Rabbit adalah finalis eSTREAM dengan performa tinggi di software. Tidak diketahui serangan praktis, tetapi AES-GCM tetap lebih direkomendasikan untuk sistem baru.',
            ],

            'trivium' => [
                'category' => 'stream-cipher',
                'title' => 'Trivium Stream Cipher',
                'intro' => 'Trivium adalah stream cipher hardware ringan finalis eSTREAM dengan state 288 bit, kunci 80-bit, dan IV 80-bit.',
                'concept' => 'State terdiri atas tiga register geser (93, 84, 111 bit). Setiap clock, keluaran adalah XOR enam bit tertentu, dan umpan balik non-linear memanfaatkan AND dari dua bit dengan bit register lain.',
                'steps' => [
                    'Isi register dengan kunci 80-bit, IV 80-bit, dan padding konstanta.',
                    'Jalankan 1152 clock pemanasan tanpa mengambil keluaran.',
                    'Setiap clock: z = s66 xor s93 xor s162 xor s177 xor s243 xor s288.',
                    'Umpan balik: bit baru = z xor (s171 AND s176) xor s264; geser seluruh state.',
                    'Keystream bit z di-XOR dengan plaintext bit per bit.',
                ],
                'formula' => 'z = s66 xor s93 xor s162 xor s177 xor s243 xor s288\ns1 = z xor s171 . s176 xor s264 (tanda . = AND)',
                'example' => 'Dengan kunci dan IV 80-bit, state 288 bit diisi 160 bit data plus 128 bit padding; 1152 clock pertama dibuang agar state benar-benar tercampur.',
                'note' => 'Trivium sangat hemat gerbang sehingga cocok untuk perangkat keras ringan, tetapi throughput per bit rendah untuk software generik.',
            ],

            'hybrid-trio' => [
                'category' => 'stream-cipher',
                'title' => 'Hybrid Trio Stream Cipher (A5/1 + RC4 + ChaCha20)',
                'intro' => 'Hybrid Trio adalah kaskade tiga stream cipher (A5/1, RC4-128, ChaCha20) dengan derivasi kunci HKDF dari satu passphrase dan lapisan integritas Encrypt-then-MAC.',
                'concept' => 'Setiap byte plaintext di-XOR dengan tiga keystream: Z_LFSR (A5/1), Z_RC4 (RC4-128), dan Z_ChaCha (ChaCha20). Satu passphrase diturunkan menjadi empat subkunci berlabel domain via HKDF (RFC 5869), dan ciphertext disahkan HMAC-SHA256 sebelum dekripsi.',
                'steps' => [
                    'HKDF-Extract(salt acak 16 byte, passphrase) menghasilkan PRK.',
                    'HKDF-Expand menurunkan K_LFSR (8 byte), K_RC4 (16 byte), K_ChaCha (32 byte), dan K_MAC (32 byte) dengan label domain berbeda.',
                    'A5/1 diinisialisasi dari K_LFSR dan menjalankan 100 clock pemanasan.',
                    'RC4-128 dijalankan dengan KSA dari K_RC4; ChaCha20 dari K_ChaCha dan nonce acak 12 byte.',
                    'C = P xor Z_LFSR xor Z_RC4 xor Z_ChaCha.',
                    'Tag = HMAC-SHA256(K_MAC, salt || nonce || C) disimpan bersama ciphertext; verifikasi hash_equals sebelum dekripsi.',
                ],
                'formula' => "C[n] = P[n] xor Z_LFSR[n] xor Z_RC4[n] xor Z_ChaCha[n]\nZ_total[n] = Z_LFSR[n] xor Z_RC4[n] xor Z_ChaCha[n]\nP[n] = C[n] xor Z_total[n] (sifat involutori XOR)",
                'example' => 'Dengan plaintext KRIPTO dan passphrase KUNCI, jejak komputasi menghasilkan ciphertext hFv+b+ (lihat lab untuk trace per karakter).',
                'note' => 'Kaskade XOR menutupi (masking) kelemahan komponen, bukan menambah kekuatan di atas ChaCha20 seragam. Kelemahan nonce reuse tidak hilang: jika nonce dipakai ulang, seluruh keystream identik dan C1 xor C2 = P1 xor P2. Gunakan nonce acak unik dan jangan pakai untuk data nyata.',
            ],

            // ==========================================
            // FUNGSI UTAMA (tambahan)
            // ==========================================

            'md5' => [
                'category' => 'fungsi-utama',
                'title' => 'Hash MD5',
                'intro' => 'MD5 menghasilkan sidik jari 128-bit dari pesan dengan struktur Merkle-Damgard berblok 512 bit dan empat register 32-bit.',
                'concept' => '64 putaran memadukan pesan melalui fungsi F, G, H, I dan konstanta turunan sin. Padding menambahkan bit 1, bit 0, dan panjang pesan modulo 2^64.',
                'steps' => [
                    'Padding pesan hingga kelipatan 512 bit dengan bit 1, bit 0, dan panjang 64-bit.',
                    'Inisialisasi empat register A, B, C, D dengan konstanta baku.',
                    'Proses setiap blok 512 bit dalam 64 putaran (empat kelompok 16).',
                    'Tambahkan hasil blok ke register (Davies-Meyer).',
                    'Gabungan A, B, C, D menjadi digest 128-bit.',
                ],
                'formula' => 'MD5(M) = gabungan A, B, C, D setelah semua blok; digest 128 bit',
                'example' => 'MD5("abc") = 900150983cd24fb0d6963f7d28e17f72. Dua pesan berbeda dengan kolisi MD5 dapat dibuat dalam hitungan detik di laptop modern.',
                'note' => 'Kolisi MD5 sudah praktis sejak 2004 (Wang dkk.) dan dipakai menempa sertifikat (Flame, 2012). Jangan gunakan untuk keamanan; hanya untuk checksum non-keamanan.',
            ],

            'sha-1' => [
                'category' => 'fungsi-utama',
                'title' => 'Hash SHA-1',
                'intro' => 'SHA-1 menghasilkan hash 160-bit dengan struktur Merkle-Damgard 80 putaran, pernah menjadi standar tanda tangan digital dan TLS.',
                'concept' => 'SHA-1 memakai fungsi f bergantian, konstanta K, dan pergeseran siklik 5 dan 30 bit. Kelemahannya: kolisi penuh dapat dibuat dengan kompleksitas di bawah batas brute force 2^80.',
                'steps' => [
                    'Padding pesan ke kelipatan 512 bit.',
                    'Inisialisasi lima register h0 sampai h4.',
                    '80 putaran dengan ekspansi pesan 16 menjadi 80 kata.',
                    'Akumulasi hasil ke register.',
                    'Gabungan lima register menjadi digest 160-bit.',
                ],
                'formula' => 'h0..h4 = 67452301, efcdab89, 98badcfe, 10325476, c3d2e1f0',
                'example' => 'Kolisi SHA-1 praktis diumumkan 2017 (SHAttered, Google-CWI) dengan dua PDF berbeda ber-hash sama. Kolisi chosen-prefix diikuti 2020.',
                'note' => 'SHA-1 sudah dilarang untuk pembangkitan tanda tangan digital (NIST). Gunakan SHA-256 atau SHA-3 untuk kebutuhan keamanan.',
            ],

            'sha-3' => [
                'category' => 'fungsi-utama',
                'title' => 'Hash SHA-3 (Keccak)',
                'intro' => 'SHA-3 adalah standar hash NIST 2015 berbasis konstruksi sponge Keccak, dengan struktur internal berbeda total dari keluarga MD/SHA-2.',
                'concept' => 'State 1600 bit (r + c) dibagi rate r dan capacity c. Fungsi keccak-f[1600] dengan 24 putaran mengacak state; fase absorb menyerap blok pesan dan fase squeeze memeras hash.',
                'steps' => [
                    'Padding pesan dengan multi-rate padding (0x06 ... 0x80).',
                    'XOR blok pesan ke bagian rate state (fase absorb).',
                    'Jalankan keccak-f[1600] (24 putaran: theta, rho, pi, chi, iota).',
                    'Setelah pesan habis, baca bagian rate state (fase squeeze).',
                    'SHA3-256 menghasilkan 256 bit; SHA3-512 menghasilkan 512 bit.',
                ],
                'formula' => 'Keccak-f[1600]: 24 putaran theta, rho, pi, chi, iota\nsecurity level = c/2 bit (c = 512 untuk SHA3-256)',
                'example' => 'SHA3-256("abc") = 3a985da74fe225b2045c172d6bd390bd855f086e3e9d525b46bfe24511431532. Struktur sponge membuatnya tahan terhadap teknik kolisi gaya MD.',
                'note' => 'SHA-3 dibuat bukan karena SHA-2 lemah, melainkan sebagai cadangan struktur berbeda. Gunakan SHA-256 atau SHA3-256 untuk kebutuhan modern.',
            ],

            'cmac-aes' => [
                'category' => 'fungsi-utama',
                'title' => 'CMAC-AES (OMAC1)',
                'intro' => 'CMAC adalah kode autentikasi pesan berbasis cipher blok AES-128 sesuai RFC 4493, memperbaiki kelemahan CBC-MAC pada pesan variabel panjang.',
                'concept' => 'CMAC menurunkan dua subkunci K1 dan K2 dari CIPH_K(0^128) dengan penggandaan di GF(2^128). Blok terakhir di-XOR dengan K1 (pesan utuh) atau K2 (pesan terpadding).',
                'steps' => [
                    'Hitung L = AES_K(0^128).',
                    'K1 = L << 1 (dengan reduksi polinomial x^128 + x^7 + x^3 + x + 1); K2 = K1 << 1.',
                    'Bagi pesan menjadi blok 128-bit M1..Mn.',
                    'Jika blok terakhir utuh: Mn = Mn xor K1; jika dipad: Mn = Mn xor K2.',
                    'Jalankan CBC-MAC: T_i = AES_K(T_{i-1} xor M_i); T = T_n adalah tag 128-bit.',
                ],
                'formula' => "K1 = (L << 1) jika MSB(L) = 0, else (L << 1) xor Rb\nT = CBC-MAC dengan blok terakhir di-XOR K1 atau K2",
                'example' => 'Untuk pesan tepat 16 byte M, tag = AES_K(M xor K1). Untuk pesan 40 byte, blok ketiga dipad 10..0 lalu di-XOR K2 sebelum putaran CBC terakhir.',
                'note' => 'CMAC tersertifikasi NIST SP 800-38B. Gunakan kunci AES yang benar dan jangan memakai tag CMAC sebagai fungsi hash.',
            ],

            'rsa-oaep' => [
                'category' => 'fungsi-utama',
                'title' => 'Enkripsi RSA dengan OAEP',
                'intro' => 'RSA-OAEP adalah skema enkripsi RSA dengan padding optimal (Optimal Asymmetric Encryption Padding) yang memperbaiki kelemahan textbook RSA.',
                'concept' => 'Textbook RSA bersifat deterministik dan malleable sehingga rentan pencarian kamus dan manipulasi. OAEP menambahkan randomness, fungsi hash, dan jaringan Feistel dua arah (MGF1) sebelum eksponensiasi.',
                'steps' => [
                    'Pad pesan dengan label, hash, zero padding, dan seed acak melalui jaringan Feistel MGF1.',
                    'Hitung c = m_padded^e mod n dengan eksponen publik.',
                    'Dekripsi: m_padded = c^d mod n.',
                    'Unpad memverifikasi hash dan memisahkan seed untuk memulihkan pesan.',
                    'Kegagalan validasi menghasilkan error tanpa membocorkan informasi.',
                ],
                'formula' => 'c = m_padded ^ e mod n\nRSAES-OAEP 2048-bit + SHA-256: pesan maksimal 190 byte',
                'example' => 'Untuk kunci 2048-bit dan SHA-256, k = 256 byte; overhead padding 2*hLen + 2 = 66 byte sehingga pesan maksimal 190 byte.',
                'note' => 'Gunakan OAEP (bukan PKCS#1 v1.5) untuk enkripsi RSA modern. Seed acak wajib unik per pesan agar pencarian kamus gagal.',
            ],

            'rsa-pss' => [
                'category' => 'fungsi-utama',
                'title' => 'Tanda Tangan RSA-PSS',
                'intro' => 'RSA-PSS (Probabilistic Signature Scheme) adalah skema tanda tangan RSA dengan padding acak yang memiliki bukti keamanan lebih kuat daripada PKCS#1 v1.5.',
                'concept' => 'PSS menyisipkan salt acak ke padding melalui MGF1 sebelum eksponensiasi d, sehingga tanda tangan atas pesan yang sama berbeda setiap kali dan reduksi keamanannya ketat.',
                'steps' => [
                    'Hitung mHash = Hash(M).',
                    'Bangun salt acak sLen byte dan M = padding || mHash || salt.',
                    'H = Hash(M); DB = padding || mHash; maskedDB = DB xor MGF1(H).',
                    'Bentuk blok EM (berakhir 0xBC) dan hitung s = EM^d mod n.',
                    'Verifikasi merekonstruksi EM dari s^e dan membandingkan dengan hash_equals.',
                ],
                'formula' => 's = EM^d mod n; EM = maskedDB || H || 0xBC\nmaskedDB = DB xor MGF1(H)',
                'example' => 'RSA 2048 + SHA-256 + salt 32 byte: dua tanda tangan atas pesan yang sama menghasilkan angka s berbeda, tetapi keduanya valid.',
                'note' => 'PSS direkomendasikan FIPS 186-5 untuk tanda tangan RSA baru, menggantikan PKCS#1 v1.5 yang rentan pemalsuan tipe Bleichenbacher.',
            ],

            'elgamal' => [
                'category' => 'fungsi-utama',
                'title' => 'Enkripsi ElGamal',
                'intro' => 'ElGamal adalah enkripsi kunci publik berbasis logaritma diskrit, menghasilkan pasangan ciphertext (c1, c2) per pesan.',
                'concept' => 'Keamanan bertumpu pada sulitnya DLP di Z_p*. Ciphertext dua kali ukuran pesan karena setiap pesan menghasilkan dua komponen, dan sifatnya malleable (homomorfik perkalian).',
                'steps' => [
                    'Pilih prima p dan generator g; kunci privat x acak; kunci publik y = g^x mod p.',
                    'Enkripsi: pilih k acak per pesan; c1 = g^k mod p.',
                    'Hitung s = y^k mod p.',
                    'c2 = m . s mod p; pasangan (c1, c2) dikirim.',
                    'Dekripsi: s = c1^x mod p; m = c2 . s^{-1} mod p.',
                ],
                'formula' => "c1 = g^k mod p; c2 = m . (y^k) mod p\nm = c2 . (c1^x)^{-1} mod p",
                'example' => 'Dengan p = 2579, g = 2, x = 765, y = 949: pesan m = 1299 dan k = 853 menghasilkan c1 = 435, c2 = 2396. Dekripsi memulihkan 1299.',
                'note' => 'Parameter kecil hanya untuk pembelajaran. ElGamal malleable: c2 dapat dikalikan faktor tanpa kunci, sehingga butuh padding atau autentikasi tambahan.',
            ],

            'ecc' => [
                'category' => 'fungsi-utama',
                'title' => 'Enkripsi ECC (ECDH + AES-GCM)',
                'intro' => 'Enkripsi kurva eliptik modern memadukan ECDH untuk kesepakatan kunci dan AES-GCM untuk enkripsi simetris, memakai kurva P-256.',
                'concept' => 'Pengirim menurunkan shared point S = dA x QB (private pengirim kali public penerima), lalu menurunkan kunci AES dari S via KDF. Enkripsi dilakukan AES-GCM dengan nonce unik.',
                'steps' => [
                    'Pilih kurva P-256 dengan parameter baku (p, a, b, G, n).',
                    'Pengirim punya kunci privat dA dan publik QA = dA G.',
                    'Hitung S = dA QB (sama dengan dB QA di sisi penerima).',
                    'Turunkan kunci AES dari koordinat S dengan HKDF.',
                    'Enkripsi pesan dengan AES-GCM; kirim QA, nonce, ciphertext, tag.',
                ],
                'formula' => 'QA = dA . G; S = dA . QB = dB . QA (ECDH)\nK = HKDF(S_x); C, T = AES-GCM(K, nonce, P)',
                'example' => 'Dua titik P-256 dijumlahkan dengan aturan chord-tangent; perkalian skalar dA G dieksekusi double-and-add sehingga 256 bit cukup setara RSA 3072 bit.',
                'note' => 'Jangan pakai shared point mentah sebagai kunci; selalu turunkan lewat KDF. ECDLP pada kurva berkualitas belum diketahui cara efisien menyelesaikannya.',
            ],

            'ecdh' => [
                'category' => 'fungsi-utama',
                'title' => 'Pertukaran Kunci ECDH',
                'intro' => 'ECDH adalah Diffie-Hellman di kurva eliptik: dua pihak menyepakati kunci bersama melalui saluran terbuka tanpa mengirim kunci itu sendiri.',
                'concept' => 'Sifat komutatif perkalian skalar titik membuat dA QB = dA dB G = dB dA G. Keamanan bertumpu pada sulitnya ECDLP: mencari d dari Q = dG pada P-256 membutuhkan waktu astronomis.',
                'steps' => [
                    'Kedua pihak sepakat kurva P-256 dan titik generator G.',
                    'A memilih dA acak, mengirim QA = dA G.',
                    'B memilih dB acak, mengirim QB = dB G.',
                    'A menghitung S = dA QB; B menghitung S = dB QA.',
                    'Kedua pihak menurunkan kunci simetris dari S dengan KDF.',
                ],
                'formula' => "QA = dA . G; QB = dB . G\nS = dA . QB = dB . QA",
                'example' => 'Jika dA = 7 dan dB = 11, keduanya menghitung S = 77 G. Pengamat yang melihat QA dan QB tidak dapat menghitung 77 G tanpa memecah ECDLP.',
                'note' => 'Gunakan kunci publik yang tervalidasi (titik di kurva, bukan infinity) untuk mencegah serangan invalid-curve. ECDH P-256 direkomendasikan NIST SP 800-56A.',
            ],

            'ecdsa' => [
                'category' => 'fungsi-utama',
                'title' => 'Tanda Tangan ECDSA',
                'intro' => 'ECDSA adalah tanda tangan digital di kurva eliptik P-256 dengan hash SHA-256, menghasilkan pasangan (r, s) dari nonce k.',
                'concept' => 'Keamanan bertumpu pada ECDLP. Nonce k wajib unik dan rahasia: kebocoran satu k membuka kunci privat, dan pengulangan k pada dua pesan membuka d secara aljabar.',
                'steps' => [
                    'Pilih nonce k acak (atau deterministik RFC 6979).',
                    'Hitung titik (x1, y1) = k G; r = x1 mod n.',
                    'Hitung s = k^{-1} (H(m) + r d) mod n.',
                    'Verifikasi: w = s^{-1}; u1 = H(m) w; u2 = r w.',
                    'Titik X = u1 G + u2 Q valid jika koordinat x-nya mod n sama dengan r.',
                ],
                'formula' => "r = (k G).x mod n; s = k^{-1} (H(m) + r d) mod n\nd = (s k - H(m)) r^{-1} mod n (jika k bocor)",
                'example' => 'Pada kurva P-256, kG dieksekusi dengan double-and-add; koordinat x titik hasil menjadi r. Verifikasi menghitung ulang titik dan membandingkan koordinatnya.',
                'note' => 'Kegagalan nonce k menyebabkan pembobolan kunci privat (contoh terkenal: Sony PS3 menggunakan k tetap). Gunakan RFC 6979 untuk k deterministik.',
            ],

            // ==========================================
            // DASAR MATEMATIKA (tambahan, perspektif terapan)
            // ==========================================

            'faktorisasi-rsa' => [
                'category' => 'dasar-matematika',
                'title' => 'Faktorisasi RSA',
                'intro' => 'Keamanan RSA bertumpu pada sulitnya memfaktorkan modulus n = p x q menjadi dua prima asalnya.',
                'concept' => 'Algoritma terbaik saat ini (General Number Field Sieve) berjalan sub-eksponensial, membuat kunci 2048-bit masih aman. Untuk n kecil, trial division dan Pollard rho cukup cepat untuk demo.',
                'steps' => [
                    'Trial division: coba semua prima sampai akar n (hanya untuk n sangat kecil).',
                    'Pollard rho: iterasi x = f(x) dengan f(x) = x^2 + c mod n; cari gcd(|x - y|, n).',
                    'Pollard p-1: bila p-1 smooth, gcd(a^{M!} - 1, n) membuka faktor.',
                    'GNFS untuk n besar: sieving, linear algebra, dan square root.',
                    'Faktor ditemukan p, q memungkinkan menghitung d dari e.',
                ],
                'formula' => 'n = p x q\nPollard rho: x_{i+1} = x_i^2 + 1 mod n; d = gcd(|x_i - x_j|, n)',
                'example' => 'Faktorisasi n = 3233: trial division menemukan 3233 = 53 x 61. Dengan p dan q, phi(n) = 52 x 60 dan kunci privat dapat dihitung.',
                'note' => 'Rekor GNFS terkini memfaktorkan RSA-250 (829 bit). Kunci 2048-bit tetap aman untuk dekade ini; kunci 1024-bit sudah tidak boleh dipakai.',
            ],

            'rabin' => [
                'category' => 'dasar-matematika',
                'title' => 'Enkripsi Rabin',
                'intro' => 'Rabin mengenkripsi dengan mengkuadratkan pesan modulo n = p x q. Keamanannya ekuivalen langsung dengan sulitnya faktorisasi n.',
                'concept' => 'Ciphertext adalah sisa kuadrat. Dekripsi memerlukan akar kuadrat modulo p dan q yang dihitung via CRT, menghasilkan empat kandidat plaintext yang disaring dengan redundansi.',
                'steps' => [
                    'Pilih dua prima p, q dengan p, q = 3 (mod 4).',
                    'Publikasi n = p q; simpan p dan q sebagai kunci privat.',
                    'Enkripsi: c = m^2 mod n.',
                    'Dekripsi: hitung m_p = c^{(p+1)/4} mod p dan m_q = c^{(q+1)/4} mod q.',
                    'CRT menyusun empat akar kandidat plaintext.',
                ],
                'formula' => 'c = m^2 mod n\nm = CRT(m_p, m_q) dengan m_p^2 = c mod p, m_q^2 = c mod q',
                'example' => 'Dengan p = 7, q = 11, n = 77 dan m = 20: c = 400 mod 77 = 15. Akar kuadrat 15 mod 7 adalah 4 dan mod 11 adalah 9, yang via CRT memberi empat kandidat m.',
                'note' => 'Keamanan Rabin terbukti ekuivalen faktorisasi (lebih kuat daripada RSA teoretis), tetapi dekripsi ambigu memerlukan padding redundan agar empat akar dapat dipilih.',
            ],

            'dsa' => [
                'category' => 'dasar-matematika',
                'title' => 'Digital Signature Algorithm (DSA)',
                'intro' => 'DSA adalah standar tanda tangan berbasis logaritma diskrit di Z_p* dengan subgrup berorder q.',
                'concept' => 'Tanda tangan (r, s) memakai nonce k per pesan. Verifikasi memanfaatkan sifat eksponensiasi modular untuk merekonstruksi r tanpa kunci privat.',
                'steps' => [
                    'Pilih prima p (512-bit untuk demo) dan q pembagi p-1.',
                    'g = h^{(p-1)/q} mod p; kunci privat x; publik y = g^x mod p.',
                    'Tanda tangan: r = (g^k mod p) mod q; s = k^{-1}(H(m) + x r) mod q.',
                    'Verifikasi: w = s^{-1}; u1 = H(m) w; u2 = r w.',
                    'r = (g^{u1} y^{u2} mod p) mod q harus sama dengan r.',
                ],
                'formula' => "r = (g^k mod p) mod q\ns = k^{-1} (H(m) + x r) mod q\nvalid jika (g^{u1} y^{u2} mod p) mod q = r",
                'example' => 'Dengan p = 23, q = 11, g = 4, x = 7, y = 4: pesan H(m) = 5 dan k = 3 memberi r = 2, s = 6. Verifikasi menghitung ulang r dari u1, u2.',
                'note' => 'Kebocoran beberapa bit nonce k (lattice attack) cukup untuk membuka kunci privat. DSA modern mengikuti FIPS 186-5 dengan k deterministik.',
            ],

            'ntru' => [
                'category' => 'dasar-matematika',
                'title' => 'NTRU (Lattice-Based Encryption)',
                'intro' => 'NTRU adalah skema enkripsi berbasis kisi (lattice) pada ring polinomial R = Z[x]/(x^N - 1), kandidat kuat kriptografi pasca-kuantum.',
                'concept' => 'Keamanan bertumpu pada sulitnya mencari vektor pendek di lattice tertentu (SVP/CVP). Operasi adalah perkalian polinomial melingkar sehingga jauh lebih cepat daripada RSA pada tingkat keamanan sama.',
                'steps' => [
                    'Pilih parameter (N, p, q) dengan q lebih besar dari p.',
                    'Kunci privat f, g: polinomial kecil berkoefisien {-1, 0, 1}.',
                    'Kunci publik h = p . f^{-1} . g mod q.',
                    'Enkripsi: pilih polinomial acak r; c = r . h + m mod q.',
                    'Dekripsi: a = f . c mod q, lalu m = f_p^{-1} . a mod p.',
                ],
                'formula' => "h = p . (f^{-1} mod q) . g mod q\nc = r . h + m mod q\nm = f_p^{-1} . (f . c mod q) mod p",
                'example' => 'Pada demo kecil N = 11, f = 1 + x - x^3, g = -1 + x^2. Perkalian melingkar menggulung suku pangkat N ke pangkat 0 (x^N = 1).',
                'note' => 'Parameter sangat kecil (N = 11) hanya untuk belajar. NTRU asli memakai N = 509 atau lebih dan menjadi dasar standardisasi pasca-kuantum NIST.',
            ],

            'knapsack-merkle-hellman' => [
                'category' => 'dasar-matematika',
                'title' => 'Knapsack Merkle-Hellman',
                'intro' => 'Knapsack Merkle-Hellman adalah skema enkripsi kunci publik pertama (1978) berbasis masalah subset-sum, sudah dipatahkan oleh Shamir pada 1982.',
                'concept' => 'Kunci privat adalah barisan superincreasing; kunci publik dibentuk dengan perkalian modular yang menyamarkannya. Enkripsi menjumlahkan elemen publik sesuai bit pesan; dekripsi kembali ke barisan mudah.',
                'steps' => [
                    'Bentuk barisan superincreasing b_i (b_i > jumlah semua b_j untuk j < i).',
                    'Pilih q lebih besar dari jumlah total dan r koprima dengan q.',
                    'Kunci publik a_i = r . b_i mod q.',
                    'Enkripsi: c = jumlah a_i untuk setiap bit pesan yang bernilai 1.',
                    'Dekripsi: kalikan c dengan r^{-1} mod q, lalu pilih b_i secara greedy dari terbesar.',
                ],
                'formula' => "a_i = r . b_i mod q (publik)\nc = sum a_i x_i (x_i bit pesan)\ndekripsi: c' = r^{-1} c mod q lalu greedy di barisan b_i",
                'example' => 'b = (1, 3, 7, 13, 26, 65), q = 190, r = 37 memberi a = (37, 111, 69, 101, 12, 125). Pesan bit (1,0,1,0,0,1) menghasilkan c = 37 + 69 + 125 = 231.',
                'note' => 'Shamir memecah skema asli dengan serangan lattice dalam waktu polinomial (1982). Dipakai di sini sebagai pelajaran sejarah kegagalan kriptografi kunci publik awal.',
            ],

            // ==========================================
            // PERSPEKTIF MATEMATIS (materi rujukan silang)
            // ==========================================

            'diffie-hellman-matematika' => [
                'algorithm' => 'diffie-hellman',
                'category' => 'dasar-matematika',
                'title' => 'Diffie-Hellman: Perspektif Logaritma Diskrit',
                'intro' => 'Sisi matematika Diffie-Hellman: kesepakatan kunci bertumpu pada sulitnya menghitung logaritma diskrit di Z_p*.',
                'concept' => 'Dari g^a dan g^b yang terlihat publik, pengamat tidak dapat menghitung g^{ab} tanpa menyelesaikan DLP. Eksponensiasi modular memakai square-and-multiply dengan kompleksitas O(log a).',
                'steps' => [
                    'Publik: prima p dan generator g.',
                    'A kirim g^a mod p; B kirim g^b mod p.',
                    'A hitung (g^b)^a; B hitung (g^a)^b; hasilnya sama.',
                    'Penyerang menghadapi masalah DLP: cari a dari g^a mod p.',
                    'Kunci sesi diturunkan dari g^{ab} dengan hash.',
                ],
                'formula' => "g^{ab} = (g^a)^b = (g^b)^a mod p\nDLP: cari a sehingga g^a = y mod p",
                'example' => 'p = 23, g = 5, a = 6, b = 15: g^a = 8, g^b = 19, kunci bersama = 8^15 = 19^6 = 2 mod 23.',
                'note' => 'Untuk p 2048-bit, DLP memerlukan Number Field Sieve; gunakan grup standar (RFC 3526) dan hindari Logjam dengan parameter yang benar.',
            ],

            'ecdh-matematika' => [
                'algorithm' => 'ecdh',
                'category' => 'dasar-matematika',
                'title' => 'ECDH: Perspektif Kurva Eliptik',
                'intro' => 'Fondasi matematis ECDH: perkalian skalar titik pada kurva eliptik P-256 dan masalah logaritma diskrit kurva eliptik (ECDLP).',
                'concept' => 'Kelompok titik kurva dengan operasi chord-tangent membentuk grup siklik. ECDLP pada kurva berkualitas tidak memiliki algoritma index calculus, sehingga kunci jauh lebih pendek daripada DLP biasa.',
                'steps' => [
                    'Definisikan kurva y^2 = x^3 - 3x + b mod p (P-256).',
                    'Penjumlahan titik memakai invers modulo p dan garis potong.',
                    'Perkalian skalar d G dieksekusi double-and-add.',
                    'Shared secret S = dA QB = dB QA.',
                    'ECDLP: mencari d dari Q = d G.',
                ],
                'formula' => "y^2 = x^3 + ax + b mod p\nS = dA . QB = dB . QA\nECDLP: cari d sehingga Q = d . G",
                'example' => 'P-256 memberi tingkat keamanan 128 bit dengan kunci 256 bit; RSA membutuhkan 3072 bit untuk tingkat yang sama.',
                'note' => 'Serangan terbaik pada NIST P-256 membutuhkan sekitar 2^128 operasi. Hindari kurva non-standar dan selalu validasi titik publik.',
            ],

            'ecdsa-matematika' => [
                'algorithm' => 'ecdsa',
                'category' => 'dasar-matematika',
                'title' => 'ECDSA: Perspektif Logaritma Diskrit Eliptik',
                'intro' => 'Sisi matematika ECDSA: peran nonce k, hubungan r dan s, dan keamanan yang bertumpu pada ECDLP di P-256.',
                'concept' => 'Tanda tangan (r, s) memanfaatkan sifat komutatif perkalian skalar. Persamaan verifikasi merekonstruksi titik k G dari komponen publik tanpa mengetahui k maupun d.',
                'steps' => [
                    'k G menghasilkan r dari koordinat x.',
                    's menyembunyikan k dengan relasi s = k^{-1}(H(m) + r d).',
                    'Verifikasi menghitung u1 G + u2 Q = k G.',
                    'Membandingkan koordinat x dengan r menyetujui tanda tangan.',
                    'Mencari d dari (r, s, H(m)) memerlukan ECDLP.',
                ],
                'formula' => 'u1 G + u2 Q = (s^{-1} H(m)) G + (s^{-1} r) d G = s^{-1}(H(m) + r d) G = k G',
                'example' => 'Jika dua pesan ditandatangani dengan k sama, s1 != s2 tetapi r sama; d = (s1 k - H1) r^{-1} terbuka langsung.',
                'note' => 'Risiko terbesar ECDSA adalah nonce. RFC 6979 menurunkan k deterministik dari kunci dan pesan sehingga kegagalan RNG tidak berakibat fatal.',
            ],

            'elgamal-matematika' => [
                'algorithm' => 'elgamal',
                'category' => 'dasar-matematika',
                'title' => 'ElGamal: Perspektif Logaritma Diskrit',
                'intro' => 'Fondasi matematis ElGamal: mengapa keamanannya setara DLP dan mengapa sifat homomorfiknya perlu diperhatikan.',
                'concept' => 'Menemukan kunci privat x dari y = g^x adalah DLP. Tanpa x, rasio c2 antar pesan masih dapat dimanipulasi karena s = y^k hanya bergantung pada k yang tak diketahui penyerang.',
                'steps' => [
                    'Kunci publik y = g^x mod p.',
                    'S = y^k = g^{xk} hanya dapat dihitung oleh pemegang x.',
                    'c2 = m . S mod p menutupi pesan dengan S.',
                    'Homomorfik: c2 dikali t membuat pesan menjadi m . t.',
                    'Dekripsi membagi c2 dengan c1^x.',
                ],
                'formula' => "S = y^k = g^{xk} mod p\nm = c2 . (c1^x)^{-1} mod p\nc2/m = S (penyerang dapat mengalikan S dengan faktor sembarang)",
                'example' => 'Menggandakan c2 tanpa menyentuh c1 membuat dekripsi menghasilkan 2m. Inilah malleability yang butuh padding Cramer-Shoup atau MAC.',
                'note' => 'Gunakan Cramer-Shoup jika malleability tidak dapat diterima; ElGamal standar cukup bila plaintext sudah diautentikasi di lapisan lain.',
            ],

            'ecc-matematika' => [
                'algorithm' => 'ecc',
                'category' => 'dasar-matematika',
                'title' => 'ECC: Perspektif Kurva Eliptik',
                'intro' => 'Fondasi matematis ECC: struktur grup titik kurva eliptik dan mengapa ECDLP lebih sulit daripada DLP diskrit biasa.',
                'concept' => 'Tidak ada index calculus pada kelompok titik kurva umum, sehingga kunci 256-bit memberi keamanan 128 bit. Komputasi perkalian skalar tetap efisien dengan double-and-add.',
                'steps' => [
                    'Kurva Weierstrass y^2 = x^3 + ax + b dengan 4a^3 + 27b^2 != 0.',
                    'Titik di kurva plus titik di infinity membentuk grup abelian.',
                    'Aturan chord untuk penjumlahan dua titik; tangent untuk penggandaan.',
                    'Skalar d dieksekusi double-and-add dengan kompleksitas O(log d).',
                    'ECDLP: diberi Q = d G; tidak ada algoritma sub-eksponensial umum.',
                ],
                'formula' => "P + Q: garis melalui P, Q memotong kurva di -R; hasilnya R\n2P: garis tangent di P memotong di -R; hasilnya R\nECDLP: Q = d G, cari d",
                'example' => 'Pada P-256, p = 2^256 - 2^224 + 2^192 + 2^96 - 1. Koordinat dihitung modulo p dengan invers via Extended Euclidean.',
                'note' => 'Kurva menyediakan keamanan per bit tertinggi yang dipakai luas. Perhatikan risiko pasca-kuantum: algoritma Shor dapat menyelesaikan ECDLP bila komputer kuantum besar tersedia.',
            ],
        ];

        DB::transaction(function () use ($materials) {

            foreach ($materials as $materialKey => $item) {

                $algorithmSlug = $item['algorithm'] ?? $materialKey;

                $category = DB::table('crypto_categories')
                    ->where('slug', $item['category'])
                    ->first();

                if (! $category) {
                    throw new RuntimeException(
                        "Kategori tidak ditemukan: {$item['category']}"
                    );
                }

                // Materi rujukan silang boleh merujuk algoritma kanonik
                // di kategori lain (pola dedup satu-sumber).
                $algorithm = DB::table('crypto_algorithms')
                    ->where('slug', $algorithmSlug)
                    ->first();

                if (! $algorithm) {
                    throw new RuntimeException(
                        "Algoritma tidak ditemukan: {$algorithmSlug}"
                    );
                }

                // Pengayaan: contoh matematis LaTeX + potongan implementasi.
                // Materi rujukan silang mewarisi pengayaan algoritma kanoniknya.
                $extras = CryptoMaterialExtras::data();
                $extra = $extras[$materialKey] ?? $extras[$algorithmSlug] ?? [];
                $extra = array_intersect_key($extra, ['math' => true, 'kode' => true]);
                $item = $item + $extra;

                $content = $this->buildContent($item);

                DB::table('materials')->updateOrInsert(
                    ['slug' => 'materi-'.$materialKey],
                    [
                        'category_id' => $category->id,
                        'algorithm_id' => $algorithm->id,
                        'title' => $item['title'],
                        'content' => $content,
                        'is_published' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        $this->command->info('Berhasil memasukkan '.count($materials).' materi CryptoLab.');
    }

    private function buildContent(array $item): string
    {
        $e = fn ($value) => e($value);

        $html = '<article class="crypto-material">';

        $html .= '<h2>Pengertian</h2>';
        $html .= '<p>'.$e($item['intro']).'</p>';

        $html .= '<h2>Konsep Dasar</h2>';
        $html .= '<p>'.$e($item['concept']).'</p>';

        $html .= '<h2>Rumus atau Prinsip</h2>';
        $html .= '<div class="material-formula">'.$this->formulaKeLatex($item['formula']).'</div>';

        $html .= '<h2>Langkah Kerja</h2><ol>';

        foreach ($item['steps'] as $step) {
            $html .= '<li>'.$e($step).'</li>';
        }

        $html .= '</ol>';

        $html .= '<h2>Contoh Perhitungan</h2>';
        $html .= '<p>'.$e($item['example']).'</p>';

        if (! empty($item['math'])) {
            $html .= '<h2>Contoh Matematis</h2>';
            $html .= '<div class="material-formula">';
            foreach ($item['math'] as $line) {
                $html .= '<p>$$'.e($this->latexize($line)).'$$</p>';
            }
            $html .= '</div>';
        }

        if (! empty($item['kode'])) {
            $html .= '<h2>Implementasi Singkat (PHP)</h2>';
            $html .= '<pre><code>'.e($item['kode']).'</code></pre>';
        }

        $html .= '<h2>Catatan Keamanan</h2>';
        $html .= '<p>'.$e($item['note']).'</p>';

        $html .= '</article>';

        return $html;
    }

    /**
     * Ubah satu blok rumus menjadi baris-baris display math KaTeX.
     * Setiap baris dijadikan blok $$ ... $$ sendiri agar auto-render
     * KaTeX menyusunnya vertikal tanpa environment.
     */
    private function formulaKeLatex(string $formula): string
    {
        $baris = preg_split('/\r\n|\r|\n/', $formula) ?: [];

        $blok = [];

        foreach ($baris as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $blok[] = '<p>$$'.e($this->latexize($line)).'$$</p>';
        }

        return implode('', $blok);
    }

    /**
     * Konversi notasi pseudo pada rumus ke LaTeX yang dirender KaTeX.
     */
    private function latexize(string $text): string
    {
        $map = [
            'XOR' => '\oplus',
            ' AND ' => ' \wedge ',
            ' OR ' => ' \lor ',
            '<<<' => '\lll',
            '>>>' => '\ggg',
            '<<' => '\ll',
            '>>' => '\gg',
            '!=' => '\neq',
            '<=' => '\le',
            '>=' => '\ge',
            ' mod ' => ' \bmod ',
            '×' => '\times',
            '·' => '\cdot',
            '…' => '\dots',
            '->' => '\to',
        ];

        $text = str_replace(array_keys($map), array_values($map), $text);

        // Eksponen dan subskrip multi karakter wajib di-brace agar
        // KaTeX tidak merender hanya karakter pertama.
        $text = (string) preg_replace('/\^([0-9A-Za-z]{2,})/', '^{$1}', $text);
        $text = (string) preg_replace('/_([0-9A-Za-z]{2,})/', '_{$1}', $text);

        return $text;
    }
}
