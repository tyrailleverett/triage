<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Events;

use HotReloadStudios\Triage\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly array $changes,
    ) {}
}
