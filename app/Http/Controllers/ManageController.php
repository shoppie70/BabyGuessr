<?php

namespace App\Http\Controllers;

use App\Models\BabyProfile;
use App\Models\Guess;
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
        $profile = BabyProfile::first();

        // トークン検証: プロフィールのトークン、または未登録時は環境設定のトークンと照合
        $validToken = $profile?->manage_token ?? config('game.manage_token');
        if (empty($validToken) || !hash_equals($validToken, $token)) {
            abort(404);
        }

        // 未登録状態の案内
        if (!$profile) {
            return view('manage.unregistered', [
                'token' => $token,
            ]);
        }

        $guesses = Guess::where('baby_profile_id', $profile->id)
            ->latest()
            ->paginate(30);

        return view('manage.show', [
            'profile' => $profile,
            'token' => $token,
            'guesses' => $guesses,
            'gameUrl' => url('/g/' . $profile->game_token),
            'diagnosticsUrl' => url('/diagnostics/' . $profile->diagnostics_token),
        ]);
    }

    /**
     * ゲーム状態の更新
     */
    public function updateStatus(Request $request, string $token): RedirectResponse
    {
        $profile = BabyProfile::firstOrFail();

        if (!hash_equals($profile->manage_token, $token)) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,revealed,closed'],
        ]);

        $profile->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('manage.show', ['token' => $token])
            ->with('status', 'ゲームの状態を更新しました。');
    }
}
