<?php

namespace App\Http\Controllers;

use App\Models\BabyProfile;
use App\Models\Guess;
use App\Services\GameJudgeResult;
use App\Services\GameJudgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function __construct(
        protected GameJudgeService $judgeService
    ) {}

    /**
     * ゲーム画面の表示
     */
    public function show(string $token): View
    {
        $profile = BabyProfile::where('game_token', $token)->firstOrFail();

        $sessionKey = 'game_session_' . $profile->id;
        $sessionIdentifier = session()->get($sessionKey);
        if (!$sessionIdentifier) {
            $sessionIdentifier = bin2hex(random_bytes(16));
            session()->put($sessionKey, $sessionIdentifier);
        }

        $attemptCount = Guess::where('baby_profile_id', $profile->id)
            ->where('session_identifier', $sessionIdentifier)
            ->count();

        // 正解済みか確認
        $hasWon = Guess::where('baby_profile_id', $profile->id)
            ->where('session_identifier', $sessionIdentifier)
            ->where('result', Guess::RESULT_CORRECT)
            ->exists();

        return view('game.show', [
            'profile' => $profile,
            'token' => $token,
            'attemptCount' => $attemptCount,
            'hasWon' => $hasWon,
            // 名字のみヒントとして公開
            'familyName' => $profile->family_name,
            'familyNameKana' => $profile->family_name_kana,
            // 正解済みまたは公開状態の場合のみ正解氏名を渡す
            'revealedFullName' => ($hasWon || $profile->isRevealed()) ? $profile->full_name : null,
            'revealedFullNameKana' => ($hasWon || $profile->isRevealed()) ? $profile->full_name_kana : null,
        ]);
    }

    /**
     * 名前回答の処理 (JSON API)
     */
    public function guess(Request $request, string $token): JsonResponse
    {
        $profile = BabyProfile::where('game_token', $token)->firstOrFail();

        if ($profile->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'このゲームは現在受付を終了しています。',
            ], 403);
        }

        $validated = $request->validate([
            'baby_name' => ['required', 'string', 'max:50'],
            'nickname' => ['nullable', 'string', 'max:50'],
        ]);

        $sessionKey = 'game_session_' . $profile->id;
        $sessionIdentifier = session()->get($sessionKey);
        if (!$sessionIdentifier) {
            $sessionIdentifier = bin2hex(random_bytes(16));
            session()->put($sessionKey, $sessionIdentifier);
        }

        // 判定実行
        $judgeResult = $this->judgeService->judge($profile, $validated['baby_name']);

        // セッションでの試行回数をカウント
        $currentAttempts = Guess::where('baby_profile_id', $profile->id)
            ->where('session_identifier', $sessionIdentifier)
            ->count();
        $attemptNo = $currentAttempts + 1;

        // 回答履歴の保存（暗号化して保存）
        $guess = Guess::create([
            'baby_profile_id' => $profile->id,
            'challenger_name_encrypted' => $validated['nickname'] ?? '名無しさん',
            'guess_encrypted' => $validated['baby_name'],
            'guess_hmac' => $judgeResult->guessHmac,
            'result' => $judgeResult->result,
            'session_identifier' => $sessionIdentifier,
            'attempt_no' => $attemptNo,
        ]);

        $responsePayload = [
            'result' => $judgeResult->result,
            'message' => $judgeResult->message,
            'attempt_no' => $attemptNo,
            'is_correct' => $judgeResult->isCorrect(),
            'is_reading_match' => $judgeResult->isReadingMatch(),
        ];

        // 正解した場合のみ、正式な氏名・読みをレスポンスに含める
        if ($judgeResult->isCorrect()) {
            $responsePayload['revealed_name'] = [
                'full_name' => $profile->full_name,
                'full_name_kana' => $profile->full_name_kana,
                'birth_date' => $profile->birth_date->format('Y年n月j日'),
                'birth_time' => $profile->birth_time ? substr($profile->birth_time, 0, 5) : null,
                'birth_place' => $profile->birth_place,
                'birth_weight' => $profile->birth_weight,
            ];
        }

        return response()->json($responsePayload);
    }
}
