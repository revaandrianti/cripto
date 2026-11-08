<?php

namespace Tests\Unit\Crypto;

use App\Crypto\Rc4;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Rc4Test extends TestCase
{
    /**
     * Jejak komputasi Bab V makalah Hybrid Trio: kunci "KUNCI" pada
     * N = 128 menghasilkan byte keystream S[t] 122, 88, 0, 79, 12, 84.
     */
    #[Test]
    public function jejak_makalah_bab_v_terreproduksi(): void
    {
        $rc4 = new Rc4('KUNCI', 128);

        $keystream = [];
        for ($i = 0; $i < 6; $i++) {
            $keystream[] = $rc4->nextByte();
        }

        $this->assertSame([122, 88, 0, 79, 12, 84], $keystream);
    }

    /**
     * Vektor uji klasik RC4 (N = 256): kunci "Key" menghasilkan
     * keystream EB 9F 77 81 B7 34 CA 72 A7 19.
     */
    #[Test]
    public function vektor_uji_klasik_rc4_256(): void
    {
        $rc4 = new Rc4('Key', 256);

        $keystream = [];
        for ($i = 0; $i < 10; $i++) {
            $keystream[] = $rc4->nextByte();
        }

        $this->assertSame([0xEB, 0x9F, 0x77, 0x81, 0xB7, 0x34, 0xCA, 0x72, 0xA7, 0x19], $keystream);
    }

    #[Test]
    public function crypt_bersifat_involutori(): void
    {
        $encryptor = new Rc4('kunci-rc4-128', 128);
        $decryptor = new Rc4('kunci-rc4-128', 128);
        $data = 'Pesan rahasia dengan karakter ASCII.';

        $this->assertSame($data, $decryptor->crypt($encryptor->crypt($data)));
    }
}
