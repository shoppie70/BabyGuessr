<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use Database\Seeders\BabyProfileTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'game.hub_token' => 'demo-hub-token-2026',
            'game.setup_token' => 'demo-setup-token-2026',
        ]);
        $this->seed(BabyProfileTestSeeder::class);
    }

    public function test_invalid_hub_token_returns_404(): void
    {
        $this->get('/hub/invalid-token')->assertNotFound();
    }

    public function test_hub_lists_babies_without_given_name(): void
    {
        $response = $this->get('/hub/demo-hub-token-2026');

        $response->assertOk()
            ->assertSee('山田')
            ->assertSee('女の子')
            ->assertSee('2000年1月15日')
            ->assertSee('名前当て')
            ->assertSee('鑑定')
            ->assertSee('新規登録')
            ->assertDontSee('花')
            ->assertDontSee('はな');
    }
}
