<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * RC5-32/12/16: blok 64 bit (dua kata 32 bit), 12 ronde,
 * kunci 16 byte. Perluasan kunci dan ronde mengikuti RFC 2040
 * (semua perkataan little-endian, rotasi banyaknya diambil mod 32).
 *
 * Diverifikasi terhadap vektor CBC RFC 2040 (kunci 01020304...080,
 * IV 0102030405060708, plaintext 1020304050607080 ->
 * ciphertext 294ddb46b3278d60).
 */
final class Rc5
{
    private const R = 12;

    private const P32 = 0xB7E15163;

    private const Q32 = 0x9E3779B9;

    /** @var list<int> 2(R+1) = 26 subkunci */
    private array $s;

    public function __construct(string $key16)
    {
        if (strlen($key16) !== 16) {
            throw new \InvalidArgumentException('Kunci RC5 harus tepat 16 byte.');
        }

        $this->s = $this->perluasKunci($key16);
    }

    /** @return list<int> */
    private function perluasKunci(string $key): array
    {
        // Kata kunci little-endian (RFC 2040)
        $l = array_values(unpack('V4', $key));
        $t = 2 * (self::R + 1);
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

    public function enkripsiBlok(string $blok8): string
    {
        if (strlen($blok8) !== 8) {
            throw new \InvalidArgumentException('Blok RC5 harus tepat 8 byte.');
        }

        [$a, $b] = array_values(unpack('V2', $blok8));
        $a = self::add($a, $this->s[0]);
        $b = self::add($b, $this->s[1]);

        for ($i = 1; $i <= self::R; $i++) {
            $a = self::add(self::rotl($a ^ $b, $b % 32), $this->s[2 * $i]);
            $b = self::add(self::rotl($a ^ $b, $a % 32), $this->s[2 * $i + 1]);
        }

        return pack('V2', $a, $b);
    }

    public function dekripsiBlok(string $blok8): string
    {
        if (strlen($blok8) !== 8) {
            throw new \InvalidArgumentException('Blok RC5 harus tepat 8 byte.');
        }

        [$a, $b] = array_values(unpack('V2', $blok8));

        for ($i = self::R; $i >= 1; $i--) {
            $b = self::rotl(self::sub($b, $this->s[2 * $i + 1]), 32 - ($a % 32)) ^ $a;
            $a = self::rotl(self::sub($a, $this->s[2 * $i]), 32 - ($b % 32)) ^ $b;
        }

        $b = self::sub($b, $this->s[1]);
        $a = self::sub($a, $this->s[0]);

        return pack('V2', $a, $b);
    }

    private static function add(int $x, int $y): int
    {
        return ($x + $y) & 0xFFFFFFFF;
    }

    private static function sub(int $x, int $y): int
    {
        return ($x - $y) & 0xFFFFFFFF;
    }

    private static function rotl(int $x, int $n): int
    {
        $n &= 31;

        return $n === 0 ? $x & 0xFFFFFFFF : (($x << $n) | ($x >> (32 - $n))) & 0xFFFFFFFF;
    }
}
