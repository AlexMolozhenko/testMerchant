<?php

declare(strict_types=1);

use App\Application\Http\Controllers\Auth\Exchange\ExchangeTokenController;
use App\Application\Http\Controllers\Auth\OAuth2\OAuth2TokenController;
use App\Application\Http\Controllers\Merchant\GetMerchantProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/up', static fn () => response()->json(['status' => 'ok']));

Route::prefix('merchant')->group(function (): void {

    // Auth — public
    Route::prefix('auth')->group(function (): void {
        Route::post('exchange', ExchangeTokenController::class);
    });

    Route::prefix('oauth2')->group(function (): void {
        Route::post('token', OAuth2TokenController::class);
    });

    // Protected routes
    Route::middleware('merchant.auth')->group(function (): void {
        Route::get('profile', GetMerchantProfileController::class);
    });
});
