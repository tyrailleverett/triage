<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Models;

use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;
use HotReloadStudios\Triage\Models\TicketNote;

it('casts status to TicketStatus enum', function (): void {
    $ticket = Ticket::factory()->create();

    expect($ticket->status)->toBeInstanceOf(TicketStatus::class);
});

it('casts priority to TicketPriority enum', function (): void {
    $ticket = Ticket::factory()->create();

    expect($ticket->priority)->toBeInstanceOf(TicketPriority::class);
});

it('has a messages relationship', function (): void {
    $ticket = Ticket::factory()
        ->has(TicketMessage::factory()->count(2), 'messages')
        ->create();

    expect($ticket->messages)->toHaveCount(2)
        ->each->toBeInstanceOf(TicketMessage::class);
});

it('has a notes relationship', function (): void {
    $ticket = Ticket::factory()
        ->has(TicketNote::factory()->count(2), 'notes')
        ->create();

    expect($ticket->notes)->toHaveCount(2)
        ->each->toBeInstanceOf(TicketNote::class);
});

it('scopes to open tickets', function (): void {
    Ticket::factory()->open()->create();
    Ticket::factory()->pending()->create();
    Ticket::factory()->closed()->create();

    expect(Ticket::open()->get())->toHaveCount(1)
        ->each(fn ($ticket) => $ticket->status->toBe(TicketStatus::Open));
});

it('scopes to assigned tickets', function (): void {
    $userId = 'user-abc-123';
    Ticket::factory()->assigned($userId)->create();
    Ticket::factory()->create();

    expect(Ticket::assignedTo($userId)->get())->toHaveCount(1)
        ->first()->assignee_id->toBe($userId);
});

it('scopes by priority', function (): void {
    Ticket::factory()->highPriority()->create();
    Ticket::factory()->lowPriority()->create();
    Ticket::factory()->create();

    expect(Ticket::withPriority(TicketPriority::High)->get())->toHaveCount(1)
        ->first()->priority->toBe(TicketPriority::High);
});
