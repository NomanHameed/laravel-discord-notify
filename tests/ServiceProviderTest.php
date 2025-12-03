<?php

namespace NomanHameed\DiscordNotify\Tests;

use NomanHameed\DiscordNotify\Services\DiscordService;
use NomanHameed\DiscordNotify\DiscordNotificationsServiceProvider;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_the_discord_service()
    {
        $service = $this->app->make(DiscordService::class);

        $this->assertInstanceOf(DiscordService::class, $service);
    }

    /** @test */
    public function it_registers_the_discord_facade()
    {
        $service = $this->app->make('discord');

        $this->assertInstanceOf(DiscordService::class, $service);
    }

    /** @test */
    public function it_loads_configuration()
    {
        $this->assertNotNull(config('discord-notifications'));
        $this->assertIsArray(config('discord-notifications'));
    }

    /** @test */
    public function it_registers_discord_notification_channel()
    {
        $channelManager = $this->app->make(\Illuminate\Notifications\ChannelManager::class);

        $channel = $channelManager->driver('discord');

        $this->assertInstanceOf(\NomanHameed\DiscordNotify\Channels\DiscordChannel::class, $channel);
    }
}
