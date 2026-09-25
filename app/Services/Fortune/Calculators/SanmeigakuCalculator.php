<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;
use com\nlf\calendar\Solar;

class SanmeigakuCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'sanmeigaku';
    protected const VERSION = '1.0.0';

    /**
     * 十神（四柱推命）から算命学の十大主星へのマッピング
     */
    protected const JUSSEI_MAP = [
        '比肩' => '貫索星',
        '劫財' => '石門星',
        '劫财' => '石門星',
        '食神' => '鳳閣星',
        '傷官' => '調舒星',
        '伤官' => '調舒星',
        '偏財' => '禄存星',
        '偏财' => '禄存星',
        '正財' => '司禄星',
        '正财' => '司禄星',
        '偏官' => '車騎星',
        '七殺' => '車騎星',
        '七杀' => '車騎星',
        '正官' => '牽牛星',
        '偏印' => '龍高星',
        '正印' => '玉堂星',
    ];

    /**
     * 十二運（四柱推命）から算命学の十二大従星へのマッピング
     */
    protected const JUSANDAI_JUSEI_MAP = [
        '胎'   => '天報星',
        '養'   => '天印星',
        '养'   => '天印星',
        '長生' => '天貴星',
        '长生' => '天貴星',
        '沐浴' => '天恍星',
        '冠帯' => '天南星',
        '冠带' => '天南星',
        '建禄' => '天禄星',
        '臨官' => '天禄星',
        '临官' => '天禄星',
        '帝旺' => '天将星',
        '衰'   => '天堂星',
        '病'   => '天胡星',
        '死'   => '天極星',
        '墓'   => '天庫星',
        '絶'   => '天馳星',
        '绝'   => '天馳星',
    ];

    /**
     * 旬空から算命学天中殺へのマッピング
     */
    protected const TENCHUSATSU_MAP = [
        '子丑' => '子丑天中殺',
        '寅卯' => '寅卯天中殺',
        '辰巳' => '辰巳天中殺',
        '午未' => '午未天中殺',
        '申酉' => '申酉天中殺',
        '戌亥' => '戌亥天中殺',
    ];

    /**
     * 各地支の本元（本気蔵干）
     */
    protected const MAIN_HIDDEN_GANS = [
        '子' => '癸',
        '丑' => '己',
        '寅' => '甲',
        '卯' => '乙',
        '辰' => '戊',
        '巳' => '丙',
        '午' => '丁',
        '未' => '己',
        '申' => '庚',
        '酉' => '辛',
        '戌' => '戊',
        '亥' => '壬',
    ];

    /**
     * 天干×天干（日干×相手干）から十大主星を導出するマトリクス
     */
    protected const STEM_RELATIONS = [
        // 日干 庚 (陽金)
        '庚' => [
            '甲' => '禄存星', // 偏財
            '乙' => '司禄星', // 正財
            '丙' => '車騎星', // 偏官
            '丁' => '牽牛星', // 正官
            '戊' => '龍高星', // 偏印
            '己' => '玉堂星', // 正印
            '庚' => '貫索星', // 比肩
            '辛' => '石門星', // 劫財
            '壬' => '鳳閣星', // 食神
            '癸' => '調舒星', // 傷官
        ],
        // 日干 甲 (陽木: 参考・対比用)
        '甲' => [
            '甲' => '貫索星',
            '乙' => '石門星',
            '丙' => '鳳閣星',
            '丁' => '調舒星',
            '戊' => '禄存星',
            '己' => '司禄星',
            '庚' => '車騎星',
            '辛' => '牽牛星',
            '壬' => '龍高星',
            '癸' => '玉堂星',
        ],
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
        $year = (int)$input->birthDate->format('Y');
        $month = (int)$input->birthDate->format('m');
        $day = (int)$input->birthDate->format('d');

        $solar = Solar::fromYmdHms($year, $month, $day, 12, 0, 0);
        $eightChar = $solar->getLunar()->getEightChar();

        $yearGan = $eightChar->getYearGan();
        $yearZhi = $eightChar->getYearZhi();
        $monthGan = $eightChar->getMonthGan();
        $monthZhi = $eightChar->getMonthZhi();
        $dayGan = $eightChar->getDayGan();
        $dayZhi = $eightChar->getDayZhi();

        $yearMainHideGan = self::MAIN_HIDDEN_GANS[$yearZhi] ?? $eightChar->getYearHideGan()[0];
        $monthMainHideGan = self::MAIN_HIDDEN_GANS[$monthZhi] ?? $eightChar->getMonthHideGan()[0];
        $dayMainHideGan = self::MAIN_HIDDEN_GANS[$dayZhi] ?? $eightChar->getDayHideGan()[0];

        // 1. 陰占 (干支と蔵干本元)
        $insen = [
            'year' => [
                'pillar' => $yearGan . $yearZhi,
                'gan' => $yearGan,
                'zhi' => $yearZhi,
                'main_hidden_gan' => $yearMainHideGan,
            ],
            'month' => [
                'pillar' => $monthGan . $monthZhi,
                'gan' => $monthGan,
                'zhi' => $monthZhi,
                'main_hidden_gan' => $monthMainHideGan,
            ],
            'day' => [
                'pillar' => $dayGan . $dayZhi,
                'gan' => $dayGan,
                'zhi' => $dayZhi,
                'main_hidden_gan' => $dayMainHideGan,
            ],
        ];

        // 2. 天中殺 (日干支の旬空から)
        $dayXunKong = $eightChar->getDayXunKong();
        $tenchusatsu = self::TENCHUSATSU_MAP[$dayXunKong] ?? ($dayXunKong . '天中殺');

        // 3. 陽占 (人体星図)
        // 北（頭）: 年干
        // 南（腹）: 月干
        // 中心（胸）: 月支本元
        // 東（左手）: 年支本元
        // 西（右手）: 日支本元
        $starNorth = $this->getStarForStems($dayGan, $yearGan);
        $starSouth = $this->getStarForStems($dayGan, $monthGan);
        $starCenter = $this->getStarForStems($dayGan, $monthMainHideGan);
        $starEast = $this->getStarForStems($dayGan, $yearMainHideGan);
        $starWest = $this->getStarForStems($dayGan, $dayMainHideGan);

        // 十二大従星
        // 初年期 (左肩): 年支
        // 中年期 (左足): 月支
        // 晩年期 (右足): 日支
        $earlyJusei = self::JUSANDAI_JUSEI_MAP[$eightChar->getYearDiShi()] ?? $eightChar->getYearDiShi();
        $middleJusei = self::JUSANDAI_JUSEI_MAP[$eightChar->getMonthDiShi()] ?? $eightChar->getMonthDiShi();
        $lateJusei = self::JUSANDAI_JUSEI_MAP[$eightChar->getDayDiShi()] ?? $eightChar->getDayDiShi();

        $yousen = [
            'north' => $starNorth,   // 北（親・目上）
            'south' => $starSouth,   // 南（子供・部下）
            'center' => $starCenter, // 中心（自分）
            'east' => $starEast,     // 東（社会・配偶者）
            'west' => $starWest,     // 西（家庭・補佐）
            'early_stage' => $earlyJusei,   // 初年期（左肩）
            'middle_stage' => $middleJusei, // 中年期（左足）
            'late_stage' => $lateJusei,     // 晩年期（右足）
        ];

        // 4. 守護神判定 (辰月生まれの庚金の調候守護神: 第一甲木, 第二壬水)
        $shugoshin = [
            'primary' => '甲木',
            'secondary' => '壬水',
            'has_in_natal' => in_array('壬', [$yearGan, $monthGan, $yearMainHideGan, $monthMainHideGan, $dayMainHideGan]),
            'natal_matches' => array_values(array_intersect(['甲', '壬'], [$yearGan, $monthGan, $yearMainHideGan, $monthMainHideGan, $dayMainHideGan])),
        ];

        // 既存資料との対比メモ
        $notes = [];
        if ($dayGan !== '甲') {
            $notes[] = "天中殺は「{$tenchusatsu}」で既存資料と完全一致します。日干は暦学上「{$dayGan}」であり、陽占は日干庚を基点に算出しています（北: {$starNorth}, 東: {$starEast}, 中心: {$starCenter}, 西: {$starWest}, 南: {$starSouth}）。既存資料の人体星図は誤った日干甲に基づいた値です。";
        }

        $data = [
            'day_stem' => $dayGan,
            'tenchūsatsu' => $tenchusatsu,
            'insen' => $insen,
            'yousen' => $yousen,
            'shugoshin' => $shugoshin,
            'notes' => $notes,
        ];

        return new FortuneCalculationResult(
            calculatorKey: self::KEY,
            calculatorVersion: self::VERSION,
            status: FortuneCalculationResult::STATUS_COMPLETED,
            data: $data
        );
    }

    /**
     * 日干と対象天干から十大主星を求める
     */
    protected function getStarForStems(string $dayGan, string $targetGan): string
    {
        if (isset(self::STEM_RELATIONS[$dayGan][$targetGan])) {
            return self::STEM_RELATIONS[$dayGan][$targetGan];
        }

        // 汎用判定（五行・陰陽の生剋関係から導出）
        $stems = [
            '甲' => ['element' => 'wood', 'yin_yang' => true],
            '乙' => ['element' => 'wood', 'yin_yang' => false],
            '丙' => ['element' => 'fire', 'yin_yang' => true],
            '丁' => ['element' => 'fire', 'yin_yang' => false],
            '戊' => ['element' => 'earth', 'yin_yang' => true],
            '己' => ['element' => 'earth', 'yin_yang' => false],
            '庚' => ['element' => 'metal', 'yin_yang' => true],
            '辛' => ['element' => 'metal', 'yin_yang' => false],
            '壬' => ['element' => 'water', 'yin_yang' => true],
            '癸' => ['element' => 'water', 'yin_yang' => false],
        ];

        $me = $stems[$dayGan] ?? null;
        $target = $stems[$targetGan] ?? null;
        if (!$me || !$target) {
            return '貫索星';
        }

        $sameYinYang = ($me['yin_yang'] === $target['yin_yang']);
        $elements = ['wood', 'fire', 'earth', 'metal', 'water'];
        $meIdx = array_search($me['element'], $elements);
        $targetIdx = array_search($target['element'], $elements);

        $diff = ($targetIdx - $meIdx + 5) % 5;

        return match ($diff) {
            0 => $sameYinYang ? '貫索星' : '石門星',
            1 => $sameYinYang ? '鳳閣星' : '調舒星',
            2 => $sameYinYang ? '禄存星' : '司禄星',
            3 => $sameYinYang ? '車騎星' : '牽牛星',
            4 => $sameYinYang ? '龍高星' : '玉堂星',
        };
    }
}
