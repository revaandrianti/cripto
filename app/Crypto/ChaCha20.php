<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * ChaCha20 sesuai RFC 8439: kunci 32 byte, nonce 12 byte, counter 32-bit.
 * Keystream byte penuh 8-bit (tanpa proyeksi 0x7F) sehingga bekerja
 * pada data biner apa pun, bukan hanya ASCII 7-bit.
 */
final class ChaCha20
{
    private const CONSTANTS = [0x61707865, 0x3320646E, 0x79622D32, 0x6B206574];

    /** @var int[] Status 16 kata 32-bit */
    private array $state = [];

    private int $counter = 0;

    public function __construct(string $key32 = '', string $nonce12 = '', int $counter = 1)
    {
        if ($key32 !== '') {
            $this->reset($key32, $nonce12, $counter);
        }
    }

    public function reset(string $key32, string $nonce12, int $counter = 1): void
    {
        if (strlen($key32) !== 32) {
            throw new \InvalidArgumentException('Kunci ChaCha20 harus tepat 32 byte.');
        }

        if (strlen($nonce12) !== 12) {
            throw new \InvalidArgumentException('Nonce ChaCha20 harus tepat 12 byte (RFC 8439).');
        }

        $this->counter = $counter;
        $this->state = array_merge(
            self::CONSTANTS,
            array_values(unpack('V8', $key32)),
            [$counter],
            array_values(unpack('V3', $nonce12)),
        );
    }

    /**
     * Quarter round pada posisi (a, b, c, d) status.
     */
    private function quarterRound(array &$s, int $a, int $b, int $c, int $d): void
    {
        $s[$a] = ($s[$a] + $s[$b]) & 0xFFFFFFFF;
        $s[$d] = $this->rotl($s[$d] ^ $s[$a], 16);
        $s[$c] = ($s[$c] + $s[$d]) & 0xFFFFFFFF;
        $s[$b] = $this->rotl($s[$b] ^ $s[$c], 12);
        $s[$a] = ($s[$a] + $s[$b]) & 0xFFFFFFFF;
        $s[$d] = $this->rotl($s[$d] ^ $s[$a], 8);
        $s[$c] = ($s[$c] + $s[$d]) & 0xFFFFFFFF;
        $s[$b] = $this->rotl($s[$b] ^ $s[$c], 7);
    }

    private function rotl(int $value, int $shift): int
    {
        return (($value << $shift) | ($value >> (32 - $shift))) & 0xFFFFFFFF;
    }

    /**
     * Blok keystream 64 byte berikutnya (counter dinaikkan otomatis).
     */
    public function nextBlock(): string
    {
        // Counter berjalan harus menjadi bagian dari state sebelum
        // putaran, karena hasil akhir menjumlahkan kembali state awal.
        $this->state[12] = $this->counter & 0xFFFFFFFF;
        $working = $this->state;

        for ($round = 0; $round < 10; $round++) {
            // Putaran kolom
            $this->quarterRound($working, 0, 4, 8, 12);
            $this->quarterRound($working, 1, 5, 9, 13);
            $this->quarterRound($working, 2, 6, 10, 14);
            $this->quarterRound($working, 3, 7, 11, 15);
            // Putaran diagonal
            $this->quarterRound($working, 0, 5, 10, 15);
            $this->quarterRound($working, 1, 6, 11, 12);
            $this->quarterRound($working, 2, 7, 8, 13);
            $this->quarterRound($working, 3, 4, 9, 14);
        }

        $block = '';

        for ($i = 0; $i < 16; $i++) {
            $block .= pack('V', ($working[$i] + $this->state[$i]) & 0xFFFFFFFF);
        }

        $this->counter++;

        return $block;
    }

    /**
     * Enkripsi/dekripsi XOR dengan buffer blok 64 byte.
     */
    public function crypt(string $data): string
    {
        $out = '';
        $length = strlen($data);
        $offset = 0;

        while ($offset < $length) {
            $keystream = $this->nextBlock();
            $chunk = substr($data, $offset, 64);
            $out .= $chunk ^ substr($keystream, 0, strlen($chunk));
            $offset += 64;
        }

        return $out;
    }
}
