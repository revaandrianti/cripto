<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * Rabbit Stream Cipher (RFC 4503):
 * Cipher berkecepatan tinggi dengan kunci 128 bit (16 byte) dan status internal 513 bit.
 */
final class Rabbit
{
    /** @var int[] 8 state words */
    private array $x = [];

    /** @var int[] 8 counter words */
    private array $c = [];

    private int $carry = 0;

    private const A = [
        0x4D34D34D, 0xD34D34D3, 0x34D34D34, 0x4D34D34D,
        0xD34D34D3, 0x34D34D34, 0x4D34D34D, 0xD34D34D3,
    ];

    public function __construct(string $key16 = '', string $iv8 = '')
    {
        if ($key16 !== '') {
            $this->reset($key16, $iv8);
        }
    }

    public function reset(string $key16, string $iv8 = ''): void
    {
        $key16 = str_pad(substr($key16, 0, 16), 16, "\0");
        $k = array_values(unpack('v8', $key16)); // 8 buah 16-bit words

        $this->x = [];
        $this->c = [];
        $this->carry = 0;

        for ($j = 0; $j < 8; $j++) {
            if ($j % 2 === 0) {
                $this->x[$j] = (($k[($j + 1) % 8] << 16) | $k[$j]) & 0xFFFFFFFF;
                $this->c[$j] = (($k[($j + 4) % 8] << 16) | $k[($j + 5) % 8]) & 0xFFFFFFFF;
            } else {
                $this->x[$j] = (($k[($j + 5) % 8] << 16) | $k[($j + 4) % 8]) & 0xFFFFFFFF;
                $this->c[$j] = (($k[$j] << 16) | $k[($j + 1) % 8]) & 0xFFFFFFFF;
            }
        }

        for ($i = 0; $i < 4; $i++) {
            $this->nextBlock();
        }

        for ($j = 0; $j < 8; $j++) {
            $this->c[$j] ^= $this->x[($j + 4) % 8];
        }
    }

    private function gFunc(int $u, int $v): int
    {
        $sum = ($u + $v) & 0xFFFFFFFF;
        $sq = bcmul((string) $sum, (string) $sum);
        $low = (int) bcmod($sq, '4294967296');
        $high = (int) bcmod(bcdiv($sq, '4294967296', 0), '4294967296');

        return ($low ^ $high) & 0xFFFFFFFF;
    }

    private function rotl(int $v, int $s): int
    {
        return (($v << $s) | ($v >> (32 - $s))) & 0xFFFFFFFF;
    }

    public function nextBlock(): string
    {
        // Counter system update
        for ($j = 0; $j < 8; $j++) {
            $temp = $this->c[$j] + self::A[$j] + $this->carry;
            $this->carry = $temp > 0xFFFFFFFF ? 1 : 0;
            $this->c[$j] = $temp & 0xFFFFFFFF;
        }

        // Next-state function
        $g = [];
        for ($j = 0; $j < 8; $j++) {
            $g[$j] = $this->gFunc($this->x[$j], $this->c[$j]);
        }

        $this->x[0] = ($g[0] + $this->rotl($g[7], 16) + $this->rotl($g[6], 16)) & 0xFFFFFFFF;
        $this->x[1] = ($g[1] + $this->rotl($g[0], 8) + $g[7]) & 0xFFFFFFFF;
        $this->x[2] = ($g[2] + $this->rotl($g[1], 16) + $this->rotl($g[0], 16)) & 0xFFFFFFFF;
        $this->x[3] = ($g[3] + $this->rotl($g[2], 8) + $g[1]) & 0xFFFFFFFF;
        $this->x[4] = ($g[4] + $this->rotl($g[3], 16) + $this->rotl($g[2], 16)) & 0xFFFFFFFF;
        $this->x[5] = ($g[5] + $this->rotl($g[4], 8) + $g[3]) & 0xFFFFFFFF;
        $this->x[6] = ($g[6] + $this->rotl($g[5], 16) + $this->rotl($g[4], 16)) & 0xFFFFFFFF;
        $this->x[7] = ($g[7] + $this->rotl($g[6], 8) + $g[5]) & 0xFFFFFFFF;

        // Extraction of 128-bit keystream (16 byte)
        $s = [];
        $s[0] = ($this->x[0] ^ ($this->x[5] >> 16)) & 0xFFFF;
        $s[1] = (($this->x[0] >> 16) ^ $this->x[3]) & 0xFFFF;
        $s[2] = ($this->x[2] ^ ($this->x[7] >> 16)) & 0xFFFF;
        $s[3] = (($this->x[2] >> 16) ^ $this->x[5]) & 0xFFFF;
        $s[4] = ($this->x[4] ^ ($this->x[1] >> 16)) & 0xFFFF;
        $s[5] = (($this->x[4] >> 16) ^ $this->x[7]) & 0xFFFF;
        $s[6] = ($this->x[6] ^ ($this->x[3] >> 16)) & 0xFFFF;
        $s[7] = (($this->x[6] >> 16) ^ $this->x[1]) & 0xFFFF;

        return pack('v8', ...$s);
    }

    public function crypt(string $data): string
    {
        $out = '';
        $len = strlen($data);
        $offset = 0;

        while ($offset < $len) {
            $block = $this->nextBlock();
            $chunk = substr($data, $offset, 16);
            $out .= $chunk ^ substr($block, 0, strlen($chunk));
            $offset += 16;
        }

        return $out;
    }
}
