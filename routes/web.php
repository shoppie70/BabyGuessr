<?php

use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ManageController;
use Illuminate\Support\Facades\Route;

// ルートURL (/) は非公開（404 またはシンプルな非公開案内）
Route::get('/', function () {
    abort(404);
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
