<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use App\Models\Guess;
use App\Services\GameJudgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetupFlowTest extends TestCase
{
    use RefreshDatabase;

    protected string $setupToken = 'demo-setup-token-2026';
    protected string $manageToken = 'demo-manage-token-2026';
    protected string $diagnosticsToken = 'demo-diag-token-2026';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'game.setup_token' => $this->setupToken,
            'game.manage_token' => $this->manageToken,
            'game.diagnostics_token' => $this->diagnosticsToken,
        ]);
    }

    /**
     * 初期状態: BabyProfile未登録でもDiagnosticsとManage画面がクラッシュせず表示できること
     */
    public function test_initial_unregistered_state_accessible_without_crash(): void
    {
        $this->assertDatabaseEmpty('baby_profiles');

        // 診断画面 (未登録表示)
        $diagResponse = $this->get('/diagnostics/' . $this->diagnosticsToken);
        $diagResponse->assertOk()
            ->assertSee('未登録')
            ->assertSee('ネタバレ防止診断画面');

        // 管理画面 (未登録案内)
        $manageResponse = $this->get('/manage/' . $this->manageToken);
        $manageResponse->assertOk()
            ->assertSee('赤ちゃん情報は未登録です');

        // セットアップ画面 (入力フォーム)
        $setupResponse = $this->get('/setup/' . $this->setupToken);
        $setupResponse->assertOk()
            ->assertSee('赤ちゃんの情報を登録する');
    }

    /**
     * 不正なSetupトークンでは404が返ること
     */
    public function test_invalid_setup_token_returns_404(): void
    {
        $this->get('/setup/invalid-secret-token')->assertNotFound();
        $this->post('/setup/invalid-secret-token/confirm', [])->assertNotFound();
        $this->post('/setup/invalid-secret-token', [])->assertNotFound();
    }

    /**
     * Validation: 必須項目が不足している場合は登録できないこと
     */
    public function test_validation_fails_when_required_fields_missing(): void
    {
        $response = $this->post('/setup/' . $this->setupToken . '/confirm', [
            'family_name' => '',
            'given_name' => '',
        ]);

        $response->assertSessionHasErrors([
            'family_name',
            'given_name',
            'family_name_kana',
            'given_name_kana',
            'sex',
            'birth_date',
        ]);
        $this->assertDatabaseEmpty('baby_profiles');
    }

    /**
     * 正常登録フロー: 入力 → 確認 → 登録 → 完了
     */
    public function test_successful_registration_flow(): void
    {
        $testData = [
            'family_name' => '山田',
            'given_name' => '花',
            'family_name_kana' => 'やまだ',
            'given_name_kana' => 'はな',
            'sex' => 'female',
            'birth_date' => '2000-01-15',
            'birth_time' => '12:00',
            'birth_place' => '検証県検証市中央区',
            'birth_weight' => 3000,
        ];

        // 1. 確認画面へのPOST
        $confirmResponse = $this->post('/setup/' . $this->setupToken . '/confirm', $testData);
        $confirmResponse->assertOk()
            ->assertViewIs('setup.confirm')
            ->assertSee('山田 花')
            ->assertSee('やまだ はな')
            ->assertSee('検証県検証市中央区');

        // 2. 確定登録POST
        $storeResponse = $this->post('/setup/' . $this->setupToken, $testData);
        $storeResponse->assertRedirect(route('setup.complete', ['token' => $this->setupToken]));

        // 3. 完了画面の表示
        $completeResponse = $this->get('/setup/' . $this->setupToken . '/complete');
        $completeResponse->assertOk()
            ->assertViewIs('setup.complete')
            ->assertSee('登録が完了しました！')
            ->assertSee('/g/game-');

        // DBに1件のみ作成されていること
        $this->assertEquals(1, BabyProfile::count());
        $profile = BabyProfile::first();
        $this->assertEquals('山田 花', $profile->full_name);
        $this->assertEquals('やまだ はな', $profile->full_name_kana);
        $this->assertEquals('検証県検証市中央区', $profile->birth_place);
    }

    /**
     * 二重登録の防止: 登録済み状態では新しいBabyProfileを登録できないこと
     */
    public function test_cannot_register_twice(): void
    {
        $testData = [
            'family_name' => '山田',
            'given_name' => '花',
            'family_name_kana' => 'やまだ',
            'given_name_kana' => 'はな',
            'sex' => 'female',
            'birth_date' => '2000-01-15',
        ];

        // 初回登録
        $this->post('/setup/' . $this->setupToken, $testData)->assertRedirect();
        $this->assertEquals(1, BabyProfile::count());

        // 2回目の登録試行 (フォーム表示時)
        $formResponse = $this->get('/setup/' . $this->setupToken);
        $formResponse->assertOk()
            ->assertViewIs('setup.already-registered')
            ->assertSee('登録は完了しています');

        // 2回目の登録試行 (POST時)
        $secondStoreResponse = $this->post('/setup/' . $this->setupToken, [
            'family_name' => '山田',
            'given_name' => '太郎',
            'family_name_kana' => 'やまだ',
            'given_name_kana' => 'たろう',
            'sex' => 'male',
            'birth_date' => '2026-01-01',
        ]);
        $secondStoreResponse->assertRedirect(route('setup.show', ['token' => $this->setupToken]));

        // レコード数は1件のままであること
        $this->assertEquals(1, BabyProfile::count());
        $profile = BabyProfile::first();
        $this->assertEquals('山田 花', $profile->full_name);
    }

    /**
     * Encryption: Setupから登録した場合でもDB raw valueに平文情報が残らないこと
     */
    public function test_raw_database_has_no_plaintext_after_setup(): void
    {
        $testData = [
            'family_name' => '山田',
            'given_name' => '花',
            'family_name_kana' => 'やまだ',
            'given_name_kana' => 'はな',
            'sex' => 'female',
            'birth_date' => '2000-01-15',
            'birth_time' => '12:00',
            'birth_place' => '検証県検証市中央区',
            'birth_weight' => 3000,
        ];

        $this->post('/setup/' . $this->setupToken, $testData);

        // 生レコードを取得
        $raw = DB::table('baby_profiles')->first();
        $this->assertNotNull($raw);

        // 平文検索
        $this->assertStringNotContainsString('山田', $raw->family_name_encrypted);
        $this->assertStringNotContainsString('花', $raw->given_name_encrypted);
        $this->assertStringNotContainsString('やまだ', $raw->family_name_kana_encrypted);
        $this->assertStringNotContainsString('はな', $raw->given_name_kana_encrypted);
        $this->assertStringNotContainsString('検証県検証市中央区', $raw->birth_place_encrypted);

        // HMACが生成されていること
        $this->assertNotEmpty($raw->given_name_hmac);
        $this->assertNotEmpty($raw->given_name_kana_hmac);
    }

    /**
     * HMAC判定: Setup登録後、正誤判定が期待通り動作すること
     */
    public function test_game_judgment_works_after_setup(): void
    {
        $testData = [
            'family_name' => '山田',
            'given_name' => '花',
            'family_name_kana' => 'やまだ',
            'given_name_kana' => 'はな',
            'sex' => 'female',
            'birth_date' => '2000-01-15',
        ];

        $this->post('/setup/' . $this->setupToken, $testData);
        $profile = BabyProfile::first();
        $judge = app(GameJudgeService::class);

        // 花 → correct
        $this->assertTrue($judge->judge($profile, '花')->isCorrect());

        // はな → reading_match
        $this->assertTrue($judge->judge($profile, 'はな')->isReadingMatch());

        // ハナ → reading_match
        $this->assertTrue($judge->judge($profile, 'ハナ')->isReadingMatch());

        // 楓 → wrong
        $this->assertTrue($judge->judge($profile, '楓')->isWrong());
    }

    /**
     * Game画面参加者には出生地・出生時刻が表示されないこと
     */
    public function test_game_screen_withholds_birth_place_and_time_from_participants(): void
    {
        $testData = [
            'family_name' => '山田',
            'given_name' => '花',
            'family_name_kana' => 'やまだ',
            'given_name_kana' => 'はな',
            'sex' => 'female',
            'birth_date' => '2000-01-15',
            'birth_time' => '12:00',
            'birth_place' => '検証県検証市中央区',
            'birth_weight' => 3000,
        ];

        $this->post('/setup/' . $this->setupToken, $testData);
        $profile = BabyProfile::first();

        // 正解前
        $response = $this->get('/g/' . $profile->game_token);
        $response->assertOk()
            ->assertSee('山田')
            ->assertSee('女の子')
            ->assertDontSee('検証県検証市中央区')
            ->assertDontSee('12:00')
            ->assertDontSee('2,612g');

        // 回答API (正解時)
        $guessResponse = $this->postJson('/g/' . $profile->game_token . '/guess', [
            'baby_name' => '花',
        ]);
        $guessResponse->assertOk()
            ->assertJson([
                'result' => 'correct',
                'revealed_name' => [
                    'full_name' => '山田 花',
                    'full_name_kana' => 'やまだ はな',
                ],
            ])
            ->assertJsonMissing(['birth_place'])
            ->assertJsonMissing(['birth_time'])
            ->assertJsonMissing(['birth_weight']);
    }
}
