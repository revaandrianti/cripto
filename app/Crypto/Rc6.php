<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * RC6-32/20/16: blok 128 bit (empat kata 32 bit), 20 ronde,
 * kunci 16 byte. Perluasan kunci turunan RC5 dengan 2R+4 subkunci.
 *
 * Diverifikasi terhadap vektor spesifikasi RC6 (kunci dan plaintext
 * nol -> ciphertext 8fc3a5365b1fc77cf2b0fb60b9e4d0dc).
 */
final class Rc6
{
    private const R = 20;

    private const P32 = 0xB7E15163;

    private const Q32 = 0x9E3779B9;

    /** @var list<int> 2R+4 = 44 subkunci */
    private array $s;

    public function __construct(string $key16)
    {
        if (strlen($key16) !== 16) {
            throw new \InvalidArgumentException('Kunci RC6 harus tepat 16 byte.');
        }

        $this->s = $this->perluasKunci($key16);
    }

    /** @return list<int> */
    private function perluasKunci(string $key): array
    {
        $l = array_values(unpack('V4', $key));
        $t = 2 * self::R + 4;
        $s = [self::P32];

        for ($i = 1; $i < $t; $i++) {
            $s[$i] = self::add($s[$i - 1], self::Q32);
        }

        $a = 0;
        $b = 0;
        $i = 0;
        $j = 0;
        $v = 3 * max($t, count($l));

        for ($k = 0; $k < $v; $k++) {
            $a = $s[$i] = self::rotl(self::add($s[$i], self::add($a, $b)), 3);
            $b = $l[$j] = self::rotl(self::add($l[$j], self::add($a, $b)), self::add($a, $b) % 32);
            $i = ($i + 1) % $t;
            $j = ($j + 1) % 4;
        }

        return $s;
    }

    public function enkripsiBlok(string $blok16): string
    {
        if (strlen($blok16) !== 16) {
            throw new \InvalidArgumentException('Blok RC6 harus tepat 16 byte.');
        }

        [$a, $b, $c, $d] = array_values(unpack('V4', $blok16));

        $b = self::add($b, $this->s[0]);
        $d = self::add($d, $this->s[1]);

        for ($i = 1; $i <= self::R; $i++) {
            $t = self::rotl(self::mul($b, self::add(2 * $b, 1)), 5);
            $u = self::rotl(self::mul($d, self::add(2 * $d, 1)), 5);
            $a2 = self::add(self::rotl($a ^ $t, $u % 32), $this->s[2 * $i]);
            $c2 = self::add(self::rotl($c ^ $u, $t % 32), $this->s[2 * $i + 1]);
            // Rotasi spesifikasi: (A, B, C, D) = (B, C_baru, D, A_baru)
            [$a, $b, $c, $d] = [$b, $c2, $d, $a2];
        }

        $a = self::add($a, $this->s[2 * self::R + 2]);
        $c = self::add($c, $this->s[2 * self::R + 3]);

        return pack('V4', $a, $b, $c, $d);
    }

    public function dekripsiBlok(string $blok16): string
    {
        if (strlen($blok16) !== 16) {
            throw new \InvalidArgumentException('Blok RC6 harus tepat 16 byte.');
        }

        [$a, $b, $c, $d] = array_values(unpack('V4', $blok16));

        $c = self::sub($c, $this->s[2 * self::R + 3]);
        $a = self::sub($a, $this->s[2 * self::R + 2]);

        for ($i = self::R; $i >= 1; $i--) {
            [$a, $b, $c, $d] = [$d, $a, $b, $c];

            $u = self::rotl(self::mul($d, self::add(2 * $d, 1)), 5);
            $t = self::rotl(self::mul($b, self::add(2 * $b, 1)), 5);
            $c = self::rotl(self::sub($c, $this->s[2 * $i + 1]), 32 - ($t % 32)) ^ $u;
            $a = self::rotl(self::sub($a, $this->s[2 * $i]), 32 - ($u % 32)) ^ $t;
        }

        $d = self::sub($d, $this->s[1]);
        $b = self::sub($b, $this->s[0]);

        return pack('V4', $a, $b, $c, $d);
    }

    private static function add(int $x, int $y): int
    {
        return ($x + $y) & 0xFFFFFFFF;
    }

    private static function sub(int $x, int $y): int
    {
        return ($x - $y) & 0xFFFFFFFF;
    }

    /**
     * Perkalian modulo 2^32. Perkalian langsung 32 x 32 bit meluap
     * dari bilangan bulat PHP 64 bit, sehingga dipecah per 16 bit.
     */
    private static function mul(int $x, int $y): int
    {
        $xl = $x & 0xFFFF;
        $xh = $x >> 16;
        $yl = $y & 0xFFFF;
        $yh = $y >> 16;

        $ll = $xl * $yl;
        $lh = ($xh * $yl + $xl * $yh) & 0xFFFF;

        return (($lh << 16) + $ll) & 0xFFFFFFFF;
    }

    private static function rotl(int $x, int $n): int
    {
        $n &= 31;

        return $n === 0 ? $x & 0xFFFFFFFF : (($x << $n) | ($x >> (32 - $n))) & 0xFFFFFFFF;
    }
}
