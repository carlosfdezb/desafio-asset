<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\SlugController;
use App\Http\Controllers\StatController;
use Illuminate\Support\Facades\Route;

Route::get('/slugs', [SlugController::class, 'index']);
Route::post('/shorten', [SlugController::class, 'shorten']);
Route::delete('/{slug}', [SlugController::class, 'delete']);

Route::get('stats/{slug}', [StatController::class, 'stats'])->middleware('throttle:api');
Route::get('qr/{slug}', [QrController::class, 'generate']);

Route::get('/health', [HealthController::class, '__invoke']);
