<?php

namespace App\Http\Controllers;

use App\Models\BabyProfile;
use App\Services\Fortune\GeminiFortuneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageController extends Controller
{
    /**
     * 管理画面の表示
     */
    public function show(string $token): View
    {
        $profile = BabyProfile::where('manage_token', $token)->first();

        if (!$profile) {
            $bootstrap = config('game.manage_token');
            if (
                !empty($bootstrap)
                && hash_equals($bootstrap, $token)
                && !BabyProfile::exists()
            ) {
                return view('manage.unregistered', [
                    'token' => $token,
                ]);
            }

            abort(404);
        }

        return view('manage.show', [
            'profile' => $profile,
            'token' => $token,
            'gameUrl' => url('/g/' . $profile->game_token),
            'diagnosticsUrl' => url('/diagnostics/' . $profile->diagnostics_token),
            'fortuneReport' => $profile->fortuneReport,
            'fortuneUrl' => url('/manage/' . $token . '/fortune'),
        ]);
    }

    /**
     * ゲーム状態の更新
     */
    public function updateStatus(Request $request, string $token): RedirectResponse
    {
        $profile = BabyProfile::where('manage_token', $token)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'in:open,revealed,closed'],
        ]);

        $profile->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('manage.show', ['token' => $token])
            ->with('status', 'ゲームの状態を更新しました。');
    }

    /**
     * AI鑑定を同期生成（または再生成）
     */
    public function generateFortune(string $token, GeminiFortuneService $gemini): RedirectResponse
    {
        $profile = BabyProfile::where('manage_token', $token)->firstOrFail();

        $gemini->queueBatch($profile);

        return redirect()->route('manage.show', ['token' => $token])
            ->with('status', '鑑定を再度依頼しました。できたころに、このページを開き直してください。');
    }

    /**
     * 鑑定レポート表示
     */
    public function fortune(string $token): View|RedirectResponse
    {
        $profile = BabyProfile::where('manage_token', $token)->firstOrFail();

        $report = $profile->fortuneReport;

        if (!$report || !$report->hasReportBody()) {
            return redirect()->route('manage.show', ['token' => $token])
                ->with('fortune_error', 'まだ鑑定が生成されていません');
        }

        $babyName = $profile->given_name_encrypted ?: 'お子さま';

        return view('manage.fortune', [
            'profile' => $profile,
            'token' => $token,
            'report' => $report,
            'data' => $report->report_ciphertext,
            'babyName' => $babyName,
        ]);
    }
}
