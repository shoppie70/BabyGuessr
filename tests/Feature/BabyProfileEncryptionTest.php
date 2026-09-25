<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use App\Models\Guess;
use Database\Seeders\BabyProfileTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BabyProfileEncryptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DBには平文で名前や読みが保存されないこと
     */
    public function test_name_and_kana_are_not_saved_as_plaintext_in_db(): void
    {
        $this->seed(BabyProfileTestSeeder::class);

        // Eloquentの自動復号を通さず、DBクエリから直接Rawデータを取得
        $rawRecord = DB::table('baby_profiles')->first();

        $this->assertNotNull($rawRecord);

        // 平文の名前・読み・出生地が含まれていないこと
        $this->assertStringNotContainsString('花', $rawRecord->given_name_encrypted);
        $this->assertStringNotContainsString('はな', $rawRecord->given_name_kana_encrypted);
        $this->assertStringNotContainsString('山田', $rawRecord->family_name_encrypted);
        $this->assertStringNotContainsString('やまだ', $rawRecord->family_name_kana_encrypted);
        $this->assertStringNotContainsString('検証県検証市中央区', $rawRecord->birth_place_encrypted);

        // 暗号化文字列形式であることを確認
        $this->assertNotEmpty($rawRecord->given_name_encrypted);
        $this->assertNotEmpty($rawRecord->given_name_hmac);
    }

    /**
     * Eloquent経由で取得したときは正しく復号できること
     */
    public function test_name_is_decrypted_correctly_via_model(): void
    {
        $this->seed(BabyProfileTestSeeder::class);

        $profile = BabyProfile::first();

        $this->assertNotNull($profile);
        $this->assertEquals('山田', $profile->family_name);
        $this->assertEquals('花', $profile->given_name);
        $this->assertEquals('やまだ', $profile->family_name_kana);
        $this->assertEquals('はな', $profile->given_name_kana);
        $this->assertEquals('検証県検証市中央区', $profile->birth_place);
        $this->assertEquals('山田 花', $profile->full_name);
        $this->assertEquals('やまだ はな', $profile->full_name_kana);
    }

    /**
     * 回答履歴の文字列も平文保存されないこと
     */
    public function test_guess_history_is_encrypted_in_db(): void
    {
        $this->seed(BabyProfileTestSeeder::class);
        $profile = BabyProfile::first();

        $guess = Guess::create([
            'baby_profile_id' => $profile->id,
            'challenger_name_encrypted' => '田中',
            'guess_encrypted' => '花',
            'guess_hmac' => 'dummy-hmac',
            'result' => Guess::RESULT_CORRECT,
            'session_identifier' => 'test-session',
            'attempt_no' => 1,
        ]);

        // 生DBレコードを確認
        $rawGuess = DB::table('guesses')->where('id', $guess->id)->first();

        $this->assertStringNotContainsString('花', $rawGuess->guess_encrypted);
        $this->assertStringNotContainsString('田中', $rawGuess->challenger_name_encrypted);

        // モデル経由で復号確認
        $retrieved = Guess::find($guess->id);
        $this->assertEquals('花', $retrieved->guess);
        $this->assertEquals('田中', $retrieved->challenger_name);
    }
}
