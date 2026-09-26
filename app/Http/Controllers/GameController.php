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

        $guessHistory = Guess::where('baby_profile_id', $profile->id)
            ->orderBy('id')
            ->get(['guess_encrypted', 'result', 'attempt_no']);

        // 正解済みか確認
        $hasWon = Guess::where('baby_profile_id', $profile->id)
            ->where('session_identifier', $sessionIdentifier)
            ->where('result', Guess::RESULT_CORRECT)
            ->exists();

        // 正解前ヒント情報 (性別、生年月日のみ)
        $sexLabel = match ($profile->sex) {
            'female' => '女の子 👧',
            'male' => '男の子 👦',
            default => '赤ちゃん',
        };

        return view('game.show', [
            'profile' => $profile,
            'token' => $token,
            'attemptCount' => $attemptCount,
            'guessHistory' => $guessHistory,
            'hasWon' => $hasWon,
            // 正解前ヒント情報
            'sexLabel' => $sexLabel,
            'birthDateLabel' => $profile->birth_date ? $profile->birth_date->format('Y年n月j日') : null,
            // 正解済みまたは公開状態の場合のみ正解氏名・読みを渡す (出生地・時刻等は一切渡さない)
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
        Guess::create([
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
            'guess' => $validated['baby_name'],
            'is_correct' => $judgeResult->isCorrect(),
            'is_reading_match' => $judgeResult->isReadingMatch(),
        ];

        // 正解した場合のみ、正式な氏名・読みをレスポンスに含める（出生地・時刻・体重は秘匿）
        if ($judgeResult->isCorrect()) {
            $responsePayload['revealed_name'] = [
                'full_name' => $profile->full_name,
                'full_name_kana' => $profile->full_name_kana,
            ];
        }

        return response()->json($responsePayload);
    }
}
