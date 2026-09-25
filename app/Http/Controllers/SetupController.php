<?php

namespace App\Http\Controllers;

use App\Models\BabyProfile;
use App\Services\NameHmacService;
use App\Services\NameNormalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __construct(
        protected NameNormalizationService $normalizer,
        protected NameHmacService $hmacService
    ) {}

    /**
     * トークンの妥当性を検証
     */
    protected function validateSetupToken(string $token): void
    {
        $validToken = config('game.setup_token');
        if (empty($validToken) || !hash_equals($validToken, $token)) {
            abort(404);
        }
    }

    /**
     * 初期登録入力画面の表示
     */
    public function show(string $token): View
    {
        $this->validateSetupToken($token);

        // 既に登録済みの場合は登録済み案内を表示
        if (BabyProfile::exists()) {
            return view('setup.already-registered', [
                'token' => $token,
            ]);
        }

        $formData = session()->get('setup_form_data', []);

        return view('setup.form', [
            'token' => $token,
            'formData' => $formData,
        ]);
    }

    /**
     * 入力内容の確認画面
     */
    public function confirm(Request $request, string $token): View|RedirectResponse
    {
        $this->validateSetupToken($token);

        if (BabyProfile::exists()) {
            return redirect()->route('setup.show', ['token' => $token]);
        }

        $validated = $request->validate([
            'family_name' => ['required', 'string', 'max:50'],
            'given_name' => ['required', 'string', 'max:50'],
            'family_name_kana' => ['required', 'string', 'max:50'],
            'given_name_kana' => ['required', 'string', 'max:50'],
            'sex' => ['required', 'in:female,male,other'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_weight' => ['nullable', 'integer', 'min:500', 'max:10000'],
        ]);

        // 確認用セッションに保存
        session()->put('setup_form_data', $validated);

        return view('setup.confirm', [
            'token' => $token,
            'data' => $validated,
        ]);
    }

    /**
     * 登録処理の実行
     */
    public function store(Request $request, string $token): View|RedirectResponse
    {
        $this->validateSetupToken($token);

        // 二重登録の完全防止
        if (BabyProfile::exists()) {
            return redirect()->route('setup.show', ['token' => $token])
                ->with('error', 'すでに赤ちゃん情報は登録されています。');
        }

        $validated = $request->validate([
            'family_name' => ['required', 'string', 'max:50'],
            'given_name' => ['required', 'string', 'max:50'],
            'family_name_kana' => ['required', 'string', 'max:50'],
            'given_name_kana' => ['required', 'string', 'max:50'],
            'sex' => ['required', 'in:female,male,other'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_weight' => ['nullable', 'integer', 'min:500', 'max:10000'],
        ]);

        // 名前・読みの正規化
        $normalizedGivenName = $this->normalizer->normalizeKanji($validated['given_name']);
        $normalizedGivenNameKana = $this->normalizer->normalizeKana($validated['given_name_kana']);

        // 判定用HMACの生成
        $givenNameHmac = $this->hmacService->generateHmac($normalizedGivenName);
        $givenNameKanaHmac = $this->hmacService->generateHmac($normalizedGivenNameKana);

        // 各種トークンの設定 (環境変数指定またはランダム生成)
        $gameToken = 'game-' . bin2hex(random_bytes(12));
        $manageToken = config('game.manage_token') ?: ('manage-' . bin2hex(random_bytes(12)));
        $diagnosticsToken = config('game.diagnostics_token') ?: ('diag-' . bin2hex(random_bytes(12)));

        // 暗号化保存 (モデルのcastsにより自動暗号化)
        $profile = BabyProfile::create([
            'family_name_encrypted' => $validated['family_name'],
            'given_name_encrypted' => $validated['given_name'],
            'family_name_kana_encrypted' => $validated['family_name_kana'],
            'given_name_kana_encrypted' => $validated['given_name_kana'],
            'given_name_hmac' => $givenNameHmac,
            'given_name_kana_hmac' => $givenNameKanaHmac,
            'birth_date' => $validated['birth_date'],
            'birth_time' => !empty($validated['birth_time']) ? ($validated['birth_time'] . ':00') : null,
            'sex' => $validated['sex'],
            'birth_place_encrypted' => $validated['birth_place'] ?? null,
            'birth_weight' => $validated['birth_weight'] ?? null,
            'status' => 'open',
            'game_token' => $gameToken,
            'manage_token' => $manageToken,
            'diagnostics_token' => $diagnosticsToken,
        ]);

        // 占術基礎計算の実行 (バックグラウンド/同期で暗号化保存)
        try {
            app(\App\Services\Fortune\FortuneManager::class)->calculateAll($profile);
            app(\App\Services\Fortune\GeminiFortuneService::class)->queueBatch($profile);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Fortune calculation failed on setup: ' . $e->getMessage());
        }

        // 一時セッションデータのクリア
        session()->forget('setup_form_data');

        // 登録完了画面表示用セッション
        session()->flash('setup_completed', true);
        session()->flash('registered_game_token', $profile->game_token);
        session()->flash('registered_manage_token', $profile->manage_token);

        return redirect()->route('setup.complete', ['token' => $token]);
    }

    /**
     * 登録完了画面の表示
     */
    public function complete(string $token): View|RedirectResponse
    {
        $this->validateSetupToken($token);

        $profile = BabyProfile::first();
        if (!$profile) {
            return redirect()->route('setup.show', ['token' => $token]);
        }

        $gameUrl = url('/g/' . $profile->game_token);
        $manageUrl = url('/manage/' . $profile->manage_token);

        return view('setup.complete', [
            'token' => $token,
            'gameUrl' => $gameUrl,
            'manageUrl' => $manageUrl,
        ]);
    }
}
