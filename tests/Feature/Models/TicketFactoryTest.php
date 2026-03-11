<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Models;

use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Models\Ticket;

it('creates a ticket with default attributes', function (): void {
    $ticket = Ticket::factory()->create();

    expect($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->priority)->toBe(TicketPriority::Normal);

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
});

it('creates a ticket with resolved state', function (): void {
    $ticket = Ticket::factory()->resolved()->create();

    expect($ticket->status)->toBe(TicketStatus::Resolved);
});

it('creates a ticket with high priority state', function (): void {
    $ticket = Ticket::factory()->highPriority()->create();

    expect($ticket->priority)->toBe(TicketPriority::High);
});

it('creates a ticket with an assigned agent', function (): void {
    $userId = 'agent-xyz-789';
    $ticket = Ticket::factory()->assigned($userId)->create();

    expect($ticket->assignee_id)->toBe($userId);
});
