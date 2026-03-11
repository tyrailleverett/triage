<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Events;

use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketMessageReceived
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketMessage $message,
    ) {}
}
