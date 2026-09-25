<?php

namespace Database\Seeders;

use App\Models\BabyProfile;
use App\Services\NameHmacService;
use App\Services\NameNormalizationService;
use Illuminate\Database\Seeder;

class BabyProfileTestSeeder extends Seeder
{
    public const GAME_TOKEN = 'demo-game-token-2026';
    public const MANAGE_TOKEN = 'demo-manage-token-2026';
    public const DIAGNOSTICS_TOKEN = 'demo-diag-token-2026';

    /**
     * Run the database seeds for testing / local development fixture.
     * Note: Do NOT call this in production DatabaseSeeder.
     */
    public function run(): void
    {
        $normalizer = app(NameNormalizationService::class);
        $hmacService = app(NameHmacService::class);

        $familyName = '山田';
        $givenName = '花';
        $familyNameKana = 'やまだ';
        $givenNameKana = 'はな';

        $normalizedGivenName = $normalizer->normalizeKanji($givenName);
        $normalizedGivenNameKana = $normalizer->normalizeKana($givenNameKana);

        $givenNameHmac = $hmacService->generateHmac($normalizedGivenName);
        $givenNameKanaHmac = $hmacService->generateHmac($normalizedGivenNameKana);

        BabyProfile::query()->updateOrCreate(
            ['game_token' => self::GAME_TOKEN],
            [
                'family_name_encrypted' => $familyName,
                'given_name_encrypted' => $givenName,
                'family_name_kana_encrypted' => $familyNameKana,
                'given_name_kana_encrypted' => $givenNameKana,
                'given_name_hmac' => $givenNameHmac,
                'given_name_kana_hmac' => $givenNameKanaHmac,
                'birth_date' => '2000-01-15',
                'birth_time' => '12:00:00',
                'sex' => 'female',
                'birth_place_encrypted' => '検証県検証市中央区',
                'birth_weight' => 3000,
                'status' => 'open',
                'manage_token' => self::MANAGE_TOKEN,
                'diagnostics_token' => self::DIAGNOSTICS_TOKEN,
            ]
        );
    }
}
