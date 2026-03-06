# Triage

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hotreloadstudios/triage.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/triage)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hotreloadstudios/triage/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hotreloadstudios/triage/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hotreloadstudios/triage.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/triage)

A self-hosted customer support ticketing system for Laravel. Install via Composer, run one artisan command, and get a fully functional ticket dashboard at `/triage` — no third-party SaaS, no frontend build toolchain required.

Triage ships with a pre-compiled React SPA (Horizon-style), a fluent SDK for programmatic ticket management, and inbound email support via Laravel Mailbox. All data lives in your own database.

## Requirements

- PHP 8.4+
- Laravel 11.x or 12.x
- PostgreSQL (primary target)

## Installation

Install the package via Composer:

```bash
composer require hotreloadstudios/triage
```

Run the install command to publish config, run migrations, and publish compiled assets:

```bash
php artisan triage:install
```

## Configuration

The install command publishes `config/triage.php`. The available options:

```php
return [
    'path' => 'triage',                          // URL prefix for the dashboard
    'middleware' => ['web'],                      // Middleware applied to dashboard routes
    'mailbox_address' => null,                   // Inbound email address (Laravel Mailbox)
    'reply_to_address' => null,                  // Reply-to base address for thread token routing
    'from_name' => env('APP_NAME'),              // Sender name for outbound ticket emails
    'from_address' => env('MAIL_FROM_ADDRESS'),  // Sender address for outbound ticket emails
    'user_model' => App\Models\User::class,      // Host app's User model
];
```

## Gated Access

All `/triage/*` routes are protected by a named gate. By default, access is denied in production and allowed in `local`/`testing` environments. Register an authorization callback in your `AppServiceProvider`:

```php
use HotReloadStudios\Triage\Facades\Triage;

Triage::auth(fn (User $user): bool => $user->isAdmin());
```

## SDK Usage

The `Triage` facade provides the full SDK. Every dashboard action is also available programmatically:

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

All SDK methods dispatch Laravel events (`TicketCreated`, `TicketReplied`, `TicketResolved`, etc.) for extensibility.

## Inbound Email

Triage uses [Laravel Mailbox](https://github.com/beyondcode/laravel-mailbox) to handle inbound email. After installing and configuring Laravel Mailbox for your mail provider (Mailgun, Postmark, SendGrid, etc.), Triage automatically:

- Creates a new ticket when an unrecognized email arrives
- Appends replies to the correct ticket thread using a reply-to token
- Matches submitters to host app `User` records by email address
- Stores guests (unmatched emails) as plain name/email without a user record
- Deduplicates inbound deliveries using the `Message-ID` header

## Dashboard

The dashboard is a pre-compiled React SPA served from a package-owned Blade shell and backed by package JSON endpoints under the `/triage` route group. No frontend build toolchain is needed in the host application.

**Features:**
- Ticket list with filters (status, priority, assignee) and search
- Full ticket thread with messages and internal notes (visually distinct)
- Reply and internal note forms
- Manual ticket creation
- Metadata sidebar (status, priority, assignee)

## Events

| Event | Fired when |
|---|---|
| `TicketCreated` | A new ticket is created via any path |
| `TicketReplied` | An agent sends a reply |
| `TicketMessageReceived` | An inbound customer message is added |
| `TicketNoteAdded` | An internal note is added |
| `TicketUpdated` | Status, priority, or assignee changes |
| `TicketResolved` | Ticket moved to Resolved |
| `TicketClosed` | Ticket moved to Closed |

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
