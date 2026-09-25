<?php

namespace App\Http\Controllers;

use App\Models\BabyProfile;
use App\Models\Guess;
use Illuminate\View\View;

class DiagnosticsController extends Controller
{
    /**
     * 診断画面の表示 (秘密情報を平文表示せず状態のみ確認)
     */
    public function show(string $token): View
    {
        $profile = BabyProfile::first();

        // トークン検証: プロフィールのトークン、または未登録時は環境設定のトークンと照合
        $validToken = $profile?->diagnostics_token ?? config('game.diagnostics_token');
        if (empty($validToken) || !hash_equals($validToken, $token)) {
            abort(404);
        }

        if ($profile) {
            $totalGuesses = Guess::where('baby_profile_id', $profile->id)->count();
            $correctGuesses = Guess::where('baby_profile_id', $profile->id)
                ->where('result', Guess::RESULT_CORRECT)
                ->count();
            $readingMatchGuesses = Guess::where('baby_profile_id', $profile->id)
                ->where('result', Guess::RESULT_READING_MATCH)
                ->count();

            $babyProfileData = [
                'registered' => true,
                'has_name' => !empty($profile->given_name_hmac),
                'has_reading' => !empty($profile->given_name_kana_hmac),
                'has_birth_date' => !empty($profile->birth_date),
                'has_birth_time' => !empty($profile->birth_time),
                'has_birth_place' => !empty($profile->birth_place_encrypted),
                'has_birth_weight' => !empty($profile->birth_weight),
                'sex' => !empty($profile->sex) ? '登録済み' : '未登録',
                'status' => match ($profile->status) {
                    'open' => '有効 (回答受付中)',
                    'revealed' => '終了 (正解公開中)',
                    'closed' => '非公開 (停止中)',
                    default => $profile->status,
                },
                'created_at' => $profile->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $profile->updated_at->format('Y-m-d H:i:s'),
            ];

            $gameStats = [
                'total_guesses' => $totalGuesses,
                'correct_guesses' => $correctGuesses,
                'reading_matches' => $readingMatchGuesses,
                'wrong_guesses' => $totalGuesses - $correctGuesses - $readingMatchGuesses,
            ];
        } else {
            // プロフィール未登録状態
            $babyProfileData = [
                'registered' => false,
                'has_name' => false,
                'has_reading' => false,
                'has_birth_date' => false,
                'has_birth_time' => false,
                'has_birth_place' => false,
                'has_birth_weight' => false,
                'sex' => '未登録',
                'status' => '未セットアップ',
                'created_at' => null,
                'updated_at' => null,
            ];

            $gameStats = [
                'total_guesses' => 0,
                'correct_guesses' => 0,
                'reading_matches' => 0,
                'wrong_guesses' => 0,
            ];
        }

        $diagnostics = [
            'baby_profile' => $babyProfileData,
            'game_stats' => $gameStats,
            'system' => [
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'db_driver' => config('database.default'),
                'hmac_configured' => !empty(config('game.hmac_secret')),
            ],
        ];

        return view('diagnostics.show', [
            'token' => $token,
            'diagnostics' => $diagnostics,
        ]);
    }
}
