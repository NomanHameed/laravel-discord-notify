<?php

namespace NomanHameed\DiscordNotify\Channels;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DiscordChannel
{
    // Discord API limits — see https://discord.com/developers/docs/resources/channel
    public const MAX_CONTENT_LENGTH = 2000;
    public const MAX_EMBED_TITLE = 256;
    public const MAX_EMBED_DESCRIPTION = 4096;
    public const MAX_EMBED_FIELD_NAME = 256;
    public const MAX_EMBED_FIELD_VALUE = 1024;
    public const MAX_EMBED_FOOTER = 2048;
    public const MAX_EMBEDS_PER_MESSAGE = 10;
    public const MAX_FIELDS_PER_EMBED = 25;

    // Safety cap on how many follow-up posts a single oversize `content` can fan out into.
    public const MAX_CONTENT_CHUNKS = 5;

    public const TRUNCATION_SUFFIX = '… [truncated]';

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

        $embeds = $this->sanitizeEmbeds($message['embeds'] ?? []);
        $contentChunks = $this->splitContent($message['content'] ?? null);

        if (empty($contentChunks) && empty($embeds)) {
            return;
        }

        // If there are no content chunks but embeds exist, emit a single embeds-only message.
        if (empty($contentChunks)) {
            $contentChunks = [null];
        }

        $username = $message['username'] ?? config('discord-notifications.default_username');
        $avatarUrl = $message['avatar_url'] ?? config('discord-notifications.default_avatar_url');
        $tts = $message['tts'] ?? false;

        foreach ($contentChunks as $index => $chunk) {
            $payload = [
                'content' => $chunk,
                'embeds' => $index === 0 ? $embeds : [], // embeds ride only on the first post
                'username' => $username,
                'avatar_url' => $avatarUrl,
                'tts' => $tts,
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
                return; // abort the rest of the sequence on first failure
            }
        }
    }

    /**
     * Split content that exceeds Discord's 2000-char limit into successive chunks.
     * The final chunk is suffixed with "… [truncated]" if we hit MAX_CONTENT_CHUNKS.
     */
    protected function splitContent(?string $content): array
    {
        if ($content === null || $content === '') {
            return [];
        }

        if (mb_strlen($content) <= self::MAX_CONTENT_LENGTH) {
            return [$content];
        }

        $chunks = [];
        $remaining = $content;

        while ($remaining !== '' && count($chunks) < self::MAX_CONTENT_CHUNKS) {
            if (mb_strlen($remaining) <= self::MAX_CONTENT_LENGTH) {
                $chunks[] = $remaining;
                break;
            }

            $isFinalAllowedChunk = count($chunks) === self::MAX_CONTENT_CHUNKS - 1;
            $limit = $isFinalAllowedChunk
                ? self::MAX_CONTENT_LENGTH - mb_strlen(self::TRUNCATION_SUFFIX)
                : self::MAX_CONTENT_LENGTH;

            $chunk = mb_substr($remaining, 0, $limit);

            // Prefer breaking on a newline if one exists near the tail of the chunk,
            // so we don't split mid-line in stack traces. Only for non-final chunks.
            if (!$isFinalAllowedChunk) {
                $breakPos = mb_strrpos($chunk, "\n");
                if ($breakPos !== false && $breakPos > $limit - 200) {
                    $chunk = mb_substr($chunk, 0, $breakPos);
                }
            }

            $consumed = mb_strlen($chunk);

            if ($isFinalAllowedChunk) {
                $chunk .= self::TRUNCATION_SUFFIX;
            }

            $chunks[] = $chunk;
            $remaining = mb_substr($remaining, $consumed);
        }

        return $chunks;
    }

    /**
     * Apply Discord's per-field limits defensively so a long description or
     * field value can't reject the whole payload.
     */
    protected function sanitizeEmbeds(array $embeds): array
    {
        if (empty($embeds)) {
            return [];
        }

        $embeds = array_slice($embeds, 0, self::MAX_EMBEDS_PER_MESSAGE);

        foreach ($embeds as &$embed) {
            if (isset($embed['title'])) {
                $embed['title'] = $this->cap($embed['title'], self::MAX_EMBED_TITLE);
            }
            if (isset($embed['description'])) {
                $embed['description'] = $this->cap($embed['description'], self::MAX_EMBED_DESCRIPTION);
            }
            if (isset($embed['footer']['text'])) {
                $embed['footer']['text'] = $this->cap($embed['footer']['text'], self::MAX_EMBED_FOOTER);
            }
            if (isset($embed['fields']) && is_array($embed['fields'])) {
                $embed['fields'] = array_slice($embed['fields'], 0, self::MAX_FIELDS_PER_EMBED);
                foreach ($embed['fields'] as &$field) {
                    if (isset($field['name'])) {
                        $field['name'] = $this->cap($field['name'], self::MAX_EMBED_FIELD_NAME);
                    }
                    if (isset($field['value'])) {
                        $field['value'] = $this->cap($field['value'], self::MAX_EMBED_FIELD_VALUE);
                    }
                }
                unset($field);
            }
        }
        unset($embed);

        return $embeds;
    }

    /**
     * Truncate a string to `$limit` chars, appending the truncation suffix if cut.
     */
    protected function cap(?string $value, int $limit): ?string
    {
        if ($value === null || mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit - mb_strlen(self::TRUNCATION_SUFFIX)) . self::TRUNCATION_SUFFIX;
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
