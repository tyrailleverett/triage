<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Models;

use HotReloadStudios\Triage\Models\TicketNote;

it('creates a note with default attributes', function (): void {
    $note = TicketNote::factory()->create();

    expect($note->ticket_id)->not->toBeNull()
        ->and($note->author_id)->not->toBeNull();
});
