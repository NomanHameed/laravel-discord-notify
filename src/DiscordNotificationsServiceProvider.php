<?php

namespace Tapday\Notifications;

use Illuminate\Support\ServiceProvider;
use Tapday\Notifications\Services\DiscordService;

class DiscordNotificationsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/config/discord-notifications.php' => config_path('discord-notifications.php'),
        ], 'discord-notifications-config');

        // Load configuration
        $this->mergeConfigFrom(
            __DIR__ . '/config/discord-notifications.php',
            'discord-notifications'
        );

        // Register the Discord notification channel
        $this->app->make(\Illuminate\Notifications\ChannelManager::class)
            ->extend('discord', function ($app) {
                return new \Tapday\Notifications\Channels\DiscordChannel();
            });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register the Discord service
        $this->app->singleton(DiscordService::class, function ($app) {
            return new DiscordService();
        });

        // Register the facade
        $this->app->bind('discord', function ($app) {
            return $app->make(DiscordService::class);
        });
    }
}
