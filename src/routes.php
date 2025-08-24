<?php

use Illuminate\Support\Facades\Route;
use ESolution\WhatsApp\Http\Controllers\{
    WebhookController, TemplateController, MessageController, BroadcastController
};

Route::middleware(config('whatsapp.routes_middleware', ['api']))
    ->prefix(config('whatsapp.routes_prefix','whatsapp'))
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
    });
