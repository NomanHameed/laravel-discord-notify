# Changelog

All notable changes to `nomanhameed/laravel-discord-notify` will be documented in this file.

## [1.0.1] - 2025-11-25

### Changed
- **BREAKING**: Package renamed from `tapday/notification` to `nomanhameed/laravel-discord-notify`
- **BREAKING**: Namespace changed from `Tapday\Notifications` to `NomanHameed\DiscordNotify`
- Updated all documentation and usage examples
- Added package badges to README
- Enhanced README with contributing guidelines and credits

### Migration Guide
If upgrading from v1.0.0, update your code:
```php
// Old (v1.0.0)
use Tapday\Notifications\Facades\Discord;

// New (v1.0.1)
use NomanHameed\DiscordNotify\Facades\Discord;
```

Then clear Laravel cache and regenerate autoload:
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
php artisan package:discover
```

## [1.0.0] - 2025-01-24

### Added
- Initial release
- Send Discord notifications via webhooks
- Support for multiple configured channels
- Rich embed support with customizable fields
- Custom usernames and avatars per channel
- Text-to-speech (TTS) support
- Laravel notification channel integration
- Facade support for easy access
- Configurable timeout and error handling
- Webhook URL validation
- Support for Laravel 10.x and 11.x
- PHP 8.1+ support

### Security
- Added Discord webhook URL validation to prevent unauthorized requests

[1.0.0]: https://github.com/nomanhameed/laravel-discord-notify/releases/tag/v1.0.0
