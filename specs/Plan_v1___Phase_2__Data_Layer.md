# Plan v1 — Phase 2: Data Layer — Enums, Migrations, Models, Factories

I have created the following plan after thorough exploration and analysis of the codebase. Follow the below plan verbatim. Trust the files and references. Do not re-verify what's written in the plan. Explore only when absolutely necessary. First implement all the proposed file changes and then I'll review all the changes together at the end.

---

## Observations

Phase 1 established the Triage package shell: `HotReloadStudios\Triage` namespace, `config/triage.php` with all PRD keys, `TriageServiceProvider` registering migrations/routes/views/assets, `TriageManager` as a singleton with gate callback support, `Triage` facade, and the `triage:install` command. The skeleton migration stub (`create_skeleton_table.php.stub`) needs to be replaced with three real migration stubs. The factory template in `database/factories/ModelFactory.php` is commented out and needs to be replaced with real factories. The PRD specifies UUID primary keys for all entities, string-based user references (no FK constraints to host users table), and specific indexes on all filter/sort columns.

---

## Approach

This phase builds the complete data layer. Three enums define the domain vocabulary (ticket status, priority, message direction). Three migration stubs create the database schema with UUID primary keys, string-typed user reference columns (for host-key compatibility), and comprehensive indexes. Three Eloquent models provide the ORM layer with relationships, casts, scopes, and accessors. Three factories enable testing with named states for common scenarios. The data model closely follows the PRD section 8, with every column, index, and constraint specified.

---

## - [ ] 1. Enums

Create three PHP-backed string enums in `src/Enums/`.

**`src/Enums/TicketStatus.php`**

| Case | Value | Description |
|---|---|---|
| `Open` | `'open'` | New or reopened ticket awaiting agent action |
| `Pending` | `'pending'` | Awaiting customer response or external action |
| `Resolved` | `'resolved'` | Agent has resolved the issue; awaiting confirmation or auto-close |
| `Closed` | `'closed'` | Ticket is finalized; no further action expected |

- Backed by `string`
- Namespace: `HotReloadStudios\Triage\Enums`

**`src/Enums/TicketPriority.php`**

| Case | Value | Description |
|---|---|---|
| `Low` | `'low'` | Non-urgent, can wait |
| `Normal` | `'normal'` | Standard priority (default) |
| `High` | `'high'` | Important, should be addressed soon |
| `Urgent` | `'urgent'` | Critical, needs immediate attention |

- Backed by `string`

**`src/Enums/MessageDirection.php`**

| Case | Value | Description |
|---|---|---|
| `Inbound` | `'inbound'` | Message from the customer/submitter |
| `Outbound` | `'outbound'` | Reply from an agent |

- Backed by `string`

All enums use TitleCase case names per project conventions.

---

## - [ ] 2. Migrations

Remove the skeleton migration stub `database/migrations/create_skeleton_table.php.stub`. Create three new migration stubs in `database/migrations/`. These are `.php.stub` files (Spatie convention) — the service provider publishes them with timestamped filenames.

Migration order matters for foreign key constraints: `tickets` first (referenced by the other two), then `ticket_messages`, then `ticket_notes`.

**`database/migrations/create_tickets_table.php.stub`**

| Column | Type | Notes |
|---|---|---|
| `id` | `uuid()->primary()` | Primary key |
| `subject` | `string` | Ticket subject line |
| `status` | `string` | Stores `TicketStatus` enum value. Default `'open'`. |
| `priority` | `string` | Stores `TicketPriority` enum value. Default `'normal'`. |
| `submitter_id` | `string()->nullable()` | Host user model key. Null for unmatched guests. Indexed. |
| `submitter_name` | `string` | Display name of the submitter |
| `submitter_email` | `string` | Email address of the submitter |
| `assignee_id` | `string()->nullable()` | Host user model key of assigned agent. Indexed. |
| `reply_token` | `string()->unique()` | Cryptographically random token for reply-to threading |
| `timestamps` | | `created_at` and `updated_at` |

**Indexes:**
- `index('status')`
- `index('priority')`
- `index('assignee_id')`
- `index('submitter_id')`
- `index('submitter_email')`
- `index('created_at')`

Note: `reply_token` already has a unique index via `unique()`. `submitter_id` and `assignee_id` indexes are declared explicitly (not via foreign key, since these are strings referencing the host app's user table).

**`database/migrations/create_ticket_messages_table.php.stub`**

| Column | Type | Notes |
|---|---|---|
| `id` | `uuid()->primary()` | Primary key |
| `ticket_id` | `foreignUuid('ticket_id')` | `constrained('tickets')->cascadeOnDelete()` |
| `direction` | `string` | Stores `MessageDirection` enum value |
| `author_id` | `string()->nullable()` | Host user model key. Null for inbound from unmatched guest. Indexed. |
| `message_id` | `string()->nullable()->unique()` | Inbound email `Message-ID` header for idempotency |
| `body` | `text` | Canonical plain-text message body |
| `raw_email` | `text()->nullable()` | Original inbound email payload for audit |
| `timestamps` | | `created_at` and `updated_at` |

**Indexes:**
- `index('ticket_id')` — implied by foreign key, but explicitly stated: the `foreignUuid` helper creates this
- `index('author_id')`

Note: `message_id` has a unique constraint for deduplication. The unique constraint is nullable-safe in PostgreSQL (multiple nulls are allowed in a unique column).

**`database/migrations/create_ticket_notes_table.php.stub`**

| Column | Type | Notes |
|---|---|---|
| `id` | `uuid()->primary()` | Primary key |
| `ticket_id` | `foreignUuid('ticket_id')` | `constrained('tickets')->cascadeOnDelete()` |
| `author_id` | `string` | Host user model key. Always an agent; never null. Indexed. |
| `body` | `text` | Note content |
| `timestamps` | | `created_at` and `updated_at` |

**Indexes:**
- `index('ticket_id')` — implied by foreign key
- `index('author_id')`

---

## - [ ] 3. Models

Create three Eloquent models in `src/Models/`. All models are `final`, use `declare(strict_types=1)`, and follow existing Pint conventions.

**`src/Models/Ticket.php`**

- Namespace: `HotReloadStudios\Triage\Models`
- Traits: `HasFactory`, `HasUuids`
- `$fillable`: `subject`, `status`, `priority`, `submitter_id`, `submitter_name`, `submitter_email`, `assignee_id`, `reply_token`
- `$keyType`: `'string'`
- `$incrementing`: `false`

`casts()` method (not `$casts` property — use the method form for consistency with modern Laravel):
| Attribute | Cast |
|---|---|
| `status` | `TicketStatus::class` |
| `priority` | `TicketPriority::class` |

**Relationships:**

- `messages(): HasMany` → `TicketMessage::class` — ordered by `created_at` ascending
- `notes(): HasMany` → `TicketNote::class` — ordered by `created_at` ascending
- `submitter(): BelongsTo` — dynamically resolve the model class from `config('triage.user_model')`. Use `foreignKey: 'submitter_id'` and `ownerKey` set to the configured model's `getKeyName()`. This relationship may return `null` for guest submitters.
- `assignee(): BelongsTo` — same dynamic resolution as `submitter()`, using `foreignKey: 'assignee_id'`

**Scopes:**

- `scopeOpen(Builder $query): Builder` — filters to `status = TicketStatus::Open`
- `scopePending(Builder $query): Builder` — filters to `status = TicketStatus::Pending`
- `scopeResolved(Builder $query): Builder` — filters to `status = TicketStatus::Resolved`
- `scopeClosed(Builder $query): Builder` — filters to `status = TicketStatus::Closed`
- `scopeAssignedTo(Builder $query, string $userId): Builder` — filters to `assignee_id = $userId`
- `scopeWithPriority(Builder $query, TicketPriority $priority): Builder` — filters to `priority = $priority`

**Factory resolution:** Override `newFactory()` to return `TicketFactory::new()` (since the package namespace differs from the default factory guessing convention, this explicit override ensures factories work correctly).

---

**`src/Models/TicketMessage.php`**

- Namespace: `HotReloadStudios\Triage\Models`
- Traits: `HasFactory`, `HasUuids`
- `$fillable`: `ticket_id`, `direction`, `author_id`, `message_id`, `body`, `raw_email`
- `$keyType`: `'string'`
- `$incrementing`: `false`

`casts()` method:
| Attribute | Cast |
|---|---|
| `direction` | `MessageDirection::class` |

**Relationships:**

- `ticket(): BelongsTo` → `Ticket::class`
- `author(): BelongsTo` — dynamically resolve from `config('triage.user_model')` using `foreignKey: 'author_id'`. Nullable for inbound guest messages.

**Scopes:**

- `scopeInbound(Builder $query): Builder` — filters to `direction = MessageDirection::Inbound`
- `scopeOutbound(Builder $query): Builder` — filters to `direction = MessageDirection::Outbound`

**Factory resolution:** Override `newFactory()` to return `TicketMessageFactory::new()`.

---

**`src/Models/TicketNote.php`**

- Namespace: `HotReloadStudios\Triage\Models`
- Traits: `HasFactory`, `HasUuids`
- `$fillable`: `ticket_id`, `author_id`, `body`
- `$keyType`: `'string'`
- `$incrementing`: `false`

No casts needed beyond defaults.

**Relationships:**

- `ticket(): BelongsTo` → `Ticket::class`
- `author(): BelongsTo` — dynamically resolve from `config('triage.user_model')` using `foreignKey: 'author_id'`. Always non-null (notes always have an agent author).

**Factory resolution:** Override `newFactory()` to return `TicketNoteFactory::new()`.

---

## - [ ] 4. Update Service Provider Migrations

Update `TriageServiceProvider::configurePackage()` to register all three migration stubs via `hasMigrations()`:

Pass the migration names (without `.php.stub` extension) in dependency order:
1. `create_tickets_table`
2. `create_ticket_messages_table`
3. `create_ticket_notes_table`

Remove the old skeleton migration reference (`create_migration_table_name_table`).

---

## - [ ] 5. Factories

Replace the commented-out `database/factories/ModelFactory.php` with three real factory classes. Each factory uses `fake()` (not `$this->faker`) per modern Pest/Laravel conventions.

**`database/factories/TicketFactory.php`**

- Namespace: `HotReloadStudios\Triage\Database\Factories`
- `protected $model = Ticket::class`

`definition()` returns:

| Attribute | Faker | Notes |
|---|---|---|
| `subject` | `fake()->sentence()` | |
| `status` | `TicketStatus::Open` | Default to Open |
| `priority` | `TicketPriority::Normal` | Default to Normal |
| `submitter_id` | `null` | Default to guest; states override |
| `submitter_name` | `fake()->name()` | |
| `submitter_email` | `fake()->safeEmail()` | |
| `assignee_id` | `null` | Default unassigned |
| `reply_token` | `Str::random(32)` | 32-char random string |

**Named states:**

- `open(): static` — sets `status` to `TicketStatus::Open`
- `pending(): static` — sets `status` to `TicketStatus::Pending`
- `resolved(): static` — sets `status` to `TicketStatus::Resolved`
- `closed(): static` — sets `status` to `TicketStatus::Closed`
- `lowPriority(): static` — sets `priority` to `TicketPriority::Low`
- `highPriority(): static` — sets `priority` to `TicketPriority::High`
- `urgent(): static` — sets `priority` to `TicketPriority::Urgent`
- `assigned(string $userId): static` — sets `assignee_id` to the given user ID
- `withSubmitter(string $userId): static` — sets `submitter_id` to the given user ID

---

**`database/factories/TicketMessageFactory.php`**

- Namespace: `HotReloadStudios\Triage\Database\Factories`
- `protected $model = TicketMessage::class`

`definition()` returns:

| Attribute | Faker | Notes |
|---|---|---|
| `ticket_id` | `Ticket::factory()` | Creates a parent ticket by default |
| `direction` | `MessageDirection::Inbound` | Default to inbound |
| `author_id` | `null` | Default to guest; states override |
| `message_id` | `null` | Default null; states override for inbound |
| `body` | `fake()->paragraphs(2, true)` | |
| `raw_email` | `null` | |

**Named states:**

- `inbound(): static` — sets `direction` to `MessageDirection::Inbound`
- `outbound(): static` — sets `direction` to `MessageDirection::Outbound`
- `withAuthor(string $userId): static` — sets `author_id` to the given user ID
- `withMessageId(): static` — sets `message_id` to `'<' . fake()->uuid() . '@mail.example.com>'` (simulates an email Message-ID header)
- `withRawEmail(string $raw): static` — sets `raw_email` to the given string

---

**`database/factories/TicketNoteFactory.php`**

- Namespace: `HotReloadStudios\Triage\Database\Factories`
- `protected $model = TicketNote::class`

`definition()` returns:

| Attribute | Faker | Notes |
|---|---|---|
| `ticket_id` | `Ticket::factory()` | Creates a parent ticket by default |
| `author_id` | `fake()->uuid()` | Notes always have an author; use a UUID string as placeholder |
| `body` | `fake()->paragraph()` | |

No named states needed — notes are simple.

---

## - [ ] 6. Remove Old Skeleton Files

Delete files that are no longer needed:

- `database/migrations/create_skeleton_table.php.stub` — replaced by three new migration stubs
- `database/factories/ModelFactory.php` — replaced by three specific factories
- `src/Commands/SkeletonCommand.php` — replaced by `TriageInstallCommand` in Phase 1

---

## - [ ] 7. Tests

### Unit Tests

**`tests/Unit/Enums/TicketStatusTest.php`**

- `it has exactly four cases` — assert `TicketStatus::cases()` has 4 items
- `it has the correct values` — assert each case maps to its expected string value (`'open'`, `'pending'`, `'resolved'`, `'closed'`)

**`tests/Unit/Enums/TicketPriorityTest.php`**

- `it has exactly four cases` — assert `TicketPriority::cases()` has 4 items
- `it has the correct values` — assert each case maps to its expected string value (`'low'`, `'normal'`, `'high'`, `'urgent'`)

**`tests/Unit/Enums/MessageDirectionTest.php`**

- `it has exactly two cases` — assert `MessageDirection::cases()` has 2 items
- `it has the correct values` — assert `Inbound` → `'inbound'`, `Outbound` → `'outbound'`

**`tests/Unit/Models/TicketTest.php`**

- `it casts status to TicketStatus enum` — create a Ticket, assert `$ticket->status` is an instance of `TicketStatus`
- `it casts priority to TicketPriority enum` — similar assertion for priority
- `it has a messages relationship` — create a Ticket with messages, assert `$ticket->messages` returns a collection of `TicketMessage`
- `it has a notes relationship` — create a Ticket with notes, assert `$ticket->notes` returns a collection of `TicketNote`
- `it scopes to open tickets` — create tickets with various statuses, assert `Ticket::open()->get()` returns only open ones
- `it scopes to assigned tickets` — create assigned and unassigned tickets, assert `Ticket::assignedTo($id)->get()` returns only the assigned one
- `it scopes by priority` — create tickets with various priorities, assert `Ticket::withPriority(TicketPriority::High)->get()` returns only high-priority ones

**`tests/Unit/Models/TicketMessageTest.php`**

- `it casts direction to MessageDirection enum` — create a TicketMessage, assert direction is `MessageDirection`
- `it belongs to a ticket` — create a message, assert `$message->ticket` is a `Ticket` instance
- `it scopes to inbound messages` — create inbound and outbound messages, assert scope filters correctly
- `it scopes to outbound messages` — same, opposite direction

**`tests/Unit/Models/TicketNoteTest.php`**

- `it belongs to a ticket` — create a note, assert `$note->ticket` is a `Ticket` instance

### Feature Tests

**`tests/Feature/Models/TicketFactoryTest.php`**

- `it creates a ticket with default attributes` — use `Ticket::factory()->create()` and assert the ticket exists in the database with Open status and Normal priority
- `it creates a ticket with resolved state` — use `Ticket::factory()->resolved()->create()` and assert status is Resolved
- `it creates a ticket with high priority state` — use `Ticket::factory()->highPriority()->create()` and assert priority is High
- `it creates a ticket with an assigned agent` — use the `assigned` state and assert `assignee_id` is set

**`tests/Feature/Models/TicketMessageFactoryTest.php`**

- `it creates a message with default attributes` — use the factory and assert ticket_id is set and direction is Inbound
- `it creates an outbound message` — use the `outbound()` state
- `it creates a message with a message ID` — use `withMessageId()` and assert `message_id` is not null and matches email format

**`tests/Feature/Models/TicketNoteFactoryTest.php`**

- `it creates a note with default attributes` — use the factory and assert ticket_id and author_id are set

### Architecture Tests

Update `tests/ArchTest.php` to add:

- `it ensures models use HasUuids trait` — arch test that all classes in `HotReloadStudios\Triage\Models` use `HasUuids`
- `it ensures enums are string-backed` — arch test that all classes in `HotReloadStudios\Triage\Enums` are backed enums

---

## Data Model Diagram

```mermaid
classDiagram
    class Ticket {
        +uuid id
        +string subject
        +TicketStatus status
        +TicketPriority priority
        +?string submitter_id
        +string submitter_name
        +string submitter_email
        +?string assignee_id
        +string reply_token
        +timestamps
        +messages() HasMany~TicketMessage~
        +notes() HasMany~TicketNote~
        +submitter() BelongsTo~User~
        +assignee() BelongsTo~User~
        +scopeOpen()
        +scopePending()
        +scopeResolved()
        +scopeClosed()
        +scopeAssignedTo(string)
        +scopeWithPriority(TicketPriority)
    }

    class TicketMessage {
        +uuid id
        +uuid ticket_id
        +MessageDirection direction
        +?string author_id
        +?string message_id
        +text body
        +?text raw_email
        +timestamps
        +ticket() BelongsTo~Ticket~
        +author() BelongsTo~User~
        +scopeInbound()
        +scopeOutbound()
    }

    class TicketNote {
        +uuid id
        +uuid ticket_id
        +string author_id
        +text body
        +timestamps
        +ticket() BelongsTo~Ticket~
        +author() BelongsTo~User~
    }

    class TicketStatus {
        <<enum>>
        Open
        Pending
        Resolved
        Closed
    }

    class TicketPriority {
        <<enum>>
        Low
        Normal
        High
        Urgent
    }

    class MessageDirection {
        <<enum>>
        Inbound
        Outbound
    }

    Ticket "1" --> "*" TicketMessage : messages
    Ticket "1" --> "*" TicketNote : notes
    Ticket --> TicketStatus : status
    Ticket --> TicketPriority : priority
    TicketMessage --> MessageDirection : direction
