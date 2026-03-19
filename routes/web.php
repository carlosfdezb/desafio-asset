<?php

use App\Http\Controllers\SlugController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [SlugController::class, 'redirect']);
