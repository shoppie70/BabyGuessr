<?php

use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

// ルートURL (/) は非公開（404ステータスで非公開案内ページを表示）
Route::get('/', function () {
    return response()->view('landing-private', [], 404);
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
});

// 運用確認・診断用 (ネタバレ防止)
Route::prefix('diagnostics/{token}')->name('diagnostics.')->group(function () {
    Route::get('/', [DiagnosticsController::class, 'show'])->name('show');
});
