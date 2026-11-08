<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CryptoCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Block Cipher',
                'slug' => 'block-cipher',
                'description' => 'Kriptografi yang memproses data dalam blok.',
            ],
            [
                'name' => 'Stream Cipher',
                'slug' => 'stream-cipher',
                'description' => 'Kriptografi yang memproses data menggunakan keystream.',
            ],
            [
                'name' => 'Fungsi Utama Kriptografi',
                'slug' => 'fungsi-utama',
                'description' => 'Fungsi enkripsi, hashing, autentikasi, dan integritas.',
            ],
            [
                'name' => 'Dasar Matematika Algoritma',
                'slug' => 'dasar-matematika',
                'description' => 'Konsep matematika yang mendasari algoritma kriptografi.',
            ],
        ];

        foreach ($categories as $category) {
            DB::table('crypto_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
