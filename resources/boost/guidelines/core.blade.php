## Triage

Triage is a self-hosted customer support ticketing package for Laravel. It embeds a pre-compiled React SPA dashboard at
a configurable URL path (default `/triage`) with no frontend build step required by the consuming application. All data
lives in the host app's own database.

## Installation

```
php artisan triage:install
```

This publishes the config, runs migrations, and publishes SPA assets to `public/vendor/triage/`. Manual publish tags:
`triage-config`, `triage-migrations`, `triage-assets`.

## Package Namespace

All source classes live under `HotReloadStudios\Triage\`. The facade is `HotReloadStudios\Triage\Facades\Triage`.

## Directory Structure

```
src/
TriageManager.php # Core SDK singleton — all ticket operations
TriageServiceProvider.php # Extends Spatie PackageServiceProvider
Commands/
TriageInstallCommand.php
Enums/
MessageDirection.php # Inbound | Outbound
TicketPriority.php # Low | Normal | High | Urgent
TicketStatus.php # Open | Pending | Resolved | Closed
Events/ # TicketCreated, TicketReplied, TicketUpdated, etc.
Facades/Triage.php
Http/Controllers/ # Thin JSON adapters — call TriageManager, nothing else
Jobs/ProcessInboundEmailJob.php
Listeners/
Mailbox/TriageMailbox.php
Models/
Ticket.php
TicketMessage.php
TicketNote.php
AgentPreference.php
Support/
QuotedReplyTrimmer.php
ReplyAddressFormatter.php
ReplyTokenGenerator.php
SubmitterResolver.php
config/triage.php
database/migrations/ # Stubs for tickets, messages, notes, preferences
resources/views/ # Blade shell for SPA
routes/web.php
```

## Configuration

Config file: `config/triage.php`

| Key | Default | Purpose |
|---|---|---|
| `path` | `'triage'` | Dashboard URL prefix |
| `middleware` | `['web']` | Middleware applied to all `/triage/*` routes |
| `mailbox_address` | `null` | Inbound email address (activates mailbox when set) |
| `reply_to_address` | `null` | Base address for plus-addressed reply tokens |
| `from_name` | `config('app.name')` | From name on outbound emails |
| `from_address` | `config('mail.from.address')` | From address on outbound emails |
| `user_model` | `'App\\Models\\User'` | Host app User model class |

## Authorization

Register the authorization gate once (e.g. in `AppServiceProvider::boot()`):

@verbatim
    <code-snippet name="Registering the Triage auth gate" lang="php">
        use HotReloadStudios\Triage\Facades\Triage;

        Triage::auth(fn (User $user): bool => $user->isAdmin());
    </code-snippet>
@endverbatim

## SDK — Creating and Managing Tickets

All ticket operations go through `Triage::` (the `TriageManager` facade). HTTP controllers are thin JSON adapters;
business logic always lives in `TriageManager`.

@verbatim
    <code-snippet name="Creating a ticket" lang="php">
        use HotReloadStudios\Triage\Facades\Triage;
        use HotReloadStudios\Triage\Enums\TicketPriority;

        $ticket = Triage::createTicket(
        subject: 'Login page broken',
        body: 'I cannot log in at all.',
        submitterEmail: 'jane@example.com',
        submitterName: 'Jane Doe',
        priority: TicketPriority::High, // optional, defaults to Normal
        assigneeId: null, // optional string user ID
        );
    </code-snippet>
@endverbatim

@verbatim
    <code-snippet name="Replying, adding notes, updating, and closing tickets" lang="php">
        use HotReloadStudios\Triage\Facades\Triage;
        use HotReloadStudios\Triage\Enums\TicketStatus;
        use HotReloadStudios\Triage\Enums\TicketPriority;

        // Reply to submitter (triggers outbound email)
        $message = Triage::replyToTicket(ticket: $ticket, body: 'We are looking into this.', agent: $user);

        // Add an internal note (never emailed to submitter)
        $note = Triage::addNote(ticket: $ticket, body: 'Reproduced on staging.', agent: $user);

        // Update status, priority, or assignee (only dispatches events on real changes)
        $ticket = Triage::updateTicket(
        ticket: $ticket,
        status: TicketStatus::Pending,
        priority: TicketPriority::Urgent,
        assignee: $user,
        );

        // Convenience shortcuts
        $ticket = Triage::assignTicket(ticket: $ticket, agent: $user);
        $ticket = Triage::resolveTicket(ticket: $ticket); // sets status to Resolved
        $ticket = Triage::closeTicket(ticket: $ticket); // sets status to Closed
    </code-snippet>
@endverbatim

The `agent` parameter on `replyToTicket`, `addNote`, and `assignTicket` accepts either an Eloquent `Model` or a raw user
ID string.

## Models

All models are under `HotReloadStudios\Triage\Models\`, use UUID primary keys (`HasUuids`), and define explicit
`$fillable`. User references (`submitter_id`, `assignee_id`, `author_id`) are stored as nullable strings, supporting
int/UUID/ULID host PKs without DB-level foreign key constraints.

**`Ticket` relations:**

@verbatim
    <code-snippet name="Ticket relations and query scopes" lang="php">
        $ticket->messages(); // HasMany TicketMessage (ordered by created_at)
        $ticket->notes(); // HasMany TicketNote (ordered by created_at)
        $ticket->submitter(); // BelongsTo configured user_model
        $ticket->assignee(); // BelongsTo configured user_model

        // Query scopes
        Ticket::open()->get();
        Ticket::pending()->get();
        Ticket::resolved()->get();
        Ticket::closed()->get();
        Ticket::assignedTo((string) $user->getKey())->get();
        Ticket::withPriority(TicketPriority::Urgent)->get();
    </code-snippet>
@endverbatim

## Events

| Event | Trigger |
|---|---|
| `TicketCreated` | `createTicket()` |
| `TicketReplied` | `replyToTicket()` |
| `TicketNoteAdded` | `addNote()` |
| `TicketUpdated` | `updateTicket()` — any real change to status, priority, or assignee |
| `TicketResolved` | `updateTicket()` when status transitions to `Resolved` |
| `TicketClosed` | `updateTicket()` when status transitions to `Closed` |
| `TicketMessageReceived` | Inbound email reply processed |

`TicketUpdated` carries a `$changes` array shaped as `array<string, array{old: mixed, new: mixed}>`.

    Built-in listeners: `TicketCreated` → confirmation email to submitter; `TicketReplied` → reply email to submitter.

    ## Inbound Email

    Set `triage.mailbox_address` in config to activate. The mailbox handler registers automatically when the value is
    non-null. Uses `beyondcode/laravel-mailbox` internally with plus-addressing (`support+triage-{token}@...`) for reply
    threading.

    @verbatim
        <code-snippet name="Activating inbound email" lang="php">
            // config/triage.php
            'mailbox_address' => env('TRIAGE_MAILBOX_ADDRESS'), // e.g. support@example.com
            'reply_to_address' => env('TRIAGE_REPLY_TO_ADDRESS'), // base address for plus-addressed tokens
        </code-snippet>
    @endverbatim

    Inbound emails are processed via `ProcessInboundEmailJob` (queued, 3 retries with exponential backoff). A reply
    token in the To address routes to an existing ticket; no token creates a new ticket. Quoted reply content is
    automatically stripped by `QuotedReplyTrimmer`.

    ## Enums

    All enums use TitleCase keys.

    - `TicketStatus`: `Open`, `Pending`, `Resolved`, `Closed`
    - `TicketPriority`: `Low`, `Normal`, `High`, `Urgent`
    - `MessageDirection`: `Inbound`, `Outbound`

    ## Architecture Rules

    - All business logic belongs in `TriageManager`. HTTP controllers are thin adapters only.
    - Never bypass the `Triage::` facade to interact with models directly for business operations.
    - Dispatched events should be listened to in the host app, not by monkey-patching package internals.
    - The package is compatible with Laravel 11.x and 12.x.