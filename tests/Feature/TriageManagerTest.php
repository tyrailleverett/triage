<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature;

use HotReloadStudios\Triage\Enums\MessageDirection;
use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Events\TicketClosed;
use HotReloadStudios\Triage\Events\TicketCreated;
use HotReloadStudios\Triage\Events\TicketMessageReceived;
use HotReloadStudios\Triage\Events\TicketNoteAdded;
use HotReloadStudios\Triage\Events\TicketReplied;
use HotReloadStudios\Triage\Events\TicketResolved;
use HotReloadStudios\Triage\Events\TicketUpdated;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

function createTestUser(string $email = 'agent@example.com'): User
{
    $user = new User;
    $user->name = 'Test User';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

// ─── createTicket ────────────────────────────────────────────────────────────

it('creates a ticket with all attributes', function (): void {
    $manager = app(TriageManager::class);

    $ticket = $manager->createTicket(
        subject: 'Test Subject',
        body: 'Test body',
        submitterEmail: 'user@example.com',
        submitterName: 'Test User',
    );

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->subject)->toBe('Test Subject')
        ->and($ticket->submitter_email)->toBe('user@example.com')
        ->and($ticket->submitter_name)->toBe('Test User')
        ->and($ticket->status)->toBe(TicketStatus::Open);

    $this->assertDatabaseHas('tickets', ['subject' => 'Test Subject']);
});

it('creates an initial inbound message on the ticket', function (): void {
    $manager = app(TriageManager::class);

    $ticket = $manager->createTicket(
        subject: 'Test',
        body: 'Hello, I need help.',
        submitterEmail: 'user@example.com',
        submitterName: 'User',
    );

    expect($ticket->messages)->toHaveCount(1)
        ->and($ticket->messages->first()->body)->toBe('Hello, I need help.')
        ->and($ticket->messages->first()->direction)->toBe(MessageDirection::Inbound);
});

it('generates a unique reply token', function (): void {
    $manager = app(TriageManager::class);

    $ticketA = $manager->createTicket('A', 'Body A', 'a@example.com', 'User A');
    $ticketB = $manager->createTicket('B', 'Body B', 'b@example.com', 'User B');

    expect($ticketA->reply_token)->not->toBe($ticketB->reply_token);
});

it('resolves submitter ID when email matches a user', function (): void {
    $user = createTestUser('submitter@example.com');
    $manager = app(TriageManager::class);

    $ticket = $manager->createTicket(
        subject: 'Hello',
        body: 'Body',
        submitterEmail: 'submitter@example.com',
        submitterName: 'Submitter',
    );

    expect($ticket->submitter_id)->toBe((string) $user->getKey());
});

it('leaves submitter ID null for unknown emails', function (): void {
    $manager = app(TriageManager::class);

    $ticket = $manager->createTicket(
        subject: 'Hello',
        body: 'Body',
        submitterEmail: 'unknown@example.com',
        submitterName: 'Guest',
    );

    expect($ticket->submitter_id)->toBeNull();
});

it('dispatches TicketCreated event', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);

    $manager->createTicket('Subject', 'Body', 'user@example.com', 'User');

    Event::assertDispatchedTimes(TicketCreated::class, 1);
});

it('sets the default priority to Normal', function (): void {
    $manager = app(TriageManager::class);

    $ticket = $manager->createTicket('Subject', 'Body', 'user@example.com', 'User');

    expect($ticket->priority)->toBe(TicketPriority::Normal);
});

it('accepts a custom priority', function (): void {
    $manager = app(TriageManager::class);

    $ticket = $manager->createTicket(
        subject: 'Urgent Issue',
        body: 'Body',
        submitterEmail: 'user@example.com',
        submitterName: 'User',
        priority: TicketPriority::Urgent,
    );

    expect($ticket->priority)->toBe(TicketPriority::Urgent);
});

// ─── replyToTicket ───────────────────────────────────────────────────────────

it('creates an outbound message on the ticket', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $message = $manager->replyToTicket($ticket, 'Here is my reply.', 'agent-id-123');

    expect($message->body)->toBe('Here is my reply.')
        ->and($message->direction)->toBe(MessageDirection::Outbound)
        ->and($message->author_id)->toBe('agent-id-123');
});

it('dispatches TicketReplied event', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $manager->replyToTicket($ticket, 'Reply body', 'agent-id');

    Event::assertDispatchedTimes(TicketReplied::class, 1);
});

it('does not change resolved ticket status', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->resolved()->create();

    $manager->replyToTicket($ticket, 'Reply', 'agent-id');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('accepts a host user model instance for agent-authored replies', function (): void {
    $agent = createTestUser();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $message = $manager->replyToTicket($ticket, 'Reply body', $agent);

    expect($message->author_id)->toBe((string) $agent->getKey());
});

// ─── addNote ─────────────────────────────────────────────────────────────────

it('creates a note on the ticket', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $note = $manager->addNote($ticket, 'Internal note.', 'agent-id-456');

    expect($note->body)->toBe('Internal note.')
        ->and($note->author_id)->toBe('agent-id-456');

    $this->assertDatabaseHas('ticket_notes', ['body' => 'Internal note.']);
});

it('dispatches TicketNoteAdded event', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $manager->addNote($ticket, 'Note', 'agent-id');

    Event::assertDispatchedTimes(TicketNoteAdded::class, 1);
});

it('accepts a host user model instance for note authors', function (): void {
    $agent = createTestUser();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $note = $manager->addNote($ticket, 'A note', $agent);

    expect($note->author_id)->toBe((string) $agent->getKey());
});

// ─── updateTicket ────────────────────────────────────────────────────────────

it('updates ticket status', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->open()->create();

    $updated = $manager->updateTicket($ticket, status: TicketStatus::Resolved);

    expect($updated->status)->toBe(TicketStatus::Resolved);
    Event::assertDispatched(TicketUpdated::class);
});

it('updates ticket priority', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $updated = $manager->updateTicket($ticket, priority: TicketPriority::High);

    expect($updated->priority)->toBe(TicketPriority::High);
});

it('updates assignee', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $updated = $manager->updateTicket($ticket, assignee: 'agent-xyz');

    expect($updated->assignee_id)->toBe('agent-xyz');
});

it('dispatches TicketResolved when status changes to Resolved', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->open()->create();

    $manager->updateTicket($ticket, status: TicketStatus::Resolved);

    Event::assertDispatchedTimes(TicketResolved::class, 1);
});

it('dispatches TicketClosed when status changes to Closed', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->open()->create();

    $manager->updateTicket($ticket, status: TicketStatus::Closed);

    Event::assertDispatchedTimes(TicketClosed::class, 1);
});

it('returns unchanged ticket when no parameters differ', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->open()->create();

    $result = $manager->updateTicket($ticket);

    expect($result->id)->toBe($ticket->id);
    Event::assertNotDispatched(TicketUpdated::class);
});

// ─── assignTicket ────────────────────────────────────────────────────────────

it('assigns an agent to a ticket', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $updated = $manager->assignTicket($ticket, 'agent-999');

    expect($updated->assignee_id)->toBe('agent-999');
});

it('accepts a host user model instance when assigning a ticket', function (): void {
    $agent = createTestUser();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $updated = $manager->assignTicket($ticket, $agent);

    expect($updated->assignee_id)->toBe((string) $agent->getKey());
});

// ─── resolveTicket / closeTicket ─────────────────────────────────────────────

it('resolves a ticket', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->open()->create();

    $resolved = $manager->resolveTicket($ticket);

    expect($resolved->status)->toBe(TicketStatus::Resolved);
});

it('closes a ticket', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->open()->create();

    $closed = $manager->closeTicket($ticket);

    expect($closed->status)->toBe(TicketStatus::Closed);
});

// ─── addInboundMessage ────────────────────────────────────────────────────────

it('appends an inbound message to the ticket', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $message = $manager->addInboundMessage($ticket, 'Follow-up question.', 'user@example.com');

    expect($message->body)->toBe('Follow-up question.')
        ->and($message->direction)->toBe(MessageDirection::Inbound);
});

it('deduplicates by message ID', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $manager->addInboundMessage($ticket, 'First.', 'user@example.com', '<msg-abc@mail.example.com>');
    $manager->addInboundMessage($ticket, 'First.', 'user@example.com', '<msg-abc@mail.example.com>');

    expect($ticket->messages()->count())->toBe(1);
});

it('preserves the ticket status when appending an inbound message', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->resolved()->create();

    $manager->addInboundMessage($ticket, 'Still need help.', 'user@example.com');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('dispatches TicketMessageReceived event', function (): void {
    Event::fake();
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $manager->addInboundMessage($ticket, 'Message', 'user@example.com');

    Event::assertDispatchedTimes(TicketMessageReceived::class, 1);
});

it('stores raw email when provided', function (): void {
    $manager = app(TriageManager::class);
    $ticket = Ticket::factory()->create();

    $rawEmail = 'From: user@example.com\nSubject: Help\n\nBody text';

    $message = $manager->addInboundMessage(
        ticket: $ticket,
        body: 'Body text',
        senderEmail: 'user@example.com',
        rawEmail: $rawEmail,
    );

    expect($message->raw_email)->toBe($rawEmail);
});
