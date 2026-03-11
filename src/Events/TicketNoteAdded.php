<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Events;

use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketNote;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketNoteAdded
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketNote $note,
    ) {}
}
