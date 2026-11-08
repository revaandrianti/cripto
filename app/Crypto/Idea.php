<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * IDEA (International Data Encryption Algorithm), 64-bit block,
 * kunci 128 bit, 8 ronde + transformasi keluaran.
 *
 * Perkalian dilakukan modulo 2^16 + 1 dengan konvensi 0 = 2^16;
 * penjumlahan modulo 2^16. Struktur ronde diverifikasi terhadap
 * vektor uji implementasi referensi ETH Zurich (antara lain
 * plaintext 0000000100020003 -> ciphertext 11fbed2b01986de5 untuk
 * kunci 00010002000300040005000600070008).
 */
final class Idea
{
    private const MOD = 65537;

    /** @var list<int> 52 subkunci 16 bit */
    private array $enkripsi;

    public function __construct(string $key16)
    {
        if (strlen($key16) !== 16) {
            throw new \InvalidArgumentException('Kunci IDEA harus tepat 16 byte.');
        }

        $this->enkripsi = $this->jadwalKunci($key16);
    }

    public function enkripsiBlok(string $blok8): string
    {
        if (strlen($blok8) !== 8) {
            throw new \InvalidArgumentException('Blok IDEA harus tepat 8 byte.');
        }

        return $this->proses(unpack('n4', $blok8), $this->enkripsi);
    }

    public function dekripsiBlok(string $blok8): string
    {
        if (strlen($blok8) !== 8) {
            throw new \InvalidArgumentException('Blok IDEA harus tepat 8 byte.');
        }

        [$o1, $o2, $o3, $o4] = array_values(unpack('n4', $blok8));
        $e = $this->enkripsi;

        // Urungkan transformasi keluaran. Keluaran diemas dengan kata
        // tengah tertukar, jadi o2 menempati posisi kata ketiga.
        $w = [
            self::mul($o1, self::mulInv($e[48])),
            ($o3 - $e[50]) & 0xFFFF,
            ($o2 - $e[49]) & 0xFFFF,
            self::mul($o4, self::mulInv($e[51])),
        ];

        for ($r = 7; $r >= 0; $r--) {
            $b = $r * 6;

            // Rekonstruksi keluaran MA-box dari pasangan kata keluaran:
            // w0 ^ w1 = a ^ c dan w2 ^ w3 = b ^ d.
            $t0 = self::mul($w[0] ^ $w[1], $e[$b + 4]);
            $t1 = self::mul((($w[2] ^ $w[3]) + $t0) & 0xFFFF, $e[$b + 5]);
            $u = ($t0 + $t1) & 0xFFFF;

            $a = $w[0] ^ $t1;
            $c = $w[1] ^ $t1;
            $bb = $w[2] ^ $u;
            $dd = $w[3] ^ $u;

            $w = [
                self::mul($a, self::mulInv($e[$b])),
                ($bb - $e[$b + 1]) & 0xFFFF,
                ($c - $e[$b + 2]) & 0xFFFF,
                self::mul($dd, self::mulInv($e[$b + 3])),
            ];
        }

        return pack('n4', ...$w);
    }

    /**
     * 52 subkunci: 8 kata big-endian per grup, kunci diputar 25 bit
     * ke kiri antar grup (enam kali).
     *
     * @return list<int>
     */
    private function jadwalKunci(string $key16): array
    {
        $k = array_values(unpack('n8', $key16));
        $sub = [];

        while (count($sub) < 52) {
            for ($i = 0; $i < 8 && count($sub) < 52; $i++) {
                $sub[] = $k[$i];
            }

            // rotasi 25 bit ke kiri atas 128 bit:
            // kata[j] = (kata[j+1] & 0x7F) << 9 | kata[j+2] >> 7
            $baru = [];

            for ($i = 0; $i < 8; $i++) {
                $baru[$i] = ((($k[($i + 1) % 8] & 0x7F) << 9) | ($k[($i + 2) % 8] >> 7));
            }

            $k = $baru;
        }

        return $sub;
    }

    /** @param array<int, int> $w @param list<int> $s */
    private function proses(array $w, array $s): string
    {
        $w = array_values($w);

        for ($r = 0; $r < 8; $r++) {
            $b = $r * 6;
            $w[0] = self::mul($w[0], $s[$b]);
            $w[1] = ($w[1] + $s[$b + 1]) & 0xFFFF;
            $w[2] = ($w[2] + $s[$b + 2]) & 0xFFFF;
            $w[3] = self::mul($w[3], $s[$b + 3]);

            $t0 = self::mul($w[0] ^ $w[2], $s[$b + 4]);
            $t1 = self::mul((($t0 + ($w[1] ^ $w[3])) & 0xFFFF), $s[$b + 5]);
            $u = ($t0 + $t1) & 0xFFFF;

            // Keluaran ronde menukar kata tengah; pemulihannya terjadi
            // pada transformasi akhir.
            $w = [$w[0] ^ $t1, $w[2] ^ $t1, $w[1] ^ $u, $w[3] ^ $u];
        }

        // Kata tengah kembali ke posisi aslinya di sini (membatalkan
        // penukaran keluaran ronde terakhir).
        $x1 = $w[0];
        $x2 = $w[1];
        $x3 = $w[2];
        $x4 = $w[3];

        $x1 = self::mul($x1, $s[48]);
        $x3 = ($x3 + $s[49]) & 0xFFFF;
        $x2 = ($x2 + $s[50]) & 0xFFFF;
        $x4 = self::mul($x4, $s[51]);

        return pack('n4', $x1, $x3, $x2, $x4);
    }

    private static function mul(int $a, int $b): int
    {
        if ($a === 0) {
            $a = 65536;
        }

        if ($b === 0) {
            $b = 65536;
        }

        $t = ($a * $b) % self::MOD;

        return $t === 65536 ? 0 : $t;
    }

    private static function mulInv(int $a): int
    {
        if ($a === 0) {
            // 0 dianggap 65536; invers 65536 mod 65537 adalah 65536 (=-1 x -1).
            $a = 65536;
        }

        $oldR = $a;
        $r = self::MOD;
        $oldS = 1;
        $s = 0;

        while ($r !== 0) {
            $q = intdiv($oldR, $r);
            [$oldR, $r] = [$r, $oldR - $q * $r];
            [$oldS, $s] = [$s, $oldS - $q * $s];
        }

        return $oldS < 0 ? $oldS + self::MOD : $oldS;
    }
}
