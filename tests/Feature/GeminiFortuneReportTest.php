<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use App\Models\FortuneReport;
use App\Services\Fortune\FortuneManager;
use App\Services\Fortune\GeminiFortuneException;
use App\Services\Fortune\GeminiFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiFortuneReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-3.5-flash',
            'services.gemini.endpoint' => 'https://generativelanguage.googleapis.com/v1beta/interactions',
        ]);
    }

    public function test_successful_generation_encrypts_report_and_excludes_pii_from_payload(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        $capturedPayload = null;
        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) use (&$capturedPayload) {
                $capturedPayload = $request->data();

                return Http::response($this->fakeGeminiHttpBody($this->validReportJson()), 200);
            },
        ]);

        $report = app(GeminiFortuneService::class)->generate($profile);

        $this->assertSame(FortuneReport::STATUS_COMPLETED, $report->status);
        $this->assertSame('gemini-3.5-flash', $report->model);
        $this->assertNotNull($report->generated_at);
        $this->assertSame(12, $report->input_tokens);
        $this->assertSame(34, $report->output_tokens);
        $this->assertArrayHasKey('reports', $report->report_ciphertext);
        $this->assertArrayHasKey('four_pillars', $report->report_ciphertext['reports']);
        $this->assertArrayHasKey('integrated_report', $report->report_ciphertext);
        $this->assertArrayHasKey('parenting_guide', $report->report_ciphertext);

        // DB平文なし
        $raw = DB::table('fortune_reports')->where('id', $report->id)->first();
        $this->assertStringNotContainsString('温かい光', $raw->report_ciphertext);
        $this->assertStringNotContainsString('catchphrase', $raw->report_ciphertext);

        // store=false / PIIなし
        $this->assertFalse($capturedPayload['store']);
        $this->assertSame('gemini-3.5-flash', $capturedPayload['model']);
        $payloadJson = json_encode($capturedPayload, JSON_UNESCAPED_UNICODE);
        foreach (['山田', '花', 'はな', 'やまだ', '検証県検証市中央区'] as $pii) {
            $this->assertStringNotContainsString($pii, $payloadJson);
        }
        $this->assertArrayHasKey('response_format', $capturedPayload);
        $this->assertSame('application/json', $capturedPayload['response_format']['mime_type']);
    }

    public function test_api_failure_keeps_previous_report(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->fakeGeminiHttpBody($this->validReportJson()), 200)
                ->push(['error' => 'boom'], 500),
        ]);

        $first = app(GeminiFortuneService::class)->generate($profile);
        $originalCatchphrase = $first->report_ciphertext['summary']['catchphrase'];

        try {
            app(GeminiFortuneService::class)->generate($profile);
            $this->fail('Expected GeminiFortuneException');
        } catch (GeminiFortuneException $e) {
            $this->assertSame('server_error', $e->errorCode);
        }

        $report = $profile->fresh()->fortuneReport;
        $this->assertSame(FortuneReport::STATUS_COMPLETED, $report->status);
        $this->assertSame($originalCatchphrase, $report->report_ciphertext['summary']['catchphrase']);
        $this->assertSame('server_error', $report->error_code);
    }

    public function test_rate_limit_error(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'rate'], 429),
        ]);

        try {
            app(GeminiFortuneService::class)->generate($profile);
            $this->fail('Expected rate_limit');
        } catch (GeminiFortuneException $e) {
            $this->assertSame('rate_limit', $e->errorCode);
        }

        $failed = $profile->fresh()->fortuneReport;
        $this->assertSame(FortuneReport::STATUS_FAILED, $failed->status);
    }

    public function test_invalid_json_error(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiHttpBody('{not-json'), 200),
        ]);

        try {
            app(GeminiFortuneService::class)->generate($profile);
            $this->fail('Expected invalid_json');
        } catch (GeminiFortuneException $e) {
            $this->assertSame('invalid_json', $e->errorCode);
        }
    }

    public function test_schema_mismatch_fails(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiHttpBody(json_encode(['summary' => ['catchphrase' => 'x']], JSON_UNESCAPED_UNICODE)),
                200
            ),
        ]);

        try {
            app(GeminiFortuneService::class)->generate($profile);
            $this->fail('Expected schema_mismatch');
        } catch (GeminiFortuneException $e) {
            $this->assertSame('schema_mismatch', $e->errorCode);
        }
    }

    public function test_manage_generate_and_fortune_view(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiHttpBody($this->validReportJson()), 200),
        ]);

        $this->post('/manage/' . $profile->manage_token . '/fortune/generate')
            ->assertRedirect('/manage/' . $profile->manage_token . '/fortune')
            ->assertSessionHas('status', '鑑定が完成しました');

        $this->get('/manage/' . $profile->manage_token . '/fortune')
            ->assertOk()
            ->assertSee('花ちゃんの鑑定')
            ->assertSee('総合鑑定')
            ->assertSee('四柱推命')
            ->assertSee('育成・開運ガイド')
            ->assertSee('温かい光をまとった子');
    }

    public function test_manage_generate_failure_shows_retry_message(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'nope'], 401),
        ]);

        $this->post('/manage/' . $profile->manage_token . '/fortune/generate')
            ->assertRedirect('/manage/' . $profile->manage_token)
            ->assertSessionHas('fortune_error', '鑑定の生成に失敗しました');
    }

    public function test_diagnostics_shows_ai_status_without_report_body(): void
    {
        $profile = $this->createAyaProfile();
        app(FortuneManager::class)->calculateAll($profile);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiHttpBody($this->validReportJson()), 200),
        ]);
        app(GeminiFortuneService::class)->generate($profile);

        $response = $this->get('/diagnostics/' . $profile->diagnostics_token);
        $response->assertOk();
        $response->assertSee('AI鑑定');
        $response->assertSee('✓ 完了');
        $response->assertSee('gemini-3.5-flash');
        $response->assertDontSee('温かい光をまとった子');
        $response->assertDontSee('花');
        $response->assertDontSee('山田');
    }

    protected function createAyaProfile(): BabyProfile
    {
        return BabyProfile::create([
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
            'game_token' => 'game-token-gemini',
            'manage_token' => 'manage-token-gemini',
            'diagnostics_token' => 'diag-token-gemini',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fakeGeminiHttpBody(string $outputText): array
    {
        return [
            'id' => 'int_test',
            'status' => 'completed',
            'steps' => [
                [
                    'type' => 'model_output',
                    'status' => 'done',
                    'content' => [
                        ['type' => 'text', 'text' => $outputText],
                    ],
                ],
            ],
            'usage' => [
                'total_input_tokens' => 12,
                'total_output_tokens' => 34,
                'total_tokens' => 46,
            ],
        ];
    }

    protected function validReportJson(): string
    {
        $oneReport = [
            'title' => 'タイトル',
            'summary' => '概要です。',
            'personality' => '性格の傾向があります。',
            'strengths' => ['やさしい', '好奇心'],
            'relationships' => '人との関わり方に傾向があります。',
            'future_tendencies' => 'これから伸ばしやすいでしょう。',
            'message' => '温かく見守ってあげてください。',
        ];

        $payload = [
            'summary' => [
                'catchphrase' => '温かい光をまとった子',
                'overview' => 'お子さまの魅力をまとめた概要です。',
            ],
            'reports' => [
                'numerology' => $oneReport,
                'shukuyo' => $oneReport,
                'nine_star_ki' => $oneReport,
                'four_pillars' => $oneReport,
                'sanmeigaku' => $oneReport,
                'western_astrology' => $oneReport,
                'zi_wei_dou_shu' => $oneReport,
            ],
            'integrated_report' => [
                'core_traits' => '本質の傾向があります。',
                'strengths' => ['共感力', '観察力'],
                'growth' => '成長のヒントがあります。',
                'relationships' => '関係性の傾向があります。',
                'future' => 'これからの傾向があります。',
            ],
            'parenting_guide' => [
                'early_childhood' => '乳幼児期のヒントです。',
                'school_age' => '学童期のヒントです。',
                'teenage' => '思春期のヒントです。',
                'advice' => ['一緒に遊ぶ時間を大切に'],
                'lucky_elements' => [
                    'numbers' => ['3', '7'],
                    'colors' => ['水色'],
                    'activities' => ['お散歩'],
                ],
            ],
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
