<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * A5/1 — stream cipher tiga LFSR (19 + 22 + 23 bit) seperti pada GSM.
 *
 * Bit dinomori dari LSB (bit 0) sisi input; keluaran diambil dari bit
 * tertinggi masing-masing register (R1[18], R2[21], R3[22]).
 *
 * Kelebihan dari makalah asal: kunci 8 byte dimuat deterministik ke
 * register, dan 100 clock pertama dibuang (praktik A5/1 nyata) agar
 * state tercampur sebelum keystream diambil.
 */
final class A51
{
    /** @var int Register 1, 19 bit */
    private int $r1 = 0;

    /** @var int Register 2, 22 bit */
    private int $r2 = 0;

    /** @var int Register 3, 23 bit */
    private int $r3 = 0;

    private const MASK_19 = (1 << 19) - 1;

    private const MASK_22 = (1 << 22) - 1;

    private const MASK_23 = (1 << 23) - 1;

    public function __construct(string $key8 = '')
    {
        if ($key8 !== '') {
            $this->reset($key8);
        }
    }

    public function reset(string $key8): void
    {
        if (strlen($key8) !== 8) {
            throw new \InvalidArgumentException('Kunci A5/1 harus tepat 8 byte (64 bit).');
        }

        $bits = '';
        foreach (str_split($key8) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        // 64 bit kunci: 19 untuk R1, 22 untuk R2, 23 untuk R3.
        $this->setRegister(
            (int) bindec(substr($bits, 0, 19)),
            (int) bindec(substr($bits, 19, 22)),
            (int) bindec(substr($bits, 41, 23)),
        );

        // 100 clock pemanasan: keluaran dibuang (praktik A5/1 nyata).
        for ($i = 0; $i < 100; $i++) {
            $this->clock();
        }
    }

    /**
     * Muat isi register secara langsung (dipakai untuk vektor uji).
     */
    public function setRegister(int $r1, int $r2, int $r3): void
    {
        $this->r1 = $r1 & self::MASK_19;
        $this->r2 = $r2 & self::MASK_22;
        $this->r3 = $r3 & self::MASK_23;
    }

    /**
     * Satu detak jam: mayoritas bit clocking, register yang cocok
     * bergeser, keluaran bit baru di-XOR dari bit tertinggi.
     */
    public function clock(): int
    {
        $c1 = ($this->r1 >> 8) & 1;
        $c2 = ($this->r2 >> 10) & 1;
        $c3 = ($this->r3 >> 10) & 1;

        $maj = ($c1 & $c2) | ($c1 & $c3) | ($c2 & $c3);

        if ($c1 === $maj) {
            $feedback = $this->parity($this->r1 & ((1 << 13) | (1 << 16) | (1 << 17) | (1 << 18)));
            $this->r1 = ((($this->r1 << 1) | $feedback) & self::MASK_19);
        }

        if ($c2 === $maj) {
            $feedback = $this->parity($this->r2 & ((1 << 20) | (1 << 21)));
            $this->r2 = ((($this->r2 << 1) | $feedback) & self::MASK_22);
        }

        if ($c3 === $maj) {
            $feedback = $this->parity($this->r3 & ((1 << 7) | (1 << 20) | (1 << 21) | (1 << 22)));
            $this->r3 = ((($this->r3 << 1) | $feedback) & self::MASK_23);
        }

        return (($this->r1 >> 18) ^ ($this->r2 >> 21) ^ ($this->r3 >> 22)) & 1;
    }

    /**
     * Keystream byte berikutnya dari n detak jam (bit pertama jadi MSB).
     */
    public function keystreamByte(int $bits = 8): int
    {
        if ($bits < 1 || $bits > 8) {
            throw new \InvalidArgumentException('Jumlah bit per byte harus 1 sampai 8.');
        }

        $value = 0;
        for ($i = 0; $i < $bits; $i++) {
            $value = ($value << 1) | $this->clock();
        }

        return $value;
    }

    /**
     * Enkripsi/dekripsi XOR terhadap data biner, satu bit per detak.
     */
    public function crypt(string $data): string
    {
        $out = '';

        foreach (str_split($data) as $byte) {
            $out .= chr(ord($byte) ^ $this->keystreamByte(8));
        }

        return $out;
    }

    private function parity(int $value): int
    {
        $parity = 0;
        while ($value) {
            $parity ^= $value & 1;
            $value >>= 1;
        }

        return $parity;
    }
}
