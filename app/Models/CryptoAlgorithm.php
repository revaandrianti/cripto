<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoAlgorithm extends Model
{
    protected $table = 'crypto_algorithms';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'type',
        'description',
        'security_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'algorithm_id');
    }
}
