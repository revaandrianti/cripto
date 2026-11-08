<?php

namespace Tests\Feature;

use App\Crypto\StreamCipherFactory;
use App\Models\User;
use Database\Seeders\CryptoAlgorithmSeeder;
use Database\Seeders\CryptoCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrioLabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CryptoCategorySeeder::class,
            CryptoAlgorithmSeeder::class,
        ]);

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
    }

    #[Test]
    public function halaman_lab_menuntut_login(): void
    {
        $this->get('/lab')->assertRedirect('/login');
    }

    #[Test]
    public function form_enkripsi_menampilkan_paket_dan_jejak_komputasi(): void
    {
        $response = $this->actingAs($this->user)->post('/lab', [
            'aksi' => 'enkripsi',
            'plaintext' => 'KRIPTO',
            'passphrase' => 'KUNCI',
        ]);

        $response->assertOk();

        $bundle = $response->viewData('bundle');
        $trace = $response->viewData('trace');

        // Jejak harus memenuhi identitas kaskade untuk setiap karakter
        // dan tetap berada dalam rentang ASCII 7-bit.
        $this->assertCount(6, $trace['rows']);

        foreach ($trace['rows'] as $row) {
            $this->assertSame(
                $row['plain'] ^ $row['z_lfsr'] ^ $row['z_rc4'] ^ $row['z_chacha'],
                $row['cipher']
            );
            $this->assertLessThan(128, $row['cipher']);
        }

        $this->assertSame(16, strlen($bundle['salt']));
        $this->assertSame(12, strlen($bundle['nonce']));

        $this->assertDatabaseHas('encryption_histories', [
            'user_id' => $this->user->id,
            'operation' => 'enkripsi',
            'result_status' => 'success',
        ]);
    }

    #[Test]
    public function dekripsi_dengan_paket_sah_mengembalikan_plaintext(): void
    {
        $bundle = StreamCipherFactory::enkripsi('Pesan uji lab', 'frasa-sandi');

        $response = $this->actingAs($this->user)->post('/lab', [
            'aksi' => 'dekripsi',
            'passphrase' => 'frasa-sandi',
            'ciphertext' => bin2hex($bundle['ciphertext']),
            'salt' => bin2hex($bundle['salt']),
            'nonce' => bin2hex($bundle['nonce']),
            'tag' => bin2hex($bundle['tag']),
        ]);

        $response->assertOk();
        $this->assertSame('Pesan uji lab', $response->viewData('plaintext'));

        $this->assertDatabaseHas('encryption_histories', [
            'user_id' => $this->user->id,
            'operation' => 'dekripsi',
            'result_status' => 'success',
        ]);
    }

    #[Test]
    public function tampering_tag_tercatat_sebagai_kegagalan(): void
    {
        $bundle = StreamCipherFactory::enkripsi('integritas', 'frasa-sandi');
        $tag = $bundle['tag'];
        $tag[0] = $tag[0] ^ "\x01";

        $response = $this->actingAs($this->user)->post('/lab', [
            'aksi' => 'dekripsi',
            'passphrase' => 'frasa-sandi',
            'ciphertext' => bin2hex($bundle['ciphertext']),
            'salt' => bin2hex($bundle['salt']),
            'nonce' => bin2hex($bundle['nonce']),
            'tag' => bin2hex($tag),
        ]);

        $response->assertOk();
        $this->assertNotNull($response->viewData('error'));

        $this->assertDatabaseHas('encryption_histories', [
            'user_id' => $this->user->id,
            'operation' => 'dekripsi',
            'result_status' => 'failed',
        ]);
    }
}
