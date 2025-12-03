<?php

namespace NomanHameed\DiscordNotify\Tests;

use NomanHameed\DiscordNotify\Services\DiscordService;
use NomanHameed\DiscordNotify\Notifications\DiscordNotification;

class DiscordServiceTest extends TestCase
{
    protected DiscordService $discordService;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure test channels BEFORE instantiating the service
        config()->set('discord-notifications.channels', [
            'general' => [
                'webhook_url' => 'https://discord.com/api/webhooks/test/general',
                'username' => 'General Bot',
                'avatar_url' => null,
            ],
            'alerts' => [
                'webhook_url' => 'https://discord.com/api/webhooks/test/alerts',
                'username' => 'Alert Bot',
                'avatar_url' => 'https://example.com/avatar.png',
            ],
        ]);

        $this->discordService = $this->app->make(DiscordService::class);
    }

    /** @test */
    public function it_can_create_an_embed()
    {
        $embed = $this->discordService->createEmbed(
            title: 'Test Title',
            description: 'Test Description',
            color: 0xff0000
        );

        $this->assertIsArray($embed);
        $this->assertEquals('Test Title', $embed['title']);
        $this->assertEquals('Test Description', $embed['description']);
        $this->assertEquals(0xff0000, $embed['color']);
        $this->assertArrayHasKey('timestamp', $embed);
    }

    /** @test */
    public function it_can_create_an_embed_with_fields()
    {
        $fields = [
            $this->discordService->createField('Field 1', 'Value 1', true),
            $this->discordService->createField('Field 2', 'Value 2', false),
        ];

        $embed = $this->discordService->createEmbed(
            title: 'Test',
            fields: $fields
        );

        $this->assertCount(2, $embed['fields']);
        $this->assertEquals('Field 1', $embed['fields'][0]['name']);
        $this->assertEquals('Value 1', $embed['fields'][0]['value']);
        $this->assertTrue($embed['fields'][0]['inline']);
    }

    /** @test */
    public function it_can_create_a_field()
    {
        $field = $this->discordService->createField('Name', 'Value', true);

        $this->assertIsArray($field);
        $this->assertEquals('Name', $field['name']);
        $this->assertEquals('Value', $field['value']);
        $this->assertTrue($field['inline']);
    }

    /** @test */
    public function it_can_get_all_configured_channels()
    {
        $channels = $this->discordService->getChannels();

        $this->assertIsArray($channels);
        $this->assertContains('general', $channels);
        $this->assertContains('alerts', $channels);
        $this->assertCount(2, $channels);
    }

    /** @test */
    public function it_can_check_if_channel_exists()
    {
        $this->assertTrue($this->discordService->hasChannel('general'));
        $this->assertTrue($this->discordService->hasChannel('alerts'));
        $this->assertFalse($this->discordService->hasChannel('nonexistent'));
    }

    /** @test */
    public function it_can_add_channel_dynamically()
    {
        $this->discordService->addChannel(
            'custom',
            'https://discord.com/api/webhooks/test/custom',
            'Custom Bot',
            'https://example.com/custom.png'
        );

        $this->assertTrue($this->discordService->hasChannel('custom'));
        $this->assertContains('custom', $this->discordService->getChannels());
    }

    /** @test */
    public function it_throws_exception_for_invalid_channel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Discord channel 'nonexistent' not found in configuration");

        $this->discordService->sendToChannel('nonexistent', 'Test message');
    }

    /** @test */
    public function it_can_create_notification_builder()
    {
        $notification = $this->discordService->notification();

        $this->assertInstanceOf(DiscordNotification::class, $notification);
    }

    /** @test */
    public function it_filters_null_values_from_embed()
    {
        $embed = $this->discordService->createEmbed(
            title: 'Test',
            description: null,
            footer: null
        );

        $this->assertArrayNotHasKey('description', $embed);
        $this->assertArrayNotHasKey('footer', $embed);
        $this->assertArrayHasKey('title', $embed);
    }
}
