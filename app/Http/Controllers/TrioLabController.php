<?php

namespace App\Http\Controllers;

use App\Crypto\AlgorithmRunner;
use App\Crypto\StreamCipherFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TrioLabController extends Controller
{
    public function index(Request $request)
    {
        return $this->labView([
            'dipilih' => $request->query('algorithm'),
        ]);
    }

    /**
     * View lab dengan data katalog pengujian.
     */
    private function labView(array $data = [])
    {
        return view('lab.index', $data + [
            'katalog' => AlgorithmRunner::katalog(),
            'tersedia' => AlgorithmRunner::daftarTersedia(),
            'dipilih' => null,
        ]);
    }

    /**
     * Enkripsi Hybrid Trio via AJAX: hasil paket ETM + jejak per karakter.
     */
    public function trio(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plaintext' => 'required|string|max:5000',
            'passphrase' => 'required|string|min:4|max:255',
        ]);

        try {
            $bundle = StreamCipherFactory::enkripsi($data['plaintext'], $data['passphrase']);
            $trace = StreamCipherFactory::traceAscii7(
                $data['plaintext'],
                $data['passphrase'],
                $bundle['salt'],
                $bundle['nonce']
            );
        } catch (\InvalidArgumentException|RuntimeException $e) {
            $this->catatRiwayat(
                DB::table('crypto_algorithms')->where('slug', 'hybrid-trio')->value('id'),
                'enkripsi',
                'failed',
                $data['plaintext'],
                $e->getMessage(),
            );

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $this->catatRiwayat(
            DB::table('crypto_algorithms')->where('slug', 'hybrid-trio')->value('id'),
            'enkripsi',
            'success',
            $data['plaintext'],
        );

        return response()->json([
            'ok' => true,
            'ciphertextHex' => strtoupper(bin2hex($bundle['ciphertext'])),
            'saltHex' => strtoupper(bin2hex($bundle['salt'])),
            'nonceHex' => strtoupper(bin2hex($bundle['nonce'])),
            'tagHex' => strtoupper(bin2hex($bundle['tag'])),
            'rows' => $trace['rows'],
            'cipher' => $trace['cipher'],
        ]);
    }

    /**
     * Endpoint pengujian satu algoritma (dipanggil via fetch).
     */
    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'algorithm' => 'required|string|max:64',
            'message' => 'required|string|max:10000',
            'key' => 'nullable|string|max:255',
            'action' => 'required|in:enkripsi,dekripsi,hash',
            'log' => 'nullable|boolean',
        ]);

        // Pembatasan input khusus untuk mencegah DoS dan crash pada algoritma tertentu
        if ($data['algorithm'] === 'rsa-oaep' && strlen($data['message']) > 190) {
            return response()->json([
                'ok' => false,
                'error' => 'Pesan untuk RSA-OAEP 2048-bit maksimal 190 byte (sesuai batas OAEP).',
            ], 422);
        }

        if (in_array($data['algorithm'], ['ntru', 'elgamal'], true) && strlen($data['message']) > 256) {
            return response()->json([
                'ok' => false,
                'error' => "Demo edukasi {$data['algorithm']} dibatasi maksimal 256 karakter untuk performa CPU.",
            ], 422);
        }

        $shouldLog = $request->boolean('log', true);

        try {
            $hasil = AlgorithmRunner::jalankan(
                $data['algorithm'],
                $data['message'],
                $data['key'] ?? '',
                $data['action'],
            );
        } catch (\InvalidArgumentException|RuntimeException $e) {
            if ($shouldLog) {
                $this->catatRiwayat(
                    DB::table('crypto_algorithms')->where('slug', $data['algorithm'])->value('id'),
                    $data['action'],
                    'failed',
                    $data['message'],
                    $e->getMessage(),
                );
            }

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        if ($shouldLog) {
            $this->catatRiwayat(
                DB::table('crypto_algorithms')->where('slug', $data['algorithm'])->value('id'),
                $hasil['operation'],
                'success',
                $data['message'],
            );
        }

        return response()->json(['ok' => true] + $hasil);
    }

    public function proses(Request $request)
    {
        if (! $request->has('aksi') && $request->filled('plaintext')) {
            $request->merge(['aksi' => 'enkripsi']);
        }

        $data = $request->validate([
            'aksi' => 'required|in:enkripsi,dekripsi',
            'plaintext' => 'required_unless:aksi,dekripsi|nullable|string|max:10000',
            'passphrase' => 'required|string|min:4|max:255',
            'ciphertext' => 'required_if:aksi,dekripsi|nullable|string|max:40000',
            'salt' => ['required_if:aksi,dekripsi', 'nullable', 'string', 'size:32', 'regex:/^[0-9a-f]+$/i'],
            'nonce' => ['required_if:aksi,dekripsi', 'nullable', 'string', 'size:24', 'regex:/^[0-9a-f]+$/i'],
            'tag' => ['required_if:aksi,dekripsi', 'nullable', 'string', 'size:64', 'regex:/^[0-9a-f]+$/i'],
        ]);

        $algorithmId = DB::table('crypto_algorithms')
            ->where('slug', 'hybrid-trio')
            ->value('id');

        if ($data['aksi'] === 'enkripsi') {
            $bundle = StreamCipherFactory::enkripsi($data['plaintext'], $data['passphrase']);
            $trace = StreamCipherFactory::traceAscii7(
                $data['plaintext'],
                $data['passphrase'],
                $bundle['salt'],
                $bundle['nonce']
            );

            $this->catatRiwayat($algorithmId, 'enkripsi', 'success', $data['plaintext']);

            return $this->labView([
                'mode' => 'enkripsi',
                'plaintext' => $data['plaintext'],
                'bundle' => $bundle,
                'trace' => $trace,
            ]);
        }

        try {
            $plaintext = StreamCipherFactory::dekripsi([
                'salt' => hex2bin($data['salt']),
                'nonce' => hex2bin($data['nonce']),
                'ciphertext' => $this->ubahCiphertext($data['ciphertext']),
                'tag' => hex2bin($data['tag']),
            ], $data['passphrase']);
        } catch (RuntimeException $e) {
            $this->catatRiwayat($algorithmId, 'dekripsi', 'failed', $data['ciphertext'], $e->getMessage());

            return $this->labView([
                'mode' => 'dekripsi',
                'error' => $e->getMessage(),
                'input' => $request->only(['ciphertext', 'salt', 'nonce', 'tag']),
            ]);
        }

        $this->catatRiwayat($algorithmId, 'dekripsi', 'success', $plaintext);

        return $this->labView([
            'mode' => 'dekripsi',
            'plaintext' => $plaintext,
        ]);
    }

    /**
     * Terima ciphertext dalam bentuk teks ASCII, hex (0x...), atau base64.
     */
    private function ubahCiphertext(string $ciphertext): string
    {
        $ciphertext = trim($ciphertext);

        if (preg_match('/^(0x)?[0-9a-f]+$/i', $ciphertext) && strlen($ciphertext) % 2 === 0) {
            return hex2bin(preg_replace('/^0x/i', '', $ciphertext));
        }

        $decoded = base64_decode($ciphertext, true);

        return $decoded === false ? $ciphertext : $decoded;
    }

    private function catatRiwayat(
        ?int $algorithmId,
        string $operation,
        string $status,
        ?string $preview = null,
        ?string $error = null,
    ): void {
        if (! auth()->check()) {
            return;
        }

        DB::table('encryption_histories')->insert([
            'user_id' => auth()->id(),
            'algorithm_id' => $algorithmId,
            'operation' => $operation,
            'input_type' => 'text',
            'message_preview' => $preview === null ? null : mb_substr($preview, 0, 60),
            'result_status' => $status,
            'error_message' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
