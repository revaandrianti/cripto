<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * NTRU versi edukasi dengan parameter klasik (N, p, q) = (11, 3, 32):
 * cincin polinomial R = Z[x]/(X^N - 1), kunci privat f (ternary),
 * kunci publik h = p * f_q * g mod q, enkripsi e = r * h + m mod q.
 *
 * Kunci f, g memakai contoh terhitung klasik (artikel NTRUEncrypt pada
 * Wikipedia) sehingga seluruh nilai antara (f_p, f_q, h, e) dapat
 * diverifikasi terhadap contoh yang dirujuk materi:
 *   f = -1 + X + X^2 - X^4 + X^6 + X^9 - X^10
 *   g = -1 + X^2 + X^3 + X^5 - X^8 - X^10
 *   h = 8 - 7X - 10X^2 - 12X^3 + 12X^4 - 8X^5 + 15X^6 - 13X^7
 *       + 12X^8 - 13X^9 + 16X^10   (mod 32)
 */
final class Ntru
{
    private const N = 11;

    private const P = 3;

    private const Q = 32;

    /** f = -1 + X + X^2 - X^4 + X^6 + X^9 - X^10 */
    private const F_KOEF = [-1, 1, 1, 0, -1, 0, 1, 0, 0, 1, -1];

    /** g = -1 + X^2 + X^3 + X^5 - X^8 - X^10 */
    private const G_KOEF = [-1, 0, 1, 1, 0, 1, 0, 0, -1, 0, -1];

    /** @var list<int> f_p (invers f mod 3) */
    private array $fp;

    /** @var list<int> f_q (invers f mod 32) */
    private array $fq;

    /** @var list<int> h mod 32 (koefisien dipusatkan ke [-16, 15]) */
    private array $h;

    public function __construct()
    {
        $this->fp = $this->inversGf(self::F_KOEF, self::P);
        $this->fq = $this->inversMod2Pangkat(self::F_KOEF, self::Q);
        $this->h = $this->kurangi($this->skalakan(
            $this->kalikan(self::G_KOEF, $this->fq),
            self::P
        ), self::Q);
    }

    /** @return list<int> kunci publik h (koefisien dipusatkan) */
    public function kunciPublik(): array
    {
        return $this->h;
    }

    /**
     * Enkripsi satu polinomial pesan: e = r * h + m mod q.
     *
     * @param  list<int>  $m  koefisien {-1, 0, 1}
     * @param  list<int>  $r  polinomial blinding
     * @return list<int> koefisien [0, q)
     */
    public function enkripsi(array $m, array $r): array
    {
        return $this->kurangi(
            $this->tambahkan($this->kalikan($r, $this->h), $m),
            self::Q
        );
    }

    /**
     * Dekripsi: a = f * e mod q dipusatkan ke [-q/2, q/2],
     * b = a mod p, m = f_p * b mod p.
     *
     * @param  list<int>  $e  koefisien [0, q)
     * @return list<int> koefisien {-1, 0, 1}
     */
    public function dekripsi(array $e): array
    {
        $a = $this->pusatkan($this->kurangi($this->kalikan($this->f(), $e), self::Q), self::Q);
        $b = $this->kurangi($a, self::P);

        return $this->pusatkan($this->kurangi($this->kalikan($this->fp, $b), self::P), self::P);
    }

    /** @return list<int> */
    private function f(): array
    {
        return self::F_KOEF;
    }

    /**
     * Perkalian siklik dalam Z[X]/(X^N - 1) (konvolusi melingkar),
     * koefisien disimpan sebagai bilangan asli sebelum dikurangi.
     *
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private function kalikan(array $a, array $b): array
    {
        $c = array_fill(0, self::N, 0);

        for ($i = 0; $i < self::N; $i++) {
            if ($a[$i] === 0) {
                continue;
            }

            for ($j = 0; $j < self::N; $j++) {
                $c[($i + $j) % self::N] += $a[$i] * $b[$j];
            }
        }

        return $c;
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private function tambahkan(array $a, array $b): array
    {
        $c = [];

        for ($i = 0; $i < self::N; $i++) {
            $c[] = $a[$i] + $b[$i];
        }

        return $c;
    }

    /**
     * @param  list<int>  $a
     * @return list<int>
     */
    private function skalakan(array $a, int $m): array
    {
        return array_map(fn (int $x): int => $x * $m, $a);
    }

    /**
     * @param  list<int>  $a
     * @return list<int>
     */
    private function kurangi(array $a, int $m): array
    {
        return array_map(fn (int $x): int => (($x % $m) + $m) % $m, $a);
    }

    /**
     * Pusatkan koefisien [0, m) menjadi rentang [-m/2, m/2).
     *
     * @param  list<int>  $a
     * @return list<int>
     */
    private function pusatkan(array $a, int $m): array
    {
        return array_map(function (int $x) use ($m): int {
            $x = (($x % $m) + $m) % $m;

            return $x > $m / 2 ? $x - $m : $x;
        }, $a);
    }

    /**
     * Invers polinomial di Z_p[X]/(X^N - 1) untuk p prima (di sini 3):
     * EEA polinomial atas GF(p), ambil koefisien bezout terhadap X^N - 1.
     *
     * @param  list<int>  $f
     * @return list<int>
     */
    private function inversGf(array $f, int $p): array
    {
        // X^N - 1 sebagai polinomial darjah N.
        $modulus = array_fill(0, self::N + 1, 0);
        $modulus[0] = $p - 1; // -1 mod p
        $modulus[self::N] = 1;

        [$s, $g] = $this->eeaGf($f, $modulus, $p);

        // s * f + g * (X^N - 1) = 1 -> s adalah invers modulo X^N - 1.
        if ($this->derajat($g) !== 0 || $g[0] !== 1) {
            throw new \RuntimeException('Polinomial f tidak invertible mod p.');
        }

        return $this->kurangi(array_slice($s, 0, self::N) + array_fill(0, self::N, 0), $p);
    }

    /**
     * EEA polinomial atas GF(p): mengembalikan (s, t) dengan
     * s * a + t * b = gcd(a, b), b disyaratkan berderajat lebih tinggi.
     *
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return array{list<int>, list<int>}
     */
    private function eeaGf(array $a, array $b, int $p): array
    {
        $r0 = $this->trimPoly($b, $p);
        $r1 = $this->trimPoly($a, $p);
        $s0 = [0];
        $s1 = [1];

        while ($this->derajat($r1) >= 0) {
            [$q, $rem] = $this->bagiPolyGf($r0, $r1, $p);
            $r0 = $r1;
            $r1 = $rem;

            $baru = $this->kurangPolyGf($s0, $this->kalikanPolyGf($q, $s1, $p), $p);
            $s0 = $s1;
            $s1 = $baru;
        }

        // Normalisasi agar gcd = 1.
        $c = $r0 === [] || $r0[0] === 0 ? 1 : $this->inversElemen($r0[0], $p);
        $s0 = $this->kalikanSkalarGf($s0, $c, $p);

        return [$this->trimPoly($s0, $p), $r0];
    }

    private function inversElemen(int $x, int $p): int
    {
        $x = (($x % $p) + $p) % $p;

        for ($y = 1; $y < $p; $y++) {
            if (($x * $y) % $p === 1) {
                return $y;
            }
        }

        throw new \RuntimeException('Elemen tidak invertible di GF(p).');
    }

    /**
     * Invers f mod 2^k (q = 32) dengan iterasi Newton mulai dari
     * invers mod 2: g <- g * (2 - f * g) mod 2^(2^i).
     *
     * @param  list<int>  $f
     * @return list<int>
     */
    private function inversMod2Pangkat(array $f, int $q): array
    {
        $g = $this->inversGf(array_map(fn (int $x): int => abs($x) % 2, $f), 2);

        $mod = 2;

        while ($mod < $q) {
            $mod = $mod * $mod > $q ? $q : $mod * $mod;
            // g = 2g - f * g^2 mod 2^mod (iterasi Newton)
            $g2 = $this->kalikan($g, $g);
            $f_g2 = $this->kalikan($f, $g2);
            $g = $this->kurangi(array_map(
                fn (int $i): int => 2 * $g[$i] - $f_g2[$i],
                range(0, self::N - 1)
            ), $mod);
        }

        return $this->kurangi($g, $q);
    }

    // ===== operasi polinomial kecil atas GF(p) =====

    /**
     * @param  list<int>  $p
     * @return list<int>
     */
    private function trimPoly(array $p, int $m): array
    {
        $p = array_map(fn (int $x): int => (($x % $m) + $m) % $m, $p);

        while ($p !== [] && end($p) === 0) {
            array_pop($p);
        }

        return array_values($p);
    }

    /**
     * @param  list<int>  $a
     */
    private function derajat(array $a): int
    {
        return count($a) - 1;
    }

    /**
     * Pembagian polinomial atas GF(p).
     *
     * @param  list<int>  $a  dibagi
     * @param  list<int>  $b  pembagi
     * @return array{list<int>, list<int>} (quotient, remainder)
     */
    private function bagiPolyGf(array $a, array $b, int $p): array
    {
        $a = $this->trimPoly($a, $p);
        $b = $this->trimPoly($b, $p);

        if ($b === []) {
            throw new \RuntimeException('Pembagian polinomial oleh nol.');
        }

        $db = $this->derajat($b);
        $invers = $this->inversElemen($b[$db], $p);
        $q = array_fill(0, max(count($a) - $db, 1), 0);

        while ($this->derajat($a) >= $db && $a !== []) {
            $da = $this->derajat($a);
            $faktor = ($a[$da] * $invers) % $p;
            $q[$da - $db] = $faktor;

            for ($i = 0; $i <= $db; $i++) {
                $a[$da - $db + $i] = (($a[$da - $db + $i] - $faktor * $b[$i]) % $p + $p) % $p;
            }

            $a = $this->trimPoly($a, $p);
        }

        return [$this->trimPoly($q, $p), $a];
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private function kalikanPolyGf(array $a, array $b, int $p): array
    {
        if ($a === [] || $b === []) {
            return [];
        }

        $c = array_fill(0, count($a) + count($b) - 1, 0);

        foreach ($a as $i => $ai) {
            foreach ($b as $j => $bj) {
                $c[$i + $j] = ($c[$i + $j] + $ai * $bj) % $p;
            }
        }

        return $this->trimPoly($c, $p);
    }

    /**
     * @param  list<int>  $a
     * @return list<int>
     */
    private function kalikanSkalarGf(array $a, int $s, int $p): array
    {
        return array_map(fn (int $x): int => ($x * $s) % $p, $a);
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private function kurangPolyGf(array $a, array $b, int $p): array
    {
        $c = [];

        for ($i = 0; $i < max(count($a), count($b)); $i++) {
            $x = $a[$i] ?? 0;
            $y = $b[$i] ?? 0;
            $c[$i] = (($x - $y) % $p + $p) % $p;
        }

        return $this->trimPoly($c, $p);
    }

    // ===== penyandian teks <-> polinomial ternary =====

    /**
     * Teks disandikan ke polinomial ternary: setiap polinomial memuat
     * 11 koefisien {-1,0,1} ~ 17 bit, cukup untuk 2 byte per polinomial.
     *
     * @return list<list<int>>
     */
    public function teksKePolinomial(string $teks): array
    {
        if ($teks === '') {
            throw new \InvalidArgumentException('Pesan NTRU tidak boleh kosong.');
        }

        // Genapkan panjang hanya bila ganjil agar semua pasangan lengkap.
        $padded = strlen($teks) % 2 === 1 ? $teks."\x00" : $teks;
        $bytes = unpack('C*', $padded);
        $polinomial = [];

        foreach (array_chunk($bytes, 2) as $pasangan) {
            $nilai = $pasangan[0] | ($pasangan[1] << 8);
            $koef = [];

            for ($i = 0; $i < self::N; $i++) {
                $digit = $nilai % 3;
                $nilai = intdiv($nilai, 3);
                $koef[] = $digit === 2 ? -1 : $digit;
            }

            $polinomial[] = $koef;
        }

        return $polinomial;
    }

    /**
     * @param  list<list<int>>  $polinomial
     */
    public function polinomialKeTeks(array $polinomial): string
    {
        $hasil = '';

        foreach ($polinomial as $koef) {
            $nilai = 0;

            for ($i = self::N - 1; $i >= 0; $i--) {
                $digit = $koef[$i] === -1 ? 2 : $koef[$i];
                $nilai = $nilai * 3 + $digit;
            }

            $hasil .= pack('CC', $nilai & 0xFF, ($nilai >> 8) & 0xFF);
        }

        return rtrim($hasil, "\x00");
    }

    /**
     * Polinomial blinding acak TERNARY SPARSE: paling banyak dua koefisien
     * non-nol. Dengan f,g,m ternary, lebar koefisien a = 3*r*g + f*m
     * terburuk 2*3*6 + 7*11 = 13 < q/2 = 16 sehingga dekripsi selalu
     * benar (pada demo edukasi ini r lebih lemah dari NTRU sungguhan).
     */
    public function rAcak(): array
    {
        $r = array_fill(0, self::N, 0);
        $posisi = array_rand(range(0, self::N - 1), random_int(1, 2));
        $posisi = (array) $posisi;

        foreach ($posisi as $i) {
            $r[$i] = random_int(0, 1) === 0 ? -1 : 1;
        }

        return $r;
    }

    /**
     * Bentuk teks polinomial ala materi, mis. "-1 + X + X^2 - X^4".
     *
     * @param  list<int>  $koef
     */
    public static function bentukPoli(array $koef): string
    {
        $bagian = [];

        foreach ($koef as $pangkat => $c) {
            if ($c === 0) {
                continue;
            }

            $tanda = $c < 0 ? '-' : '+';
            $nilai = abs($c);

            if ($pangkat === 0) {
                $simbol = (string) $nilai;
            } elseif ($pangkat === 1) {
                $simbol = $nilai === 1 ? 'X' : $nilai.'X';
            } else {
                $simbol = $nilai === 1 ? 'X^'.$pangkat : $nilai.'X^'.$pangkat;
            }

            $bagian[] = $tanda.' '.$simbol;
        }

        return $bagian === [] ? '0' : ltrim(implode(' ', $bagian), '+ ');
    }
}
