<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::with('algorithm')
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return view('materials.index', [
            'materials' => $materials,
            'activeCategory' => null,
        ]);
    }

    public function show(string $slug)
    {
        $material = Material::with('algorithm')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('materials.show', compact('material'));
    }

    /**
     * Daftar materi satu modul inti (dari navigasi sidebar).
     */
    public function category(string $category)
    {
        $category = DB::table('crypto_categories')
            ->where('slug', $category)
            ->first();

        abort_unless($category, 404);

        $materials = Material::with('algorithm')
            ->where('is_published', true)
            ->where('category_id', $category->id)
            ->orderBy('title')
            ->get();

        return view('materials.index', [
            'materials' => $materials,
            'activeCategory' => $category,
        ]);
    }
}
