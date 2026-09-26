<?php

namespace App\Http\Controllers;

use App\Models\BabyProfile;
use Illuminate\View\View;

class HubController extends Controller
{
    public function show(string $token): View
    {
        $valid = config('game.hub_token');
        if (empty($valid) || !hash_equals($valid, $token)) {
            abort(404);
        }

        $setupToken = config('game.setup_token');

        $babies = BabyProfile::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (BabyProfile $profile) {
                $sexLabel = match ($profile->sex) {
                    'female' => '女の子',
                    'male' => '男の子',
                    default => '赤ちゃん',
                };

                $parts = array_filter([
                    $profile->family_name ?: null,
                    $sexLabel,
                    $profile->birth_date?->format('Y年n月j日'),
                ]);

                return [
                    'label' => implode(' · ', $parts) ?: '赤ちゃん',
                    'gameUrl' => url('/g/' . $profile->game_token),
                    'manageUrl' => url('/manage/' . $profile->manage_token),
                    'diagnosticsUrl' => url('/diagnostics/' . $profile->diagnostics_token),
                ];
            });

        return view('hub.show', [
            'babies' => $babies,
            'setupUrl' => $setupToken ? url('/setup/' . $setupToken) : null,
        ]);
    }
}
