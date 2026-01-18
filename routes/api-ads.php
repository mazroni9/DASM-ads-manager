<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ads\AdAccountController;
use App\Http\Controllers\Api\Ads\CampaignController;
use App\Http\Controllers\Api\Ads\CreativeController;
use App\Http\Controllers\Api\Ads\AdServingController;
use App\Http\Controllers\Api\Ads\WalletController;
use App\Http\Controllers\Api\Ads\ReportController;

/*
|--------------------------------------------------------------------------
| Ads Platform API Routes
|--------------------------------------------------------------------------
|
| All routes require authentication (Bearer token)
|
*/

Route::prefix('ads')->middleware(['auth:sanctum'])->group(function () {
    
    // Account
    Route::get('/account', [AdAccountController::class, 'show']);

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::post('/topup', [WalletController::class, 'topup']);
        // Webhook for payment gateways (no auth required, uses signature)
        Route::post('/webhooks/{provider}', [WalletController::class, 'webhook'])
            ->withoutMiddleware(['auth:sanctum']);
    });

    // Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{id}', [CampaignController::class, 'show']);
        Route::patch('/{id}', [CampaignController::class, 'update']);
        Route::post('/{id}/submit', [CampaignController::class, 'submit']);
        Route::post('/{id}/pause', [CampaignController::class, 'pause']);
        Route::post('/{id}/resume', [CampaignController::class, 'resume']);

        // Creatives (nested)
        Route::post('/{id}/creatives', [CreativeController::class, 'store']);
    });

    // Creatives
    Route::prefix('creatives')->group(function () {
        Route::get('/', [CreativeController::class, 'index']);
        Route::get('/{id}', [CreativeController::class, 'show']);
        Route::patch('/{id}', [CreativeController::class, 'update']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/summary', [ReportController::class, 'summary']);
        Route::get('/campaign/{id}', [ReportController::class, 'campaign']);
    });
});

// Ad Serving & Tracking (public-ish, needs rate limiting)
Route::prefix('ads')->group(function () {
    // Serve ads (rate limited, no auth required for public placements)
    Route::get('/serve', [AdServingController::class, 'serve'])
        ->middleware(['throttle:100,1']); // 100 requests per minute

    // Track events (rate limited)
    Route::post('/track', [AdServingController::class, 'track'])
        ->middleware(['throttle:200,1']); // 200 requests per minute
});
