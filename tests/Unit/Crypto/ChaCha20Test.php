<?php

namespace Tests\Unit\Crypto;

use App\Crypto\ChaCha20;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChaCha20Test extends TestCase
{
    /**
     * Vektor uji resmi RFC 8439 bagian 2.4.2: enkripsi pesan
     * "sunscreen" dengan kunci 00..1f, nonce 00 00 00 00 00 00 00 4a
     * 00 00 00 00, counter 1.
     */
    #[Test]
    public function cocok_dengan_vektor_uji_rfc_8439(): void
    {
        $key = pack('C*', ...range(0x00, 0x1F));
        $nonce = hex2bin('000000000000004a00000000');
        $plaintext = "Ladies and Gentlemen of the class of '99: If I could offer you only one tip for the future, sunscreen would be it.";

        $chacha = new ChaCha20($key, $nonce, 1);
        $ciphertext = $chacha->crypt($plaintext);

        $expected = '6e2e359a2568f98041ba0728dd0d6981e97e7aec1d4360c20a27afccfd9fae0bf91b65c5524733ab8f593dabcd62b3571639d624e65152ab8f530c359f0861d807ca0dbf500d6a6156a38e088a22b65e52bc514d16ccf806818ce91ab77937365af90bbf74a35be6b40b8eedf2785e42874d';

        $this->assertSame($expected, bin2hex($ciphertext));
    }

    /**
     * Vektor uji resmi RFC 8439 bagian 2.3.2: fungsi blok dengan
     * nonce 00 00 00 09 00 00 00 4a 00 00 00 00 menghasilkan blok
     * keystream 10f1e7e4...
     */
    #[Test]
    public function blok_keystream_cocok_vektor_rfc_8439_paragraf_2_3_2(): void
    {
        $key = pack('C*', ...range(0x00, 0x1F));
        $nonce = hex2bin('000000090000004a00000000');

        $chacha = new ChaCha20($key, $nonce, 1);
        $block = $chacha->crypt(str_repeat("\0", 64));

        $this->assertSame(
            '10f1e7e4d13b5915500fdd1fa32071c4c7d1f4c733c068030422aa9ac3d46c4ed2826446079faa0914c2d705d98b02a2b5129cd1de164eb9cbd083e8a2503c4e',
            bin2hex($block)
        );
    }

    #[Test]
    public function crypt_bersifat_involutori(): void
    {
        $key = random_bytes(32);
        $nonce = random_bytes(12);
        $data = random_bytes(200); // lebih dari satu blok 64 byte

        $encryptor = new ChaCha20($key, $nonce);
        $decryptor = new ChaCha20($key, $nonce);

        $this->assertSame($data, $decryptor->crypt($encryptor->crypt($data)));
    }

    #[Test]
    public function nonce_bukan_12_byte_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ChaCha20(str_repeat('k', 32), 'pendek');
    }
}
