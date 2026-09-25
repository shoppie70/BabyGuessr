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
        $profile = BabyProfile::where('diagnostics_token', $token)->firstOrFail();

        $totalGuesses = Guess::where('baby_profile_id', $profile->id)->count();
        $correctGuesses = Guess::where('baby_profile_id', $profile->id)
            ->where('result', Guess::RESULT_CORRECT)
            ->count();
        $readingMatchGuesses = Guess::where('baby_profile_id', $profile->id)
            ->where('result', Guess::RESULT_READING_MATCH)
            ->count();

        // 状態のみ判定 (平文値そのものはビューに渡さない)
        $diagnostics = [
            'baby_profile' => [
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
            ],
            'game_stats' => [
                'total_guesses' => $totalGuesses,
                'correct_guesses' => $correctGuesses,
                'reading_matches' => $readingMatchGuesses,
                'wrong_guesses' => $totalGuesses - $correctGuesses - $readingMatchGuesses,
            ],
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
