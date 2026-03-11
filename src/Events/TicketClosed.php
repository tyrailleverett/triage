<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Events;

use HotReloadStudios\Triage\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketClosed
{
    use Dispatchable;

    public function __construct(public readonly Ticket $ticket) {}
}
