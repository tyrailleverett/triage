<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Listeners;

use HotReloadStudios\Triage\Events\TicketCreated;
use HotReloadStudios\Triage\Mail\TicketConfirmationMail;
use Illuminate\Support\Facades\Mail;

final class SendTicketConfirmationListener
{
    public function handle(TicketCreated $event): void
    {
        if (empty(config('triage.from_address'))) {
            return;
        }

        Mail::to($event->ticket->submitter_email)->queue(new TicketConfirmationMail($event->ticket));
    }
}
