<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * Trivium: Stream cipher berorientasi hardware (eSTREAM Profile II).
 * Menggunakan 3 shift register: A (93 bit), B (84 bit), C (111 bit) = 288 bit.
 */
final class Trivium
{
    /** @var int[] 93 bits */
    private array $a = [];

    /** @var int[] 84 bits */
    private array $b = [];

    /** @var int[] 111 bits */
    private array $c = [];

    public function __construct(string $key10 = '', string $iv10 = '')
    {
        if ($key10 !== '') {
            $this->reset($key10, $iv10);
        }
    }

    public function reset(string $key10, string $iv10 = ''): void
    {
        $key10 = str_pad(substr($key10, 0, 10), 10, "\0");
        $iv10 = str_pad(substr($iv10, 0, 10), 10, "\0");

        $this->a = array_fill(0, 93, 0);
        $this->b = array_fill(0, 84, 0);
        $this->c = array_fill(0, 111, 0);

        // Load 80-bit key into a[0..79]
        for ($i = 0; $i < 10; $i++) {
            $byte = ord($key10[$i]);
            for ($j = 0; $j < 8; $j++) {
                $this->a[$i * 8 + $j] = ($byte >> $j) & 1;
            }
        }

        // Load 80-bit IV into b[0..79]
        for ($i = 0; $i < 10; $i++) {
            $byte = ord($iv10[$i]);
            for ($j = 0; $j < 8; $j++) {
                $this->b[$i * 8 + $j] = ($byte >> $j) & 1;
            }
        }

        // c[108..110] set to 1
        $this->c[108] = 1;
        $this->c[109] = 1;
        $this->c[110] = 1;

        // Pemanasan 4 * 288 = 1152 putaran
        for ($i = 0; $i < 1152; $i++) {
            $this->clock();
        }
    }

    public function clock(): int
    {
        $t1 = $this->a[65] ^ $this->a[92];
        $t2 = $this->b[68] ^ $this->b[83];
        $t3 = $this->c[65] ^ $this->c[110];

        $z = $t1 ^ $t2 ^ $t3;

        $t1 ^= ($this->a[90] & $this->a[91]) ^ $this->b[77];
        $t2 ^= ($this->b[81] & $this->b[82]) ^ $this->c[86];
        $t3 ^= ($this->c[108] & $this->c[109]) ^ $this->a[68];

        array_unshift($this->a, $t3);
        array_pop($this->a);

        array_unshift($this->b, $t1);
        array_pop($this->b);

        array_unshift($this->c, $t2);
        array_pop($this->c);

        return $z;
    }

    public function nextByte(): int
    {
        $val = 0;
        for ($i = 0; $i < 8; $i++) {
            $val |= ($this->clock() << $i);
        }

        return $val;
    }

    public function crypt(string $data): string
    {
        $out = '';
        foreach (str_split($data) as $char) {
            $out .= chr(ord($char) ^ $this->nextByte());
        }

        return $out;
    }
}
