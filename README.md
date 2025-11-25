# Laravel Discord Notifications

A Laravel package for sending notifications to Discord channels via webhooks.

## Installation

Install the package via Composer:

```bash
composer require nomanhameed/laravel-discord-notify
```

The package will automatically register itself.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=discord-notifications-config
```

Add your Discord webhook URLs to your `.env` file:

```env
DISCORD_GENERAL_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
DISCORD_ALERTS_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
DISCORD_LOGS_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN

# Optional settings
DISCORD_DEFAULT_USERNAME="Laravel Bot"
DISCORD_TIMEOUT=10
DISCORD_LOG_ERRORS=true
DISCORD_THROW_EXCEPTIONS=false
```

## Usage

### Basic Usage

```php
use NomanHameed\DiscordNotify\Facades\Discord;

// Send a simple message
Discord::sendToChannel('general', 'Hello from Laravel!');

// Send with embeds
$embed = Discord::createEmbed(
    'New Order',
    'Order #12345 has been placed',
    0x00ff00,
    [
        Discord::createField('Customer', 'John Doe', true),
        Discord::createField('Amount', '$99.99', true),
    ]
);

Discord::sendToChannel('orders', null, [$embed]);
```

### Using the Service

```php
use NomanHameed\DiscordNotify\Services\DiscordService;

class OrderController extends Controller
{
    public function store(Request $request, DiscordService $discord)
    {
        // ... create order logic
        
        $discord->sendToChannel('orders', "New order: #{$order->id}");
    }
}
```

### Advanced Usage

```php
// Send to multiple channels
Discord::sendToMultipleChannels(['general', 'alerts'], 'System maintenance in 5 minutes');

// Send directly to webhook
Discord::sendToWebhook($webhookUrl, 'Direct message');

// Create rich embeds
$embed = Discord::createEmbed(
    title: 'System Alert',
    description: 'High CPU usage detected',
    color: 0xff0000,
    fields: [
        Discord::createField('Server', 'web-01', true),
        Discord::createField('CPU Usage', '95%', true),
    ],
    footer: 'Monitoring System',
    thumbnail: 'https://example.com/alert-icon.png'
);

Discord::sendToChannel('alerts', null, [$embed]);
```

### Using Notifications

```php
use NomanHameed\DiscordNotify\Notifications\DiscordNotification;

// In your notification class
public function toDiscord($notifiable)
{
    return (new DiscordNotification())
        ->content('Your order has been shipped!')
        ->to(config('discord-notifications.channels.orders.webhook_url'))
        ->username('Order Bot')
        ->embeds([
            Discord::createEmbed('Order Shipped', 'Tracking: 1234567890')
        ]);
}
```

## Features

- ✅ Send messages to multiple Discord channels
- ✅ Rich embed support
- ✅ Custom usernames and avatars per channel
- ✅ Text-to-speech support
- ✅ Laravel notification integration
- ✅ Facade support
- ✅ Configurable error handling
- ✅ Timeout configuration

## Getting Discord Webhook URLs

1. Go to your Discord server
2. Right-click on the channel
3. Select "Edit Channel"
4. Go to "Integrations" → "Webhooks"
5. Click "Create Webhook"
6. Copy the webhook URL

## Configuration Options

The `config/discord-notifications.php` file contains all configuration options:

- `default_username`: Default bot username
- `default_avatar_url`: Default bot avatar
- `timeout`: Request timeout in seconds
- `channels`: Array of channel configurations
- `log_errors`: Whether to log errors
- `throw_exceptions`: Whether to throw exceptions on failure

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
