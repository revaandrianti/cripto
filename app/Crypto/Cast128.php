<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * CAST-128: blok 64 bit, kunci 128 bit, 16 ronde Feistel dengan tiga
 * fungsi ronde bergilir (RFC 2144). Jadwal kunci menghasilkan Km1..Km16
 * (masking, 32 bit) dan Kr1..Kr16 (rotasi, 5 bit rendah) melalui empat
 * pasangan iterasi z/x dengan empat pola formula subkunci.
 *
 * Diverifikasi terhadap vektor resmi RFC 2144 (kunci 128, 80, dan 40 bit).
 */
final class Cast128
{
    use Cast128SBoxes;

    /** @var list<int> 16 pasang subkunci [Kmi, Kri] */
    private array $subkunci;

    /** Jumlah ronde: 16 untuk kunci > 80 bit, 12 untuk <= 80 bit (RFC 2144 §2.5). */
    private int $ronde;

    public function __construct(string $kunci16)
    {
        if (strlen($kunci16) !== 16) {
            throw new \InvalidArgumentException('Kunci CAST-128 harus tepat 16 byte.');
        }

        // Panjang kunci efektif = posisi byte nol terakhir + 1 (40..128 bit).
        $efektif = 16;
        while ($efektif > 5 && ord($kunci16[$efektif - 1]) === 0) {
            $efektif--;
        }

        $this->ronde = $efektif <= 10 ? 12 : 16;
        $this->subkunci = $this->jadwalKunci($kunci16, $this->ronde);
    }

    /** Enkripsi satu blok 64 bit. */
    public function enkripsiBlok(string $blok): string
    {
        [$l, $r] = $this->belah($blok);

        for ($i = 0; $i < $this->ronde; $i++) {
            [$kmi, $kri] = $this->subkunci[$i];
            $t = $r;
            $r = $l ^ $this->f($r, $kmi, $kri, (($i % 3) + 1));
            $l = $t;
        }

        return pack('N2', $r, $l);
    }

    /** Dekripsi satu blok: subkunci terbalik, tipe ronde ikut urutan terbalik. */
    public function dekripsiBlok(string $blok): string
    {
        [$l, $r] = $this->belah($blok);

        for ($i = $this->ronde - 1; $i >= 0; $i--) {
            [$kmi, $kri] = $this->subkunci[$i];
            $t = $r;
            $r = $l ^ $this->f($r, $kmi, $kri, (($i % 3) + 1));
            $l = $t;
        }

        return pack('N2', $r, $l);
    }

    /**
     * Fungsi ronde menurut tipe 1/2/3 (RFC 2144 §2.2).
     */
    private function f(int $d, int $km, int $kr, int $tipe): int
    {
        $i = $this->rotl($this->opKunci($km, $d, $tipe), $kr & 31);
        $ia = ($i >> 24) & 0xFF;
        $ib = ($i >> 16) & 0xFF;
        $ic = ($i >> 8) & 0xFF;
        $id = $i & 0xFF;

        return match ($tipe) {
            // ((S1^S2) - S3) + S4
            1 => $this->tambah($this->kurang($this->xor32(self::S1[$ia], self::S2[$ib]), self::S3[$ic]), self::S4[$id]),
            // ((S1 - S2) + S3) ^ S4
            2 => $this->xor32($this->tambah($this->kurang(self::S1[$ia], self::S2[$ib]), self::S3[$ic]), self::S4[$id]),
            // ((S1 + S2) ^ S3) - S4
            default => $this->kurang($this->xor32($this->tambah(self::S1[$ia], self::S2[$ib]), self::S3[$ic]), self::S4[$id]),
        };
    }

    private function opKunci(int $km, int $d, int $tipe): int
    {
        return match ($tipe) {
            1 => $this->tambah($km, $d),
            2 => $km ^ $d,
            default => $this->kurang($km, $d),
        };
    }

    private function rotl(int $x, int $n): int
    {
        if ($n === 0) {
            return $x & 0xFFFFFFFF;
        }

        return (($x << $n) | ($x >> (32 - $n))) & 0xFFFFFFFF;
    }

    private function tambah(int $a, int $b): int
    {
        return ($a + $b) & 0xFFFFFFFF;
    }

    private function kurang(int $a, int $b): int
    {
        return ($a - $b) & 0xFFFFFFFF;
    }

    private function xor32(int $a, int $b): int
    {
        return ($a ^ $b) & 0xFFFFFFFF;
    }

    /**
     * @return array{int, int}
     */
    private function belah(string $blok): array
    {
        $kata = unpack('N2', $blok);

        return [$kata[1], $kata[2]];
    }

    /**
     * Jadwal kunci RFC 2144 §2.4 (semua indeks byte, kata paling atas
     * adalah byte pertama). Empat iterasi z/x selalu dijalankan agar
     * K1..K32 lengkap: Kmi = Ki, Kri = K(16+i) — juga untuk 12 ronde.
     *
     * @return list<array{int, int}>
     */
    private function jadwalKunci(string $kunci, int $ronde): array
    {
        $x = array_values(unpack('C16', $kunci));
        $z = array_fill(0, 16, 0);
        $k = [];

        for ($iterasi = 0; $iterasi < 4; $iterasi++) {
            // z-pass: formula identik di setiap iterasi.
            [$z[0], $z[1], $z[2], $z[3]] = $this->byte16(
                $this->kata4($x, 0) ^ self::S5[$x[13]] ^ self::S6[$x[15]] ^ self::S7[$x[12]] ^ self::S8[$x[14]] ^ self::S7[$x[8]]
            );
            [$z[4], $z[5], $z[6], $z[7]] = $this->byte16(
                $this->kata4($x, 8) ^ self::S5[$z[0]] ^ self::S6[$z[2]] ^ self::S7[$z[1]] ^ self::S8[$z[3]] ^ self::S8[$x[10]]
            );
            [$z[8], $z[9], $z[10], $z[11]] = $this->byte16(
                $this->kata4($x, 12) ^ self::S5[$z[7]] ^ self::S6[$z[6]] ^ self::S7[$z[5]] ^ self::S8[$z[4]] ^ self::S5[$x[9]]
            );
            [$z[12], $z[13], $z[14], $z[15]] = $this->byte16(
                $this->kata4($x, 4) ^ self::S5[$z[10]] ^ self::S6[$z[9]] ^ self::S7[$z[11]] ^ self::S8[$z[8]] ^ self::S6[$x[11]]
            );

            // Pola A (setelah z-pass ke-1 dan ke-3).
            if ($iterasi % 2 === 0) {
                $k[] = self::S5[$z[8]] ^ self::S6[$z[9]] ^ self::S7[$z[7]] ^ self::S8[$z[6]] ^ self::S5[$z[2]];
                $k[] = self::S5[$z[10]] ^ self::S6[$z[11]] ^ self::S7[$z[5]] ^ self::S8[$z[4]] ^ self::S6[$z[6]];
                $k[] = self::S5[$z[12]] ^ self::S6[$z[13]] ^ self::S7[$z[3]] ^ self::S8[$z[2]] ^ self::S7[$z[9]];
                $k[] = self::S5[$z[14]] ^ self::S6[$z[15]] ^ self::S7[$z[1]] ^ self::S8[$z[0]] ^ self::S8[$z[12]];
            } else {
                // Pola C (setelah z-pass ke-2 dan ke-4).
                $k[] = self::S5[$z[3]] ^ self::S6[$z[2]] ^ self::S7[$z[12]] ^ self::S8[$z[13]] ^ self::S5[$z[9]];
                $k[] = self::S5[$z[1]] ^ self::S6[$z[0]] ^ self::S7[$z[14]] ^ self::S8[$z[15]] ^ self::S6[$z[12]];
                $k[] = self::S5[$z[7]] ^ self::S6[$z[6]] ^ self::S7[$z[8]] ^ self::S8[$z[9]] ^ self::S7[$z[2]];
                $k[] = self::S5[$z[5]] ^ self::S6[$z[4]] ^ self::S7[$z[10]] ^ self::S8[$z[11]] ^ self::S8[$z[6]];
            }

            // x-pass: formula identik di setiap iterasi (memakai x dan z terbaru).
            [$x[0], $x[1], $x[2], $x[3]] = $this->byte16(
                $this->kata4($z, 8) ^ self::S5[$z[5]] ^ self::S6[$z[7]] ^ self::S7[$z[4]] ^ self::S8[$z[6]] ^ self::S7[$z[0]]
            );
            [$x[4], $x[5], $x[6], $x[7]] = $this->byte16(
                $this->kata4($z, 0) ^ self::S5[$x[0]] ^ self::S6[$x[2]] ^ self::S7[$x[1]] ^ self::S8[$x[3]] ^ self::S8[$z[2]]
            );
            [$x[8], $x[9], $x[10], $x[11]] = $this->byte16(
                $this->kata4($z, 4) ^ self::S5[$x[7]] ^ self::S6[$x[6]] ^ self::S7[$x[5]] ^ self::S8[$x[4]] ^ self::S5[$z[1]]
            );
            [$x[12], $x[13], $x[14], $x[15]] = $this->byte16(
                $this->kata4($z, 12) ^ self::S5[$x[10]] ^ self::S6[$x[9]] ^ self::S7[$x[11]] ^ self::S8[$x[8]] ^ self::S6[$z[3]]
            );

            // Pola B (setelah x-pass ke-1 dan ke-3).
            if ($iterasi % 2 === 0) {
                $k[] = self::S5[$x[3]] ^ self::S6[$x[2]] ^ self::S7[$x[12]] ^ self::S8[$x[13]] ^ self::S5[$x[8]];
                $k[] = self::S5[$x[1]] ^ self::S6[$x[0]] ^ self::S7[$x[14]] ^ self::S8[$x[15]] ^ self::S6[$x[13]];
                $k[] = self::S5[$x[7]] ^ self::S6[$x[6]] ^ self::S7[$x[8]] ^ self::S8[$x[9]] ^ self::S7[$x[3]];
                $k[] = self::S5[$x[5]] ^ self::S6[$x[4]] ^ self::S7[$x[10]] ^ self::S8[$x[11]] ^ self::S8[$x[7]];
            } else {
                // Pola D (setelah x-pass ke-2 dan ke-4).
                $k[] = self::S5[$x[8]] ^ self::S6[$x[9]] ^ self::S7[$x[7]] ^ self::S8[$x[6]] ^ self::S5[$x[3]];
                $k[] = self::S5[$x[10]] ^ self::S6[$x[11]] ^ self::S7[$x[5]] ^ self::S8[$x[4]] ^ self::S6[$x[7]];
                $k[] = self::S5[$x[12]] ^ self::S6[$x[13]] ^ self::S7[$x[3]] ^ self::S8[$x[2]] ^ self::S7[$x[8]];
                $k[] = self::S5[$x[14]] ^ self::S6[$x[15]] ^ self::S7[$x[1]] ^ self::S8[$x[0]] ^ self::S8[$x[13]];
            }
        }

        $subkunci = [];

        for ($i = 0; $i < $ronde; $i++) {
            $subkunci[] = [$k[$i] & 0xFFFFFFFF, $k[16 + $i] & 31];
        }

        return $subkunci;
    }

    /**
     * Kata 32 bit dari empat byte berurutan (byte pertama paling atas).
     *
     * @param  list<int>  $bytes
     */
    private function kata4(array $bytes, int $mulai): int
    {
        return ($bytes[$mulai] << 24) | ($bytes[$mulai + 1] << 16) | ($bytes[$mulai + 2] << 8) | $bytes[$mulai + 3];
    }

    /** Pecah kata 32 bit menjadi empat byte (byte pertama paling atas). */
    private function byte16(int $kata): array
    {
        return [($kata >> 24) & 0xFF, ($kata >> 16) & 0xFF, ($kata >> 8) & 0xFF, $kata & 0xFF];
    }
}
