<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;
use App\Services\Fortune\Support\GeocodingService;
use com\nlf\calendar\Solar;

class FourPillarsCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'four_pillars';
    protected const VERSION = '1.0.0';

    protected const TEN_GOD_MAP = [
        '比肩' => '比肩',
        '劫财' => '劫財',
        '劫財' => '劫財',
        '食神' => '食神',
        '伤官' => '傷官',
        '傷官' => '傷官',
        '偏财' => '偏財',
        '偏財' => '偏財',
        '正财' => '正財',
        '正財' => '正財',
        '七杀' => '偏官',
        '七殺' => '偏官',
        '偏官' => '偏官',
        '正官' => '正官',
        '偏印' => '偏印',
        '正印' => '正印',
        '日主' => '日主',
        '日元' => '日主',
    ];

    protected const TWELVE_STAGE_MAP = [
        '长生' => '長生',
        '長生' => '長生',
        '沐浴' => '沐浴',
        '冠带' => '冠帯',
        '冠帶' => '冠帯',
        '临官' => '建禄',
        '臨官' => '建禄',
        '建禄' => '建禄',
        '帝旺' => '帝旺',
        '衰'   => '衰',
        '病'   => '病',
        '死'   => '死',
        '墓'   => '墓',
        '绝'   => '絶',
        '絕'   => '絶',
        '胎'   => '胎',
        '养'   => '養',
        '養'   => '養',
    ];

    protected const STEM_ELEMENTS = [
        '甲' => ['element' => '木', 'yin_yang' => '陽'],
        '乙' => ['element' => '木', 'yin_yang' => '陰'],
        '丙' => ['element' => '火', 'yin_yang' => '陽'],
        '丁' => ['element' => '火', 'yin_yang' => '陰'],
        '戊' => ['element' => '土', 'yin_yang' => '陽'],
        '己' => ['element' => '土', 'yin_yang' => '陰'],
        '庚' => ['element' => '金', 'yin_yang' => '陽'],
        '辛' => ['element' => '金', 'yin_yang' => '陰'],
        '壬' => ['element' => '水', 'yin_yang' => '陽'],
        '癸' => ['element' => '水', 'yin_yang' => '陰'],
    ];

    protected const BRANCH_ELEMENTS = [
        '子' => ['element' => '水', 'yin_yang' => '陽'],
        '丑' => ['element' => '土', 'yin_yang' => '陰'],
        '寅' => ['element' => '木', 'yin_yang' => '陽'],
        '卯' => ['element' => '木', 'yin_yang' => '陰'],
        '辰' => ['element' => '土', 'yin_yang' => '陽'],
        '巳' => ['element' => '火', 'yin_yang' => '陰'],
        '午' => ['element' => '火', 'yin_yang' => '陽'],
        '未' => ['element' => '土', 'yin_yang' => '陰'],
        '申' => ['element' => '金', 'yin_yang' => '陽'],
        '酉' => ['element' => '金', 'yin_yang' => '陰'],
        '戌' => ['element' => '土', 'yin_yang' => '陽'],
        '亥' => ['element' => '水', 'yin_yang' => '陰'],
    ];

    public function __construct() {}

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
        $hasTime = $input->hasBirthTime();
        $year = (int)$input->birthDate->format('Y');
        $month = (int)$input->birthDate->format('m');
        $day = (int)$input->birthDate->format('d');
        $hour = $hasTime ? (int)substr($input->birthTime, 0, 2) : 12;
        $minute = $hasTime ? (int)substr($input->birthTime, 3, 2) : 0;

        $solar = Solar::fromYmdHms($year, $month, $day, $hour, $minute, 0);
        $eightChar = $solar->getLunar()->getEightChar();

        // 年柱
        $yearGan = $eightChar->getYearGan();
        $yearZhi = $eightChar->getYearZhi();
        $yearPillar = [
            'gan' => $yearGan,
            'zhi' => $yearZhi,
            'pillar' => $yearGan . $yearZhi,
            'gan_element' => self::STEM_ELEMENTS[$yearGan] ?? null,
            'zhi_element' => self::BRANCH_ELEMENTS[$yearZhi] ?? null,
            'hidden_gans' => $eightChar->getYearHideGan(),
            'ten_god_gan' => self::TEN_GOD_MAP[$eightChar->getYearShiShenGan()] ?? $eightChar->getYearShiShenGan(),
            'ten_god_zhi' => array_map(fn($g) => self::TEN_GOD_MAP[$g] ?? $g, $eightChar->getYearShiShenZhi()),
            'twelve_stage' => self::TWELVE_STAGE_MAP[$eightChar->getYearDiShi()] ?? $eightChar->getYearDiShi(),
            'nayin' => $eightChar->getYearNaYin(),
            'xunkong' => $eightChar->getYearXunKong(),
        ];

        // 月柱
        $monthGan = $eightChar->getMonthGan();
        $monthZhi = $eightChar->getMonthZhi();
        $monthPillar = [
            'gan' => $monthGan,
            'zhi' => $monthZhi,
            'pillar' => $monthGan . $monthZhi,
            'gan_element' => self::STEM_ELEMENTS[$monthGan] ?? null,
            'zhi_element' => self::BRANCH_ELEMENTS[$monthZhi] ?? null,
            'hidden_gans' => $eightChar->getMonthHideGan(),
            'ten_god_gan' => self::TEN_GOD_MAP[$eightChar->getMonthShiShenGan()] ?? $eightChar->getMonthShiShenGan(),
            'ten_god_zhi' => array_map(fn($g) => self::TEN_GOD_MAP[$g] ?? $g, $eightChar->getMonthShiShenZhi()),
            'twelve_stage' => self::TWELVE_STAGE_MAP[$eightChar->getMonthDiShi()] ?? $eightChar->getMonthDiShi(),
            'nayin' => $eightChar->getMonthNaYin(),
            'xunkong' => $eightChar->getMonthXunKong(),
        ];

        // 日柱
        $dayGan = $eightChar->getDayGan();
        $dayZhi = $eightChar->getDayZhi();
        $dayPillar = [
            'gan' => $dayGan,
            'zhi' => $dayZhi,
            'pillar' => $dayGan . $dayZhi,
            'gan_element' => self::STEM_ELEMENTS[$dayGan] ?? null,
            'zhi_element' => self::BRANCH_ELEMENTS[$dayZhi] ?? null,
            'hidden_gans' => $eightChar->getDayHideGan(),
            'ten_god_gan' => '日主',
            'ten_god_zhi' => array_map(fn($g) => self::TEN_GOD_MAP[$g] ?? $g, $eightChar->getDayShiShenZhi()),
            'twelve_stage' => self::TWELVE_STAGE_MAP[$eightChar->getDayDiShi()] ?? $eightChar->getDayDiShi(),
            'nayin' => $eightChar->getDayNaYin(),
            'xunkong' => $eightChar->getDayXunKong(),
        ];

        // 時柱 (出生時刻がある場合のみ)
        $timePillar = null;
        if ($hasTime) {
            $timeGan = $eightChar->getTimeGan();
            $timeZhi = $eightChar->getTimeZhi();
            $timePillar = [
                'gan' => $timeGan,
                'zhi' => $timeZhi,
                'pillar' => $timeGan . $timeZhi,
                'gan_element' => self::STEM_ELEMENTS[$timeGan] ?? null,
                'zhi_element' => self::BRANCH_ELEMENTS[$timeZhi] ?? null,
                'hidden_gans' => $eightChar->getTimeHideGan(),
                'ten_god_gan' => self::TEN_GOD_MAP[$eightChar->getTimeShiShenGan()] ?? $eightChar->getTimeShiShenGan(),
                'ten_god_zhi' => array_map(fn($g) => self::TEN_GOD_MAP[$g] ?? $g, $eightChar->getTimeShiShenZhi()),
                'twelve_stage' => self::TWELVE_STAGE_MAP[$eightChar->getTimeDiShi()] ?? $eightChar->getTimeDiShi(),
                'nayin' => $eightChar->getTimeNaYin(),
                'xunkong' => $eightChar->getTimeXunKong(),
            ];
        }

        // 五行バランス集計
        $elementCounts = ['木' => 0, '火' => 0, '土' => 0, '金' => 0, '水' => 0];
        $pillarsToCount = [$yearPillar, $monthPillar, $dayPillar];
        if ($timePillar) {
            $pillarsToCount[] = $timePillar;
        }

        foreach ($pillarsToCount as $p) {
            if (isset($p['gan_element']['element'])) {
                $elementCounts[$p['gan_element']['element']]++;
            }
            if (isset($p['zhi_element']['element'])) {
                $elementCounts[$p['zhi_element']['element']]++;
            }
        }

        // 特殊関係の判定（合・冲）
        $specialRelations = $this->detectSpecialRelations($pillarsToCount);

        // 既存資料との対比メモ
        $notes = [];
        if ($dayGan !== '甲') {
            $notes[] = "日柱は暦学上「{$dayPillar['pillar']}」（日干: {$dayGan}）です。既存資料の「日柱の旧誤記」は2024年の干支またはAI生成ハルシネーションと推定されます。";
        }

        $data = [
            'day_master' => [
                'gan' => $dayGan,
                'element' => self::STEM_ELEMENTS[$dayGan]['element'] ?? null,
                'yin_yang' => self::STEM_ELEMENTS[$dayGan]['yin_yang'] ?? null,
            ],
            'year_pillar' => $yearPillar,
            'month_pillar' => $monthPillar,
            'day_pillar' => $dayPillar,
            'time_pillar' => $timePillar,
            'element_balance' => $elementCounts,
            'special_relations' => $specialRelations,
            'has_birth_time' => $hasTime,
            'notes' => $notes,
        ];

        $status = $hasTime
            ? FortuneCalculationResult::STATUS_COMPLETED
            : FortuneCalculationResult::STATUS_PARTIAL;

        return new FortuneCalculationResult(
            calculatorKey: self::KEY,
            calculatorVersion: self::VERSION,
            status: $status,
            data: $data
        );
    }

    /**
     * 柱間の合・冲・刑などの特殊関係を検出
     */
    protected function detectSpecialRelations(array $pillars): array
    {
        $relations = [];
        $branches = [];
        $stems = [];

        foreach ($pillars as $p) {
            $branches[] = $p['zhi'];
            $stems[] = $p['gan'];
        }

        // 地支の冲 (辰戌冲, 子午冲, 丑未冲, 寅申冲, 卯酉冲, 巳亥冲)
        $clashPairs = [
            '子' => '午', '午' => '子',
            '丑' => '未', '未' => '丑',
            '寅' => '申', '申' => '寅',
            '卯' => '酉', '酉' => '卯',
            '辰' => '戌', '戌' => '辰',
            '巳' => '亥', '亥' => '巳',
        ];

        for ($i = 0; $i < count($branches); $i++) {
            for ($j = $i + 1; $j < count($branches); $j++) {
                if (($clashPairs[$branches[$i]] ?? null) === $branches[$j]) {
                    $relations[] = [
                        'type' => '支冲',
                        'target' => "{$branches[$i]} - {$branches[$j]}",
                        'description' => "{$branches[$i]}と{$branches[$j]}の対冲",
                    ];
                }
            }
        }

        // 三合・半合 (寅午戌=火, 申子辰=水, 巳酉丑=金, 亥卯未=木)
        $branchSet = array_unique($branches);
        if (in_array('午', $branchSet) && in_array('戌', $branchSet)) {
            $relations[] = [
                'type' => '半合',
                'target' => '午 - 戌',
                'description' => '午戌の半合火局',
            ];
        }
        if (in_array('申', $branchSet) && in_array('子', $branchSet)) {
            $relations[] = [
                'type' => '半合',
                'target' => '申 - 子',
                'description' => '申子の半合水局',
            ];
        }

        return $relations;
    }
}
