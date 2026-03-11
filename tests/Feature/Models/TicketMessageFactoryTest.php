<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Models;

use HotReloadStudios\Triage\Enums\MessageDirection;
use HotReloadStudios\Triage\Models\TicketMessage;

it('creates a message with default attributes', function (): void {
    $message = TicketMessage::factory()->create();

    expect($message->ticket_id)->not->toBeNull()
        ->and($message->direction)->toBe(MessageDirection::Inbound);
});

it('creates an outbound message', function (): void {
    $message = TicketMessage::factory()->outbound()->create();

    expect($message->direction)->toBe(MessageDirection::Outbound);
});

it('creates a message with a message ID', function (): void {
    $message = TicketMessage::factory()->withMessageId()->create();

    expect($message->message_id)->not->toBeNull()
        ->toMatch('/^<.+@mail\.example\.com>$/');
});
