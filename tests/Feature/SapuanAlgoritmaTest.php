<?php

namespace Tests\Feature;

use App\Crypto\AlgorithmRunner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sapuan semua algoritma dalam katalog: setiap algoritma harus bisa
 * dijalankan lewat endpoint /lab/run tanpa galat, dan cipher simetris
 * harus pulih sempurna lewat paketDekripsi.
 */
class SapuanAlgoritmaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function semua_algoritma_tersedia_bisa_dijalankan(): void
    {
        $tersedia = AlgorithmRunner::daftarTersedia();
        $semua = array_keys(iterator_to_array((function () {
            foreach (AlgorithmRunner::katalog() as $algorithms) {
                foreach ($algorithms as $slug => $config) {
                    yield $slug => $config;
                }
            }
        })()));

        // Semua yang tersedia memang dijalankan; daftarTersedia mencakup
        // seluruh katalog pada build PHP ini (tanpa algoritma mati).
        foreach ($semua as $slug) {
            $this->assertContains($slug, $tersedia, "Algoritma {$slug} tidak tersedia.");
        }
    }

    #[Test]
    public function cipher_blok_dan_stream_pulih_bolak_balik(): void
    {
        $pesan = 'Aku Benci Indonesia';

        foreach (['idea', 'rc5', 'rc6', 'skipjack', 'cast-128', 'seed', 'serpent'] as $slug) {
            $this->postRoundtrip($slug, $pesan, "Roundtrip {$slug} gagal");
        }

        foreach (['rc4', 'a5-1', 'lfsr', 'otp', 'chacha20', 'salsa20'] as $slug) {
            $this->postRoundtrip($slug, $pesan, "Roundtrip stream {$slug} gagal");
        }
    }

    #[Test]
    public function algoritma_lainnya_berhasil_dijalankan(): void
    {
        $kasus = [
            'aes' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'ecb' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'cbc' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'cfb' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'ofb' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'ctr' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'aes-encryption' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'sha-256' => ['abc', '', 'hash'],
            'sha-512' => ['abc', '', 'hash'],
            'md5' => ['abc', '', 'hash'],
            'sha-1' => ['abc', '', 'hash'],
            'sha-3' => ['abc', '', 'hash'],
            'hmac' => ['abc', 'KUNCI', 'enkripsi'],
            'cmac-aes' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'rsa-oaep' => ['Aku Benci Indonesia', '', 'enkripsi'],
            'rsa-signature' => ['Aku Benci Indonesia', '', 'enkripsi'],
            'ecdsa' => ['Aku Benci Indonesia', '', 'enkripsi'],
            'ecdh' => ['Aku Benci Indonesia', '', 'enkripsi'],
            'dsa' => ['Aku Benci Indonesia', '', 'enkripsi'],
            'hybrid-trio' => ['Aku Benci Indonesia', 'KUNCI', 'enkripsi'],
            'modulo' => ['17', '12', 'enkripsi'],
            'faktorisasi-rsa' => ['3233', '', 'enkripsi'],
            'rabin' => ['123', '', 'enkripsi'],
            'diffie-hellman' => ['Aku Benci Indonesia', '', 'enkripsi'],
            'elgamal' => ['Aku', '', 'enkripsi'],
            'knapsack-merkle-hellman' => ['1010100011010001', 'KUNCI', 'enkripsi'],
            'ntru' => ['KRIPTO', '', 'enkripsi'],
        ];

        foreach ($kasus as $slug => [$pesan, $kunci, $aksi]) {
            $response = $this->actingAs($this->user)->postJson('/lab/run', [
                'algorithm' => $slug,
                'message' => $pesan,
                'key' => $kunci,
                'action' => $aksi,
            ]);

            $response->assertOk()->assertJson(['ok' => true]);
            $this->assertNotEmpty($response->json('results'), "Hasil {$slug} kosong");
        }
    }

    /**
     * Enkripsi lalu dekripsi paketDekripsi (meniru alur "Bolak-balik" UI).
     * Verifikasi round-trip diambil dari respons enkripsi.
     */
    private function postRoundtrip(string $slug, string $pesan, string $pesanGagal): void
    {
        $enkripsi = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => $slug,
            'message' => $pesan,
            'key' => 'KUNCI-PENGUJIAN',
            'action' => 'enkripsi',
        ]);

        $enkripsi->assertOk()->assertJson(['ok' => true]);

        $paket = $enkripsi->json('paketDekripsi');

        $this->assertNotNull($paket, "{$slug} tidak mengembalikan paketDekripsi");

        $this->assertStringContainsString(
            'BERHASIL',
            $enkripsi->json('results.2.value'),
            $pesanGagal,
        );

        $dekripsi = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => $slug,
            'message' => $paket,
            'key' => 'KUNCI-PENGUJIAN',
            'action' => 'dekripsi',
        ]);

        $dekripsi->assertOk()->assertJson(['ok' => true]);
        $this->assertSame($pesan, $dekripsi->json('results.0.value'), "Dekripsi {$slug} tidak pulih");
    }
}
