<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\SlugController;
use App\Http\Controllers\StatController;
use Illuminate\Support\Facades\Route;

Route::post('/shorten', [SlugController::class, 'shorten']);
Route::delete('/{slug}', [SlugController::class, 'delete']);

Route::get('stats/{slug}', [StatController::class, 'stats']);

Route::get('/health', [HealthController::class, '__invoke']);
