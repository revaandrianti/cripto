<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * A5/2 — Varian ekspor GSM yang memakai empat LFSR:
 * R1 (19 bit), R2 (22 bit), R3 (23 bit), dan R4 (17 bit).
 * Clocking R1, R2, R3 dikontrol oleh majority bit dari R4.
 */
final class A52
{
    private int $r1 = 0;

    private int $r2 = 0;

    private int $r3 = 0;

    private int $r4 = 0;

    private const MASK_19 = (1 << 19) - 1;

    private const MASK_22 = (1 << 22) - 1;

    private const MASK_23 = (1 << 23) - 1;

    private const MASK_17 = (1 << 17) - 1;

    public function __construct(string $key8 = '')
    {
        if ($key8 !== '') {
            $this->reset($key8);
        }
    }

    public function reset(string $key8): void
    {
        if (strlen($key8) !== 8) {
            $key8 = str_pad(substr($key8, 0, 8), 8, "\0");
        }

        $bits = '';
        foreach (str_split($key8) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $this->r1 = ((int) bindec(substr($bits, 0, 19))) & self::MASK_19;
        $this->r2 = ((int) bindec(substr($bits, 19, 22))) & self::MASK_22;
        $this->r3 = ((int) bindec(substr($bits, 41, 16))) & self::MASK_23;
        $this->r4 = ((int) bindec(substr($bits, 48, 16))) & self::MASK_17;

        for ($i = 0; $i < 100; $i++) {
            $this->clock();
        }
    }

    public function clock(): int
    {
        // R4 selalu bergeser
        $fb4 = (($this->r4 >> 16) ^ ($this->r4 >> 11)) & 1;
        $this->r4 = (($this->r4 << 1) | $fb4) & self::MASK_17;

        // Kontrol majority dari R4 (bit 3, 7, 10)
        $c1 = ($this->r4 >> 3) & 1;
        $c2 = ($this->r4 >> 7) & 1;
        $c3 = ($this->r4 >> 10) & 1;
        $maj = ($c1 & $c2) | ($c1 & $c3) | ($c2 & $c3);

        if ($c1 === $maj) {
            $fb1 = (($this->r1 >> 18) ^ ($this->r1 >> 17) ^ ($this->r1 >> 16) ^ ($this->r1 >> 13)) & 1;
            $this->r1 = (($this->r1 << 1) | $fb1) & self::MASK_19;
        }

        if ($c2 === $maj) {
            $fb2 = (($this->r2 >> 21) ^ ($this->r2 >> 20)) & 1;
            $this->r2 = (($this->r2 << 1) | $fb2) & self::MASK_22;
        }

        if ($c3 === $maj) {
            $fb3 = (($this->r3 >> 22) ^ ($this->r3 >> 21) ^ ($this->r3 >> 20) ^ ($this->r3 >> 7)) & 1;
            $this->r3 = (($this->r3 << 1) | $fb3) & self::MASK_23;
        }

        return (($this->r1 >> 18) ^ ($this->r2 >> 21) ^ ($this->r3 >> 22) ^ ($this->r4 >> 16)) & 1;
    }

    public function nextByte(): int
    {
        $val = 0;
        for ($i = 0; $i < 8; $i++) {
            $val = ($val << 1) | $this->clock();
        }

        return $val;
    }

    public function crypt(string $data): string
    {
        $out = '';
        foreach (str_split($data) as $c) {
            $out .= chr(ord($c) ^ $this->nextByte());
        }

        return $out;
    }
}
