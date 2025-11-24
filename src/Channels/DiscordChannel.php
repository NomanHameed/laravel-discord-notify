<?php

namespace Tapday\Notifications\Channels;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DiscordChannel
{
    protected $client;
    protected $timeout;
    protected $logErrors;
    protected $throwExceptions;

    public function __construct()
    {
        $this->client = new Client();
        $this->timeout = config('discord-notifications.timeout', 10);
        $this->logErrors = config('discord-notifications.log_errors', true);
        $this->throwExceptions = config('discord-notifications.throw_exceptions', false);
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toDiscord($notifiable);

        if (!$message || !isset($message['webhook_url'])) {
            return;
        }

        $webhookUrl = $message['webhook_url'];

        if (!$this->isValidDiscordWebhook($webhookUrl)) {
            if ($this->logErrors) {
                Log::error('Invalid Discord webhook URL provided', ['url' => $webhookUrl]);
            }
            return;
        }

        $payload = [
            'content' => $message['content'] ?? null,
            'embeds' => $message['embeds'] ?? [],
            'username' => $message['username'] ?? config('discord-notifications.default_username'),
            'avatar_url' => $message['avatar_url'] ?? config('discord-notifications.default_avatar_url'),
            'tts' => $message['tts'] ?? false,
        ];

        try {
            $this->client->post($webhookUrl, [
                'json' => array_filter($payload, function ($value) {
                    return $value !== null && $value !== [];
                }),
                'timeout' => $this->timeout,
            ]);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Validate if the URL is a valid Discord webhook.
     */
    protected function isValidDiscordWebhook(string $url): bool
    {
        return (bool) preg_match(
            '#^https://discord\.com/api/webhooks/\d+/[\w-]+$#',
            $url
        );
    }

    /**
     * Handle exceptions.
     */
    protected function handleException(RequestException $e)
    {
        if ($this->logErrors) {
            $response = $e->getResponse();
            Log::error('Discord notification failed: ' . $e->getMessage(), [
                'response' => $response ? $response->getBody()->getContents() : null,
                'status_code' => $response ? $response->getStatusCode() : null,
            ]);
        }

        if ($this->throwExceptions) {
            throw $e;
        }
    }
}
