<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Models;

use HotReloadStudios\Triage\Enums\MessageDirection;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;

it('casts direction to MessageDirection enum', function (): void {
    $message = TicketMessage::factory()->create();

    expect($message->direction)->toBeInstanceOf(MessageDirection::class);
});

it('belongs to a ticket', function (): void {
    $message = TicketMessage::factory()->create();

    expect($message->ticket)->toBeInstanceOf(Ticket::class);
});

it('scopes to inbound messages', function (): void {
    TicketMessage::factory()->inbound()->create();
    TicketMessage::factory()->outbound()->create();

    expect(TicketMessage::inbound()->get())->toHaveCount(1)
        ->first()->direction->toBe(MessageDirection::Inbound);
});

it('scopes to outbound messages', function (): void {
    TicketMessage::factory()->inbound()->create();
    TicketMessage::factory()->outbound()->create();

    expect(TicketMessage::outbound()->get())->toHaveCount(1)
        ->first()->direction->toBe(MessageDirection::Outbound);
});
