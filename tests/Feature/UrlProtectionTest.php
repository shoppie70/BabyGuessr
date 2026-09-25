<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use Database\Seeders\BabyProfileTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrlProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BabyProfileTestSeeder::class);
    }

    /**
     * ルートURL (/) は 404 となること
     */
    public function test_root_url_returns_404(): void
    {
        $response = $this->get('/');
        $response->assertNotFound();
    }

    /**
     * 無効なトークンではアクセスできないこと (404)
     */
    public function test_invalid_tokens_return_404(): void
    {
        $this->get('/g/invalid-token-123')->assertNotFound();
        $this->get('/manage/invalid-token-123')->assertNotFound();
        $this->get('/diagnostics/invalid-token-123')->assertNotFound();
    }

    /**
     * 有効なトークンでは 200 OK が返り、noindex, nofollow が設定されていること
     */
    public function test_valid_tokens_return_200_and_have_noindex_tag(): void
    {
        $profile = BabyProfile::first();

        // 1. ゲーム画面
        $gameResponse = $this->get('/g/' . $profile->game_token);
        $gameResponse->assertOk();
        $gameResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        // HTMLソースに正解名「花」が露出していないこと（苗字「山田」はOK）
        $gameResponse->assertDontSee('山田 花');

        // 2. 管理画面
        $manageResponse = $this->get('/manage/' . $profile->manage_token);
        $manageResponse->assertOk();
        $manageResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // 3. 診断画面
        $diagResponse = $this->get('/diagnostics/' . $profile->diagnostics_token);
        $diagResponse->assertOk();
        $diagResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        // 診断画面には平文の名前・読みが表示されていないこと
        $diagResponse->assertDontSee('花');
        $diagResponse->assertDontSee('はな');
        $diagResponse->assertDontSee('やまだ');
    }
}
