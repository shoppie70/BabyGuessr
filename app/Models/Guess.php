<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guess extends Model
{
    use HasFactory;

    public const RESULT_CORRECT = 'correct';
    public const RESULT_READING_MATCH = 'reading_match';
    public const RESULT_WRONG = 'wrong';

    protected $fillable = [
        'baby_profile_id',
        'challenger_name_encrypted',
        'guess_encrypted',
        'guess_hmac',
        'result',
        'session_identifier',
        'attempt_no',
    ];

    protected function casts(): array
    {
        return [
            'challenger_name_encrypted' => 'encrypted',
            'guess_encrypted' => 'encrypted',
            'attempt_no' => 'integer',
        ];
    }

    public function babyProfile(): BelongsTo
    {
        return $this->belongsTo(BabyProfile::class);
    }

    protected function challengerName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->challenger_name_encrypted,
            set: fn ($value) => ['challenger_name_encrypted' => $value],
        );
    }

    protected function guess(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->guess_encrypted,
            set: fn ($value) => ['guess_encrypted' => $value],
        );
    }

    public function isCorrect(): bool
    {
        return $this->result === self::RESULT_CORRECT;
    }

    public function isReadingMatch(): bool
    {
        return $this->result === self::RESULT_READING_MATCH;
    }

    public function isWrong(): bool
    {
        return $this->result === self::RESULT_WRONG;
    }
}
