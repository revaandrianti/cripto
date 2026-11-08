<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = DB::table('crypto_categories')
            ->orderBy('id')
            ->get();

        $algorithmCount = DB::table('crypto_algorithms')
            ->where('is_active', true)
            ->count();

        $materialCount = DB::table('materials')
            ->count();

        $historyCount = DB::table('encryption_histories')
            ->where('user_id', auth()->id())
            ->count();

        $recentActivities = DB::table('encryption_histories')
            ->leftJoin(
                'crypto_algorithms',
                'encryption_histories.algorithm_id',
                '=',
                'crypto_algorithms.id'
            )
            ->where('encryption_histories.user_id', auth()->id())
            ->select(
                'encryption_histories.*',
                'crypto_algorithms.name as algorithm_name'
            )
            ->latest('encryption_histories.created_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'categories',
            'algorithmCount',
            'materialCount',
            'historyCount',
            'recentActivities'
        ));
    }
}
