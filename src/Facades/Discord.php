<?php

namespace NomanHameed\DiscordNotify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void sendToChannel(string $channelName, string $message = null, array $embeds = [], string $username = null, string $avatarUrl = null, bool $tts = false)
 * @method static void sendToMultipleChannels(array $channels, string $message = null, array $embeds = [], string $username = null, string $avatarUrl = null, bool $tts = false)
 * @method static void sendToWebhook(string $webhookUrl, string $message = null, array $embeds = [], string $username = null, string $avatarUrl = null, bool $tts = false)
 * @method static array createEmbed(string $title = null, string $description = null, int $color = 0x00ff00, array $fields = [], string $footer = null, string $thumbnail = null, string $image = null, string $url = null)
 * @method static array createField(string $name, string $value, bool $inline = false)
 * @method static array getChannels()
 * @method static bool hasChannel(string $channelName)
 * @method static void addChannel(string $name, string $webhookUrl, string $username = null, string $avatarUrl = null)
 * @method static \NomanHameed\DiscordNotify\Notifications\DiscordNotification notification()
 */
class Discord extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'discord';
    }
}
