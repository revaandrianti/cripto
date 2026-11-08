<?php

namespace Tests\Unit\Crypto;

use App\Crypto\A51;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class A51Test extends TestCase
{
    /**
     * Jejak komputasi Bab V makalah Hybrid Trio: status awal
     * R1 = 0x55555, R2 = 0x333333, R3 = 0x787878, tujuh detak per
     * karakter (mode ASCII 7-bit) harus menghasilkan byte keystream
     * 108, 21, 60, 0, 59, 98.
     */
    #[Test]
    public function jejak_makalah_bab_v_terreproduksi(): void
    {
        $a51 = new A51;
        $a51->setRegister(0x55555, 0x333333, 0x787878);

        $keystream = [];
        for ($i = 0; $i < 6; $i++) {
            $keystream[] = $a51->keystreamByte(7);
        }

        $this->assertSame([108, 21, 60, 0, 59, 98], $keystream);
    }

    #[Test]
    public function crypt_bersifat_involutori(): void
    {
        $encryptor = new A51('a51demo1');
        $decryptor = new A51('a51demo1');
        $data = random_bytes(64);

        $this->assertSame($data, $decryptor->crypt($encryptor->crypt($data)));
    }

    #[Test]
    public function reset_dengan_kunci_sama_menghasilkan_keystream_sama(): void
    {
        $a = new A51('kunciabc');
        $b = new A51('kunciabc');

        $this->assertSame($a->crypt(str_repeat("\0", 32)), $b->crypt(str_repeat("\0", 32)));
    }

    #[Test]
    public function kunci_bukan_8_byte_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new A51('pendek');
    }
}
