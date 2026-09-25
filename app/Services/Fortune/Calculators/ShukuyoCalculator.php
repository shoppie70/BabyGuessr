<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;
use com\nlf\calendar\Solar;

class ShukuyoCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'shukuyo';
    protected const VERSION = '1.0.0';

    /**
     * 27宿マスター定義 (順序: 角宿から順に27宿)
     */
    protected const SHUKU_LIST = [
        1  => ['name' => '角宿', 'kana' => 'かくしゅく', 'category' => '和善宿', 'palace' => '東方・青竜', 'zodiac' => '乙女宮2足・天秤宮2足'],
        2  => ['name' => '亢宿', 'kana' => 'こうしゅく', 'category' => '軽躁宿', 'palace' => '東方・青竜', 'zodiac' => '天秤宮4足'],
        3  => ['name' => '氐宿', 'kana' => 'ていしゅく', 'category' => '剛柔宿', 'palace' => '東方・青竜', 'zodiac' => '天秤宮3足・蠍宮1足'],
        4  => ['name' => '房宿', 'kana' => 'ぼうしゅく', 'category' => '和善宿', 'palace' => '東方・青竜', 'zodiac' => '蠍宮4足'],
        5  => ['name' => '心宿', 'kana' => 'しんしゅく', 'category' => '急速宿', 'palace' => '東方・青竜', 'zodiac' => '蠍宮4足'],
        6  => ['name' => '尾宿', 'kana' => 'びしゅく',   'category' => '急速宿', 'palace' => '東方・青竜', 'zodiac' => '蠍宮4足'],
        7  => ['name' => '箕宿', 'kana' => 'きしゅく',   'category' => '猛悪宿', 'palace' => '東方・青竜', 'zodiac' => '人馬宮4足'],
        8  => ['name' => '斗宿', 'kana' => 'としゅく',   'category' => '安重宿', 'palace' => '北方・玄武', 'zodiac' => '人馬宮3足・磨羯宮1足'],
        9  => ['name' => '女宿', 'kana' => 'じょしゅく', 'category' => '急速宿', 'palace' => '北方・玄武', 'zodiac' => '磨羯宮4足'],
        10 => ['name' => '虚宿', 'kana' => 'きょしゅく', 'category' => '剛柔宿', 'palace' => '北方・玄武', 'zodiac' => '磨羯宮3足・宝瓶宮1足'],
        11 => ['name' => '危宿', 'kana' => 'きしゅく',   'category' => '剛柔宿', 'palace' => '北方・玄武', 'zodiac' => '宝瓶宮4足'],
        12 => ['name' => '室宿', 'kana' => 'しつしゅく', 'category' => '猛悪宿', 'palace' => '北方・玄武', 'zodiac' => '宝瓶宮3足・双魚宮1足'],
        13 => ['name' => '壁宿', 'kana' => 'へきしゅく', 'category' => '安重宿', 'palace' => '北方・玄武', 'zodiac' => '双魚宮4足'],
        14 => ['name' => '奎宿', 'kana' => 'けいしゅく', 'category' => '和善宿', 'palace' => '西方・白虎', 'zodiac' => '双魚宮3足・白羊宮1足'],
        15 => ['name' => '婁宿', 'kana' => 'ろうしゅく', 'category' => '急速宿', 'palace' => '西方・白虎', 'zodiac' => '白羊宮4足'],
        16 => ['name' => '胃宿', 'kana' => 'いしゅく',   'category' => '剛柔宿', 'palace' => '西方・白虎', 'zodiac' => '白羊宮3足・金牛宮1足'],
        17 => ['name' => '昴宿', 'kana' => 'ぼうしゅく', 'category' => '剛柔宿', 'palace' => '西方・白虎', 'zodiac' => '金牛宮4足'],
        18 => ['name' => '畢宿', 'kana' => 'ひっしゅく', 'category' => '安重宿', 'palace' => '西方・白虎', 'zodiac' => '金牛宮3足・双児宮1足'],
        19 => ['name' => '觜宿', 'kana' => 'ししゅく',   'category' => '和善宿', 'palace' => '西方・白虎', 'zodiac' => '双児宮4足'],
        20 => ['name' => '参宿', 'kana' => 'しんしゅく', 'category' => '猛悪宿', 'palace' => '西方・白虎', 'zodiac' => '双児宮3足・巨蟹宮1足'],
        21 => ['name' => '井宿', 'kana' => 'せいしゅく', 'category' => '軽躁宿', 'palace' => '南方・朱雀', 'zodiac' => '巨蟹宮4足'],
        22 => ['name' => '鬼宿', 'kana' => 'きしゅく',   'category' => '急速宿', 'palace' => '南方・朱雀', 'zodiac' => '巨蟹宮3足・獅子宮1足'],
        23 => ['name' => '柳宿', 'kana' => 'りゅうしゅく', 'category' => '猛悪宿', 'palace' => '南方・朱雀', 'zodiac' => '獅子宮4足'],
        24 => ['name' => '星宿', 'kana' => 'せいしゅく', 'category' => '猛悪宿', 'palace' => '南方・朱雀', 'zodiac' => '獅子宮3足・処女宮1足'],
        25 => ['name' => '張宿', 'kana' => 'ちょうしゅく', 'category' => '和善宿', 'palace' => '南方・朱雀', 'zodiac' => '処女宮4足'],
        26 => ['name' => '翼宿', 'kana' => 'よくしゅく', 'category' => '安重宿', 'palace' => '南方・朱雀', 'zodiac' => '処女宮3足・天秤宮1足'],
        27 => ['name' => '軫宿', 'kana' => 'しんしゅく', 'category' => '急速宿', 'palace' => '南方・朱雀', 'zodiac' => '天秤宮4足'],
    ];

    /**
     * 旧暦各月1日の宿番号 (角宿=1〜軫宿=27)
     * 1月:室(12), 2月:奎(14), 3月:胃(16), 4月:畢(18), 5月:参(20), 6月:鬼(22),
     * 7月:張(25), 8月:角(1), 9月:氐(3), 10月:心(5), 11月:斗(8), 12月:虚(10)
     */
    protected const MONTH_FIRST_SHUKU = [
        1  => 12, // 室
        2  => 14, // 奎
        3  => 16, // 胃
        4  => 18, // 畢
        5  => 20, // 参
        6  => 22, // 鬼
        7  => 25, // 張
        8  => 1,  // 角
        9  => 3,  // 氐
        10 => 5,  // 心
        11 => 8,  // 斗
        12 => 10, // 虚
    ];

    /**
     * 曜日と曜星の対応
     */
    protected const YOUSEI = [
        0 => '日曜日・太陽星',
        1 => '月曜日・太陰星',
        2 => '火曜日・火星',
        3 => '水曜日・水星',
        4 => '木曜日・木星',
        5 => '金曜日・金星',
        6 => '土曜日・土星',
    ];

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
        $hour = $input->hasBirthTime() ? (int)substr($input->birthTime, 0, 2) : 12;
        $minute = $input->hasBirthTime() ? (int)substr($input->birthTime, 3, 2) : 0;

        // 6tail/lunar-php による太陽暦・太陰暦の精密計算
        $solar = Solar::fromYmdHms($year, $month, $day, $hour, $minute, 0);
        $lunar = $solar->getLunar();

        $lunarYear = $lunar->getYear();
        $lunarMonth = abs($lunar->getMonth()); // 閏月は負数で返ることがあるため正数化
        $lunarDay = $lunar->getDay();
        $isLeapMonth = $lunar->getMonth() < 0;

        $lunarGanzhiYear = $lunar->getYearInGanZhi();

        // 宿曜27宿の計算: (旧暦月1日の宿 + 旧暦日 - 1) % 27
        $firstShuku = self::MONTH_FIRST_SHUKU[$lunarMonth] ?? 1;
        $shukuIndex = (($firstShuku + ($lunarDay - 1) - 1) % 27) + 1;

        $shukuInfo = self::SHUKU_LIST[$shukuIndex] ?? self::SHUKU_LIST[5];

        $dayOfWeek = (int)$input->birthDate->format('w');
        $weekdayJapanese = ['日', '月', '火', '水', '木', '金', '土'][$dayOfWeek] . '曜日';
        $yousei = self::YOUSEI[$dayOfWeek] ?? '';

        $data = [
            'lunar_date' => [
                'year' => $lunarYear,
                'month' => $lunarMonth,
                'day' => $lunarDay,
                'is_leap' => $isLeapMonth,
                'ganzhi_year' => $lunarGanzhiYear,
                'display' => "{$lunarGanzhiYear}年 " . ($isLeapMonth ? '閏' : '') . "{$lunarMonth}月{$lunarDay}日",
            ],
            'weekday' => $weekdayJapanese,
            'yousei' => $yousei,
            'honmei_shuku' => [
                'index' => $shukuIndex,
                'name' => $shukuInfo['name'],
                'kana' => $shukuInfo['kana'],
                'category' => $shukuInfo['category'], // '急速宿', etc.
                'palace' => $shukuInfo['palace'],     // '東方・青竜', etc.
                'zodiac' => $shukuInfo['zodiac'],     // '蠍宮4足', etc.
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
}
