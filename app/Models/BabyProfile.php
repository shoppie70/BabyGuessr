<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BabyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_name_encrypted',
        'given_name_encrypted',
        'family_name_kana_encrypted',
        'given_name_kana_encrypted',
        'given_name_hmac',
        'given_name_kana_hmac',
        'birth_date',
        'birth_time',
        'sex',
        'birth_place_encrypted',
        'birth_weight',
        'status',
        'game_token',
        'manage_token',
        'diagnostics_token',
    ];

    protected function casts(): array
    {
        return [
            'family_name_encrypted' => 'encrypted',
            'given_name_encrypted' => 'encrypted',
            'family_name_kana_encrypted' => 'encrypted',
            'given_name_kana_encrypted' => 'encrypted',
            'birth_place_encrypted' => 'encrypted',
            'birth_date' => 'date',
            'birth_weight' => 'integer',
        ];
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(Guess::class);
    }

    /**
     * Alias for plain attribute access
     */
    protected function familyName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->family_name_encrypted,
            set: fn ($value) => ['family_name_encrypted' => $value],
        );
    }

    protected function givenName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->given_name_encrypted,
            set: fn ($value) => ['given_name_encrypted' => $value],
        );
    }

    protected function familyNameKana(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->family_name_kana_encrypted,
            set: fn ($value) => ['family_name_kana_encrypted' => $value],
        );
    }

    protected function givenNameKana(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->given_name_kana_encrypted,
            set: fn ($value) => ['given_name_kana_encrypted' => $value],
        );
    }

    protected function birthPlace(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birth_place_encrypted,
            set: fn ($value) => ['birth_place_encrypted' => $value],
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->family_name_encrypted ?? '') . ' ' . ($this->given_name_encrypted ?? ''));
    }

    public function getFullNameKanaAttribute(): string
    {
        return trim(($this->family_name_kana_encrypted ?? '') . ' ' . ($this->given_name_kana_encrypted ?? ''));
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isRevealed(): bool
    {
        return $this->status === 'revealed';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
