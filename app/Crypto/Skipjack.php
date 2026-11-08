<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * Skipjack: blok 64 bit (empat kata 16 bit), kunci 80 bit (10 byte),
 * 32 ronde unbalanced Feistel dengan dua aturan bergantian
 * (Rule A untuk ronde 1-8 dan 17-24, Rule B untuk ronde 9-16 dan 25-32).
 * Fungsi G = Feistel 4 langkah di atas tabel F 256 byte; byte kunci
 * dipilih melingkar dengan penghitung ronde.
 *
 * Diverifikasi terhadap contoh spesifikasi NIST (kunci 00998877665544332211,
 * plaintext 33221100ddccbbaa -> ciphertext 2587cae27a12d300) beserta
 * jejak evolusi kata per ronde.
 */
final class Skipjack
{
    /** @var list<int> */
    private const F = [
        0xA3, 0xD7, 0x09, 0x83, 0xF8, 0x48, 0xF6, 0xF4,
        0xB3, 0x21, 0x15, 0x78, 0x99, 0xB1, 0xAF, 0xF9,
        0xE7, 0x2D, 0x4D, 0x8A, 0xCE, 0x4C, 0xCA, 0x2E,
        0x52, 0x95, 0xD9, 0x1E, 0x4E, 0x38, 0x44, 0x28,
        0x0A, 0xDF, 0x02, 0xA0, 0x17, 0xF1, 0x60, 0x68,
        0x12, 0xB7, 0x7A, 0xC3, 0xE9, 0xFA, 0x3D, 0x53,
        0x96, 0x84, 0x6B, 0xBA, 0xF2, 0x63, 0x9A, 0x19,
        0x7C, 0xAE, 0xE5, 0xF5, 0xF7, 0x16, 0x6A, 0xA2,
        0x39, 0xB6, 0x7B, 0x0F, 0xC1, 0x93, 0x81, 0x1B,
        0xEE, 0xB4, 0x1A, 0xEA, 0xD0, 0x91, 0x2F, 0xB8,
        0x55, 0xB9, 0xDA, 0x85, 0x3F, 0x41, 0xBF, 0xE0,
        0x5A, 0x58, 0x80, 0x5F, 0x66, 0x0B, 0xD8, 0x90,
        0x35, 0xD5, 0xC0, 0xA7, 0x33, 0x06, 0x65, 0x69,
        0x45, 0x00, 0x94, 0x56, 0x6D, 0x98, 0x9B, 0x76,
        0x97, 0xFC, 0xB2, 0xC2, 0xB0, 0xFE, 0xDB, 0x20,
        0xE1, 0xEB, 0xD6, 0xE4, 0xDD, 0x47, 0x4A, 0x1D,
        0x42, 0xED, 0x9E, 0x6E, 0x49, 0x3C, 0xCD, 0x43,
        0x27, 0xD2, 0x07, 0xD4, 0xDE, 0xC7, 0x67, 0x18,
        0x89, 0xCB, 0x30, 0x1F, 0x8D, 0xC6, 0x8F, 0xAA,
        0xC8, 0x74, 0xDC, 0xC9, 0x5D, 0x5C, 0x31, 0xA4,
        0x70, 0x88, 0x61, 0x2C, 0x9F, 0x0D, 0x2B, 0x87,
        0x50, 0x82, 0x54, 0x64, 0x26, 0x7D, 0x03, 0x40,
        0x34, 0x4B, 0x1C, 0x73, 0xD1, 0xC4, 0xFD, 0x3B,
        0xCC, 0xFB, 0x7F, 0xAB, 0xE6, 0x3E, 0x5B, 0xA5,
        0xAD, 0x04, 0x23, 0x9C, 0x14, 0x51, 0x22, 0xF0,
        0x29, 0x79, 0x71, 0x7E, 0xFF, 0x8C, 0x0E, 0xE2,
        0x0C, 0xEF, 0xBC, 0x72, 0x75, 0x6F, 0x37, 0xA1,
        0xEC, 0xD3, 0x8E, 0x62, 0x8B, 0x86, 0x10, 0xE8,
        0x08, 0x77, 0x11, 0xBE, 0x92, 0x4F, 0x24, 0xC5,
        0x32, 0x36, 0x9D, 0xCF, 0xF3, 0xA6, 0xBB, 0xAC,
        0x5E, 0x6C, 0xA9, 0x13, 0x57, 0x25, 0xB5, 0xE3,
        0xBD, 0xA8, 0x3A, 0x01, 0x05, 0x59, 0x2A, 0x46,
    ];

    /** @var list<int> kunci 10 byte */
    private array $kunci;

    public function __construct(string $kunci10)
    {
        if (strlen($kunci10) !== 10) {
            throw new \InvalidArgumentException('Kunci Skipjack harus tepat 10 byte (80 bit).');
        }

        $this->kunci = array_values(unpack('C10', $kunci10));
    }

    /** Enkripsi satu blok 64 bit. */
    public function enkripsiBlok(string $blok): string
    {
        $w = array_values(unpack('n4', $blok));

        for ($r = 0; $r < 32; $r++) {
            // Ronde 0-7 dan 16-23 memakai Rule A; sisanya Rule B.
            $w = (($r >> 3) % 2 === 0)
                ? $this->aturanA($w, $r)
                : $this->aturanB($w, $r);
        }

        return pack('n4', ...$w);
    }

    /** Dekripsi satu blok 64 bit: urungkan ronde dari belakang. */
    public function dekripsiBlok(string $blok): string
    {
        $w = array_values(unpack('n4', $blok));

        for ($r = 31; $r >= 0; $r--) {
            $w = (($r >> 3) % 2 === 0)
                ? $this->aturanAInv($w, $r)
                : $this->aturanBInv($w, $r);
        }

        return pack('n4', ...$w);
    }

    /** Rule A: w1' = G(w1) ^ w4 ^ counter, w2' = G(w1), w3' = w2, w4' = w3. */
    private function aturanA(array $w, int $r): array
    {
        $t = $this->g($w[0], $r);

        return [$t ^ $w[3] ^ ($r + 1), $t, $w[1], $w[2]];
    }

    /** Rule B: w1' = w4, w2' = G(w1), w3' = w1 ^ w2 ^ counter, w4' = w3. */
    private function aturanB(array $w, int $r): array
    {
        $t = $this->g($w[0], $r);

        return [$w[3], $t, $w[0] ^ $w[1] ^ ($r + 1), $w[2]];
    }

    private function aturanAInv(array $w, int $r): array
    {
        return [
            $this->gInv($w[1], $r),
            $w[2],
            $w[3],
            $w[0] ^ $w[1] ^ ($r + 1),
        ];
    }

    private function aturanBInv(array $w, int $r): array
    {
        $w1 = $this->gInv($w[1], $r);

        return [$w1, $w[2] ^ $w1 ^ ($r + 1), $w[3], $w[0]];
    }

    /** G: Feistel 4 langkah, byte kunci ke 4r..4r+3 (mod 10). */
    private function g(int $w, int $r): int
    {
        $kiri = $w >> 8;
        $kanan = $w & 0xFF;

        for ($i = 0; $i < 4; $i++) {
            $cv = $this->kunci[(4 * $r + $i) % 10];
            [$kiri, $kanan] = [$kanan, $kiri ^ self::F[$kanan ^ $cv]];
        }

        return ($kiri << 8) | $kanan;
    }

    private function gInv(int $w, int $r): int
    {
        $kiri = $w >> 8;
        $kanan = $w & 0xFF;

        for ($i = 3; $i >= 0; $i--) {
            $cv = $this->kunci[(4 * $r + $i) % 10];
            [$kiri, $kanan] = [$kanan ^ self::F[$kiri ^ $cv], $kiri];
        }

        return ($kiri << 8) | $kanan;
    }
}
