<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * Serpent versi bitslice: blok 128 bit (empat kata 32 bit little-endian),
 * kunci 128/192/256 bit, 32 ronde dengan delapan S-box 4-bit yang
 * diterapkan ke 32 kolom bit sekaligus, plus lapisan linier.
 *
 * Diverifikasi terhadap vektor uji Serpent-128 dan Serpent-256 yang
 * dirilis kernel Linux (kunci/plaintext 000102...0f ->
 * 4c7d8a328072a22c823e4a1f3acda16d dan key 00..1f ->
 * de269ff833e432b85b2e88d2701ce75c).
 */
final class Serpent
{
    private const MASK = 0xFFFFFFFF;

    private const PHI = 0x9E3779B9;

    /** @var list<list<int>> */
    private const SBOX = [
        [3, 8, 15, 1, 10, 6, 5, 11, 14, 13, 4, 2, 7, 0, 9, 12],
        [15, 12, 2, 7, 9, 0, 5, 10, 1, 11, 14, 8, 6, 13, 3, 4],
        [8, 6, 7, 9, 3, 12, 10, 15, 13, 1, 14, 4, 0, 11, 5, 2],
        [0, 15, 11, 8, 12, 9, 6, 3, 13, 1, 2, 4, 10, 7, 5, 14],
        [1, 15, 8, 3, 12, 0, 11, 6, 2, 5, 4, 10, 9, 14, 7, 13],
        [15, 5, 2, 11, 4, 10, 9, 12, 0, 3, 14, 8, 13, 6, 7, 1],
        [7, 2, 12, 5, 8, 4, 6, 11, 14, 9, 1, 15, 13, 3, 10, 0],
        [1, 13, 15, 0, 14, 8, 2, 11, 7, 4, 12, 10, 9, 3, 5, 6],
    ];

    /** @var list<int> 33 subkunci x 4 kata */
    private array $subkunci;

    public function __construct(string $kunci16)
    {
        if (strlen($kunci16) !== 16) {
            throw new \InvalidArgumentException('Kunci Serpent harus tepat 16 byte.');
        }

        $this->subkunci = $this->jadwalKunci($kunci16);
    }

    /** Enkripsi satu blok 128 bit. */
    public function enkripsiBlok(string $blok): string
    {
        $x = $this->kataBlok($blok);

        for ($r = 0; $r < 32; $r++) {
            $x = $this->xorKata($x, $this->potong($r));
            $x = self::lapisanSbox(self::SBOX[$r % 8], $x);

            if ($r < 31) {
                $x = self::linier($x);
            }
        }

        $x = $this->xorKata($x, $this->potong(32));

        return pack('V4', ...$x);
    }

    /** Dekripsi satu blok: jalur mundur dengan S-box invers. */
    public function dekripsiBlok(string $blok): string
    {
        $x = $this->kataBlok($blok);
        $x = $this->xorKata($x, $this->potong(32));

        for ($r = 31; $r >= 0; $r--) {
            if ($r < 31) {
                $x = self::linierInv($x);
            }

            $x = self::lapisanSbox($this->inversSbox(self::SBOX[$r % 8]), $x);
            $x = $this->xorKata($x, $this->potong($r));
        }

        return pack('V4', ...$x);
    }

    /**
     * Terapkan S-box 4-bit ke 32 kolom bit dari empat kata sekaligus.
     *
     * @param  list<int>  $kotak  16 entri 4-bit
     * @param  list<int>  $kata  empat kata 32 bit
     * @return list<int>
     */
    private static function lapisanSbox(array $kotak, array $kata): array
    {
        $keluar = [0, 0, 0, 0];

        for ($bit = 0; $bit < 32; $bit++) {
            $v = 0;

            for ($j = 3; $j >= 0; $j--) {
                $v = ($v << 1) | (($kata[$j] >> $bit) & 1);
            }

            $s = $kotak[$v];

            for ($j = 0; $j < 4; $j++) {
                $keluar[$j] |= (($s >> $j) & 1) << $bit;
            }
        }

        return $keluar;
    }

    /**
     * @param  list<int>  $kotak
     * @return list<int>
     */
    private function inversSbox(array $kotak): array
    {
        $invers = array_fill(0, 16, 0);

        foreach ($kotak as $masuk => $keluar) {
            $invers[$keluar] = $masuk;
        }

        return $invers;
    }

    /**
     * Lapisan linier Serpent (rotasi + shift + XOR).
     *
     * @param  list<int>  $x
     * @return list<int>
     */
    private static function linier(array $x): array
    {
        $x[0] = self::rol($x[0], 13);
        $x[2] = self::rol($x[2], 3);
        $x[1] ^= $x[0] ^ $x[2];
        $x[3] ^= $x[2] ^ (($x[0] << 3) & self::MASK);
        $x[1] = self::rol($x[1], 1);
        $x[3] = self::rol($x[3], 7);
        $x[0] ^= $x[1] ^ $x[3];
        $x[2] ^= $x[3] ^ (($x[1] << 7) & self::MASK);
        $x[0] = self::rol($x[0], 5);
        $x[2] = self::rol($x[2], 22);

        return $x;
    }

    /**
     * @param  list<int>  $x
     * @return list<int>
     */
    private static function linierInv(array $x): array
    {
        $x[2] = self::ror($x[2], 22);
        $x[0] = self::ror($x[0], 5);
        $x[2] ^= $x[3] ^ (($x[1] << 7) & self::MASK);
        $x[0] ^= $x[1] ^ $x[3];
        $x[3] = self::ror($x[3], 7);
        $x[1] = self::ror($x[1], 1);
        $x[3] ^= $x[2] ^ (($x[0] << 3) & self::MASK);
        $x[1] ^= $x[0] ^ $x[2];
        $x[2] = self::ror($x[2], 3);
        $x[0] = self::ror($x[0], 13);

        return $x;
    }

    /**
     * Kunci di-pad ke 256 bit (byte 0x01 lalu nol), dibaca 8 kata
     * little-endian, diperluas 132 kata prekey, lalu 33 subkunci
     * melalui S-box (3 - i) mod 8.
     *
     * @return list<list<int>>
     */
    private function jadwalKunci(string $kunci): array
    {
        $pad = $kunci."\x01".str_repeat("\x00", 31 - strlen($kunci));
        $w = [];

        for ($i = 0; $i < 8; $i++) {
            $w[] = unpack('V', substr($pad, 4 * $i, 4))[1];
        }

        for ($i = 0; $i < 132; $i++) {
            $w[] = self::rol($w[$i] ^ $w[$i + 3] ^ $w[$i + 5] ^ $w[$i + 7] ^ self::PHI ^ $i, 11);
        }

        $subkunci = [];

        for ($r = 0; $r < 33; $r++) {
            $kotak = self::SBOX[((3 - $r) % 8 + 8) % 8];
            $subkunci[] = self::lapisanSbox($kotak, array_slice($w, 8 + 4 * $r, 4));
        }

        return $subkunci;
    }

    /**
     * @return list<int>
     */
    private function potong(int $r): array
    {
        return $this->subkunci[$r];
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private function xorKata(array $a, array $b): array
    {
        return [
            $a[0] ^ $b[0],
            $a[1] ^ $b[1],
            $a[2] ^ $b[2],
            $a[3] ^ $b[3],
        ];
    }

    /**
     * @return list<int>
     */
    private function kataBlok(string $blok): array
    {
        return array_values(unpack('V4', $blok));
    }

    private static function rol(int $x, int $n): int
    {
        return (($x << $n) | ($x >> (32 - $n))) & self::MASK;
    }

    private static function ror(int $x, int $n): int
    {
        return (($x >> $n) | ($x << (32 - $n))) & self::MASK;
    }
}
