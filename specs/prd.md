# Triage — Product Requirements Document

> **Version:** 1.1
> **Date:** March 6, 2026
> **Status:** Draft

---

## 1. Product Overview

Triage is a self-hosted Laravel package that gives any Laravel application a fully functional customer support ticketing system — without leaving the PHP ecosystem or paying for a third-party SaaS like Zendesk or Help Scout. It is installed via Composer, mounts to a gated route (`/triage`), and ships with a pre-compiled React SPA dashboard that requires no frontend build toolchain from the consuming application.

Beyond the visual dashboard, Triage exposes a fluent SDK (`Triage::createTicket(...)`) that allows host applications to create and manage tickets programmatically — from webhooks, form submissions, scheduled jobs, or any other application code — without touching the database directly.

Existing solutions are either fully external (expensive, no data ownership, complex integrations) or require significant custom development to embed into a Laravel app. Triage sits between those two extremes: it is opinionated and ready-to-use out of the box, but runs entirely inside the host application with full access to the app's database, users, mail, and events.

The initial scope is deliberate: ticket management only. Inbound emails (via Laravel Mailbox) automatically create or update tickets. Agents manage tickets through the dashboard. Everything else — live chat, knowledge bases, customer portals — is explicitly deferred.

### Product Principles

1. **Own your data.** All data lives in the host application's database. No third-party SaaS dependency.
2. **Zero frontend build step for consumers.** The React SPA is pre-compiled and published as static assets, matching the Horizon/Telescope pattern.
3. **Convention over configuration.** Sensible defaults ship out of the box; configuration is opt-in, not required.
4. **SDK-first internals.** The dashboard is a consumer of the same SDK available to host apps. No logic lives in controllers that isn't accessible programmatically.
5. **Leverage the ecosystem.** Use Laravel Mailbox for inbound email rather than reinventing IMAP/webhook parsing.

---

## 2. Target Users

### Primary Persona: Support Agent

- **Context:** Works inside a Laravel SaaS product, handling customer support via email today
- **Pain point:** Support lives in generic email clients; no ticket tracking, no assignment, no history
- **Need:** A dashboard inside their app to see, assign, reply to, and resolve support requests
- **Values:** Speed, simplicity, and not switching context to an external tool

### Secondary Persona: Laravel Developer (Package Consumer)

- **Context:** Building or maintaining a Laravel SaaS; does not want to pay for Zendesk or integrate a heavy external API
- **Need:** Drop-in Composer package with a clean SDK for programmatic ticket management, a working dashboard, and email integration
- **Values:** Minimal config, no frontend build requirements, clean programmatic API, good test coverage

---

## 3. User Stories

### Support Agent

- As an agent, I want to view a list of all open tickets so I can prioritize my work.
- As an agent, I want to open a ticket and see the full message thread so I understand the customer's issue.
- As an agent, I want to reply to a ticket so the customer receives my response via email.
- As an agent, I want to add an internal note to a ticket so I can leave context for other agents without emailing the customer.
- As an agent, I want to change a ticket's status (open, pending, resolved, closed) so the queue stays accurate.
- As an agent, I want to set ticket priority (low, normal, high, urgent) so critical issues surface quickly.
- As an agent, I want to assign a ticket to myself or another agent so ownership is clear.
- As an agent, I want to create a ticket manually so I can log issues reported outside of email.
- As an agent, I want to filter and search tickets by status, priority, and assignee so I can find what I need fast.
- As an agent, I want to see which internal notes exist on a ticket so I have full context from my teammates.
- As an agent, I want to configure which events send me notifications so I only receive alerts that matter to me.

### Customer (Submitter)

- As a customer, I want to send a support email and receive a confirmation so I know my request was received.
- As a customer, I want to reply to the confirmation email and have my reply added to the ticket thread so communication is seamless.

### Laravel Developer

- As a developer, I want to install the package via Composer and get it working with one `php artisan triage:install` command.
- As a developer, I want to register a gate callback to control who can access the dashboard, the same way Horizon works.
- As a developer, I want to use `Triage::createTicket(...)` in my application code to open tickets programmatically.
- As a developer, I want to use `Triage::replyToTicket(...)`, `Triage::resolveTicket(...)`, and other SDK methods to fully manage tickets without touching the database.
- As a developer, I want to add internal notes to tickets via the SDK so automated systems can annotate tickets.

---

## 4. System Architecture

Triage is a Laravel package (Composer) consumed inside a host Laravel application. It registers its own route group (`/triage`), a service provider, and a set of migrations. The frontend is a pre-compiled standalone React SPA distributed as static assets published to `public/vendor/triage/` and served through a package-owned Blade shell.

**Key Components:**

| Component | Responsibility |
|---|---|
| `TriageServiceProvider` | Registers routes, migrations, config, assets, gate |
| `TriageManager` (`Triage` facade) | Core SDK — all ticket operations go through here |
| Dashboard Shell Controller | Serves the package Blade shell for `/triage` and SPA deep links |
| Dashboard API Controllers | Thin JSON HTTP layer over `TriageManager`; no business logic |
| `TriageMailbox` | Laravel Mailbox handler; parses inbound email, delegates to SDK |
| Gate (`Triage::auth()`) | Controls access to all `/triage/*` routes |

**SDK-first principle:** Every action available in the dashboard (create ticket, reply, add note, update status, assign) is implemented in `TriageManager` first. Controllers call the SDK. This ensures the programmatic API and the dashboard are always in sync.

**Asset Distribution (Horizon-style):**

```
Package ships: resources/dist/{app.js, app.css, manifest.json}
php artisan triage:publish --assets → public/vendor/triage/
```

**Inbound Email Flow:**

```
Customer sends email
  → Mail provider (any Laravel Mailbox-supported provider)
  → Laravel Mailbox parses and dispatches to TriageMailbox handler
  → Match reply-to token → append TicketMessage to existing ticket
    OR no match → Triage::createTicket(...) with submitter matched to User
  → Confirmation/notification email sent via queued job
```

**Reply Threading Model:**

Outbound reply emails include a unique token in the reply-to address (e.g., `support+triage-{token}@example.com`). Inbound replies are matched by this token — more reliable than subject-line matching and works across all mail clients.

---

## 5. Core Entities

| Entity | Description |
|---|---|
| **Ticket** | A support request with subject, status, priority, and a linked submitter (host `User`). |
| **TicketMessage** | A single message in the ticket thread: inbound from customer, or outbound reply from agent. |
| **TicketNote** | An internal-only note on a ticket, visible to agents only, never sent to the customer. |
| **User (host app)** | The host app's `User` model. Used for both agents (assignees/authors) and submitters (matched by email). |

---

## 6. MVP Feature Set

### 6.1 Ticket Management SDK

The `Triage` facade exposes a fluent SDK for all ticket operations. Example API surface:

```php
// Create a ticket (submitter matched to host User by email, or stored as guest)
Triage::createTicket(
    subject: 'Login not working',
    body: 'I cannot log in since yesterday.',
    submitterEmail: 'jane@example.com',
    submitterName: 'Jane Doe',
    priority: TicketPriority::High,
);

// Reply to a ticket (sends email to submitter)
Triage::replyToTicket(ticket: $ticket, body: 'We are looking into this.', agent: $user);

// Add an internal note (not sent to customer)
Triage::addNote(ticket: $ticket, body: 'Looks like a DB issue, escalating.', agent: $user);

// Update ticket
Triage::updateTicket(ticket: $ticket, status: TicketStatus::Resolved, assignee: $user);
Triage::assignTicket(ticket: $ticket, agent: $user);
Triage::closeTicket(ticket: $ticket);
Triage::resolveTicket(ticket: $ticket);
```

All SDK methods dispatch Laravel events (e.g., `TicketCreated`, `TicketReplied`, `TicketResolved`) for host-app extensibility.

### 6.2 Ticket Statuses & Priorities

**Statuses:** `Open`, `Pending`, `Resolved`, `Closed` — PHP-backed enum `TicketStatus`

**Priorities:** `Low`, `Normal`, `High`, `Urgent` — PHP-backed enum `TicketPriority`

### 6.3 Internal Notes

- Agents can add notes to any ticket from the dashboard.
- Notes are stored as `TicketNote` records, separate from `TicketMessage`.
- Notes are visually distinguished in the ticket thread (e.g., yellow background, lock icon).
- Notes are never included in outbound emails to the submitter.
- Notes are accessible via `Triage::addNote(...)` for programmatic use.

### 6.4 Inbound Email (via Laravel Mailbox)

- Laravel Mailbox is a required production dependency of Triage, not an optional integration.
- A `TriageMailbox` handler is registered with Laravel Mailbox.
- Inbound email to the configured address routes through the handler.
- **New ticket:** No reply-to token match → `Triage::createTicket(...)` with submitter matched to host `User` by email; plain name/email stored for unmatched guests.
- **Existing ticket update:** Reply-to token matched → `Triage::addInboundMessage(...)` appends to the correct thread.
- Inbound messages persist the mail `Message-ID` header as `ticket_messages.message_id` when present.
- Idempotency is enforced at the message-record level: inbound deliveries with a previously seen `message_id` must not create a new ticket or append a duplicate message.
- A confirmation email with a reply-to token address is sent to the submitter upon ticket creation.
- Processing is dispatched to a queued job for resilience.

### 6.5 Email Content Handling

- Triage stores a canonical plain-text message body for all ticket messages; this normalized text is what the dashboard renders and what search indexes target.
- For inbound mail, HTML bodies may be parsed to plain text, but raw HTML is not rendered back to agents in MVP.
- `ticket_messages.raw_email` stores the original inbound payload for audit/debug purposes.
- Quoted-thread trimming is supported on a best-effort basis for common email reply formats; if trimming fails, Triage stores the normalized full body rather than dropping content.
- File attachments are out of scope for MVP. They are ignored during processing and are not persisted or exposed in the dashboard.

### 6.6 Submitter Identity

- When a ticket is created (via email or SDK), the submitter email is looked up against the host app's `User` model (configured via `triage.user_model`).
- If a match is found, `tickets.submitter_id` is set to that user's ID.
- If no match, `submitter_name` and `submitter_email` are stored as plain strings; `submitter_id` is null. This supports guest / pre-registration submitters.
- This design allows a future customer portal to link existing users to their tickets without a migration.

### 6.7 Host User Key Compatibility

- Triage must support host applications whose configured user model uses integer, UUID, or ULID primary keys.
- To remain compatible across host applications, `submitter_id`, `assignee_id`, and `author_id` are stored as nullable strings and resolved through the configured `triage.user_model` at the application layer.
- Triage does not create database-level foreign key constraints to the host application's users table for these columns.
- Each user-reference column is indexed for lookup and filtering, and package code is responsible for casting the configured user model key to and from string consistently.

### 6.8 Dashboard UI (Standalone React SPA)

- **Ticket list page** — Table with filters (status, priority, assignee), search, and pagination.
- **Ticket detail page** — Unified thread showing messages and internal notes (visually distinct), metadata sidebar, reply form, and note form with toggle.
- **Create ticket modal/page** — Form for manual ticket creation.
- **Settings page** — Agent-scoped preference management at `/triage/settings`, with sub-navigation (Notifications, Profile, Appearance, Security). Only Notifications is fully implemented in MVP.
- Pre-compiled; no build step required in host app.
- Served from `public/vendor/triage/` through a package Blade shell.
- Client-side routing is owned by the package frontend; server-side data access goes through package JSON endpoints under `/triage/api/*`.

### 6.9 Agent Notification Preferences

Each agent can configure per-event notification toggles from the Settings → Notifications page. Preferences are stored in the `triage_agent_preferences` table, keyed by `user_id` (the host app's user primary key, stored as a string for compatibility).

**Toggle options:**

| Preference | Default | Description |
|---|---|---|
| `notify_ticket_assigned` | `true` | Notified when a ticket is assigned to the agent |
| `notify_ticket_replied` | `true` | Notified when a customer replies to a ticket the agent owns |
| `notify_note_added` | `false` | Notified when a teammate adds an internal note |
| `notify_status_changed` | `true` | Notified when a ticket status is updated |
| `daily_digest` | `false` | Receive a daily summary of queue activity |
| `email_notifications` | `true` | Master toggle: send all notifications to the agent's email address |

Preferences are upserted on first save (defaults applied on first visit if no record exists). The notification delivery layer reads these preferences before dispatching notification emails.

> **Note:** Actual notification email dispatch based on these preferences is wired up when the notification system is implemented. This phase stores and exposes preferences; the dispatch layer consumes them.

### 6.10 Gated Access (Horizon-style)

- All `/triage/*` routes protected by a named gate (`triage`).
- Default: denies access in production, allows in `local`/`testing`.
- Override: `Triage::auth(fn (User $user): bool => $user->isAdmin());` in `AppServiceProvider::boot()`.

---

## 7. Platform Features

### 7.1 Installation & Configuration

**Install command:** `php artisan triage:install`
- Publishes config to `config/triage.php`
- Publishes and runs migrations
- Publishes compiled assets to `public/vendor/triage/`
- Verifies Laravel Mailbox is installed and guides the consuming application through mailbox route/provider configuration

**Dependency boundary:**
- Triage requires Laravel Mailbox as a production Composer dependency.
- The package registers and documents its mailbox handler as part of the default install path; mailbox functionality is not a separate optional add-on.
- If mailbox-specific configuration is incomplete, dashboard and SDK ticket management continue to work, but inbound email handling remains disabled until configuration is finished.

**Config (`config/triage.php`):**

| Key | Default | Description |
|---|---|---|
| `path` | `triage` | URL prefix for the dashboard |
| `middleware` | `['web']` | Middleware applied to dashboard routes |
| `mailbox_address` | `null` | Inbound email address (used with Laravel Mailbox) |
| `reply_to_address` | `null` | Reply-to base address for token threading (e.g., `support@example.com`) |
| `from_name` | `config('app.name')` | Sender name for outbound ticket emails |
| `from_address` | `config('mail.from.address')` | Sender address for outbound ticket emails |
| `user_model` | `App\Models\User` | Host app's `User` model (for agents and submitter matching) |

### 7.2 API (dashboard shell + JSON endpoints)

All dashboard shell and JSON endpoints require triage gate authorization, except the mailbox webhook.

| Endpoint | Method | Description |
|---|---|---|
| `GET /triage` | GET | Dashboard shell |
| `GET /triage/{view?}` | GET | Dashboard shell for SPA deep links (`tickets/*`, `settings/*`) |
| `GET /triage/api/tickets` | GET | List tickets (paginated, filtered) |
| `POST /triage/api/tickets` | POST | Create ticket |
| `GET /triage/api/tickets/{ticket}` | GET | Ticket detail |
| `PATCH /triage/api/tickets/{ticket}` | PATCH | Update status / priority / assignee |
| `POST /triage/api/tickets/{ticket}/messages` | POST | Reply to ticket |
| `POST /triage/api/tickets/{ticket}/notes` | POST | Add internal note |
| `GET /triage/api/settings/notifications` | GET | Fetch agent notification preferences |
| `PATCH /triage/api/settings/notifications` | PATCH | Save agent notification preferences |
| `POST /triage/mailbox` | POST | Inbound email webhook (Laravel Mailbox) |

### 7.3 Events

| Event | Fired when |
|---|---|
| `TicketCreated` | A new ticket is created via any path |
| `TicketReplied` | An agent sends a reply |
| `TicketMessageReceived` | An inbound customer message is added |
| `TicketNoteAdded` | An internal note is added |
| `TicketUpdated` | Status, priority, or assignee changes |
| `TicketResolved` | Ticket moved to Resolved |
| `TicketClosed` | Ticket moved to Closed |

### 7.4 Security

- All dashboard routes gated; no unauthenticated access.
- Mailbox webhook uses Laravel Mailbox's built-in provider signature verification.
- Reply-to tokens are cryptographically random (128-bit); not guessable.
- All React output is XSS-safe by default.
- Eloquent parameter binding throughout; no raw queries.
- Submitter name/email validated before persistence.

---

## 8. Data Model

```
tickets
├── id (uuid)
├── subject (string)
├── status (enum: Open, Pending, Resolved, Closed)
├── priority (enum: Low, Normal, High, Urgent)
├── submitter_id (nullable string, indexed) -- stores configured user model key; null if unmatched guest
├── submitter_name (string)
├── submitter_email (string)
├── assignee_id (nullable string, indexed)  -- stores configured user model key
├── reply_token (string, unique)            -- for reply-to threading
├── created_at
└── updated_at

ticket_messages
├── id (uuid)
├── ticket_id (FK → tickets.id)
├── direction (enum: Inbound, Outbound)
├── author_id (nullable string, indexed)    -- stores configured user model key; null for inbound from guest
├── message_id (nullable string, unique)    -- persisted from inbound Message-ID for idempotency
├── body (text)
├── raw_email (nullable text)               -- original payload for inbound
├── created_at
└── updated_at

ticket_notes
├── id (uuid)
├── ticket_id (FK → tickets.id)
├── author_id (string, indexed)             -- stores configured user model key; always an agent
├── body (text)
├── created_at
└── updated_at
```

```
triage_agent_preferences
├── id (uuid)
├── user_id (string, unique, indexed)   -- host app user key; one row per agent
├── notify_ticket_assigned (boolean, default true)
├── notify_ticket_replied (boolean, default true)
├── notify_note_added (boolean, default false)
├── notify_status_changed (boolean, default true)
├── daily_digest (boolean, default false)
├── email_notifications (boolean, default true)
├── created_at
└── updated_at
```

**Indexes:** `tickets.status`, `tickets.priority`, `tickets.assignee_id`, `tickets.submitter_id`, `tickets.submitter_email`, `tickets.reply_token`, `tickets.created_at`; `ticket_messages.ticket_id`, `ticket_messages.author_id`, `ticket_messages.message_id`; `ticket_notes.ticket_id`, `ticket_notes.author_id`.

**User model reference strategy:** Because Triage is a reusable package and the host application's user primary key may be `bigint`, `uuid`, or `ulid`, user references are stored as strings rather than database foreign keys. Relations to the configured user model are resolved in package code using the configured primary key name and cast semantics.

---

## 9. Non-Functional Requirements

### Performance
- Ticket list loads within 300ms for up to 10,000 tickets with proper DB indexes.
- Inbound email webhook responds < 200ms (processing dispatched to queue).

### Reliability
- Inbound email processing is idempotent; duplicate webhook deliveries must not create duplicate tickets or duplicate message records.
- `ticket_messages.message_id` is used as the primary deduplication key for inbound mail and is protected by a uniqueness constraint.
- `reply_token` is used for ticket threading, not as the sole idempotency key.
- Failed reply emails do not break the request cycle; dispatched via queue.

### Scalability
- PostgreSQL with indexes on all filter/sort columns.
- Pagination enforced on all list endpoints.

### Compatibility
- Laravel 11.x and 12.x
- PHP 8.4+
- PostgreSQL (primary target)

---

## 10. Out of Scope (MVP)

- **Customer portal** — Customers cannot log in to view ticket history; email-only interaction.
- **Live chat / real-time updates** — No WebSockets/Reverb in MVP.
- **Knowledge base / FAQ** — Deferred.
- **Canned responses / macros** — Deferred.
- **Custom fields / tags** — Deferred.
- **Team/group management** — Any auth user is an agent in MVP.
- **SLA / escalation rules** — Deferred.
- **Multi-mailbox** — Single mailbox address per installation in MVP.
- **IMAP polling** — Webhook-based inbound only in MVP.
- **File attachments** — Attachments are ignored and not persisted in MVP.
- **Billing** — Self-hosted package; no subscription tiers.

---

## 11. Success Criteria

1. A developer can install the package, run `php artisan triage:install`, and have a working ticket dashboard at `/triage` with no additional configuration beyond registering a gate callback.
2. `Triage::createTicket(...)` and all SDK methods work correctly from host application code.
3. An inbound email creates a new ticket, matched to a host `User` where the email matches.
4. A customer replying to the confirmation email (via reply-to token) appends the message to the correct ticket thread.
5. An agent reply from the dashboard sends the customer an email and stores the message.
6. Internal notes are visible to agents in the thread but never sent to the customer.
7. All routes return 403 to unauthorized users; the gate blocks access correctly.
8. Test suite covers all SDK methods, inbound email routing, thread token matching, note isolation, gate authorization, and submitter identity matching.
9. An agent can save notification preferences from the Settings → Notifications page; revisiting the page reflects the saved state. Default preferences are applied on first visit.

---

## 12. Resolved Design Decisions

- **Inbound provider:** Provider-agnostic; uses Laravel Mailbox which supports Mailgun, Postmark, SendGrid, and others out of the box.
- **Submitter identity:** Matched to host app `User` by email; null `submitter_id` for unmatched guests. Guest tickets can be linked retroactively when a user registers.
- **Host user key compatibility:** User references are stored as strings and resolved against the configured user model so the package supports integer, UUID, and ULID user keys without host-specific migration changes.
- **Reply threading:** Reply-to address token (e.g., `support+triage-{token}@example.com`) — most reliable across all mail clients; implemented as a unique random token stored on each ticket.
- **Inbound deduplication:** `Message-ID` is persisted on inbound message records and protected by a uniqueness constraint; `reply_token` is used to route the message to the correct ticket.
- **Email content scope:** MVP stores and renders normalized plain text, keeps the raw inbound payload for auditability, trims quoted replies on a best-effort basis, and excludes attachments.
