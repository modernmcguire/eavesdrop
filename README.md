# Eavesdrop

## Log every outbound SMS and email — and optionally intercept them

[![Latest Version on Packagist](https://img.shields.io/packagist/v/modernmcguire/eavesdrop.svg?style=flat-square)](https://packagist.org/packages/modernmcguire/eavesdrop)
[![Total Downloads](https://img.shields.io/packagist/dt/modernmcguire/eavesdrop.svg?style=flat-square)](https://packagist.org/packages/modernmcguire/eavesdrop)


Eavesdrop records every outbound SMS and email your app sends, saves it to the database, and gives you a clean UI to inspect it. On top of the log, it can optionally **intercept** messages — halting them before they're delivered. You can safely trigger real flows in development without anything reaching a customer.

Eavesdrop is two things stacked:

1. **A logger** — every matched SMS and email is captured, in every environment.
2. **An optional intercept** — when enabled, matched messages are logged and never sent.

Logging is on by default everywhere. Interception is **off on production** by default, so real messages are never blocked on prod. No changes to your existing notifications or mailables are required.

---

## How it works

- **SMS** notifications have no transport layer the way mail does, so Eavesdrop listens for Laravel's `NotificationSending` event. When a notification is bound for a watched channel, it's logged; if interception is on, the send is halted and the channel driver is never invoked.
- **Email** is captured through Laravel's Mail layer via the `MessageSending` event — this covers both `Mail::send()` calls and mail-channel notifications. It's logged; if interception is on, the message is halted before delivery.

The inbox UI is a single Blade page powered by Alpine.js (loaded from CDN) that talks to two JSON endpoints — no Livewire, no build step.

## Installation

```bash
composer require modernmcguire/eavesdrop
```

1. Run migrations

```bash
php artisan migrate
```

2. Publish and configure

```bash
php artisan vendor:publish --tag=config
```

Then list the SMS channels you want to watch in `config/eavesdrop.php`:

```php
'sms' => [
    'channels' => [
        'twilio',
        // 'vonage',
        // NotificationChannels\Twilio\TwilioChannel::class,
    ],
],
```

Entries may be a short driver name (`'twilio'`, `'vonage'`) or a channel class — matching is namespace- and case-insensitive. Email needs no channel configuration; all outbound mail is captured.

### And that's it!

## Configuration

| Key                    | Env                        | Default                    | Description                                                  |
|------------------------|----------------------------|----------------------------|--------------------------------------------------------------|
| `sms.log`              | `EAVESDROP_SMS_LOG`        | `true`                     | Log matched outbound SMS.                                     |
| `sms.intercept`        | `EAVESDROP_SMS_INTERCEPT`  | `! app()->isProduction()`  | Halt matched SMS so they're never sent. Off on prod.         |
| `sms.channels`         | —                          | `['twilio']`               | Notification channels to watch.                              |
| `email.log`            | `EAVESDROP_EMAIL_LOG`      | `true`                     | Log outbound email.                                          |
| `email.intercept`      | `EAVESDROP_EMAIL_INTERCEPT`| `! app()->isProduction()`  | Halt email so it's never sent. Off on prod.                  |
| `endpoint`             | `EAVESDROP_ENDPOINT`       | `eavesdrop`                | Inbox path.                                                  |
| `middleware`           | —                          | `['web']`                  | Middleware for the inbox. Eavesdrop's `Authorize` middleware is always appended. |

## Mail transport

Eavesdrop registers an `eavesdrop` mail transport — a black hole that accepts every message and delivers nothing. Point your mailer at it to guarantee no email ever leaves the app, while the logger still captures everything to the inbox:

```env
MAIL_MAILER=eavesdrop
```

No `config/mail.php` changes needed — the mailer is registered automatically (and skipped if you define your own `eavesdrop` mailer). This is entirely opt-in; your default mailer is untouched unless you set it.

## Authorization

Out of the box the inbox is only accessible in the `local` environment. To allow access anywhere else (staging, production), define a `viewEavesdrop` gate in your `AppServiceProvider`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewEavesdrop', function (User $user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
}
```

Once you define the gate, it becomes the single source of truth in **every** environment. To keep guest access working locally, allow a nullable user:

```php
Gate::define('viewEavesdrop', function (?User $user) {
    return app()->environment('local') || $user?->isAdmin();
});
```

## Usage

The UI can be found at `/eavesdrop` (configurable via the config file).

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ben@modernmcguire.com instead of using the issue tracker.

## Credits

-   [Matt Murtaugh](https://github.com/mattmurtaugh)
-   [Ben Miller](https://github.com/modernmcguire)
-   [All Contributors](../../contributors)
-   [Icon Creator - kerismaker](https://www.flaticon.com/authors/kerismaker)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
