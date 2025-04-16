<?php

namespace Renderbit\LaravelWhatsapp;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class WhatsappServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register package routes (optional)
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Publish optional vendor files
        $this->publishes([
            __DIR__ . '/../routes/api.php' => base_path('routes/vendor/whatsapp-api.php'),
            __DIR__ . '/../config/whatsapp.php' => config_path('whatsapp.php'),
        ], 'whatsapp-config');
    }

    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../config/whatsapp.php', 'whatsapp');

        // Bind the client
        $this->app->singleton(WhatsappClient::class, function ($app) {
            return new WhatsappClient(
                config('whatsapp'),
                $app->make(LoggerInterface::class),
                $app->make(CacheInterface::class)
            );
        });

        $this->app->alias(WhatsappClient::class, 'whatsapp');
    }
}
