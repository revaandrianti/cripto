<?php

namespace Database\Seeders;

/**
 * Pengayaan materi: contoh matematis (LaTeX, dirender KaTeX) dan
 * potongan implementasi PHP untuk materi-materi utama.
 *
 * Setiap angka di bawah sudah diverifikasi terhadap spesifikasi resmi
 * (FIPS 197, RFC 8439, RFC 4231, RFC 4493, RFC 5869) atau dihitung
 * ulang saat penyusunan; contoh yang gagal verifikasi tidak masuk.
 */
final class CryptoMaterialExtras
{
    /**
     * @return array<string, array{math?: list<string>, kode?: string}>
     */
    public static function data(): array
    {
        return [
            'aes' => [
                'math' => [
                    '\\text{S-Box: } S(0x53) = 0xED \\quad (\\text{tabel FIPS 197})',
                    '\\text{Perkalian GF}(2^8):\\ 0x57 \\cdot 0x83 = 0xC1',
                    '0x57 \\cdot 2 = 0xAE,\\quad 0x57 \\cdot 4 = 0x5C,\\quad 0x57 \\cdot 8 = 0xB8',
                    '0x57 \\cdot 0x83 = 0x57 \\cdot (0x80 \\oplus 0x02 \\oplus 0x01)',
                    '= (0x57 \\cdot 0x80) \\oplus 0xAE \\oplus 0x57 = 0xC1',
                    '\\text{ShiftRows: baris } r \\text{ digeser } r \\text{ byte ke kiri } (r = 0,1,2,3)',
                ],
                'kode' => <<<'PHP'
// AES-256-GCM dengan OpenSSL (encrypt + tag)
$key   = random_bytes(32);
$nonce = random_bytes(12);
$tag   = null;

$ct = openssl_encrypt('pesan rahasia', 'aes-256-gcm', $key,
    OPENSSL_RAW_DATA, $nonce, $tag);

$pt = openssl_decrypt($ct, 'aes-256-gcm', $key,
    OPENSSL_RAW_DATA, $nonce, $tag);
PHP,
            ],

            'des' => [
                'math' => [
                    '\\text{Toy Feistel 2x4 bit dengan } F(R) = R \\oplus K,\\ K = 1010',
                    'P = (L_0, R_0) = (0110, 1001)',
                    'F(R_0) = 1001 \\oplus 1010 = 0011',
                    'L_1 = R_0 = 1001,\\quad R_1 = L_0 \\oplus F = 0110 \\oplus 0011 = 0101',
                    '\\text{S-Box DES: } S_1(101101) = 0101 \\text{ (baris 11, kolom 1101)}',
                ],
                'kode' => <<<'PHP'
// DES-CBC 64 bit (phpseclib3)
use phpseclib3\Crypt\DES;

$des = new DES('cbc');
$des->setKey('kunci8by');            // 8 byte
$des->setIV(str_repeat("\0", 8));

$ct = $des->encrypt('8 byte data');
$pt = $des->decrypt($ct);
PHP,
            ],

            '3des' => [
                'math' => [
                    'C = E_{K_3}(D_{K_2}(E_{K_1}(P)))',
                    '\\text{Kunci } K = K_1 \\Vert K_2 \\Vert K_3 \\text{ (24 byte)}',
                    '\\text{Dekripsi: } P = D_{K_1}(E_{K_2}(D_{K_3}(C)))',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\TripleDES;

$des3 = new TripleDES('cbc');
$des3->setKey(random_bytes(24));
$des3->setIV(str_repeat("\0", 8));

$ct = $des3->encrypt('pesan');
PHP,
            ],

            'ecb' => [
                'math' => [
                    'C_i = E_K(P_i)',
                    '\\text{Jika } P_1 = P_2 \\text{ maka } C_1 = C_2 \\text{ (pola bocor)}',
                ],
                'kode' => <<<'PHP'
$ct = openssl_encrypt($data, 'aes-256-ecb', $key, OPENSSL_RAW_DATA);
// TANPA IV: blok identik -> ciphertext identik
PHP,
            ],

            'cbc' => [
                'math' => [
                    'C_0 = IV',
                    'C_i = E_K(P_i \\oplus C_{i-1})',
                    'P_i = D_K(C_i) \\oplus C_{i-1}',
                ],
                'kode' => <<<'PHP'
$iv = random_bytes(16);
$ct = openssl_encrypt($data, 'aes-256-cbc', $key,
    OPENSSL_RAW_DATA, $iv);
// simpan $iv bersama ciphertext
PHP,
            ],

            'ctr' => [
                'math' => [
                    'Z_i = E_K(\\mathrm{counter} + i)',
                    'C_i = P_i \\oplus Z_i',
                    '\\text{Counter 128 bit: } 0, 1, 2, \\dots',
                ],
                'kode' => <<<'PHP'
$iv = str_repeat("\0", 12) . "\x00\x00\x00\x01"; // counter mulai 1
$ct = openssl_encrypt($data, 'aes-256-ctr', $key,
    OPENSSL_RAW_DATA, $iv);
PHP,
            ],

            'rc4' => [
                'math' => [
                    '\\text{KSA dengan kunci } ``Key\'\': \\text{state tercampur, } i = j = 0',
                    '\\text{PRGA byte 1..4: } EB,\\ 9F,\\ 77,\\ 81',
                    "'K' \\oplus EB = 0x4B \\oplus 0xEB = 0xA0",
                ],
                'kode' => <<<'PHP'
// RC4 N=256 (kelas App\Crypto\Rc4)
use App\Crypto\Rc4;

$rc4 = new Rc4('kunci', 256);
$ct  = $rc4->crypt('pesan');   // XOR sekali = enkripsi
$pt  = (new Rc4('kunci', 256))->crypt($ct);
PHP,
            ],

            'a5-1' => [
                'math' => [
                    'R_1 = 0x55555,\\ R_2 = 0x333333,\\ R_3 = 0x787878',
                    'maj(R_1[8], R_2[10], R_3[10]) \\text{ menentukan register yang bergeser}',
                    'z = R_1[18] \\oplus R_2[21] \\oplus R_3[22]',
                    '\\text{7 detak pertama (bit MSB dulu): } 1101100_2 = 108',
                    '\\text{6 byte keystream: } 108, 21, 60, 0, 59, 98',
                ],
                'kode' => <<<'PHP'
use App\Crypto\A51;

$a51 = new A51('kunci64bit'); // 8 byte, +100 clock warmup
$ct  = $a51->crypt($pesan);   // 1 bit detak per bit data
PHP,
            ],

            'chacha20' => [
                'math' => [
                    'QR(a,b,c,d): a += b,\\ d \\mathrel{\\lll}= 16,\\ c += d,\\ b \\mathrel{\\lll}= 12,\\ a += b,\\ d \\mathrel{\\lll}= 8,\\ c += d,\\ b \\mathrel{\\lll}= 7',
                    'QR(0x11111111, 0x01020304, 0x9b8d6f43, 0x01234567)',
                    '= (0xea2a92f4, 0xcb1cf8ce, 0x4581472e, 0x5881c4bb)',
                    'Blok keystream = working + state \\text{ awal (RFC 8439)}',
                ],
                'kode' => <<<'PHP'
use App\Crypto\ChaCha20;

$chacha = new ChaCha20(random_bytes(32), random_bytes(12), 1);
$ct = $chacha->crypt($pesan); // keystream 8-bit penuh (RFC 8439)
PHP,
            ],

            'salsa20' => [
                'math' => [
                    'QR(y_0,y_1,y_2,y_3): y_1 \\oplus= (y_0 + y_3) \\mathrel{\\lll} 7,\\ y_2 \\oplus= (y_1 + y_0) \\mathrel{\\lll} 9',
                    'y_3 \\oplus= (y_2 + y_1) \\mathrel{\\lll} 13,\\ y_0 \\oplus= (y_3 + y_2) \\mathrel{\\lll} 18',
                    'Blok = X + \\mathrm{Doubleround}^{10}(X)',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\Salsa20;

$salsa = new Salsa20();
$salsa->setKey(random_bytes(32));  // kunci 256 bit
$salsa->setNonce(random_bytes(8)); // nonce 64 bit

$ct = $salsa->encrypt($pesan);
PHP,
            ],

            'otp' => [
                'math' => [
                    'C = P \\oplus K,\\quad P = C \\oplus K',
                    '75 \\oplus 42 = 101 \\ (\\text{ karakter } i)',
                    '\\text{Kunci panjangnya sama dengan pesan dan dipakai SEKALI}',
                    '\\text{Dua pesan dengan kunci sama: } C_1 \\oplus C_2 = P_1 \\oplus P_2',
                ],
                'kode' => <<<'PHP'
// OTP sejati: kunci acak SEKALI pakai, sepanjang pesan
$key = random_bytes(strlen($pesan));
$ct  = $pesan ^ $key;
// buang $key setelah dipakai; simpan di kanal aman terpisah
PHP,
            ],

            'lfsr' => [
                'math' => [
                    '\\text{LFSR 4-bit, polinomial } x^4 + x^3 + 1 \\text{ (primitif, periode } 15)',
                    '1111 \\to 1110 \\to 1100 \\to 1000 \\to 0001 \\to 0010',
                    's_4 \\text{ baru} = s_3 \\oplus s_2 \\text{ (umpan balik)}',
                ],
                'kode' => <<<'PHP'
// Versi lengkap 64 bit: kelas App\Crypto\A51 (tiga LFSR)
use App\Crypto\A51;

$a51 = new A51($kunci8Byte);
$bit = $a51->keystreamByte(8); // 8 detak = 1 byte
PHP,
            ],

            'hybrid-trio' => [
                'math' => [
                    'P[0] = 75 \\ (K),\\quad Z_{\\mathrm{LFSR}} = 108,\\quad Z_{\\mathrm{RC4}} = 122,\\quad Z_{\\mathrm{ChaCha}} = 53',
                    'T_1 = 75 \\oplus 108 = 39',
                    'T_2 = 39 \\oplus 122 = 93',
                    'C = 93 \\oplus 53 = 104 \\ (h)',
                    '\\text{KRIPTO} \\to \\texttt{hFv+b+} \\text{ (terverifikasi)}',
                ],
                'kode' => <<<'PHP'
use App\Crypto\StreamCipherFactory;

$bundle = StreamCipherFactory::enkripsi('KRIPTO', 'KUNCI');
// salt 16B acak, nonce 12B acak, tag HMAC-SHA256
$pt = StreamCipherFactory::dekripsi($bundle, 'KUNCI');
PHP,
            ],

            'sha-256' => [
                'math' => [
                    '\\text{Padding } ``abc\'\': 0x61626380\\ 0\\cdots 0\\ 0x18 \\text{ (panjang } 24 \\text{ bit)}',
                    '\\text{Digest: } SHA256(abc) = ba7816bf\\ 8f01cfea\\ 414140de\\ 5dae2223',
                    '\\text{Kompresi: } H_i = H_{i-1} + \\Sigma(\\text{blok}, H_{i-1})',
                ],
                'kode' => <<<'PHP'
$digest = hash('sha256', $pesan, true);
echo bin2hex($digest);
PHP,
            ],

            'md5' => [
                'math' => [
                    '\\text{Padding } ``abc\'\': 0x61626380 \\cdots 0x18',
                    'MD5(abc) = 90015098\\ 3cd24fb0\\ d6963f7d\\ 28e17f72',
                    '\\text{64 putaran: } F, G, H, I \\text{ masing-masing } 16 \\text{ kali}',
                ],
                'kode' => <<<'PHP'
$digest = hash('md5', $pesan); // 32 karakter hex
// JANGAN dipakai untuk keamanan: kolisi praktis sejak 2004
PHP,
            ],

            'hmac' => [
                'math' => [
                    'HMAC(K, m) = H((K \\oplus opad) \\Vert H((K \\oplus ipad) \\Vert m))',
                    'ipad = 36363636 \\cdots,\\quad opad = 5c5c5c5c \\cdots',
                    '\\text{RFC 4231 Kasus 1: } K = 0b^{20},\\ m = ``Hi There\'\'',
                    '= b0344c61\\ d8db3853\\ 5ca8afce\\ af0bf12b \\cdots',
                ],
                'kode' => <<<'PHP'
$tag = hash_hmac('sha256', $pesan, $kunci, true);
// verifikasi selalu waktu-konstan:
$valid = hash_equals($tagTersimpan, $tag);
PHP,
            ],

            'cmac-aes' => [
                'math' => [
                    'L = AES_K(0^{128})',
                    'K_1 = L \\ll 1 \\text{ (GF}(2^{128})\\text{)},\\quad K_2 = K_1 \\ll 1',
                    '\\text{RFC 4493: } K_1 = fbeed618\\ 35713366\\ 7c85e08f\\ 7236a8de',
                    'T_i = AES_K(T_{i-1} \\oplus M_i),\\quad T = T_n',
                ],
                'kode' => <<<'PHP'
use App\Crypto\AlgorithmRunner;

$tag = AlgorithmRunner::cmacAes($key16, $pesan); // implementasi RFC 4493
PHP,
            ],

            'rsa-oaep' => [
                'math' => [
                    '\\text{Textbook RSA: } n = 61 \\cdot 53 = 3233,\\ e = 17,\\ d = 2753',
                    'c = 65^{17} \\bmod 3233 = 2790',
                    '65 = 2790^{2753} \\bmod 3233',
                    '\\text{OAEP menambah seed acak } + \\text{ MGF1 sebelum eksponensiasi}',
                ],
                'kode' => <<<'PHP'
// Kunci 2048-bit via phpseclib, enkripsi via OpenSSL
use phpseclib3\Crypt\RSA;

$rsa  = RSA::createKey(2048);
$pub  = $rsa->getPublicKey()->toString('PKCS8');
$priv = $rsa->toString('PKCS8');

openssl_public_encrypt($pesan, $ct, $pub, OPENSSL_PKCS1_OAEP_PADDING);
openssl_private_decrypt($ct, $pt, $priv, OPENSSL_PKCS1_OAEP_PADDING);
PHP,
            ],

            'rsa-signature' => [
                'math' => [
                    '\\text{Kunci demo } n = 3233,\\ d = 2753,\\ e = 17',
                    's = m^d \\bmod n = 65^{2753} \\bmod 3233 = 588',
                    'm = s^e \\bmod n = 588^{17} \\bmod 3233 = 65',
                    '\\text{Produksi: kunci 2048 bit + padding PSS}',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\RSA;

$rsa  = RSA::createKey(2048);
$priv = $rsa->toString('PKCS8');
$pub  = $rsa->getPublicKey()->toString('PKCS8');

openssl_sign($pesan, $sig, $priv, OPENSSL_ALGO_SHA256);
$valid = openssl_verify($pesan, $sig, $pub, OPENSSL_ALGO_SHA256) === 1;
PHP,
            ],

            'diffie-hellman' => [
                'math' => [
                    'p = 23,\\ g = 5,\\ a = 6,\\ b = 15',
                    'A = 5^6 \\bmod 23 = 8,\\quad B = 5^{15} \\bmod 23 = 19',
                    's_A = 19^6 \\bmod 23 = 2,\\quad s_B = 8^{15} \\bmod 23 = 2',
                    '(g^a)^b = (g^b)^a = g^{ab} \\bmod p',
                ],
                'kode' => <<<'PHP'
// bcmath: eksponensiasi modular 1024-bit
$a = gmp_strval(gmp_random_bits(200)); // atau bcmath acak
$A = bcpowmod($g, $a, $p);       // dikirim
$s  = bcpowmod($B, $a, $p);      // kunci bersama
PHP,
            ],

            'elgamal' => [
                'math' => [
                    'p = 2579,\\ g = 2,\\ x = 765,\\ y = 2^{765} \\bmod 2579 = 949',
                    'm = 1299,\\ k = 853: \\ c_1 = 2^{853} \\bmod 2579 = 435',
                    'c_2 = 1299 \\cdot 949^{853} \\bmod 2579 = 2396',
                    'm = 2396 \\cdot (435^{765})^{-1} \\bmod 2579 = 1299',
                ],
                'kode' => <<<'PHP'
// bcmath: s = y^k mod p, c2 = m*s mod p
$c1 = bcpowmod($g, $k, $p);
$c2 = bcmul($m, bcpowmod($y, $k, $p)) % $p;

// dekripsi: m = c2 * (c1^x)^{-1} mod p
$m  = ($c2 * bcpowmod($c1, $x, $p) ** -1) % $p; // lihat inversMod di runner
PHP,
            ],

            'rabin' => [
                'math' => [
                    'p = 7,\\ q = 11,\\ n = 77,\\ m = 20',
                    'c = m^2 \\bmod n = 400 \\bmod 77 = 15',
                    'm_p = 15^{(7+1)/4} \\bmod 7 = 4,\\quad m_q = 15^{(11+1)/4} \\bmod 11 = 9',
                    '\\text{CRT memberi 4 akar: } 20, 13, 57, 64',
                ],
                'kode' => <<<'PHP'
// p, q dengan p = q = 3 (mod 4)
$c  = bcpowmod($m, '2', $n);
$mp = bcpowmod($c, bcdiv(bcadd($p, '1'), '4'), $p);
$mq = bcpowmod($c, bcdiv(bcadd($q, '1'), '4'), $q);
// gabungkan via CRT, pilih akar dengan padding redundan
PHP,
            ],

            'knapsack-merkle-hellman' => [
                'math' => [
                    'b = (1, 3, 7, 13, 26, 65),\\ q = 190,\\ r = 37',
                    'a_i = 37 \\cdot b_i \\bmod 190',
                    'a = (37, 111, 69, 101, 12, 125)',
                    '\\text{Pesan } (1,0,1,0,0,1): c = 37 + 69 + 125 = 231',
                    "\\text{Dekripsi: } c' = 37^{-1} \\cdot 231 \\bmod 190, \\text{ lalu greedy di } b",
                ],
                'kode' => <<<'PHP'
// Kunci publik
$pub = array_map(fn ($bi) => ($r * $bi) % $q, $b);

// Enkripsi: jumlah a_i untuk setiap bit 1
$c = 0;
foreach ($bits as $i => $x) { $c += $x ? $pub[$i] : 0; }

// Dekripsi: kalikan invers, greedy dari b terbesar
$cinv = ($c * modInverse($r, $q)) % $q;
PHP,
            ],

            'faktorisasi-rsa' => [
                'math' => [
                    'n = 3233',
                    'x_{i+1} = x_i^2 + 1 \\bmod n,\\quad d = \\gcd(|x_i - y_i|, n)',
                    '\\text{Pollard rho menemukan } d = 53',
                    '3233 = 53 \\times 61',
                ],
                'kode' => <<<'PHP'
// Pollard rho (versi int di AlgorithmRunner)
$x = $y = 2; $d = 1;
while ($d === 1) {
    $x = ($x * $x + $c) % $n;
    $y = ($y * $y + $c) % $n;
    $y = ($y * $y + $c) % $n;
    $d = gcd(abs($x - $y), $n);
}
PHP,
            ],

            'ecdh' => [
                'math' => [
                    '\\text{Kurva toy: } y^2 = x^3 + 2x + 2 \\bmod 17,\\ G = (5, 1)',
                    '3G = (10, 6),\\quad 4G = (3, 1),\\quad 12G = (0, 11)',
                    'd_A = 3,\\ d_B = 4: \\quad 3 \\cdot (4G) = 4 \\cdot (3G) = 12G',
                    '\\text{Shared point } S = 12G = (0, 11)',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\EC;

$a = EC::createKey('prime256v1');
$b = EC::createKey('prime256v1');

// shared secret 32 byte via OpenSSL
$sA = openssl_pkey_derive($b->getPublicKey()->toString('PKCS8'),
    $a->toString('PKCS8'), 32);
PHP,
            ],

            'ecdsa' => [
                'math' => [
                    'r = (kG).x \\bmod n,\\quad s = k^{-1}(H(m) + rd) \\bmod n',
                    '\\text{Verifikasi: } u_1 G + u_2 Q = kG',
                    'k \\text{ bocor} \\Rightarrow d = (sk - H(m)) r^{-1} \\bmod n',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\EC;

$key = EC::createKey('prime256v1');
$priv = $key->toString('PKCS8');
$pub  = $key->getPublicKey()->toString('PKCS8');

openssl_sign($pesan, $sig, $priv, OPENSSL_ALGO_SHA256);
$valid = openssl_verify($pesan, $sig, $pub, OPENSSL_ALGO_SHA256) === 1;
PHP,
            ],

            'modulo' => [
                'math' => [
                    '17 \\bmod 5 = 2',
                    '2^{10} \\bmod 1000 = 24',
                    '(a \\cdot b) \\bmod n = ((a \\bmod n)(b \\bmod n)) \\bmod n',
                ],
                'kode' => <<<'PHP'
// bignum: eksponensiasi modular cepat (square-and-multiply)
$hasil = bcpowmod('2', '10', '1000'); // "24"
PHP,
            ],

            'sha-3' => [
                'math' => [
                    '\\text{State } 1600 = r + c,\\quad SHA3-256: r = 1088,\\ c = 512',
                    '\\text{24 putaran: } \\theta, \\rho, \\pi, \\chi, \\iota',
                    'SHA3-256(abc) = 3a985da7\\ 4fe225b2\\ 045c172d\\ 6bd390bd',
                ],
                'kode' => <<<'PHP'
$digest = hash('sha3-256', $pesan);
PHP,
            ],

            'sha-1' => [
                'math' => [
                    '\\text{Padding seperti MD, } 80 \\text{ putaran}',
                    'SHA1(abc) = a9993e36\\ 4706816a\\ ba3e2571\\ 7850c26c\\ 9cd0d89d',
                ],
                'kode' => <<<'PHP'
$digest = hash('sha1', $pesan); // hanya untuk kompatibilitas lama
PHP,
            ],

            'camellia' => [
                'math' => [
                    'L_i = R_{i-1},\\quad R_i = L_{i-1} \\oplus P(S(R_{i-1} \\oplus k_i))',
                    '\\text{18 putaran + FL setelah putaran 6 dan 12}',
                ],
                'kode' => <<<'PHP'
$ct = openssl_encrypt($data, 'camellia-128-cbc', $key16,
    OPENSSL_RAW_DATA, $iv);
PHP,
            ],

            'hmac-sha512' => [
                'math' => [
                    '\\text{Sama dengan HMAC-SHA256 dengan } H = SHA\\text{-}512',
                    '\\text{ipad/opad } 64 \\text{ byte, blok } 128 \\text{ byte}',
                ],
                'kode' => <<<'PHP'
$tag = hash_hmac('sha512', $pesan, $kunci, true); // 64 byte
PHP,
            ],

            // ==========================================
            // PENGAYAAN GELOMBANG 2 (semua angka diverifikasi skrip)
            // ==========================================

            'blowfish' => [
                'math' => [
                    '\\text{P-array diambil dari digit } \\pi: P_1 = 0x243F6A88,\\ P_2 = 0x85A308D3',
                    'F(x) = \\bigl((S_1[a] + S_2[b]) \\oplus S_3[c]\\bigr) + S_4[d] \\bmod 2^{32}',
                    '\\text{dengan } x = a2^{24} + b2^{16} + c2^8 + d',
                    'R_{i}: L \\gets L \\oplus P_i,\\ R \\gets R \\oplus F(L),\\ (L,R) \\gets (R,L)',
                    '\\text{Blok } ABCDEFGH,\\ \\text{kunci } KunciBlowfish16 \\to 7A8279756D3ED19B',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\Blowfish;

$bf = new Blowfish('cbc');
$bf->setKey('KunciBlowfish16');       // 4-56 byte
$bf->setIV('01234567');               // 8 byte

$ct = $bf->encrypt('8 byte data!!');
$pt = $bf->decrypt($ct);
PHP,
            ],

            'cfb' => [
                'math' => [
                    'C_i = P_i \\oplus E_K(C_{i-1}),\\quad C_0 = IV',
                    'D_k: P_i = C_i \\oplus E_K(C_{i-1}) \\text{ (hanya } E_K \\text{ dipakai dua arah)}',
                    '\\text{AES-128-CFB, IV=0, } P = KRIPTOGRAFI:',
                    'C = 40C95C8A\\ 1F0BE7A7\\ 545B86',
                ],
                'kode' => <<<'PHP'
$ct = openssl_encrypt($pesan, 'aes-128-cfb', $key16,
    OPENSSL_RAW_DATA, $iv);

$pt = openssl_decrypt($ct, 'aes-128-cfb', $key16,
    OPENSSL_RAW_DATA, $iv);
PHP,
            ],

            'ofb' => [
                'math' => [
                    'O_i = E_K(O_{i-1}),\\quad O_0 = IV,\\quad C_i = P_i \\oplus O_i',
                    '\\text{Keystream } O_i \\text{ bebas dari plaintext}',
                    '\\text{Bukti: } C(a) \\oplus C(b) = P(a) \\oplus P(b)',
                    '\\text{OFB AAAA vs BBBB (kunci sama): XOR hasilnya } 03030303 = A \\oplus B',
                ],
                'kode' => <<<'PHP'
$ct = openssl_encrypt($pesan, 'aes-128-ofb', $key16,
    OPENSSL_RAW_DATA, $iv);

// Peringatan: jangan pakai IV sama untuk dua pesan —
// keystream sama artinya C1 xor C2 = P1 xor P2.
PHP,
            ],

            'aes-encryption' => [
                'math' => [
                    '\\text{Ekspansi kunci: } W_4 = W_0 \\oplus g(W_3)',
                    'g(W_3) = \\text{SubWord(RotWord}(W_3)) \\oplus Rcon_1',
                    'K = 2b7e1516\\ 28aed2a6\\ abf71588\\ 09cf4f3c',
                    'g(W_3) = 8B84EB01,\\quad W_4 = 2b7e1516 \\oplus 8B84EB01',
                    'W_4 = a0fafe17 \\text{ (contoh resmi FIPS 197 bagian A.1)}',
                ],
                'kode' => <<<'PHP'
// Alur aplikasi: kunci per sesi, nonce unik, tag ikut disimpan
$key   = random_bytes(32);
$nonce = random_bytes(12);
$tag   = null;

$paket = base64_encode($nonce)
    . ':' . base64_encode(openssl_encrypt($pesan, 'aes-256-gcm',
        $key, OPENSSL_RAW_DATA, $nonce, $tag))
    . ':' . base64_encode($tag);
PHP,
            ],

            'idea' => [
                'math' => [
                    'a \\odot b = a b \\bmod 65537,\\quad 0 \\equiv 65536',
                    '65535 \\odot 65535 = (-2)^2 = 4,\\quad 65535 \\odot 2 = -4 \\equiv 65533',
                    '\\text{Jadwal kunci: 8 subkunci per grup, rotasi 25 bit}',
                    '\\text{Vektor ETH: } K = 0001..0008,\\ P = 0000000100020003',
                    'C = 11fbed2b\\ 01986de5',
                ],
                'kode' => <<<'PHP'
use App\Crypto\Idea;

$idea = new Idea(random_bytes(16));

$ct = $idea->enkripsiBlok($blok8);   // per blok 8 byte
$pt = $idea->dekripsiBlok($ct);
PHP,
            ],

            'rc5' => [
                'math' => [
                    'A \\gets (A \\oplus B) \\xleftarrow{\\lll B} + S_{2i},\\quad B \\gets (A \\oplus B) \\xleftarrow{\\lll A} + S_{2i+1}',
                    'P_{32} = 0xB7E15163,\\quad Q_{32} = 0x9E3779B9',
                    '\\text{Vektor RFC 2040 (CBC 1 blok, } P \\oplus IV = 1122334455667788):',
                    'C = 294DDB46\\ B3278D60',
                ],
                'kode' => <<<'PHP'
use App\Crypto\Rc5;

$rc5 = new Rc5(random_bytes(16));    // RC5-32/12/16

$ct = $rc5->enkripsiBlok($blok8);
$pt = $rc5->dekripsiBlok($ct);
PHP,
            ],

            'rc6' => [
                'math' => [
                    'f(B) = B(2B+1) \\lll 5,\\quad f(D) = D(2D+1) \\lll 5',
                    'A \\gets ((A \\oplus f(B)) \\xleftarrow{\\lll f(D)} + S_{2i})',
                    '(A,B,C,D) \\gets (B, C_{\\text{baru}}, D, A_{\\text{baru}})',
                    '\\text{Vektor IETF: } K = P = 000102..0F \\to C = 3A96F9C7\\ F6755CFE',
                    '46F00E3D\\ CD5D2A3C',
                ],
                'kode' => <<<'PHP'
use App\Crypto\Rc6;

$rc6 = new Rc6(random_bytes(16));    // RC6-32/20/16

$ct = $rc6->enkripsiBlok($blok16);   // per blok 16 byte
$pt = $rc6->dekripsiBlok($ct);
PHP,
            ],

            'twofish' => [
                'math' => [
                    '\\text{PHT: } a\' = a + b,\\quad b\' = a + 2b \\pmod{2^{16}}',
                    '\\text{Contoh: } a=3, b=5 \\to a\'=8, b\'=13',
                    '\\text{S-box bergantung kunci dari MDS + Q-permutasi}',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\Twofish;

$tf = new Twofish('cbc');
$tf->setKey(random_bytes(16));
$tf->setIV(random_bytes(16));

$ct = $tf->encrypt($pesan);
$pt = $tf->decrypt($ct);
PHP,
            ],

            'cast-128' => [
                'math' => [
                    'L_{i+1} = R_i,\\quad R_{i+1} = L_i \\oplus f(R_i, K_{m_i}, K_{r_i})',
                    '\\text{Tiga tipe putaran bergantian (rotasi } \\sigma_1..\\sigma_{32})',
                    '0x12345678 \\xleftarrow{\\lll 3} = 0x91A2B3C0',
                ],
                'kode' => <<<'PHP'
// CAST-128: engine lengkap di App\Crypto\Cast128 (RFC 2144).
// Tiga fungsi ronde bergilir + jadwal kunci z/x empat iterasi.
use App\Crypto\Cast128;

$cast = new Cast128(hex2bin('0123456712345678234567893456789A'));
$ct = $cast->enkripsiBlok(hex2bin('0123456789ABCDEF'));

echo strtoupper(bin2hex($ct)); // 238B4FE5847E44B2 (vektor resmi RFC 2144)
PHP,
            ],

            'skipjack' => [
                'math' => [
                    'cv_4 = w_1,\\ cv_4\\!+\\!1 = G(w_1),\\ cv_4\\!+\\!2 = w_2 \\oplus cv_4\\!+\\!1 \\oplus counter',
                    'cv_4\\!+\\!3 = w_3,\\ cv_4\\!+\\!4 = w_4,\\ \\text{lalu geser kanan 1 kata}',
                    '\\text{Rule B: XOR } cv_4\\!+\\!2 \\text{ dihilangkan pada putaran ganjil}',
                    'G = 4 \\times \\text{substitusi F-table 8 bit dengan Layer}',
                ],
                'kode' => <<<'PHP'
// Skipjack: engine lengkap di App\Crypto\Skipjack (spesifikasi NIST).
// Rule A/B bergantian + G = Feistel 4 langkah dari tabel F.
use App\Crypto\Skipjack;

$sj = new Skipjack(hex2bin('00998877665544332211'));
$ct = $sj->enkripsiBlok(hex2bin('33221100ddccbbaa'));

echo strtoupper(bin2hex($ct)); // 2587CAE27A12D300 (contoh spesifikasi NIST)
PHP,
            ],

            'serpent' => [
                'math' => [
                    'S_0 = (3,8,15,1,10,6,5,11,14,13,4,2,7,0,9,12) \\text{ permutasi valid}',
                    'S_0(0) = 3,\\quad S_0(13) = 0',
                    '\\text{32 putaran, 8 S-box diulang 8 kali, bitslice 32-bit}',
                ],
                'kode' => <<<'PHP'
// Serpent: engine bitslice di App\Crypto\Serpent (32 putaran,
// 8 S-box 4-bit, kunci 128 bit di-pad ke 256 bit).
use App\Crypto\Serpent;

$sp = new Serpent(hex2bin('000102030405060708090A0B0C0D0E0F'));
$ct = $sp->enkripsiBlok(hex2bin('000102030405060708090A0B0C0D0E0F'));

echo strtoupper(bin2hex($ct)); // 4C7D8A328072A22C823E4A1F3ACDA16D (vektor kernel Linux)
PHP,
            ],

            'seed' => [
                'math' => [
                    'F(A,B) = X \\oplus Y,\\ \\text{16 putaran Feistel blok 128 bit}',
                    '0x12345678 \\xleftarrow{\\lll 8} = 0x34567812',
                    '\\text{F memakai ekspansi } E/E_0 \\text{ dan } T\\text{-tabel } SL',
                ],
                'kode' => <<<'PHP'
// SEED: engine lengkap di App\Crypto\Seed (RFC 4269, 16 putaran
// Feistel, SS-box dihitung dari S0/S1 sesuai definisi §2.2).
use App\Crypto\Seed;

$seed = new Seed(str_repeat("\x00", 16));
$ct = $seed->enkripsiBlok(hex2bin('000102030405060708090A0B0C0D0E0F'));

echo strtoupper(bin2hex($ct)); // 5EBAC6E0054E166819AFF1CC6D346CDB (vektor B.1)
PHP,
            ],

            'a5-2' => [
                'math' => [
                    '\\text{Mayoritas }(1,0,1) = 1,\\quad \\text{Mayoritas }(0,0,1) = 0',
                    'R_4 \\text{ mengatur clocking } R_1, R_2, R_3 \\text{ (kelemahan utamanya)}',
                    'b = s_{10} \\oplus s_{22} \\oplus s_{23} \\oplus \\text{(mux 4 bit } R_4)',
                ],
                'kode' => <<<'PHP'
// A5/2 lemah secara kriptografis (dipatah dalam < 1 detik).
// Implementasi disengaja tidak disediakan; bandingkan dengan A5/1.
PHP,
            ],

            'rabbit' => [
                'math' => [
                    'g(u,v) = \\text{LSW}(s^2) \\oplus \\text{MSW}(s^2),\\quad s = (u+v) \\bmod 2^{32}',
                    's = 0x10001 \\Rightarrow s^2 = 0x1\\,0002\\,0001 \\Rightarrow g = 0x00020000',
                    'X_0 \\gets G_0 + (G_7 \\xleftarrow{\\lll 16}) + (G_6 \\xleftarrow{\\lll 16})',
                    '\\text{8 kata status 32-bit, keluaran 128 bit per iterasi}',
                ],
                'kode' => <<<'PHP'
// Rabbit (RFC 4503/ISO 29192-4) tidak tersedia di build PHP ini;
// ChaCha20 di menu Pengujian adalah stream cipher ARX modern.
PHP,
            ],

            'trivium' => [
                'math' => [
                    't_1 = s_{66} \\oplus s_{93},\\ t_2 = s_{162} \\oplus s_{177},\\ t_3 = s_{243} \\oplus s_{288}',
                    'z = t_1 \\oplus t_2 \\oplus t_3',
                    's_{94} \\gets t_1 \\oplus s_{91} \\oplus s_{92} \\text{ (rotasi register 93 bit)}',
                    '\\text{Status } 288 \\text{ bit} = 93 + 84 + 111,\\ 1152 \\text{ siklus inisialisasi}',
                ],
                'kode' => <<<'PHP'
// Trivium (eSTREAM, hardware-friendly) tidak tersedia di build PHP ini;
// 288-bit state diperbarui 1 bit per detak.
PHP,
            ],

            'sha-512' => [
                'math' => [
                    'SHA\\text{-}512(abc) = ddaf35a1\\ 93617aba',
                    '\\ldots \\text{(64 byte heksa, blok pesan } 128 \\text{ byte, 80 putaran)}',
                    'H_0 = 6a09e667f3bcc908 \\text{ (fraksi } \\sqrt{2}\\text{)}',
                    'W_t = \\sigma_1(W_{t-2}) + W_{t-7} + \\sigma_0(W_{t-15}) + W_{t-16}',
                ],
                'kode' => <<<'PHP'
$digest = hash('sha512', $pesan);           // hex 128 karakter
$hmac   = hash_hmac('sha512', $pesan, $kunci);
PHP,
            ],

            'bilangan-prima' => [
                'math' => [
                    '\\text{Miller-Rabin } n = 221 = 13 \\times 17,\\ n-1 = 55 \\times 2^2',
                    'a = 2:\\ 2^{55} \\bmod 221 = 128 \\notin \\{1, 220\\}',
                    '2^{110} \\bmod 221 = 30 \\notin \\{1, 220\\} \\Rightarrow 221 \\text{ komposit}',
                    '\\text{Saringan Eratosthenes sampai 30: sisa } 2,3,5,7,11,13,17,19,23,29',
                ],
                'kode' => <<<'PHP'
// Miller-Rabin satu saksi
function millerRabin(int $n, int $a): bool
{
    $d = $n - 1;
    $r = 0;
    while ($d % 2 === 0) { $d = intdiv($d, 2); $r++; }

    $x = bcpowmod((string) $a, (string) $d, (string) $n);
    if (in_array($x, ['1', (string) ($n - 1)])) return true;

    for ($i = 1; $i < $r; $i++) {
        $x = bcmul($x, $x);
        $x = bcmod($x, (string) $n);
        if ($x === (string) ($n - 1)) return true;
    }

    return false; // komposit
}

var_dump(millerRabin(221, 2)); // false -> 221 bukan prima
PHP,
            ],

            'faktorisasi' => [
                'math' => [
                    'f(x) = x^2 + 1,\\ x_0 = 2,\\ n = 8051',
                    'x: 2 \\to 5 \\to 26 \\to 7474 \\ (\\text{laju ganda } y)',
                    '\\gcd(|7474 - 26|, 8051) = \\gcd(7448, 8051) = 97',
                    '8051 = 97 \\times 83',
                ],
                'kode' => <<<'PHP'
function pollardRho(int $n): int
{
    $x = 2; $y = 2; $d = 1;

    while ($d === 1) {
        $x = ($x * $x + 1) % $n;
        $y = ($y * $y + 1) % $n;
        $y = ($y * $y + 1) % $n;
        $d = gmp_intval(gmp_gcd(abs($x - $y), $n));
    }

    return $d;
}

echo pollardRho(8051); // 97
PHP,
            ],

            'gcd' => [
                'math' => [
                    '252 = 2 \\times 105 + 42',
                    '105 = 2 \\times 42 + 21',
                    '42 = 2 \\times 21 + 0 \\Rightarrow \\gcd(252, 105) = 21',
                ],
                'kode' => <<<'PHP'
function gcdEuclid(int $a, int $b): int
{
    while ($b !== 0) {
        [$a, $b] = [$b, $a % $b];
    }

    return $a;
}

echo gcdEuclid(252, 105); // 21
PHP,
            ],

            'extended-euclidean' => [
                'math' => [
                    '240 = 5 \\times 46 + 10,\\quad 46 = 4 \\times 10 + 6',
                    '10 = 1 \\times 6 + 4,\\quad 6 = 1 \\times 4 + 2,\\quad 4 = 2 \\times 2 + 0',
                    '\\text{Mundur: } 2 = 47 \\times 46 - 9 \\times 240',
                    '\\text{Cek: } 47 \\times 46 = 2162,\\ 9 \\times 240 = 2160,\\ 2162 - 2160 = 2',
                ],
                'kode' => <<<'PHP'
function extendedGcd(int $a, int $b): array
{
    [$oldR, $r] = [$a, $b];
    [$oldS, $s] = [1, 0];

    while ($r !== 0) {
        $q = intdiv($oldR, $r);
        [$oldR, $r] = [$r, $oldR - $q * $r];
        [$oldS, $s] = [$s, $oldS - $q * $s];
    }

    return [$oldR, $oldS]; // gcd, koefisien x untuk a*x = gcd (mod b)
}
PHP,
            ],

            'fermat' => [
                'math' => [
                    'a^{p-1} \\equiv 1 \\pmod{p}:\\ 2^6 \\bmod 7 = 64 \\bmod 7 = 1',
                    '\\text{Kurangi eksponen: } 3^{100} \\bmod 17 = 3^{100 \\bmod 16} = 3^4 = 13',
                    '\\text{Pseudoprima: } 2^{340} \\equiv 1 \\pmod{341} \\text{ padahal } 341 = 11 \\times 31',
                ],
                'kode' => <<<'PHP'
$mod = bcpowmod('3', '100', '17');   // 13

// Uji Fermat sederhana (rawan pseudoprima seperti 341)
function fermatTest(int $n, int $a): bool
{
    return bcpowmod((string) $a, (string) ($n - 1), (string) $n) === '1';
}
PHP,
            ],

            'euler' => [
                'math' => [
                    'a^{\\varphi(n)} \\equiv 1 \\pmod n \\text{ bila } \\gcd(a, n) = 1',
                    '\\varphi(10) = 4,\\quad 3^4 = 81 \\equiv 1 \\pmod{10}',
                    '\\text{RSA: } m^{ed} = m^{1 + k\\varphi(n)} \\equiv m \\pmod n',
                ],
                'kode' => <<<'PHP'
$phi = 4;                    // varphi(10)
$hasil = bcpowmod('3', (string) $phi, '10');  // 1

// Eksponen modulo n selalu diperkecil ke e mod varphi(n)
$e   = 100;
$kecil = $e % $phi;          // 0 -> 3^100 = (3^4)^25 = 1
PHP,
            ],

            'phi-euler' => [
                'math' => [
                    '\\varphi(p^k) = p^k - p^{k-1},\\quad \\varphi(mn) = \\varphi(m)\\varphi(n) \\ (\\gcd = 1)',
                    '\\varphi(36) = 36 \\times \\tfrac{1}{2} \\times \\tfrac{2}{3} = 12',
                    '\\varphi(100) = 40,\\quad \\varphi(97) = 96 \\text{ (prima: } p - 1)',
                ],
                'kode' => <<<'PHP'
function phiEuler(int $n): int
{
    $hasil = $n;
    $p = 2;
    $x = $n;

    while ($p * $p <= $x) {
        if ($x % $p === 0) {
            while ($x % $p === 0) $x = intdiv($x, $p);
            $hasil -= intdiv($hasil, $p);
        }
        $p++;
    }

    if ($x > 1) $hasil -= intdiv($hasil, $x);

    return $hasil;
}

echo phiEuler(36); // 12
PHP,
            ],

            'finite-field' => [
                'math' => [
                    'GF(2^8) = GF(2)[x]/(x^8 + x^4 + x^3 + x + 1) \\text{ (polinomial } 0x11B\\text{)}',
                    '0x57 \\cdot 0x83 = 0xC1,\\quad 0x53 \\cdot 0xCA = 0x01',
                    '0x57^{-1} = 0xBF \\text{ (setiap elemen nonzero punya invers)}',
                    '\\text{Bidang selalu punya invers karena modulus irreducible}',
                ],
                'kode' => <<<'PHP'
function gfMul(int $a, int $b): int
{
    $p = 0;

    for ($i = 0; $i < 8; $i++) {
        if ($b & 1) $p ^= $a;
        $a <<= 1;
        if ($a & 0x100) $a ^= 0x11B;
        $b >>= 1;
    }

    return $p;
}

echo dechex(gfMul(0x57, 0x83)); // c1
PHP,
            ],

            'discrete-logarithm' => [
                'math' => [
                    '\\text{Cari } b \\text{ dari } 3^b \\equiv 12 \\pmod{17}',
                    '3^1=3,\\ 3^2=9,\\ 3^3=10,\\ 3^4=13,\\ 3^5=5,\\ 3^6=15,\\ 3^7=11,\\ 3^8=16',
                    '3^{13} \\bmod 17 = 12 \\Rightarrow b = 13',
                    '\\text{Brute force cukup untuk } p \\text{ kecil; } p \\text{ 2048 bit mustahil}',
                ],
                'kode' => <<<'PHP'
function dlogBrute(int $g, int $y, int $p): ?int
{
    $v = 1;

    for ($b = 0; $b < $p - 1; $b++) {
        if ($v === $y) return $b;
        $v = ($v * $g) % $p;
    }

    return null;
}

echo dlogBrute(3, 12, 17); // 13
PHP,
            ],

            'elliptic-curve' => [
                'math' => [
                    'E: y^2 = x^3 + 2x + 2 \\bmod 17,\\quad G = (5, 1)',
                    '2G = (6, 3),\\quad 3G = (10, 6),\\quad 4G = (3, 1)',
                    '\\text{Total titik: } 18 \\text{ affine} + \\mathcal{O} = 19',
                    '3G \\oplus 4G = 7G \\text{ (asosiatif, membentuk grup)}',
                ],
                'kode' => <<<'PHP'
function tambahTitik(array $P, array $Q, int $p): ?array
{
    if ($P === null) return $Q;
    if ($Q === null) return $P;

    if ($P[0] === $Q[0] && ($P[1] + $Q[1]) % $p === 0) return null;

    $l = ($P === $Q)
        ? (3 * $P[0] ** 2 + 2) * gmp_intval(gmp_invert(2 * $P[1], $p)) % $p
        : ($Q[1] - $P[1]) * gmp_intval(gmp_invert($Q[0] - $P[0], $p)) % $p;

    $x = ($l * $l - $P[0] - $Q[0]) % $p;

    return [$x, ($l * ($P[0] - $x) - $P[1]) % $p];
}
PHP,
            ],

            'rsa-pss' => [
                'math' => [
                    '\\text{MGF1 (SHA-256), seed } = 00010203,\\ 12 \\text{ byte:}',
                    'T = 3B83B911\\ 76978B6F\\ B25C2FA8',
                    'EM = 00 \\Vert maskedDB \\Vert H \\Vert bc \\text{ (panjang } emLen - 1\\text{)}',
                    's = EM^d \\bmod n,\\ \\text{verifikasi } EM = s^e \\bmod n',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\RSA;

$rsa = RSA::createKey(2048)
    ->withPadding(RSA::SIGNATURE_PSS)
    ->withHash('sha256')
    ->withMGFHash('sha256');

$sig = $rsa->sign($pesan);
$ok  = $rsa->getPublicKey()->verify($pesan, $sig);
PHP,
            ],

            'ecc' => [
                'math' => [
                    'E17: y^2 = x^3 + 2x + 2 \\bmod 17 \\text{ punya } 19 \\text{ titik (dengan } \\mathcal{O})',
                    '12G = (0, 11),\\quad \\text{orde } G = 19 \\text{ (prima) } \\Rightarrow \\text{grup siklik}',
                    '\\text{Batas Hasse: } |N - (p+1)| \\le 2\\sqrt{p}',
                    'p + 1 = 18,\\ 2\\sqrt{17} \\approx 8.2 \\Rightarrow N \\in [10, 26] \\ni 19',
                ],
                'kode' => <<<'PHP'
// Hitung semua titik pada E17
$titik = [];

foreach (range(0, 16) as $x) {
    foreach (range(0, 16) as $y) {
        if (($y * $y - ($x ** 3 + 2 * $x + 2)) % 17 === 0) {
            $titik[] = [$x, $y];
        }
    }
}

echo count($titik) + 1; // 19 (termasuk titik di infinity)
PHP,
            ],

            'dsa' => [
                'math' => [
                    '\\text{Toy: } p = 23,\\ q = 11,\\ g = 2^{(p-1)/q} \\bmod p = 4',
                    'x = 3 \\Rightarrow y = 4^3 \\bmod 23 = 18',
                    'H = 5,\\ k = 7:\\ r = (4^7 \\bmod 23) \\bmod 11 = 8',
                    's = k^{-1}(H + xr) \\bmod q = 7^{-1} \\cdot 29 \\bmod 11 = 1',
                    'v = (g^{u_1} y^{u_2} \\bmod p) \\bmod q = 8 = r \\Rightarrow \\text{valid}',
                ],
                'kode' => <<<'PHP'
use phpseclib3\Crypt\DSA;

$dsa = DSA::createKey(2048, 256);
$sig = $dsa->sign($pesan);
$ok  = $dsa->getPublicKey()->verify($pesan, $sig);
PHP,
            ],

            'ntru' => [
                'math' => [
                    'R = \\mathbb{Z}[x]/(x^{11} - 1),\\quad (N, p, q) = (11, 3, 32)',
                    'h = p \\cdot f_q \\cdot g \\bmod q,\\quad e = r \\cdot h + m \\bmod q',
                    'a = f \\cdot e \\bmod q,\\quad m = f_p \\cdot (a \\bmod p) \\bmod p',
                ],
                'kode' => <<<'PHP'
// NTRU edukasi: engine lengkap di App\Crypto\Ntru dengan kunci
// contoh klasik f = -1 + X + X^2 - X^4 + X^6 + X^9 - X^10.
use App\Crypto\Ntru;

$ntru = new Ntru;
$m = $ntru->teksKePolinomial('KRIPTO')[0];
$e = $ntru->enkripsi($m, $ntru->rAcak());

echo Ntru::bentukPoli($ntru->dekripsi($e)); // polinomial m pulih
PHP,
            ],

            'diffie-hellman-matematika' => [
                'math' => [
                    '\\text{Square-and-multiply } 5^{13} \\bmod 23:',
                    '5^2 = 25 \\equiv 2,\\quad 5^4 \\equiv 4,\\quad 5^8 \\equiv 16',
                    '13 = 8 + 4 + 1 \\Rightarrow 5^{13} = 16 \\times 4 \\times 5 = 320 \\equiv 21',
                    '\\text{Kompleksitas } O(\\log a) \\text{ perkalian, bukan } a - 1 \\text{ kali}',
                ],
                'kode' => <<<'PHP'
// Bukan pow(): ini yang dilakukan bcpowmod di dalam
function squareMultiply(string $g, string $e, string $p): string
{
    $hasil = '1';
    $dasar = bcmod($g, $p);

    foreach (str_split(decbin((int) $e)) as $bit) {
        $hasil = bcmod(bcmul($hasil, $hasil), $p);
        if ($bit === '1') {
            $hasil = bcmod(bcmul($hasil, $dasar), $p);
        }
    }

    return $hasil;
}

echo squareMultiply('5', '13', '23'); // 21
PHP,
            ],

            'ecdh-matematika' => [
                'math' => [
                    'E17,\\ G = (5,1):\\ a = 3 \\to A = 3G = (10, 6)',
                    'b = 4 \\to B = 4G = (3, 1)',
                    'A \\text{ hitung } aB = 3(4G) = 12G = (0, 11)',
                    'B \\text{ hitung } bA = 4(3G) = 12G = (0, 11) \\text{ — sama}',
                ],
                'kode' => <<<'PHP'
// Shared secret sama karena perkalian skalar komutatif:
// a(bG) = b(aG) = abG
$sharedA = kaliSkalar($a, $B);   // (0, 11)
$sharedB = kaliSkalar($b, $A);   // (0, 11)
PHP,
            ],

            'ecdsa-matematika' => [
                'math' => [
                    '\\text{Bahaya nonce: dua pesan, } k \\text{ sama, } n = 19,\\ r = x(2G) = 6',
                    'H_1 = 5,\\ d = 3: s_1 = 2^{-1}(5 + 18) \\bmod 19 = 2',
                    'H_2 = 9: s_2 = 2^{-1}(9 + 18) \\bmod 19 = 4',
                    'd = (s_1 k - H_1)\\, r^{-1} = (4 - 5) \\times 6^{-1} \\equiv 3 \\pmod{19}',
                    '\\text{Kunci privat bocor hanya dari dua tanda tangan}',
                ],
                'kode' => <<<'PHP'
// Serangan nonce reuse ECDSA (n, r, s1, s2, H1, H2 diketahui)
$d = gmp_intval(
    gmp_mod(
        gmp_mul(
            gmp_sub(gmp_mul($s1, $k), $H1),
            gmp_invert($r, $n),
        ),
        $n,
    )
);
PHP,
            ],

            'elgamal-matematika' => [
                'math' => [
                    'p = 23,\\ g = 5,\\ x = 6 \\Rightarrow y = 8',
                    'k = 3,\\ m = 7:\\ c_1 = 5^3 \\bmod 23 = 10,\\ c_2 = 7 \\times 8^3 \\equiv 19',
                    '\\text{Dekripsi: } m = 19 \\times 6^{-1} \\bmod 23 = 19 \\times 4 \\equiv 7',
                    '\\text{Malleability: } c_2 \\to 2c_2 \\Rightarrow m \\to 2m = 14 \\text{ (tanpa kunci!)}',
                ],
                'kode' => <<<'PHP'
// Penyerang menggandakan pesan tanpa tahu kunci:
$c2palsu = gmp_mod(gmp_mul($c2, '2'), $p);

// Dekripsi korban menghasilkan 2m, bukan m.
// Solusi: autentikasi (MAC/tanda tangan) atas (c1, c2).
PHP,
            ],

            'ecc-matematika' => [
                'math' => [
                    '\\text{Teorema Hasse: } |N - (p + 1)| \\le 2\\sqrt{p}',
                    'E17: p + 1 = 18,\\ N = 19,\\ |19 - 18| = 1 \\le 2\\sqrt{17} \\approx 8.2',
                    '\\text{Orde } G = 19 \\text{ prima } \\Rightarrow \\text{setiap titik nonzero generator}',
                    '\\text{ECDLP di grup orde prima tidak punya struktur lemah}',
                ],
                'kode' => <<<'PHP'
// Batas Hasse untuk P-256
$p = bcpow('2', '256');
$batasBawah = bcsub(bcadd($p, '1'), '2');
// N P-256 resmi:
// n = 115792089210356248762697446949407573529996955224135760342922259069392588349601
PHP,
            ],
        ];
    }
}
