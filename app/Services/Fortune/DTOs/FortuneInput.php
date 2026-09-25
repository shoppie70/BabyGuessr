<?php

namespace App\Services\Fortune\DTOs;

use App\Models\BabyProfile;
use App\Services\Fortune\Support\GeocodingService;
use App\Services\Fortune\Support\HepburnConverter;
use Carbon\Carbon;

class FortuneInput
{
    public function __construct(
        public readonly string $familyName,
        public readonly string $givenName,
        public readonly string $familyNameKana,
        public readonly string $givenNameKana,
        public readonly string $familyNameRoman,
        public readonly string $givenNameRoman,
        public readonly string $sex, // 'female', 'male', 'other'
        public readonly Carbon $birthDate,
        public readonly ?string $birthTime = null, // '12:00'
        public readonly ?string $birthPlace = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly string $timezone = 'Asia/Tokyo'
    ) {}

    public static function fromProfile(BabyProfile $profile): self
    {
        $familyNameRoman = HepburnConverter::convert($profile->family_name);
        $givenNameRoman = HepburnConverter::convert($profile->given_name);

        $coords = GeocodingService::resolve($profile->birth_place);

        $birthTime = $profile->birth_time ? substr($profile->birth_time, 0, 5) : null;

        return new self(
            familyName: $profile->family_name,
            givenName: $profile->given_name,
            familyNameKana: $profile->family_name_kana,
            givenNameKana: $profile->given_name_kana,
            familyNameRoman: $familyNameRoman,
            givenNameRoman: $givenNameRoman,
            sex: $profile->sex,
            birthDate: Carbon::parse($profile->birth_date),
            birthTime: $birthTime,
            birthPlace: $profile->birth_place,
            latitude: $coords['lat'],
            longitude: $coords['lng'],
            timezone: 'Asia/Tokyo'
        );
    }

    /**
     * フルネームのローマ字表記（姓名順: 名 姓、例: HANA YAMADA）
     */
    public function getFullNameRomanWestern(): string
    {
        return trim($this->givenNameRoman . ' ' . $this->familyNameRoman);
    }

    /**
     * フルネームのローマ字表記（日本順: 姓 名、例: YAMADA HANA）
     */
    public function getFullNameRomanJapanese(): string
    {
        return trim($this->familyNameRoman . ' ' . $this->givenNameRoman);
    }

    /**
     * 出生時刻を持っているかどうか
     */
    public function hasBirthTime(): bool
    {
        return !empty($this->birthTime);
    }

    /**
     * 出生日時のCarbonインスタンス（時刻なしの場合は 00:00 または 12:00 正午）
     */
    public function getBirthDateTime(): Carbon
    {
        if ($this->hasBirthTime()) {
            return Carbon::parse($this->birthDate->format('Y-m-d') . ' ' . $this->birthTime, $this->timezone);
        }

        // 時刻不明時は正午を仮定
        return Carbon::parse($this->birthDate->format('Y-m-d') . ' 12:00:00', $this->timezone);
    }

    /**
     * 入力内容の一意ハッシュ（冪等性チェック用）
     */
    public function generateHash(): string
    {
        return hash('sha256', implode('|', [
            $this->familyName,
            $this->givenName,
            $this->familyNameKana,
            $this->givenNameKana,
            $this->familyNameRoman,
            $this->givenNameRoman,
            $this->sex,
            $this->birthDate->format('Y-m-d'),
            $this->birthTime ?? '',
            $this->birthPlace ?? '',
            $this->latitude ?? '',
            $this->longitude ?? '',
            $this->timezone,
        ]));
    }
}
