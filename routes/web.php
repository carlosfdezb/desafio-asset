<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SlugController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class);

Route::get('/{slug}', [SlugController::class, 'redirect']);
