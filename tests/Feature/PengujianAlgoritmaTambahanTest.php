<?php

namespace Tests\Feature;

use App\Crypto\AlgorithmRunner;
use App\Models\User;
use Database\Seeders\CryptoAlgorithmSeeder;
use Database\Seeders\CryptoCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PengujianAlgoritmaTambahanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CryptoCategorySeeder::class,
            CryptoAlgorithmSeeder::class,
        ]);

        $this->user = User::factory()->create();
    }

    #[Test]
    public function cmac_aes_cocok_vektor_rfc_4493(): void
    {
        // RFC 4493.4.1: kunci 2b7e1516..., pesan 6b c1 be e2 (16 byte)
        $key = hex2bin('2b7e151628aed2a6abf7158809cf4f3c');
        $msg = hex2bin('6bc1bee22e409f96e93d7e117393172a');

        $tag = AlgorithmRunner::cmacAes($key, $msg);

        $this->assertSame('070a16b46b4d4144f79bdd9dd04a287c', bin2hex($tag));
    }

    #[Test]
    public function des_phpseclib_roundtrip(): void
    {
        $response = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'des',
            'message' => '8 byte data',
            'key' => 'kunci',
            'action' => 'enkripsi',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('BERHASIL', $response->json('results.2.value'));
    }

    #[Test]
    public function trio_ajax_mengembalikan_paket_dan_trace(): void
    {
        $response = $this->actingAs($this->user)->postJson('/lab/trio', [
            'plaintext' => 'KRIPTO',
            'passphrase' => 'KUNCI',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertCount(6, $response->json('rows'));
        $this->assertSame(16, strlen(hex2bin($response->json('saltHex'))));
    }

    #[Test]
    public function trio_ajax_menolak_passphrase_pendek(): void
    {
        $this->actingAs($this->user)
            ->postJson('/lab/trio', ['plaintext' => 'x', 'passphrase' => 'abc'])
            ->assertStatus(422);
    }
}
