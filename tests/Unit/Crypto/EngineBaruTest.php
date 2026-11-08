<?php

namespace Tests\Unit\Crypto;

use App\Crypto\Cast128;
use App\Crypto\Ntru;
use App\Crypto\Seed;
use App\Crypto\Serpent;
use App\Crypto\Skipjack;
use PHPUnit\Framework\TestCase;

class EngineBaruTest extends TestCase
{
    public function test_skipjack_vektor_nist(): void
    {
        $skipjack = new Skipjack(hex2bin('00998877665544332211'));
        $ciphertext = $skipjack->enkripsiBlok(hex2bin('33221100ddccbbaa'));

        $this->assertSame('2587cae27a12d300', bin2hex($ciphertext));
        $this->assertSame(
            '33221100ddccbbaa',
            bin2hex($skipjack->dekripsiBlok($ciphertext)),
        );
    }

    public function test_seed_vektor_rfc4269_b1(): void
    {
        $seed = new Seed(str_repeat("\x00", 16));
        $ciphertext = $seed->enkripsiBlok(hex2bin('000102030405060708090A0B0C0D0E0F'));

        $this->assertSame('5ebac6e0054e166819aff1cc6d346cdb', bin2hex($ciphertext));
        $this->assertSame(
            '000102030405060708090a0b0c0d0e0f',
            bin2hex($seed->dekripsiBlok($ciphertext)),
        );
    }

    public function test_seed_vektor_rfc4269_b3(): void
    {
        $seed = new Seed(hex2bin('4706480851E61BE85D74BFB3FD956185'));
        $ciphertext = $seed->enkripsiBlok(hex2bin('83A2F8A288641FB9A4E9A5CC2F131C7D'));

        $this->assertSame('ee54d13ebcae706d226bc3142cd40d4a', bin2hex($ciphertext));
    }

    public function test_cast128_tiga_vektor_rfc2144(): void
    {
        $vektor = [
            ['0123456712345678234567893456789A', '238b4fe5847e44b2'],
            ['01234567123456782345000000000000', 'eb6a711a2c02271b'],
            ['01234567120000000000000000000000', '7ac816d16e9b302e'],
        ];

        foreach ($vektor as [$kunci, $harap]) {
            $cast = new Cast128(hex2bin($kunci));
            $ciphertext = $cast->enkripsiBlok(hex2bin('0123456789ABCDEF'));

            $this->assertSame($harap, bin2hex($ciphertext), "kunci {$kunci}");
            $this->assertSame(
                '0123456789abcdef',
                bin2hex($cast->dekripsiBlok($ciphertext)),
            );
        }
    }

    public function test_serpent_vektor_kernel_linux(): void
    {
        $serpent = new Serpent(hex2bin('000102030405060708090A0B0C0D0E0F'));
        $ciphertext = $serpent->enkripsiBlok(hex2bin('000102030405060708090A0B0C0D0E0F'));

        $this->assertSame('4c7d8a328072a22c823e4a1f3acda16d', bin2hex($ciphertext));
        $this->assertSame(
            '000102030405060708090a0b0c0d0e0f',
            bin2hex($serpent->dekripsiBlok($ciphertext)),
        );
    }

    public function test_ntru_contoh_klasik(): void
    {
        $ntru = new Ntru;

        // Contoh klasik NTRUEncrypt (Wikipedia): h, e, dan hasil dekripsi.
        $this->assertSame([8, 25, 22, 20, 12, 24, 15, 19, 12, 19, 16], $ntru->kunciPublik());

        // m = -1 + X^3 - X^4 - X^8 + X^9 + X^10, r = -1 + X^2 + X^3 + X^4 - X^5 - X^7
        $m = [-1, 0, 0, 1, -1, 0, 0, 0, -1, 1, 1];
        $r = [-1, 0, 1, 1, 1, -1, 0, -1, 0, 0, 0];
        $e = $ntru->enkripsi($m, $r);

        $this->assertSame([14, 11, 26, 24, 14, 16, 30, 7, 25, 6, 19], $e);
        $this->assertSame($m, $ntru->dekripsi($e));
    }

    public function test_ntru_sandian_teks_bolak_balik(): void
    {
        $ntru = new Ntru;

        for ($i = 0; $i < 25; $i++) {
            $teks = "Kripto-NTRU-{$i}";
            $polinomial = $ntru->teksKePolinomial($teks);
            $pulih = array_map(
                fn (array $m): array => $ntru->dekripsi($ntru->enkripsi($m, $ntru->rAcak())),
                $polinomial,
            );

            $this->assertSame($teks, $ntru->polinomialKeTeks($pulih));
        }
    }
}
