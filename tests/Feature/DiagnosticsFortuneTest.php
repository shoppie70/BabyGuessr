<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use App\Models\FortuneCalculation;
use App\Services\Fortune\FortuneManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticsFortuneTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostics_shows_calculation_statuses_without_revealing_results(): void
    {
        // 山田花のプロファイルを作成
        $profile = BabyProfile::create([
            'family_name_encrypted' => '山田',
            'given_name_encrypted' => '花',
            'family_name_kana_encrypted' => 'やまだ',
            'given_name_kana_encrypted' => 'はな',
            'given_name_hmac' => hash_hmac('sha256', '花', 'secret'),
            'given_name_kana_hmac' => hash_hmac('sha256', 'はな', 'secret'),
            'birth_date' => '2000-01-15',
            'birth_time' => '12:00:00',
            'sex' => 'female',
            'birth_place_encrypted' => '検証県検証市中央区',
            'status' => 'open',
            'game_token' => 'game-token-1234',
            'manage_token' => 'manage-token-1234',
            'diagnostics_token' => 'diag-token-1234',
        ]);

        // 全占術を計算
        $manager = app(FortuneManager::class);
        $manager->calculateAll($profile);

        // 診断画面へアクセス
        $response = $this->get('/diagnostics/' . $profile->diagnostics_token);

        $response->assertStatus(200);
        $response->assertSee('占術基礎計算ステータス (7系統)');
        $response->assertSee('数秘術');
        $response->assertSee('宿曜占星術');
        $response->assertSee('九星気学');
        $response->assertSee('四柱推命');
        $response->assertSee('算命学');
        $response->assertSee('西洋占星術');
        $response->assertSee('紫微斗数');
        $response->assertSee('完了');

        // ネタバレ・結果本文の非露出チェック
        $response->assertDontSee('Life Path');
        $response->assertDontSee('心宿');
        $response->assertDontSee('一白水星');
        $response->assertDontSee('丙午');
        $response->assertDontSee('車騎星');
        $response->assertDontSee('Aries 16');
        $response->assertDontSee('破軍星');
    }
}
