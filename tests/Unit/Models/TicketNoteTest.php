<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Models;

use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketNote;

it('belongs to a ticket', function (): void {
    $note = TicketNote::factory()->create();

    expect($note->ticket)->toBeInstanceOf(Ticket::class);
});
