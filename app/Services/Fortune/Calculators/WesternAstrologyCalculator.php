<?php

namespace App\Services\Fortune\Calculators;

use App\Services\Fortune\Contracts\FortuneCalculatorInterface;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use App\Services\Fortune\DTOs\FortuneInput;
use App\Services\Fortune\Support\GeocodingService;
use Astronomy\Body;
use Astronomy\Ephemeris;
use Astronomy\Houses;
use Astronomy\HouseSystem;
use Astronomy\Time;
use DateTimeImmutable;
use DateTimeZone;

class WesternAstrologyCalculator implements FortuneCalculatorInterface
{
    protected const KEY = 'western_astrology';
    protected const VERSION = '1.0.0';

    protected const SIGNS = [
        0 => ['en' => 'Aries', 'ja' => '牡羊座', 'element' => 'fire', 'modality' => 'cardinal'],
        1 => ['en' => 'Taurus', 'ja' => '牡牛座', 'element' => 'earth', 'modality' => 'fixed'],
        2 => ['en' => 'Gemini', 'ja' => '双子座', 'element' => 'air', 'modality' => 'mutable'],
        3 => ['en' => 'Cancer', 'ja' => '蟹座', 'element' => 'water', 'modality' => 'cardinal'],
        4 => ['en' => 'Leo', 'ja' => '獅子座', 'element' => 'fire', 'modality' => 'fixed'],
        5 => ['en' => 'Virgo', 'ja' => '乙女座', 'element' => 'earth', 'modality' => 'mutable'],
        6 => ['en' => 'Libra', 'ja' => '天秤座', 'element' => 'air', 'modality' => 'cardinal'],
        7 => ['en' => 'Scorpio', 'ja' => '蠍座', 'element' => 'water', 'modality' => 'fixed'],
        8 => ['en' => 'Sagittarius', 'ja' => '射手座', 'element' => 'fire', 'modality' => 'mutable'],
        9 => ['en' => 'Capricorn', 'ja' => '山羊座', 'element' => 'earth', 'modality' => 'cardinal'],
        10 => ['en' => 'Aquarius', 'ja' => '水瓶座', 'element' => 'air', 'modality' => 'fixed'],
        11 => ['en' => 'Pisces', 'ja' => '魚座', 'element' => 'water', 'modality' => 'mutable'],
    ];

    protected const PLANETS = [
        'Sun'     => ['body' => Body::Sun,     'ja' => '太陽'],
        'Moon'    => ['body' => Body::Moon,    'ja' => '月'],
        'Mercury' => ['body' => Body::Mercury, 'ja' => '水星'],
        'Venus'   => ['body' => Body::Venus,   'ja' => '金星'],
        'Mars'    => ['body' => Body::Mars,    'ja' => '火星'],
        'Jupiter' => ['body' => Body::Jupiter, 'ja' => '木星'],
        'Saturn'  => ['body' => Body::Saturn,  'ja' => '土星'],
        'Uranus'  => ['body' => Body::Uranus,  'ja' => '天王星'],
        'Neptune' => ['body' => Body::Neptune, 'ja' => '海王星'],
        'Pluto'   => ['body' => Body::Pluto,   'ja' => '冥王星'],
    ];

    protected const ASPECTS = [
        ['type' => 'conjunction', 'name' => '合 (0°)', 'angle' => 0.0, 'orb' => 8.0],
        ['type' => 'sextile',     'name' => 'セクスタイル (60°)', 'angle' => 60.0, 'orb' => 6.0],
        ['type' => 'square',      'name' => 'スクエア (90°)', 'angle' => 90.0, 'orb' => 7.0],
        ['type' => 'trine',       'name' => 'トライン (120°)', 'angle' => 120.0, 'orb' => 8.0],
        ['type' => 'opposition',  'name' => 'オポジション (180°)', 'angle' => 180.0, 'orb' => 8.0],
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
        $dateStr = $input->birthDate->format('Y-m-d');
        $timeStr = $hasTime ? $input->birthTime : '12:00';

        // UTC日時の生成 (Asia/Tokyo から UTC へ変換)
        $localDt = new DateTimeImmutable("{$dateStr} {$timeStr}:00", new DateTimeZone($input->timezone));
        $utcDt = $localDt->setTimezone(new DateTimeZone('UTC'));

        $jdUt = Time::julianDay($utcDt);
        $jdTT = Time::tt($jdUt);

        // 緯度経度の解決
        $coords = GeocodingService::resolve($input->birthPlace);
        $lat = $input->latitude ?? $coords['lat'];
        $lng = $input->longitude ?? $coords['lng'];

        // ハウス計算 (出生時刻がある場合のみ)
        $houses = null;
        $ascSign = null;
        $mcSign = null;
        if ($hasTime) {
            $houses = Houses::calculate(HouseSystem::Placidus, $jdUt, latitude: $lat, geographicLongitude: $lng);
            $ascSign = $this->parseDegreeToSign($houses->ascendant);
            $mcSign = $this->parseDegreeToSign($houses->midheaven);
        }

        // 天体位置の計算
        $celestialBodies = [];
        $elementsCount = ['fire' => 0, 'earth' => 0, 'air' => 0, 'water' => 0];
        $modalitiesCount = ['cardinal' => 0, 'fixed' => 0, 'mutable' => 0];

        foreach (self::PLANETS as $key => $pInfo) {
            $pos = Ephemeris::position($pInfo['body'], $jdTT);
            $signInfo = $this->parseDegreeToSign($pos->longitude);

            $houseNumber = null;
            if ($houses) {
                $houseNumber = $this->getHouseNumber($pos->longitude, $houses->cusps);
            }

            $celestialBodies[$key] = [
                'name_en' => $key,
                'name_ja' => $pInfo['ja'],
                'longitude' => round($pos->longitude, 4),
                'speed' => round($pos->speed, 4),
                'is_retrograde' => $pos->speed < 0,
                'sign' => $signInfo['sign_en'],
                'sign_ja' => $signInfo['sign_ja'],
                'degree' => $signInfo['degree'],
                'formatted' => "{$signInfo['sign_en']} {$signInfo['degree']}°",
                'house' => $houseNumber,
                'element' => $signInfo['element'],
                'modality' => $signInfo['modality'],
            ];

            $elementsCount[$signInfo['element']]++;
            $modalitiesCount[$signInfo['modality']]++;
        }

        // 主要アスペクトの計算
        $aspects = $this->calculateAspects($celestialBodies);

        // ハウスカスプ一覧
        $houseCusps = null;
        if ($houses) {
            $houseCusps = [];
            for ($h = 1; $h <= 12; $h++) {
                $cuspDeg = $houses->cusps[$h];
                $cSign = $this->parseDegreeToSign($cuspDeg);
                $houseCusps[$h] = [
                    'house' => $h,
                    'longitude' => round($cuspDeg, 4),
                    'sign' => $cSign['sign_en'],
                    'sign_ja' => $cSign['sign_ja'],
                    'degree' => $cSign['degree'],
                ];
            }
        }

        $notes = [];
        if ($hasTime) {
            $notes[] = 'VSOP87/ELP2000相当。位置は JPL Horizons（DE441）と照合済み（Phase 3.1 Verified Fixture）。ハウスは Placidus。';
        }

        $data = [
            'planets' => $celestialBodies,
            'angles' => $hasTime ? [
                'ascendant' => [
                    'longitude' => round($houses->ascendant, 4),
                    'sign' => $ascSign['sign_en'],
                    'sign_ja' => $ascSign['sign_ja'],
                    'degree' => $ascSign['degree'],
                    'formatted' => "{$ascSign['sign_en']} {$ascSign['degree']}°",
                ],
                'midheaven' => [
                    'longitude' => round($houses->midheaven, 4),
                    'sign' => $mcSign['sign_en'],
                    'sign_ja' => $mcSign['sign_ja'],
                    'degree' => $mcSign['degree'],
                    'formatted' => "{$mcSign['sign_en']} {$mcSign['degree']}°",
                ],
            ] : null,
            'houses' => $houseCusps,
            'aspects' => $aspects,
            'element_balance' => $elementsCount,
            'modality_balance' => $modalitiesCount,
            'coordinates' => [
                'latitude' => $lat,
                'longitude' => $lng,
            ],
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
     * 黄道経度 (0〜360) からサイン、サイン内度数、エレメント、クオリティを導出
     */
    protected function parseDegreeToSign(float $longitude): array
    {
        $lon = fmod($longitude, 360.0);
        if ($lon < 0) {
            $lon += 360.0;
        }

        $signIndex = (int)floor($lon / 30.0);
        $remDeg = $lon - ($signIndex * 30.0);
        $signMeta = self::SIGNS[$signIndex];

        return [
            'sign_index' => $signIndex,
            'sign_en' => $signMeta['en'],
            'sign_ja' => $signMeta['ja'],
            'degree' => (int)floor($remDeg),
            'degree_float' => round($remDeg, 4),
            'element' => $signMeta['element'],
            'modality' => $signMeta['modality'],
        ];
    }

    /**
     * Placidusカスプにおけるハウス番号 (1〜12) の判定
     */
    protected function getHouseNumber(float $longitude, array $cusps): int
    {
        $lon = fmod($longitude, 360.0);
        if ($lon < 0) {
            $lon += 360.0;
        }

        for ($h = 1; $h <= 12; $h++) {
            $c1 = $cusps[$h];
            $nextH = ($h === 12) ? 1 : $h + 1;
            $c2 = $cusps[$nextH];

            if ($c1 < $c2) {
                if ($lon >= $c1 && $lon < $c2) {
                    return $h;
                }
            } else {
                if ($lon >= $c1 || $lon < $c2) {
                    return $h;
                }
            }
        }

        return 1;
    }

    /**
     * 10天体間のアスペクト計算
     */
    protected function calculateAspects(array $planets): array
    {
        $aspectList = [];
        $keys = array_keys($planets);

        for ($i = 0; $i < count($keys); $i++) {
            for ($j = $i + 1; $j < count($keys); $j++) {
                $p1Key = $keys[$i];
                $p2Key = $keys[$j];
                $p1 = $planets[$p1Key];
                $p2 = $planets[$p2Key];

                $diff = abs($p1['longitude'] - $p2['longitude']);
                $diff = fmod($diff, 360.0);
                if ($diff > 180.0) {
                    $diff = 360.0 - $diff;
                }

                foreach (self::ASPECTS as $aspectDef) {
                    $orbDist = abs($diff - $aspectDef['angle']);
                    if ($orbDist <= $aspectDef['orb']) {
                        $aspectList[] = [
                            'body1' => $p1Key,
                            'body1_ja' => $p1['name_ja'],
                            'body2' => $p2Key,
                            'body2_ja' => $p2['name_ja'],
                            'aspect_type' => $aspectDef['type'],
                            'aspect_name' => $aspectDef['name'],
                            'exact_angle' => round($diff, 2),
                            'orb' => round($orbDist, 2),
                        ];
                        break;
                    }
                }
            }
        }

        return $aspectList;
    }
}
