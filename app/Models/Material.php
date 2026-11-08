<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = [
        'category_id',
        'algorithm_id',
        'title',
        'slug',
        'content',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function algorithm(): BelongsTo
    {
        return $this->belongsTo(CryptoAlgorithm::class, 'algorithm_id');
    }
}
