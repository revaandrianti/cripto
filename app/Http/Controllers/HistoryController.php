<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    /**
     * Halaman riwayat aktivitas pengguna yang sedang login.
     */
    public function index()
    {
        $status = request()->query('status', 'semua');

        $aktivitas = DB::table('encryption_histories')
            ->leftJoin(
                'crypto_algorithms',
                'encryption_histories.algorithm_id',
                '=',
                'crypto_algorithms.id'
            )
            ->where('encryption_histories.user_id', auth()->id())
            ->when(in_array($status, ['success', 'failed']), function ($query) use ($status) {
                return $query->where('encryption_histories.result_status', $status);
            })
            ->select(
                'encryption_histories.*',
                'crypto_algorithms.name as algorithm_name'
            )
            ->latest('encryption_histories.created_at')
            ->paginate(20)
            ->withQueryString();

        $ringkasan = [
            'semua' => DB::table('encryption_histories')->where('user_id', auth()->id())->count(),
            'success' => DB::table('encryption_histories')->where('user_id', auth()->id())->where('result_status', 'success')->count(),
            'failed' => DB::table('encryption_histories')->where('user_id', auth()->id())->where('result_status', 'failed')->count(),
        ];

        return view('history.index', compact('aktivitas', 'status', 'ringkasan'));
    }

    /**
     * Halaman daftar akun: siapa saja yang terdaftar di CryptoLab.
     */
    public function akun()
    {
        $pengguna = DB::table('users')
            ->leftJoin('encryption_histories', 'users.id', '=', 'encryption_histories.user_id')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at')
            ->selectRaw('users.id, users.name, users.email, users.created_at, count(encryption_histories.id) as aktivitas')
            ->orderBy('users.created_at')
            ->paginate(15);

        return view('history.akun', compact('pengguna'));
    }
}
