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

            // 占術計算ステータス (本文は含めずステータスのみ抽出)
            $calculations = $profile->fortuneCalculations->keyBy('calculator_key');
            $fortuneKeys = [
                'numerology' => '数秘術',
                'shukuyo' => '宿曜占星術',
                'nine_star_ki' => '九星気学',
                'four_pillars' => '四柱推命',
                'sanmeigaku' => '算命学',
                'western_astrology' => '西洋占星術',
                'zi_wei_dou_shu' => '紫微斗数',
            ];

            $fortuneStats = [];
            foreach ($fortuneKeys as $key => $label) {
                $calc = $calculations->get($key);
                $fortuneStats[$key] = [
                    'label' => $label,
                    'status' => $calc?->status ?? 'uncalculated',
                    'version' => $calc?->calculator_version ?? null,
                    'calculated_at' => $calc?->calculated_at?->format('Y-m-d H:i:s'),
                ];
            }

            $aiReport = $profile->fortuneReport;
            $aiReportStats = [
                'status' => $aiReport?->status ?? 'not_generated',
                'model' => $aiReport?->model,
                'generated_at' => $aiReport?->generated_at?->format('Y-m-d H:i:s'),
                'input_tokens' => $aiReport?->input_tokens,
                'output_tokens' => $aiReport?->output_tokens,
                'error_code' => $aiReport?->error_code,
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

            $fortuneStats = [];
            $aiReportStats = [
                'status' => 'not_generated',
                'model' => null,
                'generated_at' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'error_code' => null,
            ];
        }

        $diagnostics = [
            'baby_profile' => $babyProfileData,
            'game_stats' => $gameStats,
            'fortune_stats' => $fortuneStats,
            'ai_report' => $aiReportStats,
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
