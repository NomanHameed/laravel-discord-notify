<?php

namespace NomanHameed\DiscordNotify\Notifications;

use Illuminate\Notifications\Notification;
use NomanHameed\DiscordNotify\Channels\DiscordChannel;

class DiscordNotification extends Notification
{
    protected $message;
    protected $webhookUrl;
    protected $embeds;
    protected $username;
    protected $avatarUrl;
    protected $tts;

    public function __construct(
        string $message = null,
        string $webhookUrl = null,
        array $embeds = [],
        string $username = null,
        string $avatarUrl = null,
        bool $tts = false
    ) {
        $this->message = $message;
        $this->webhookUrl = $webhookUrl;
        $this->embeds = $embeds;
        $this->username = $username;
        $this->avatarUrl = $avatarUrl;
        $this->tts = $tts;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [DiscordChannel::class];
    }

    /**
     * Get the Discord representation of the notification.
     */
    public function toDiscord($notifiable): array
    {
        return [
            'webhook_url' => $this->webhookUrl,
            'content' => $this->message,
            'embeds' => $this->embeds,
            'username' => $this->username,
            'avatar_url' => $this->avatarUrl,
            'tts' => $this->tts,
        ];
    }

    /**
     * Set the message content.
     */
    public function content(string $content): self
    {
        $this->message = $content;
        return $this;
    }

    /**
     * Set the webhook URL.
     */
    public function to(string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    /**
     * Set the embeds.
     */
    public function embeds(array $embeds): self
    {
        $this->embeds = $embeds;
        return $this;
    }

    /**
     * Set the username.
     */
    public function username(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the avatar URL.
     */
    public function avatar(string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    /**
     * Enable text-to-speech.
     */
    public function tts(bool $tts = true): self
    {
        $this->tts = $tts;
        return $this;
    }
}
