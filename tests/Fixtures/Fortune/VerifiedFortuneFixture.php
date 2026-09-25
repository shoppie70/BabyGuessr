<?php

namespace Tests\Fixtures\Fortune;

use App\Services\Fortune\DTOs\FortuneInput;
use Carbon\Carbon;

final class VerifiedFortuneFixture
{
    private static ?array $data = null;

    public static function all(): array
    {
        return self::$data ??= require __DIR__.'/verified_fixture.php';
    }

    public static function section(string $key): array
    {
        return self::all()[$key];
    }

    public static function input(): FortuneInput
    {
        $i = self::section('input');

        return new FortuneInput(
            familyName: $i['family_name'],
            givenName: $i['given_name'],
            familyNameKana: $i['family_name_kana'],
            givenNameKana: $i['given_name_kana'],
            familyNameRoman: $i['family_name_roman'],
            givenNameRoman: $i['given_name_roman'],
            sex: $i['sex'],
            birthDate: Carbon::parse($i['birth_date']),
            birthTime: $i['birth_time'],
            birthPlace: $i['birth_place'],
            latitude: $i['latitude'],
            longitude: $i['longitude'],
            timezone: $i['timezone'],
        );
    }

    public static function inputWithoutTime(): FortuneInput
    {
        $i = self::section('input');

        return new FortuneInput(
            familyName: $i['family_name'],
            givenName: $i['given_name'],
            familyNameKana: $i['family_name_kana'],
            givenNameKana: $i['given_name_kana'],
            familyNameRoman: $i['family_name_roman'],
            givenNameRoman: $i['given_name_roman'],
            sex: $i['sex'],
            birthDate: Carbon::parse($i['birth_date']),
            birthTime: null,
            birthPlace: null,
        );
    }
}
