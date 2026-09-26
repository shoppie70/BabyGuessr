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

        $normalizedGivenName = $this->normalizer->normalizeKanji($validated['given_name']);
        $normalizedGivenNameKana = $this->normalizer->normalizeKana($validated['given_name_kana']);

        $givenNameHmac = $this->hmacService->generateHmac($normalizedGivenName);
        $givenNameKanaHmac = $this->hmacService->generateHmac($normalizedGivenNameKana);

        // 複数登録時に衝突しないよう、毎回ランダム発行する
        $gameToken = 'game-' . bin2hex(random_bytes(12));
        $manageToken = 'manage-' . bin2hex(random_bytes(12));
        $diagnosticsToken = 'diag-' . bin2hex(random_bytes(12));

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

        try {
            app(\App\Services\Fortune\FortuneManager::class)->calculateAll($profile);
            app(\App\Services\Fortune\GeminiFortuneService::class)->queueBatch($profile);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Fortune calculation failed on setup: ' . $e->getMessage());
        }

        session()->forget('setup_form_data');
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

        if (!session('setup_completed')) {
            return redirect()->route('setup.show', ['token' => $token]);
        }

        $gameToken = session('registered_game_token');
        $manageToken = session('registered_manage_token');

        return view('setup.complete', [
            'token' => $token,
            'gameUrl' => $gameToken ? url('/g/' . $gameToken) : null,
            'manageUrl' => $manageToken ? url('/manage/' . $manageToken) : null,
        ]);
    }
}
