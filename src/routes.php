<?php

use Illuminate\Support\Facades\Route;
use ESolution\WhatsApp\Http\Controllers\{
    WebhookController,
    TemplateController,
    MessageController,
    BroadcastController,
    AssetController,
    FlowsController,
    MediaController,
    OnboardingController,
    ProfileController,
    AnalyticsController,
    TokenController
};

Route::middleware(config('whatsapp.routes_middleware', ['api']))
    ->prefix(config('whatsapp.routes_prefix', 'whatsapp'))
    ->group(function () {
        Route::get('webhook', [WebhookController::class, 'verify']);
        Route::post('webhook', [WebhookController::class, 'handle']);

        Route::get('templates', [TemplateController::class, 'index']);
        Route::post('templates', [TemplateController::class, 'store']);
        Route::get('templates/{id}', [TemplateController::class, 'show']);
        Route::put('templates/{id}', [TemplateController::class, 'update']);
        Route::delete('templates/{id}', [TemplateController::class, 'destroy']);
        Route::post('templates/{id}/sync', [TemplateController::class, 'syncFromMeta']);

        Route::post('messages/send', [MessageController::class, 'send']);

        Route::get('broadcasts', [BroadcastController::class, 'index']);
        Route::post('broadcasts', [BroadcastController::class, 'store']);
        Route::post('broadcasts/{id}/schedule', [BroadcastController::class, 'schedule']);
        Route::post('broadcasts/{id}/pause', [BroadcastController::class, 'pause']);
        Route::post('broadcasts/{id}/resume', [BroadcastController::class, 'resume']);

        // Tech Provider Assets
        Route::get('accounts/{account}/phone-numbers', [AssetController::class, 'index']);
        Route::get('accounts/{account}/phone-numbers/{phoneNumberId}', [AssetController::class, 'show']);
        Route::post('accounts/{account}/phone-numbers/{phoneNumberId}/register', [AssetController::class, 'register']);
        Route::post('accounts/{account}/phone-numbers/{phoneNumberId}/verify', [AssetController::class, 'verify']);

        // Tech Provider Flows
        Route::get('accounts/{account}/flows', [FlowsController::class, 'index']);
        Route::post('accounts/{account}/flows', [FlowsController::class, 'store']);
        Route::post('accounts/{account}/flows/{flowId}/assets', [FlowsController::class, 'updateAsset']);
        Route::post('accounts/{account}/flows/{flowId}/publish', [FlowsController::class, 'publish']);

        // Tech Provider Media
        Route::post('accounts/{account}/media', [MediaController::class, 'store']);
        Route::get('accounts/{account}/media/{mediaId}', [MediaController::class, 'show']);
        Route::delete('accounts/{account}/media/{mediaId}', [MediaController::class, 'destroy']);

        // Tech Provider Onboarding
        Route::post('onboarding/exchange-token', [OnboardingController::class, 'exchangeToken']);
        Route::post('onboarding/debug-token', [OnboardingController::class, 'debugToken']);

        // Tech Provider Profile
        Route::get('accounts/{account}/profile/{phoneNumberId}', [ProfileController::class, 'show']);
        Route::post('accounts/{account}/profile/{phoneNumberId}', [ProfileController::class, 'update']);

        // Tech Provider Analytics
        Route::get('accounts/{account}/analytics', [AnalyticsController::class, 'wabaMetrics']);
        Route::get('accounts/{account}/phone-numbers/{phoneNumberId}/health', [AnalyticsController::class, 'phoneHealth']);

        // Inbound Tokens
        Route::post('tokens', [TokenController::class, 'store']);
        Route::post('tokens/consume', [TokenController::class, 'consume']);
    });
