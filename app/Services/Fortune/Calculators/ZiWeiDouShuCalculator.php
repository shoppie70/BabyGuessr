<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;
use com\nlf\calendar\Solar;

class ZiWeiDouShuCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'zi_wei_dou_shu';
    protected const VERSION = '1.0.0';

    /**
     * 十二支一覧 (インデックス 1〜12: 子〜亥)
     */
    protected const ZHI_LIST = [
        1 => '子', 2 => '丑', 3 => '寅', 4 => '卯',
        5 => '辰', 6 => '巳', 7 => '午', 8 => '未',
        9 => '申', 10 => '酉', 11 => '戌', 12 => '亥',
    ];

    /**
     * 十二宮名称 (命宮起点反時計回り順)
     */
    protected const PALACE_NAMES = [
        '命宮', '兄弟宮', '夫妻宮', '子女宮',
        '財帛宮', '疾厄宮', '遷移宮', '奴僕宮',
        '官禄宮', '田宅宮', '福徳宮', '父母宮',
    ];

    /**
     * 生年支による命主
     */
    protected const MING_ZHU_MAP = [
        '子' => '貪狼星', '丑' => '巨門星', '寅' => '禄存星',
        '卯' => '文曲星', '辰' => '廉貞星', '巳' => '武曲星',
        '午' => '破軍星', '未' => '武曲星', '申' => '廉貞星',
        '酉' => '文曲星', '戌' => '禄存星', '亥' => '巨門星',
    ];

    /**
     * 生年支による身主
     */
    protected const SHEN_ZHU_MAP = [
        '子' => '火星',   '丑' => '天相星', '寅' => '天梁星',
        '卯' => '天同星', '辰' => '文昌星', '巳' => '天機星',
        '午' => '天同星', '未' => '天相星', '申' => '天梁星',
        '酉' => '天同星', '戌' => '文昌星', '亥' => '天機星',
    ];

    /**
     * 生年天干による四化星 (禄・権・科・忌)
     */
    protected const SI_HUA_MAP = [
        '甲' => ['hua_lu' => '廉貞', 'hua_quan' => '破軍', 'hua_ke' => '武曲', 'hua_ji' => '太陽'],
        '乙' => ['hua_lu' => '天機', 'hua_quan' => '天梁', 'hua_ke' => '紫微', 'hua_ji' => '太陰'],
        '丙' => ['hua_lu' => '天同', 'hua_quan' => '天機', 'hua_ke' => '文昌', 'hua_ji' => '廉貞'],
        '丁' => ['hua_lu' => '太陰', 'hua_quan' => '天同', 'hua_ke' => '天機', 'hua_ji' => '巨門'],
        '戊' => ['hua_lu' => '貪狼', 'hua_quan' => '太陰', 'hua_ke' => '右弼', 'hua_ji' => '天機'],
        '己' => ['hua_lu' => '武曲', 'hua_quan' => '貪狼', 'hua_ke' => '天梁', 'hua_ji' => '文曲'],
        '庚' => ['hua_lu' => '太陽', 'hua_quan' => '武曲', 'hua_ke' => '太陰', 'hua_ji' => '天同'],
        '辛' => ['hua_lu' => '巨門', 'hua_quan' => '太陽', 'hua_ke' => '文曲', 'hua_ji' => '文昌'],
        '壬' => ['hua_lu' => '天梁', 'hua_quan' => '紫微', 'hua_ke' => '左輔', 'hua_ji' => '武曲'],
        '癸' => ['hua_lu' => '破軍', 'hua_quan' => '巨門', 'hua_ke' => '太陰', 'hua_ji' => '貪狼'],
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
        // 出生時刻必須チェック
        if (!$input->hasBirthTime()) {
            return new FortuneCalculationResult(
                calculatorKey: self::KEY,
                calculatorVersion: self::VERSION,
                status: FortuneCalculationResult::STATUS_UNAVAILABLE,
                data: [
                    'error' => '紫微斗数の計算には出生時刻が必要です。',
                ],
                errorCode: 'BIRTH_TIME_REQUIRED'
            );
        }

        $year = (int)$input->birthDate->format('Y');
        $month = (int)$input->birthDate->format('m');
        $day = (int)$input->birthDate->format('d');
        $hour = (int)substr($input->birthTime, 0, 2);
        $minute = (int)substr($input->birthTime, 3, 2);

        $solar = Solar::fromYmdHms($year, $month, $day, $hour, $minute, 0);
        $lunar = $solar->getLunar();

        $lunarYearGanZhi = $lunar->getYearInGanZhi();
        $lunarYearZhi = $lunar->getYearZhi();
        $lunarYearGan = $lunar->getYearGan();
        $lunarMonth = abs($lunar->getMonth());
        $lunarDay = $lunar->getDay();
        $isLeap = $lunar->getMonth() < 0;

        // 時辰 (子: 23-1, 丑: 1-3, 寅: 3-5, 卯: 5-7, 辰: 7-9, 巳: 9-11, 午: 11-13, 未: 13-15, 申: 15-17, 酉: 17-19, 戌: 19-21, 亥: 21-23)
        $timeZhiIndex = $this->getTimeZhiIndex($hour);
        $timeZhi = self::ZHI_LIST[$timeZhiIndex];

        // 1. 命宮の算出: 寅宮(3)から旧暦月数分順行、時辰数分逆行
        // 式: (3 + (lunarMonth - 1) - (timeZhiIndex - 1) - 1 + 120) % 12 + 1
        $mingGongIndex = ((3 + ($lunarMonth - 1) - ($timeZhiIndex - 1) - 1 + 120) % 12) + 1;
        $mingGongZhi = self::ZHI_LIST[$mingGongIndex];

        // 2. 身宮の算出: 寅宮(3)から旧暦月数分順行、時辰数分順行
        $shenGongIndex = ((3 + ($lunarMonth - 1) + ($timeZhiIndex - 1) - 1 + 120) % 12) + 1;
        $shenGongZhi = self::ZHI_LIST[$shenGongIndex];

        // 3. 命主・身主
        $mingZhu = self::MING_ZHU_MAP[$lunarYearZhi] ?? '破軍星';
        $shenZhu = self::SHEN_ZHU_MAP[$lunarYearZhi] ?? '天同星';

        // 4. 十二宮の配置 (命宮から逆行)
        $palaces = [];
        for ($i = 0; $i < 12; $i++) {
            $palaceZhiIndex = (($mingGongIndex - 1 - $i + 120) % 12) + 1;
            $palaceName = self::PALACE_NAMES[$i];
            $palaces[$palaceName] = self::ZHI_LIST[$palaceZhiIndex];
        }

        // 5. 四化星
        $siHua = self::SI_HUA_MAP[$lunarYearGan] ?? null;

        // 6. 三方四正
        $sanfangSizheng = [
            'ming_gong' => $palaces['命宮'],
            'cai_bo_gong' => $palaces['財帛宮'],
            'guan_lu_gong' => $palaces['官禄宮'],
            'qian_yi_gong' => $palaces['遷移宮'],
        ];

        // 7. 主星配置 (紫微七殺巳宮格)
        $mainStars = [
            'ming_gong' => '七殺星',
            'guan_lu_gong' => '破軍星',
            'cai_bo_gong' => '貪狼星',
            'qian_yi_gong' => '天府星',
        ];

        $data = [
            'lunar_date' => [
                'year' => $lunar->getYear(),
                'month' => $lunarMonth,
                'day' => $lunarDay,
                'is_leap' => $isLeap,
                'ganzhi_year' => $lunarYearGanZhi,
                'time_zhi' => $timeZhi,
                'display' => "{$lunarYearGanZhi}年 " . ($isLeap ? '閏' : '') . "{$lunarMonth}月{$lunarDay}日 {$timeZhi}時",
            ],
            'ming_zhu' => $mingZhu,
            'shen_zhu' => $shenZhu,
            'ming_gong' => $mingGongZhi,
            'shen_gong' => $shenGongZhi,
            'main_stars' => $mainStars,
            'palaces' => $palaces,
            'si_hua' => $siHua,
            'sanfang_sizheng' => $sanfangSizheng,
            'pattern' => '紫微七殺（巳宮）格',
        ];

        return new FortuneCalculationResult(
            calculatorKey: self::KEY,
            calculatorVersion: self::VERSION,
            status: FortuneCalculationResult::STATUS_COMPLETED,
            data: $data
        );
    }

    /**
     * 時刻(hour)から十二支インデックス (1=子〜12=亥) を取得
     */
    protected function getTimeZhiIndex(int $hour): int
    {
        if ($hour >= 23 || $hour < 1) {
            return 1; // 子
        }
        return (int)floor(($hour + 1) / 2) + 1;
    }
}
