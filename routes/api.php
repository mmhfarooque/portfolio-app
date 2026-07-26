<?php

use App\Http\Controllers\Api\NasAnalyticsController;
use App\Http\Middleware\VerifyNasToken;
use Illuminate\Support\Facades\Route;

Route::post('/nas/analytics', [NasAnalyticsController::class, 'store'])
    ->middleware(VerifyNasToken::class)
    ->name('api.nas.analytics');
