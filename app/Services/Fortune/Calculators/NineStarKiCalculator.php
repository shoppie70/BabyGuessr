<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;
use App\Services\Fortune\Support\GeocodingService;
use com\nlf\calendar\Solar;

class NineStarKiCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'nine_star_ki';
    protected const VERSION = '1.0.0';

    protected const PALACE_NAMES = [
        1 => '坎宮',
        2 => '坤宮',
        3 => '震宮',
        4 => '巽宮',
        5 => '中宮',
        6 => '乾宮',
        7 => '兌宮',
        8 => '艮宮',
        9 => '離宮',
    ];

    protected const STAR_NAMES = [
        1 => '一白水星',
        2 => '二黒土星',
        3 => '三碧木星',
        4 => '四緑木星',
        5 => '五黄土星',
        6 => '六白金星',
        7 => '七赤金星',
        8 => '八白土星',
        9 => '九紫火星',
    ];

    protected const STAR_ELEMENTS = [
        1 => '水',
        2 => '土',
        3 => '木',
        4 => '木',
        5 => '土',
        6 => '金',
        7 => '金',
        8 => '土',
        9 => '火',
    ];

    /**
     * 後天八卦における九星の巡行順序（中宮からの各宮オフセット）
     * 中宮: offset 0
     * 乾宮: offset 1
     * 兌宮: offset 2
     * 艮宮: offset 3
     * 離宮: offset 4
     * 坎宮: offset 5
     * 坤宮: offset 6
     * 震宮: offset 7
     * 巽宮: offset 8
     */
    protected const PALACE_OFFSETS = [
        5 => 0, // 中宮
        6 => 1, // 乾宮
        7 => 2, // 兌宮
        8 => 3, // 艮宮
        9 => 4, // 離宮
        1 => 5, // 坎宮
        2 => 6, // 坤宮
        3 => 7, // 震宮
        4 => 8, // 巽宮
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

        $solar = Solar::fromYmdHms(
            $year,
            $month,
            $day,
            $hour,
            $minute,
            0
        );
        $lunar = $solar->getLunar();

        // 1. 本命星 (年九星)
        $yearNineStar = $lunar->getYearNineStar();
        $honmeiNumber = $yearNineStar->getIndex() + 1;
        $honmeiName = self::STAR_NAMES[$honmeiNumber];

        // 2. 月命星 (月九星)
        $monthNineStar = $lunar->getMonthNineStar();
        $getsumeiNumber = $monthNineStar->getIndex() + 1;
        $getsumeiName = self::STAR_NAMES[$getsumeiNumber];

        // 3. 日命星 (日九星)
        $dayNineStar = $lunar->getDayNineStar();
        $nichimeiNumber = $dayNineStar->getIndex() + 1;
        $nichimeiName = self::STAR_NAMES[$nichimeiNumber];

        // 4. 傾宮 (月命盤において本命星が回座する宮)
        // 月命星が中宮に入ったとき、オフセット offset で回座する星 = ((getsumei - 1 + offset) % 9) + 1
        // その星が本命星と一致するオフセットを探す
        $keikyuPalaceNumber = $this->findPalaceForStarInChart($getsumeiNumber, $honmeiNumber);
        $keikyuName = self::PALACE_NAMES[$keikyuPalaceNumber];

        // 5. 同会 (本命盤において、本命星の定位である宮に回座する星)
        // 本命星の定位宮 = honmeiNumber (中宮5の場合は坤2または乾6が用いられるが通常本命盤で坎宮1等)
        $honmeiHomePalace = $honmeiNumber;
        $dokaiStarNumber = $this->getStarInPalace($honmeiNumber, $honmeiHomePalace);
        $dokaiName = self::STAR_NAMES[$dokaiStarNumber] . '同会';

        // 6. 節気区分
        $prevJieQi = $lunar->getPrevJie();
        $nextJieQi = $lunar->getNextJie();
        $currentJieQi = $lunar->getJieQi() ?: ($prevJieQi ? $prevJieQi->getName() : null);

        // 7. 時差補正・真太陽時
        $solarTimeData = null;
        $timeNineStarName = null;
        $notes = [];

        if ($hasTime) {
            $coords = GeocodingService::resolve($input->birthPlace);
            $longitude = $coords['lng'] ?? 135.0;
            // 明石（東経135度）との経度差補正（1度 = 4分）
            $timeOffsetMinutes = round(($longitude - 135.0) * 4, 2);

            $birthTimeCarbon = $input->getBirthDateTime();
            $localMeanTime = $birthTimeCarbon ? $birthTimeCarbon->copy()->addMinutes($timeOffsetMinutes) : null;

            $timeNineStar = $lunar->getTimeNineStar();
            $timeNineStarNumber = $timeNineStar->getIndex() + 1;
            $timeNineStarName = self::STAR_NAMES[$timeNineStarNumber];

            $solarTimeData = [
                'birth_place' => $input->birthPlace,
                'longitude' => $longitude,
                'latitude' => $coords['lat'] ?? null,
                'longitude_offset_minutes' => $timeOffsetMinutes,
                'local_mean_time' => $localMeanTime ? $localMeanTime->format('H:i') : null,
                'time_nine_star' => $timeNineStarName,
            ];

            // 既存fixtureとの対比メモ
            if ($nichimeiName !== '五黄土星' && $timeNineStarName === '五黄土星') {
                $notes[] = "日命星は暦学上「{$nichimeiName}」（陽遁）。既存資料の「五黄土星」は時命星（時九星: {$timeNineStarName}）と一致しています。";
            }
        }

        $data = [
            'honmei_star' => [
                'number' => $honmeiNumber,
                'name' => $honmeiName,
                'element' => self::STAR_ELEMENTS[$honmeiNumber],
            ],
            'getsumei_star' => [
                'number' => $getsumeiNumber,
                'name' => $getsumeiName,
                'element' => self::STAR_ELEMENTS[$getsumeiNumber],
            ],
            'nichimei_star' => [
                'number' => $nichimeiNumber,
                'name' => $nichimeiName,
                'element' => self::STAR_ELEMENTS[$nichimeiNumber],
            ],
            'keikyu' => [
                'palace_number' => $keikyuPalaceNumber,
                'name' => $keikyuName,
            ],
            'dokai' => [
                'star_number' => $dokaiStarNumber,
                'star_name' => self::STAR_NAMES[$dokaiStarNumber],
                'name' => $dokaiName,
            ],
            'solar_term' => [
                'current' => $currentJieQi,
                'prev' => $prevJieQi ? $prevJieQi->getName() : null,
                'next' => $nextJieQi ? $nextJieQi->getName() : null,
            ],
            'solar_time_correction' => $solarTimeData,
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
     * 中宮に centerStar がある盤において、targetStar が回座している宮番号 (1〜9) を求める
     */
    protected function findPalaceForStarInChart(int $centerStar, int $targetStar): int
    {
        foreach (self::PALACE_OFFSETS as $palaceNumber => $offset) {
            $starInPalace = (($centerStar - 1 + $offset) % 9) + 1;
            if ($starInPalace === $targetStar) {
                return $palaceNumber;
            }
        }

        return 5;
    }

    /**
     * 中宮に centerStar がある盤において、targetPalace に回座している星番号 (1〜9) を求める
     */
    protected function getStarInPalace(int $centerStar, int $targetPalace): int
    {
        $offset = self::PALACE_OFFSETS[$targetPalace] ?? 0;
        return (($centerStar - 1 + $offset) % 9) + 1;
    }
}
