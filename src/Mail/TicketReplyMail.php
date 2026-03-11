<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Mail;

use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;
use HotReloadStudios\Triage\Support\ReplyAddressFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TicketReplyMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Ticket $ticket, public readonly TicketMessage $message) {}

    public function envelope(): Envelope
    {
        $formatter = new ReplyAddressFormatter;

        return new Envelope(
            from: new Address(
                (string) config('triage.from_address'),
                (string) config('triage.from_name'),
            ),
            to: [$this->ticket->submitter_email],
            replyTo: [new Address($formatter->format($this->ticket->reply_token))],
            subject: "Re: {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'triage::emails.ticket-reply',
            with: [
                'ticket' => $this->ticket,
                'message' => $this->message,
            ],
        );
    }

    /** @return array<int, never> */
    public function attachments(): array
    {
        return [];
    }
}
