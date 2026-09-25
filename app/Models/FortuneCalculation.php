<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FortuneCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'baby_profile_id',
        'calculator_key',
        'calculator_version',
        'input_hash',
        'status',
        'result_ciphertext',
        'error_code',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'result_ciphertext' => 'encrypted:array',
            'calculated_at' => 'datetime',
        ];
    }

    public function babyProfile(): BelongsTo
    {
        return $this->belongsTo(BabyProfile::class);
    }
}
