<?php

namespace NomanHameed\DiscordNotify\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use NomanHameed\DiscordNotify\DiscordNotificationsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            DiscordNotificationsServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Discord' => \NomanHameed\DiscordNotify\Facades\Discord::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default config values
        config()->set('discord-notifications.webhook_url', 'https://discord.com/api/webhooks/test/test');
        config()->set('discord-notifications.username', 'Test Bot');
        config()->set('discord-notifications.avatar_url', null);
    }
}
