<?php

namespace App\Services\Fortune\DTOs;

class FortuneCalculationResult
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $calculatorKey,
        public readonly string $calculatorVersion,
        public readonly string $status, // 'completed', 'partial', 'unavailable', 'failed'
        public readonly array $data,
        public readonly ?string $errorCode = null,
        public readonly ?string $calculatedAt = null
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isUnavailable(): bool
    {
        return $this->status === self::STATUS_UNAVAILABLE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function toArray(): array
    {
        return [
            'calculator_key' => $this->calculatorKey,
            'calculator_version' => $this->calculatorVersion,
            'status' => $this->status,
            'calculated_at' => $this->calculatedAt ?? now()->toIso8601String(),
            'error_code' => $this->errorCode,
            'data' => $this->data,
        ];
    }
}
