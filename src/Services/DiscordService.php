<?php

namespace NomanHameed\DiscordNotify\Services;

use Illuminate\Support\Facades\Notification;
use NomanHameed\DiscordNotify\Notifications\DiscordNotification;

class DiscordService
{
    protected $channels;

    public function __construct()
    {
        $this->channels = config('discord-notifications.channels', []);
    }

    /**
     * Send a notification to a specific Discord channel.
     */
    public function sendToChannel(
        string $channelName,
        string $message = null,
        array $embeds = [],
        string $username = null,
        string $avatarUrl = null,
        bool $tts = false
    ): void {
        $webhookUrl = $this->getWebhookUrl($channelName);

        if (!$webhookUrl) {
            throw new \InvalidArgumentException("Discord channel '{$channelName}' not found in configuration");
        }

        $channelConfig = $this->channels[$channelName];
        $username = $username ?? $channelConfig['username'] ?? null;
        $avatarUrl = $avatarUrl ?? $channelConfig['avatar_url'] ?? null;

        $notification = new DiscordNotification($message, $webhookUrl, $embeds, $username, $avatarUrl, $tts);

        Notification::route('discord', $channelName)->notify($notification);
    }

    /**
     * Send a notification to multiple Discord channels.
     */
    public function sendToMultipleChannels(
        array $channels,
        string $message = null,
        array $embeds = [],
        string $username = null,
        string $avatarUrl = null,
        bool $tts = false
    ): void {
        foreach ($channels as $channel) {
            $this->sendToChannel($channel, $message, $embeds, $username, $avatarUrl, $tts);
        }
    }

    /**
     * Send a notification using webhook URL directly.
     */
    public function sendToWebhook(
        string $webhookUrl,
        string $message = null,
        array $embeds = [],
        string $username = null,
        string $avatarUrl = null,
        bool $tts = false
    ): void {
        $notification = new DiscordNotification($message, $webhookUrl, $embeds, $username, $avatarUrl, $tts);

        Notification::route('discord', 'webhook')->notify($notification);
    }

    /**
     * Create a Discord embed.
     */
    public function createEmbed(
        string $title = null,
        string $description = null,
        int $color = 0x00ff00,
        array $fields = [],
        string $footer = null,
        string $thumbnail = null,
        string $image = null,
        string $url = null
    ): array {
        $embed = array_filter([
            'title' => $title,
            'description' => $description,
            'color' => $color,
            'fields' => $fields,
            'timestamp' => now()->toISOString(),
            'footer' => $footer ? ['text' => $footer] : null,
            'thumbnail' => $thumbnail ? ['url' => $thumbnail] : null,
            'image' => $image ? ['url' => $image] : null,
            'url' => $url,
        ]);

        return $embed;
    }

    /**
     * Create an embed field.
     */
    public function createField(string $name, string $value, bool $inline = false): array
    {
        return [
            'name' => $name,
            'value' => $value,
            'inline' => $inline,
        ];
    }

    /**
     * Get webhook URL for a channel.
     */
    protected function getWebhookUrl(string $channelName): ?string
    {
        return $this->channels[$channelName]['webhook_url'] ?? null;
    }

    /**
     * Get all configured channels.
     */
    public function getChannels(): array
    {
        return array_keys($this->channels);
    }

    /**
     * Check if a channel exists.
     */
    public function hasChannel(string $channelName): bool
    {
        return isset($this->channels[$channelName]);
    }

    /**
     * Add a channel dynamically.
     */
    public function addChannel(string $name, string $webhookUrl, string $username = null, string $avatarUrl = null): void
    {
        $this->channels[$name] = [
            'webhook_url' => $webhookUrl,
            'username' => $username,
            'avatar_url' => $avatarUrl,
        ];
    }

    /**
     * Create a notification builder.
     */
    public function notification(): DiscordNotification
    {
        return new DiscordNotification();
    }
}
