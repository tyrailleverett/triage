<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Events;

use HotReloadStudios\Triage\Events\TicketCreated;
use HotReloadStudios\Triage\Events\TicketReplied;
use HotReloadStudios\Triage\Events\TicketUpdated;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;

it('constructs TicketCreated with a ticket', function (): void {
    $ticket = Ticket::factory()->create();

    $event = new TicketCreated($ticket);

    expect($event->ticket)->toBe($ticket);
});

it('constructs TicketReplied with ticket and message', function (): void {
    $ticket = Ticket::factory()->create();
    $message = TicketMessage::factory()->for($ticket)->create();

    $event = new TicketReplied($ticket, $message);

    expect($event->ticket)->toBe($ticket)
        ->and($event->message)->toBe($message);
});

it('constructs TicketUpdated with changes array', function (): void {
    $ticket = Ticket::factory()->create();
    $changes = ['status' => ['old' => 'open', 'new' => 'resolved']];

    $event = new TicketUpdated($ticket, $changes);

    expect($event->ticket)->toBe($ticket)
        ->and($event->changes)->toBe($changes);
});
