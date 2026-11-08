<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * HKDF (HMAC-based Extract-and-Expand Key Derivation Function)
 * sesuai RFC 5869.
 */
final class Hkdf
{
    /**
     * HKDF-Extract: PRK = HMAC-Hash(salt, IKM).
     */
    public static function extract(string $salt, string $ikm, string $algo = 'sha256'): string
    {
        return hash_hmac($algo, $ikm, $salt !== '' ? $salt : str_repeat("\0", strlen(hash($algo, '', true))), true);
    }

    /**
     * HKDF-Expand: OKM = T(1) || T(2) || ... dipotong sepanjang $length.
     * T(i) = HMAC-Hash(PRK, T(i-1) || info || i).
     */
    public static function expand(string $prk, string $info, int $length, string $algo = 'sha256'): string
    {
        $hashLen = strlen(hash($algo, '', true));

        if ($length <= 0) {
            return '';
        }

        if ($length > 255 * $hashLen) {
            throw new \InvalidArgumentException('Panjang OKM melebihi batas 255 * HashLen sesuai RFC 5869.');
        }

        $okm = '';
        $t = '';
        $counter = 1;

        while (strlen($okm) < $length) {
            $t = hash_hmac($algo, $t.$info.chr($counter), $prk, true);
            $okm .= $t;
            $counter++;
        }

        return substr($okm, 0, $length);
    }

    /**
     * Gabungan extract + expand sekaligus (menggunakan native hash_hkdf PHP).
     */
    public static function derive(string $ikm, string $salt, string $info, int $length, string $algo = 'sha256'): string
    {
        return hash_hkdf($algo, $ikm, $length, $info, $salt);
    }
}
