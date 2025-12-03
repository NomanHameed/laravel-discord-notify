<?php

namespace NomanHameed\DiscordNotify\Tests;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_has_default_configuration()
    {
        $config = config('discord-notifications');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('webhook_url', $config);
    }

    /** @test */
    public function it_can_override_configuration()
    {
        config()->set('discord-notifications.webhook_url', 'https://custom.webhook.url');

        $this->assertEquals('https://custom.webhook.url', config('discord-notifications.webhook_url'));
    }

    /** @test */
    public function it_can_configure_multiple_channels()
    {
        config()->set('discord-notifications.channels', [
            'channel1' => ['webhook_url' => 'url1'],
            'channel2' => ['webhook_url' => 'url2'],
        ]);

        $channels = config('discord-notifications.channels');

        $this->assertCount(2, $channels);
        $this->assertArrayHasKey('channel1', $channels);
        $this->assertArrayHasKey('channel2', $channels);
    }
}
