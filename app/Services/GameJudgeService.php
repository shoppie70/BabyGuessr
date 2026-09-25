<?php

namespace App\Services;

use App\Models\BabyProfile;

class GameJudgeService
{
    public function __construct(
        protected NameNormalizationService $normalizationService,
        protected NameHmacService $hmacService
    ) {}

    /**
     * 赤ちゃんの名前を判定
     * 1. 漢字一致判定 (correct)
     * 2. 読み一致判定 (reading_match)
     * 3. 不一致判定 (wrong)
     */
    public function judge(BabyProfile $profile, string $rawGuess): GameJudgeResult
    {
        $normalizedKanji = $this->normalizationService->normalizeKanji($rawGuess);
        $normalizedKana = $this->normalizationService->normalizeKana($rawGuess);

        $kanjiHmac = $this->hmacService->generateHmac($normalizedKanji);
        $kanaHmac = $this->hmacService->generateHmac($normalizedKana);

        // 1. 漢字が正解と一致するか判定
        if ($this->hmacService->matches($kanjiHmac, $profile->given_name_hmac)) {
            return new GameJudgeResult(
                result: GameJudgeResult::CORRECT,
                message: 'おめでとうございます！！大正解です！🎉',
                guessHmac: $kanjiHmac,
                normalizedKanji: $normalizedKanji,
                normalizedKana: $normalizedKana
            );
        }

        // 2. 読みが正解と一致するか判定 (例: "はな", "ハナ")
        if ($this->hmacService->matches($kanaHmac, $profile->given_name_kana_hmac)) {
            return new GameJudgeResult(
                result: GameJudgeResult::READING_MATCH,
                message: '読み方は大正解！でも、漢字がまだ違います。もう一息！💡',
                guessHmac: $kanaHmac,
                normalizedKanji: $normalizedKanji,
                normalizedKana: $normalizedKana
            );
        }

        // 3. 不正解
        return new GameJudgeResult(
            result: GameJudgeResult::WRONG,
            message: '残念！ちがう名前です。もう一度チャレンジしてみてね！💪',
            guessHmac: $kanjiHmac,
            normalizedKanji: $normalizedKanji,
            normalizedKana: $normalizedKana
        );
    }
}
