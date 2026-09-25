<?php

use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\SetupController;
use App\Models\BabyProfile;
use Illuminate\Support\Facades\Route;

// ルートURL (/) は非公開。local のときだけ開発用リンクを出す。
Route::get('/', function () {
    $links = [];
    if (app()->environment('local')) {
        $profile = BabyProfile::query()->first();
        $setup = config('game.setup_token');
        $manage = $profile?->manage_token ?: config('game.manage_token');
        $diagnostics = $profile?->diagnostics_token ?: config('game.diagnostics_token');
        $links = array_filter([
            '登録' => $setup ? url('/setup/'.$setup) : null,
            '名前当て' => $profile ? url('/g/'.$profile->game_token) : null,
            '鑑定' => $manage ? url('/manage/'.$manage) : null,
            '診断' => $diagnostics ? url('/diagnostics/'.$diagnostics) : null,
        ]);
    }

    return response()->view('landing-private', ['links' => $links], 404);
});

// 初期セットアップフロー (1デプロイにつき1回限りの登録)
Route::prefix('setup/{token}')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'show'])->name('show');
    Route::post('/confirm', [SetupController::class, 'confirm'])->name('confirm');
    Route::post('/', [SetupController::class, 'store'])->name('store');
    Route::get('/complete', [SetupController::class, 'complete'])->name('complete');
});

// ゲーム参加者用
Route::prefix('g/{token}')->name('game.')->group(function () {
    Route::get('/', [GameController::class, 'show'])->name('show');
    Route::post('/guess', [GameController::class, 'guess'])->name('guess');
});

// 管理者・両親用
Route::prefix('manage/{token}')->name('manage.')->group(function () {
    Route::get('/', [ManageController::class, 'show'])->name('show');
    Route::post('/status', [ManageController::class, 'updateStatus'])->name('status');
    Route::post('/fortune/generate', [ManageController::class, 'generateFortune'])->name('fortune.generate');
    Route::get('/fortune', [ManageController::class, 'fortune'])->name('fortune');
});

// 運用確認・診断用 (ネタバレ防止)
Route::prefix('diagnostics/{token}')->name('diagnostics.')->group(function () {
    Route::get('/', [DiagnosticsController::class, 'show'])->name('show');
});
