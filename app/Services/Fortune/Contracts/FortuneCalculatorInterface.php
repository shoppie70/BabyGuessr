<?php

namespace App\Services\Fortune\Contracts;

use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;

interface FortuneCalculatorInterface
{
    /**
     * 占術の一意識別キー (例: 'numerology', 'shukuyo', 'four_pillars', etc.)
     */
    public function getKey(): string;

    /**
     * 計算エンジンのバージョン
     */
    public function getVersion(): string;

    /**
     * 基礎計算を実行し構造化データを返却
     */
    public function calculate(FortuneInput $input): FortuneCalculationResult;
}
