<?php

namespace ESolution\WhatsApp;

use Illuminate\Support\ServiceProvider;
use ESolution\WhatsApp\Services\WhatsAppService;
use ESolution\WhatsApp\Console\Commands\BroadcastRunCommand;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/whatsapp.php', 'whatsapp');

        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService(config('whatsapp'));
        });

        foreach (
            [
                Services\TechProvider\AssetService::class,
                Services\TechProvider\FlowsService::class,
                Services\TechProvider\MediaService::class,
                Services\TechProvider\OnboardingService::class,
                Services\TechProvider\ProfileService::class,
                Services\TechProvider\AnalyticsService::class,
            ] as $service
        ) {
            $this->app->singleton($service, function ($app) use ($service) {
                return new $service(config('whatsapp'));
            });
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([__DIR__ . '/../config/whatsapp.php' => config_path('whatsapp.php')], 'whatsapp-config');
        $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'whatsapp-migrations');

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([BroadcastRunCommand::class]);
        }
    }
}
