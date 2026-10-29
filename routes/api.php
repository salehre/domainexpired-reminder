<?php

use App\Http\Controllers\ExpiryController;
use App\Http\Middleware\ApiKey;
use Illuminate\Support\Facades\Route;

// Loaded with the /api prefix from AppServiceProvider.
Route::get('/health', fn () => response()->json(['ok' => true]));

Route::middleware(['throttle:20,1', ApiKey::class])->group(function () {
    Route::get('/expiring', [ExpiryController::class, 'index']);
    Route::post('/run', [ExpiryController::class, 'run']);
});
