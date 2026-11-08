<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CryptoAlgorithmSeeder;
use Database\Seeders\CryptoCategorySeeder;
use Database\Seeders\CryptoMaterialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PengujianAlgoritmaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CryptoCategorySeeder::class,
            CryptoAlgorithmSeeder::class,
            CryptoMaterialSeeder::class,
        ]);

        $this->user = User::factory()->create();
    }

    #[Test]
    public function hash_sha256_menghasilkan_digest_yang_benar(): void
    {
        $response = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'sha-256',
            'message' => 'abc',
            'action' => 'hash',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(
            'BA7816BF8F01CFEA414140DE5DAE2223B00361A396177A9CB410FF61F20015AD',
            $response->json('results.0.value')
        );
    }

    #[Test]
    public function rc4_stream_roundtrip_berhasil(): void
    {
        $response = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'rc4',
            'message' => 'Pesan uji stream',
            'key' => 'kunci-rahasia',
            'action' => 'enkripsi',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('BERHASIL', $response->json('results.2.value'));
    }

    #[Test]
    public function aes_gcm_dapat_didekripsi_kembali(): void
    {
        $enkripsi = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'aes',
            'message' => 'rahasia aes',
            'key' => 'passphrase',
            'action' => 'enkripsi',
        ]);

        $enkripsi->assertOk();

        $paket = $this->ambilHasil($enkripsi->json('results'), 'Paket dekripsi (hex, iv||ct||tag)');

        $dekripsi = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'aes',
            'message' => strtolower($paket),
            'key' => 'passphrase',
            'action' => 'dekripsi',
        ]);

        $dekripsi->assertOk();
        $this->assertSame('rahasia aes', $dekripsi->json('results.0.value'));
    }

    #[Test]
    public function ecb_dan_cbc_menghasilkan_pola_berbeda(): void
    {
        $pesan = str_repeat('AABBCCDD', 8);

        $ecb = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'ecb', 'message' => $pesan, 'key' => 'k', 'action' => 'enkripsi',
        ]);
        $cbc = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'cbc', 'message' => $pesan, 'key' => 'k', 'action' => 'enkripsi',
        ]);

        $this->assertNotSame(
            $this->ambilHasil($ecb->json('results'), 'Ciphertext (hex)'),
            $this->ambilHasil($cbc->json('results'), 'Ciphertext (hex)'),
            'ECB dan CBC harus menghasilkan ciphertext berbeda untuk pesan yang sama.'
        );
    }

    #[Test]
    public function modulo_menghitung_sisa_dengan_benar(): void
    {
        $response = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'modulo',
            'message' => '17',
            'key' => '5',
            'action' => 'enkripsi',
        ]);

        $response->assertOk();
        $this->assertSame('2', $response->json('results.0.value'));
    }

    #[Test]
    public function hybrid_trio_menampilkan_verifikasi_berhasil(): void
    {
        $response = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'hybrid-trio',
            'message' => 'KRIPTO',
            'key' => 'KUNCI',
            'action' => 'enkripsi',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('BERHASIL', $response->json('results.4.value'));
    }

    #[Test]
    public function algoritma_tak_dikenal_menghasilkan_422(): void
    {
        $this->actingAs($this->user)
            ->postJson('/lab/run', [
                'algorithm' => 'tidak-ada',
                'message' => 'x',
                'action' => 'hash',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function halaman_pengujian_menampilkan_katalog(): void
    {
        $response = $this->actingAs($this->user)->get('/lab');

        $response->assertOk();
        $response->assertSee('Pengujian Algoritma');
        $response->assertSee('AES-256-GCM');
        $response->assertSee('Hybrid Trio');
    }

    #[Test]
    public function halaman_kategori_materi_difilter_per_modul(): void
    {
        $response = $this->get('/materials/kategori/stream-cipher');

        $response->assertOk();
        $response->assertSee('Stream Cipher');
        $response->assertSee('RC4 Stream Cipher');
        $response->assertDontSee('Aritmetika Modulo');
    }

    #[Test]
    public function rsa_oaep_menolak_pesan_melebihi_190_byte(): void
    {
        $this->actingAs($this->user)
            ->postJson('/lab/run', [
                'algorithm' => 'rsa-oaep',
                'message' => str_repeat('A', 195),
                'key' => '',
                'action' => 'enkripsi',
            ])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
            ]);
    }

    #[Test]
    public function ntru_menolak_pesan_melebihi_256_karakter(): void
    {
        $this->actingAs($this->user)
            ->postJson('/lab/run', [
                'algorithm' => 'ntru',
                'message' => str_repeat('B', 300),
                'key' => '',
                'action' => 'enkripsi',
            ])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
            ]);
    }

    #[Test]
    public function live_typing_tanpa_log_tidak_menyimpan_riwayat(): void
    {
        $awal = DB::table('encryption_histories')->count();

        $response = $this->actingAs($this->user)->postJson('/lab/run', [
            'algorithm' => 'modulo',
            'message' => '10',
            'key' => '3',
            'action' => 'enkripsi',
            'log' => false,
        ]);

        $response->assertOk();
        $this->assertSame($awal, DB::table('encryption_histories')->count());
    }

    #[Test]
    public function kategori_tak_dikenal_menghasilkan_404(): void
    {
        $this->get('/materials/kategori/tidak-ada')->assertNotFound();
    }

    private function ambilHasil(array $results, string $label): string
    {
        foreach ($results as $result) {
            if ($result['label'] === $label) {
                return $result['value'];
            }
        }

        $this->fail("Kolom hasil tidak ditemukan: {$label}");
    }
}
