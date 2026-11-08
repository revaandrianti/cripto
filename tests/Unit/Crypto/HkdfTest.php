<?php

namespace Tests\Unit\Crypto;

use App\Crypto\Hkdf;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HkdfTest extends TestCase
{
    /**
     * Vektor uji resmi RFC 5869 Lampiran A, Uji Kasus 1 (SHA-256).
     */
    #[Test]
    public function cocok_dengan_vektor_uji_rfc_5869_kasus_1(): void
    {
        $ikm = str_repeat("\x0b", 22);
        $salt = hex2bin('000102030405060708090a0b0c');
        $info = hex2bin('f0f1f2f3f4f5f6f7f8f9');

        $prk = Hkdf::extract($salt, $ikm);
        $this->assertSame(
            '077709362c2e32df0ddc3f0dc47bba6390b6c73bb50f9c3122ec844ad7c2b3e5',
            bin2hex($prk),
            'PRK harus cocok dengan RFC 5869 Kasus 1.'
        );

        $okm = Hkdf::expand($prk, $info, 42);
        $this->assertSame(
            '3cb25f25faacd57a90434f64d0362f2a2d2d0a90cf1a5a4c5db02d56ecc4c5bf34007208d5b887185865',
            bin2hex($okm),
            'OKM harus cocok dengan RFC 5869 Kasus 1.'
        );
    }

    /**
     * Vektor uji resmi RFC 5869 Lampiran A, Uji Kasus 3 (SHA-256,
     * tanpa salt dan tanpa info — salt diganti nol sepanjang HashLen).
     */
    #[Test]
    public function cocok_dengan_vektor_uji_rfc_5869_kasus_3(): void
    {
        $ikm = str_repeat("\x0b", 22);

        $prk = Hkdf::extract('', $ikm);
        $this->assertSame(
            '19ef24a32c717b167f33a91d6f648bdf96596776afdb6377ac434c1c293ccb04',
            bin2hex($prk)
        );

        $okm = Hkdf::expand($prk, '', 42);
        $this->assertSame(
            '8da4e775a563c18f715f802a063c5a31b8a11f5c5ee1879ec3454e5f3c738d2d9d201395faa4b61a96c8',
            bin2hex($okm)
        );
    }

    #[Test]
    public function subkunci_dengan_info_berbeda_menghasilkan_byte_berbeda(): void
    {
        $a = Hkdf::expand('prk-statis', 'INFO-A', 32);
        $b = Hkdf::expand('prk-statis', 'INFO-B', 32);

        $this->assertNotSame($a, $b);
        $this->assertSame(32, strlen($a));
    }
}
