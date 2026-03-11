<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Listeners;

use HotReloadStudios\Triage\Events\TicketReplied;
use HotReloadStudios\Triage\Mail\TicketReplyMail;
use Illuminate\Support\Facades\Mail;

final class SendTicketReplyMailListener
{
    public function handle(TicketReplied $event): void
    {
        if (empty(config('triage.from_address'))) {
            return;
        }

        Mail::to($event->ticket->submitter_email)->queue(new TicketReplyMail($event->ticket, $event->message));
    }
}
