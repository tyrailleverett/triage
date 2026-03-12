# Triage

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hotreloadstudios/triage.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/triage)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hotreloadstudios/triage/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hotreloadstudios/triage/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hotreloadstudios/triage.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/triage)

A self-hosted customer support ticketing system for Laravel. Install via Composer, run one artisan command, and get a fully functional ticket dashboard at `/triage` — no third-party SaaS, no frontend build toolchain required.

Triage ships with a pre-compiled React SPA (Horizon-style), a fluent SDK for programmatic ticket management, and inbound email support via Laravel Mailbox. All data lives in your own database.

## Requirements

- PHP 8.4+
- Laravel 11.x or 12.x
- PostgreSQL (primary supported database)
- A queue driver configured (database, Redis, etc.)

## Getting started

### 1. Install

```bash
composer require hotreloadstudios/triage
php artisan triage:install
```

The install command publishes your config, runs all four migrations, and publishes the pre-compiled dashboard assets to `public/vendor/triage/`.

### 2. Authorize access

By default, the dashboard denies all access in production. Register an authorization callback in `AppServiceProvider::boot()`:

```php
use HotReloadStudios\Triage\Facades\Triage;

Triage::auth(fn (User $user): bool => $user->isAdmin());
```

### 3. Open the dashboard

Navigate to `/triage`. You're in.

---

## SDK

Every dashboard action is available programmatically through the `Triage` facade:

```php
use HotReloadStudios\Triage\Facades\Triage;
use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;

// Create a ticket
Triage::createTicket(
    subject: 'Login not working',
    body: 'I cannot log in since yesterday.',
    submitterEmail: 'jane@example.com',
    submitterName: 'Jane Doe',
    priority: TicketPriority::High,
);

// Reply to a ticket (sends email to submitter)
Triage::replyToTicket(ticket: $ticket, body: 'We are looking into this.', agent: $user);

// Add an internal note (never sent to the customer)
Triage::addNote(ticket: $ticket, body: 'Looks like a DB issue, escalating.', agent: $user);

// Update a ticket
Triage::updateTicket(ticket: $ticket, status: TicketStatus::Resolved, assignee: $user);
Triage::assignTicket(ticket: $ticket, agent: $user);
Triage::resolveTicket(ticket: $ticket);
Triage::closeTicket(ticket: $ticket);
```

All SDK methods dispatch Laravel events. See [Events](docs/events.md) for the full list.

---

## Documentation

| Guide | Description |
|---|---|
| [Installation](docs/installation.md) | Full install walkthrough, manual publishing, upgrading |
| [Configuration](docs/configuration.md) | All `config/triage.php` options explained |
| [Authorization](docs/authorization.md) | Gated access, auth callbacks, middleware |
| [SDK Reference](docs/sdk.md) | All `Triage` facade methods with examples |
| [Events](docs/events.md) | Full event reference and listener examples |
| [Inbound Email](docs/inbound-email.md) | Laravel Mailbox setup, provider config, reply threading |
| [Dashboard](docs/dashboard.md) | Features, deep linking, asset publishing |
| [Notification Preferences](docs/notification-preferences.md) | Per-agent notification settings |
| [Data Model](docs/data-model.md) | Tables, columns, indexes, Eloquent models and scopes |

---

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
