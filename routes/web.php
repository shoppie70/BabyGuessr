<?php

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', [Controller::class, 'index'])->name('index');
Route::get('/register', [Controller::class, 'register'])->name('register');
Route::post('/store', [Controller::class, 'store'])->name('store');
Route::post('/challenge', [Controller::class, 'challenge'])->name('challenge');
