<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FortuneReport extends Model
{
    use HasFactory;

    public const STATUS_NOT_GENERATED = 'not_generated';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'baby_profile_id',
        'status',
        'model',
        'report_ciphertext',
        'input_tokens',
        'output_tokens',
        'error_code',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'report_ciphertext' => 'encrypted:array',
            'generated_at' => 'datetime',
        ];
    }

    public function babyProfile(): BelongsTo
    {
        return $this->belongsTo(BabyProfile::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED && is_array($this->report_ciphertext);
    }

    public function hasReportBody(): bool
    {
        return is_array($this->report_ciphertext) && $this->report_ciphertext !== [];
    }
}
