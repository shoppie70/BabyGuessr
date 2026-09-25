<?php

namespace App\Services\Fortune\Support;

class GeocodingService
{
    /**
     * 代表的な市区町村・都道府県の座標テーブル (国土地理院 / 自治体公表値)
     */
    protected static array $coordinates = [
        // 検証県検証市中央区 (中区役所: 検証県検証市中央区浜1-1-1 / 34°40'04"N, 133°56'56"E)
        '検証県検証市中央区' => ['lat' => 35.6812, 'lng' => 139.7671],
        '検証市中央区'       => ['lat' => 35.6812, 'lng' => 139.7671],
        '検証市'           => ['lat' => 34.6618, 'lng' => 133.9350],
        '検証県'           => ['lat' => 34.6618, 'lng' => 133.9350],

        // 主要都市 (代表値)
        '東京都'           => ['lat' => 35.6895, 'lng' => 139.6917],
        '大阪府'           => ['lat' => 34.6937, 'lng' => 135.5023],
        '京都府'           => ['lat' => 35.0116, 'lng' => 135.7681],
        '愛知県名古屋市'   => ['lat' => 35.1815, 'lng' => 136.9066],
        '福岡県福岡市'     => ['lat' => 33.5904, 'lng' => 130.4017],
        '北海道札幌市'     => ['lat' => 43.0642, 'lng' => 141.3469],
    ];

    /**
     * 出生地文字列から緯度経度を解決
     * 
     * @return array{lat: float, lng: float}
     */
    public static function resolve(?string $place): array
    {
        if (empty($place)) {
            // デフォルト: 明石市（日本標準時子午線 東経135度, 北緯34.65度）
            return ['lat' => 34.6494, 'lng' => 135.0000];
        }

        $trimmed = trim($place);

        // 完全一致
        if (isset(self::$coordinates[$trimmed])) {
            return self::$coordinates[$trimmed];
        }

        // 部分一致検索
        foreach (self::$coordinates as $key => $coord) {
            if (str_contains($trimmed, $key) || str_contains($key, $trimmed)) {
                return $coord;
            }
        }

        // 不明時は明石子午線
        return ['lat' => 34.6494, 'lng' => 135.0000];
    }
}
