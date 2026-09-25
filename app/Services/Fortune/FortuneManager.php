<?php

namespace App\Services\Fortune;

use App\Models\BabyProfile;
use App\Models\FortuneCalculation;
use App\Services\Fortune\Calculators\FourPillarsCalculator;
use App\Services\Fortune\Calculators\NineStarKiCalculator;
use App\Services\Fortune\Calculators\NumerologyCalculator;
use App\Services\Fortune\Calculators\SanmeigakuCalculator;
use App\Services\Fortune\Calculators\ShukuyoCalculator;
use App\Services\Fortune\Calculators\WesternAstrologyCalculator;
use App\Services\Fortune\Calculators\ZiWeiDouShuCalculator;
use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;

class FortuneManager
{
    /**
     * @var array<string, FortuneCalculatorInterface>
     */
    protected array $calculators = [];

    public function __construct(
        NumerologyCalculator $numerology,
        ShukuyoCalculator $shukuyo,
        NineStarKiCalculator $nineStarKi,
        FourPillarsCalculator $fourPillars,
        SanmeigakuCalculator $sanmeigaku,
        WesternAstrologyCalculator $westernAstrology,
        ZiWeiDouShuCalculator $ziWeiDouShu
    ) {
        $this->registerCalculator($numerology);
        $this->registerCalculator($shukuyo);
        $this->registerCalculator($nineStarKi);
        $this->registerCalculator($fourPillars);
        $this->registerCalculator($sanmeigaku);
        $this->registerCalculator($westernAstrology);
        $this->registerCalculator($ziWeiDouShu);
    }

    public function registerCalculator(FortuneCalculatorInterface $calculator): void
    {
        $this->calculators[$calculator->getKey()] = $calculator;
    }

    /**
     * @return array<string, FortuneCalculatorInterface>
     */
    public function getCalculators(): array
    {
        return $this->calculators;
    }

    /**
     * 全占術を計算し暗号化保存する
     *
     * @return array<string, FortuneCalculationResult>
     */
    public function calculateAll(BabyProfile $profile, bool $force = false): array
    {
        $input = FortuneInput::fromProfile($profile);
        $inputHash = $input->generateHash();
        $results = [];

        foreach ($this->calculators as $key => $calculator) {
            $results[$key] = $this->calculateAndSave($profile, $calculator, $input, $inputHash, $force);
        }

        return $results;
    }

    /**
     * 特定の占術を計算し暗号化保存する
     */
    public function calculateSingle(BabyProfile $profile, string $calculatorKey, bool $force = false): FortuneCalculationResult
    {
        if (!isset($this->calculators[$calculatorKey])) {
            throw new \InvalidArgumentException("Calculator not found: {$calculatorKey}");
        }

        $input = FortuneInput::fromProfile($profile);
        $inputHash = $input->generateHash();

        return $this->calculateAndSave($profile, $this->calculators[$calculatorKey], $input, $inputHash, $force);
    }

    protected function calculateAndSave(
        BabyProfile $profile,
        FortuneCalculatorInterface $calculator,
        FortuneInput $input,
        string $inputHash,
        bool $force = false
    ): FortuneCalculationResult {
        $key = $calculator->getKey();

        // 既存の計算キャッシュチェック (同じ入力ハッシュかつ強制再計算でなければキャッシュ利用)
        if (!$force) {
            $existing = FortuneCalculation::where('baby_profile_id', $profile->id)
                ->where('calculator_key', $key)
                ->first();

            if ($existing && $existing->input_hash === $inputHash && $existing->status !== FortuneCalculationResult::STATUS_FAILED) {
                return new FortuneCalculationResult(
                    calculatorKey: $existing->calculator_key,
                    calculatorVersion: $existing->calculator_version,
                    status: $existing->status,
                    data: $existing->result_ciphertext ?? [],
                    errorCode: $existing->error_code,
                    calculatedAt: $existing->calculated_at?->toIso8601String()
                );
            }
        }

        try {
            $result = $calculator->calculate($input);
        } catch (\Throwable $e) {
            $result = new FortuneCalculationResult(
                calculatorKey: $key,
                calculatorVersion: $calculator->getVersion(),
                status: FortuneCalculationResult::STATUS_FAILED,
                data: [],
                errorCode: $e->getMessage()
            );
        }

        // DBに暗号化保存
        FortuneCalculation::updateOrCreate(
            [
                'baby_profile_id' => $profile->id,
                'calculator_key' => $key,
            ],
            [
                'calculator_version' => $result->calculatorVersion,
                'input_hash' => $inputHash,
                'status' => $result->status,
                'result_ciphertext' => $result->data,
                'error_code' => $result->errorCode,
                'calculated_at' => now(),
            ]
        );

        return $result;
    }
}
