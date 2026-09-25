<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;

class NumerologyCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'numerology';
    protected const VERSION = '1.0.0';

    /**
     * ピタゴラス数秘術アルファベット対応表
     */
    protected const PYTHAGOREAN_TABLE = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9,
        'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'O' => 6, 'P' => 7, 'Q' => 8, 'R' => 9,
        'S' => 1, 'T' => 2, 'U' => 3, 'V' => 4, 'W' => 5, 'X' => 6, 'Y' => 7, 'Z' => 8,
    ];

    protected const VOWELS = ['A', 'I', 'U', 'E', 'O'];

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }

    public function calculate(FortuneInput $input): FortuneCalculationResult
    {
        $fullNameRoman = $input->getFullNameRomanWestern(); // 例: "HANA YAMADA"

        // 1. Life Path Number (生年月日の還元)
        $year = (int)$input->birthDate->format('Y');
        $month = (int)$input->birthDate->format('m');
        $day = (int)$input->birthDate->format('d');

        $yearReduced = $this->reduceNumber($year, false);
        $monthReduced = $this->reduceNumber($month, false);
        $dayReduced = $this->reduceNumber($day, false);

        $lifePathRaw = $yearReduced + $monthReduced + $dayReduced;
        $lifePath = $this->reduceNumberWithMaster($lifePathRaw);

        // 2. Birthday Number (生まれた日)
        $birthday = $this->reduceNumberWithMaster($day);

        // 3. 姓名分解とアルファベット計算 (名・姓)
        $givenRoman = strtoupper($input->givenNameRoman);
        $familyRoman = strtoupper($input->familyNameRoman);

        $givenVowelsSum = $this->sumVowels($givenRoman);
        $givenConsonantsSum = $this->sumConsonants($givenRoman);
        $givenTotalSum = $this->sumLetters($givenRoman);

        $familyVowelsSum = $this->sumVowels($familyRoman);
        $familyConsonantsSum = $this->sumConsonants($familyRoman);
        $familyTotalSum = $this->sumLetters($familyRoman);

        // 4. Destiny Number (表現数 / 運命数)
        $destinyRaw = $this->reduceNumber($givenTotalSum, false) + $this->reduceNumber($familyTotalSum, false);
        $destiny = $this->reduceNumberWithMaster($destinyRaw);

        // 5. Soul Number (ハート数 / 魂の望み)
        $soulRaw = $this->reduceNumber($givenVowelsSum, false) + $this->reduceNumber($familyVowelsSum, false);
        $soul = $this->reduceNumberWithMaster($soulRaw);

        // 6. Personality Number (人格数)
        $personalityRaw = $this->reduceNumber($givenConsonantsSum, false) + $this->reduceNumber($familyConsonantsSum, false);
        $personality = $this->reduceNumberWithMaster($personalityRaw);

        // 7. Maturity Number (成熟数: Life Path + Destiny)
        // 既存資料では 11 + 9 = 20 -> 2 (2 / 20)
        $lpBase = $lifePath['raw'] ?? $lifePath['number'];
        $destinyBase = $destiny['number'];
        $maturityRaw = $lpBase + $destinyBase;
        $maturity = $this->reduceNumberWithMaster($maturityRaw);

        $data = [
            'name_used' => $fullNameRoman,
            'given_name_roman' => $givenRoman,
            'family_name_roman' => $familyRoman,
            'life_path' => [
                'display' => $lifePath['display'],
                'primary' => $lifePath['number'],
                'master' => $lifePath['is_master'] ? $lifePath['raw'] : null,
                'breakdown' => [
                    'year' => $yearReduced,
                    'month' => $monthReduced,
                    'day' => $dayReduced,
                ],
            ],
            'destiny' => [
                'display' => $destiny['display'],
                'primary' => $destiny['number'],
                'master' => $destiny['is_master'] ? $destiny['raw'] : null,
            ],
            'soul' => [
                'display' => $soul['display'],
                'primary' => $soul['number'],
                'master' => $soul['is_master'] ? $soul['raw'] : null,
            ],
            'personality' => [
                'display' => $personality['display'],
                'primary' => $personality['number'],
                'master' => $personality['is_master'] ? $personality['raw'] : null,
            ],
            'birthday' => [
                'display' => (string)$birthday['number'],
                'primary' => $birthday['number'],
            ],
            'maturity' => [
                'display' => $maturity['raw'] === 20 ? '2 / 20' : $maturity['display'],
                'primary' => $maturity['number'],
                'raw_sum' => $maturityRaw,
            ],
        ];

        return new FortuneCalculationResult(
            calculatorKey: self::KEY,
            calculatorVersion: self::VERSION,
            status: FortuneCalculationResult::STATUS_COMPLETED,
            data: $data,
            calculatedAt: now()->toIso8601String()
        );
    }

    /**
     * 文字列の母音のみの合計
     */
    protected function sumVowels(string $word): int
    {
        $sum = 0;
        foreach (str_split($word) as $char) {
            if (in_array($char, self::VOWELS, true)) {
                $sum += self::PYTHAGOREAN_TABLE[$char] ?? 0;
            }
        }
        return $sum;
    }

    /**
     * 文字列の子音のみの合計 (Yも子音として扱う)
     */
    protected function sumConsonants(string $word): int
    {
        $sum = 0;
        foreach (str_split($word) as $char) {
            if (!in_array($char, self::VOWELS, true) && isset(self::PYTHAGOREAN_TABLE[$char])) {
                $sum += self::PYTHAGOREAN_TABLE[$char];
            }
        }
        return $sum;
    }

    /**
     * 文字列全体の文字数秘合計
     */
    protected function sumLetters(string $word): int
    {
        $sum = 0;
        foreach (str_split($word) as $char) {
            $sum += self::PYTHAGOREAN_TABLE[$char] ?? 0;
        }
        return $sum;
    }

    /**
     * 単数（1〜9）への還元
     */
    protected function reduceNumber(int $num, bool $keepMaster = false): int
    {
        while ($num > 9) {
            if ($keepMaster && in_array($num, [11, 22, 33], true)) {
                return $num;
            }
            $num = array_sum(str_split((string)$num));
        }
        return $num;
    }

    /**
     * マスターナンバー (11, 22, 33) を考慮した還元
     * 
     * @return array{number: int, display: string, is_master: bool, raw: int}
     */
    protected function reduceNumberWithMaster(int $num): array
    {
        $raw = $num;
        $isMaster = false;

        while ($num > 9) {
            if (in_array($num, [11, 22, 33], true)) {
                $isMaster = true;
                break;
            }
            $num = array_sum(str_split((string)$num));
        }

        $single = $isMaster ? array_sum(str_split((string)$num)) : $num;
        $display = $isMaster ? ($num . ' / ' . $single) : (string)$single;

        return [
            'number' => $single,
            'display' => $display,
            'is_master' => $isMaster,
            'raw' => $raw,
        ];
    }
}
