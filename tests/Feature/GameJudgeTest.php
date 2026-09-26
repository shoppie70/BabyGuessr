<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use App\Models\Guess;
use App\Services\GameJudgeResult;
use App\Services\GameJudgeService;
use Database\Seeders\BabyProfileTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameJudgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BabyProfileTestSeeder::class);
    }

    /**
     * 正解: 「花」で正解になること
     */
    public function test_correct_guess_returns_correct_result(): void
    {
        $profile = BabyProfile::first();
        $judgeService = app(GameJudgeService::class);

        // 単体判定
        $result = $judgeService->judge($profile, '花');
        $this->assertTrue($result->isCorrect());
        $this->assertEquals(GameJudgeResult::CORRECT, $result->result);

        // 空白付きでも正解
        $resultWithSpaces = $judgeService->judge($profile, '　花 ');
        $this->assertTrue($resultWithSpaces->isCorrect());
    }

    /**
     * 読み一致: 「はな」「ハナ」で読み一致になること
     */
    public function test_reading_match_guess_returns_reading_match_result(): void
    {
        $profile = BabyProfile::first();
        $judgeService = app(GameJudgeService::class);

        // ひらがな
        $resultHiragana = $judgeService->judge($profile, 'はな');
        $this->assertTrue($resultHiragana->isReadingMatch());
        $this->assertEquals(GameJudgeResult::READING_MATCH, $resultHiragana->result);

        // カタカナ
        $resultKatakana = $judgeService->judge($profile, 'ハナ');
        $this->assertTrue($resultKatakana->isReadingMatch());

        // 空白付きカタカナ
        $resultWithSpaces = $judgeService->judge($profile, ' ア ヤ ');
        $this->assertTrue($resultWithSpaces->isReadingMatch());
    }

    /**
     * 不正解: 「楓」で不正解になること
     */
    public function test_wrong_guess_returns_wrong_result(): void
    {
        $profile = BabyProfile::first();
        $judgeService = app(GameJudgeService::class);

        $resultWrongKanji = $judgeService->judge($profile, '楓');
        $this->assertTrue($resultWrongKanji->isWrong());
        $this->assertEquals(GameJudgeResult::WRONG, $resultWrongKanji->result);

        $resultWrongKana = $judgeService->judge($profile, 'かえで');
        $this->assertTrue($resultWrongKana->isWrong());
    }

    /**
     * APIエンドポイント経由での回答テスト
     */
    public function test_guess_api_endpoint_flow(): void
    {
        $profile = BabyProfile::first();
        $url = route('game.guess', ['token' => $profile->game_token]);

        // 1回目: 不正解「楓」
        $response1 = $this->postJson($url, [
            'baby_name' => '楓',
            'nickname' => 'ともだちA',
        ]);
        $response1->assertOk()
            ->assertJson([
                'result' => 'wrong',
                'guess' => '楓',
                'is_correct' => false,
                'is_reading_match' => false,
                'attempt_no' => 1,
            ])
            ->assertJsonMissing(['revealed_name']);

        // 2回目: 読み一致「はな」
        $response2 = $this->postJson($url, [
            'baby_name' => 'はな',
            'nickname' => 'ともだちA',
        ]);
        $response2->assertOk()
            ->assertJson([
                'result' => 'reading_match',
                'is_correct' => false,
                'is_reading_match' => true,
                'attempt_no' => 2,
            ])
            ->assertJsonMissing(['revealed_name']);

        // 3回目: 読み一致「ハナ」
        $response3 = $this->postJson($url, [
            'baby_name' => 'ハナ',
            'nickname' => 'ともだちA',
        ]);
        $response3->assertOk()
            ->assertJson([
                'result' => 'reading_match',
                'is_correct' => false,
                'is_reading_match' => true,
                'attempt_no' => 3,
            ]);

        // 4回目: 大正解「花」
        $response4 = $this->postJson($url, [
            'baby_name' => '花',
            'nickname' => 'ともだちA',
        ]);
        $response4->assertOk()
            ->assertJson([
                'result' => 'correct',
                'is_correct' => true,
                'is_reading_match' => false,
                'attempt_no' => 4,
                'revealed_name' => [
                    'full_name' => '山田 花',
                    'full_name_kana' => 'やまだ はな',
                ],
            ]);

        // DBに4件の回答が記録されていること
        $this->assertEquals(4, Guess::where('baby_profile_id', $profile->id)->count());

        // 画面にみんなの回答が出ること
        $this->get(route('game.show', ['token' => $profile->game_token]))
            ->assertOk()
            ->assertSee('みんなの回答')
            ->assertSee('楓')
            ->assertSee('はな')
            ->assertSee('ハナ')
            ->assertSee('花');

        // 別セッションからも同じ一覧が見えること
        $this->flushSession();
        $this->get(route('game.show', ['token' => $profile->game_token]))
            ->assertOk()
            ->assertSee('みんなの回答')
            ->assertSee('楓')
            ->assertSee('花');
    }
}
