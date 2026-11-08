<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * RC4 dengan ruang state N yang dapat dikonfigurasi.
 *
 * N = 256 adalah RC4 asli; N = 128 adalah varian modulo 128 pada
 * makalah Hybrid Trio (ruang permutasi Z_128). Status internal
 * (S, i, j) bertahan antar panggilan crypt() sehingga data dapat
 * diproses per chunk secara streaming.
 */
final class Rc4
{
    /** @var int[] Permutasi state */
    private array $s = [];

    private int $i = 0;

    private int $j = 0;

    private int $n;

    public function __construct(string $key = '', int $n = 128)
    {
        $this->n = $n;

        if ($key !== '') {
            $this->reset($key);
        }
    }

    public function reset(string $key): void
    {
        $n = $this->n;

        if ($key === '') {
            throw new \InvalidArgumentException('Kunci RC4 tidak boleh kosong.');
        }

        $this->s = range(0, $n - 1);
        $this->i = 0;
        $this->j = 0;

        $keyLength = strlen($key);
        $j = 0;

        // KSA (Key Scheduling Algorithm)
        for ($i = 0; $i < $n; $i++) {
            $j = ($j + $this->s[$i] + ord($key[$i % $keyLength])) % $n;
            [$this->s[$i], $this->s[$j]] = [$this->s[$j], $this->s[$i]];
        }
    }

    /**
     * Satu byte keystream: PRGA (Pseudo-Random Generation Algorithm).
     */
    public function nextByte(): int
    {
        $n = $this->n;

        $this->i = ($this->i + 1) % $n;
        $this->j = ($this->j + $this->s[$this->i]) % $n;
        [$this->s[$this->i], $this->s[$this->j]] = [$this->s[$this->j], $this->s[$this->i]];

        $t = ($this->s[$this->i] + $this->s[$this->j]) % $n;

        return $this->s[$t];
    }

    /**
     * Enkripsi/dekripsi XOR; idempoten karena keystream sama.
     */
    public function crypt(string $data): string
    {
        $out = '';

        foreach (str_split($data) as $byte) {
            $out .= chr(ord($byte) ^ $this->nextByte());
        }

        return $out;
    }
}
