<?php

namespace App\Services;

class GameJudgeResult
{
    public const CORRECT = 'correct';
    public const READING_MATCH = 'reading_match';
    public const WRONG = 'wrong';

    public function __construct(
        public readonly string $result, // 'correct', 'reading_match', 'wrong'
        public readonly string $message,
        public readonly string $guessHmac,
        public readonly string $normalizedKanji,
        public readonly string $normalizedKana
    ) {}

    public function isCorrect(): bool
    {
        return $this->result === self::CORRECT;
    }

    public function isReadingMatch(): bool
    {
        return $this->result === self::READING_MATCH;
    }

    public function isWrong(): bool
    {
        return $this->result === self::WRONG;
    }
}
