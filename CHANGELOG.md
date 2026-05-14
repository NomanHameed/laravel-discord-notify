# Changelog

All notable changes to `nomanhameed/laravel-discord-notify` will be documented in this file.

## [1.0.3] - 2026-05-14

### Fixed
- Discord rejected payloads where `content` exceeded 2000 characters (`400 Bad Request: "Must be 2000 or fewer in length"`). `DiscordChannel::send()` now splits oversize content into successive webhook posts, attaching embeds only to the first post and preferring newline break points for cleaner stack-trace splits. The fan-out is capped at 5 posts; if the content would need more, the final post is suffixed with `… [truncated]`.
- Defensive caps applied to all embed fields (title, description, footer, field name, field value) and to embeds-per-message / fields-per-embed counts, so an oversize embed no longer rejects the whole request.

### Added
- `DiscordChannelTest` covering single-post, splitting, embed-on-first-chunk, truncation-on-cap, embed-field caps, and no-op-when-empty.

## [1.0.2] - 2025-12-03

### Added
- Added support for Laravel 12.x
- Added support for Orchestra Testbench 10.x

### Fixed
- Fixed PHPUnit exit code issue by disabling failOnWarning for coverage driver warnings

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
