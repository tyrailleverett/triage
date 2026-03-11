<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Mail;

use HotReloadStudios\Triage\Mail\TicketReplyMail;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
    config()->set('triage.from_address', 'support@example.com');
    config()->set('triage.from_name', 'Support Team');
    config()->set('triage.reply_to_address', 'support@example.com');
});

it('sends a reply email when an agent replies', function (): void {
    Mail::fake();

    $manager = app(TriageManager::class);

    $ticket = Ticket::factory()->create([
        'submitter_email' => 'customer@example.com',
    ]);

    $manager->replyToTicket($ticket, 'Here is the help you need.', 'agent-1');

    Mail::assertQueued(
        TicketReplyMail::class,
        fn (TicketReplyMail $mail): bool => $mail->hasTo('customer@example.com'),
    );
});

it('includes the correct reply-to address', function (): void {
    $ticket = Ticket::factory()->create([
        'submitter_email' => 'customer@example.com',
        'reply_token' => mb_str_pad('abc123', 32, '0'),
    ]);

    $message = $ticket->messages()->create([
        'direction' => \HotReloadStudios\Triage\Enums\MessageDirection::Outbound,
        'author_id' => null,
        'body' => 'Agent reply',
        'message_id' => null,
        'raw_email' => null,
    ]);

    $mailable = new TicketReplyMail($ticket, $message);

    $envelope = $mailable->envelope();

    $replyTo = $envelope->replyTo;

    expect($replyTo)->not->toBeEmpty();

    $replyToEmail = $replyTo[0]->address ?? (string) $replyTo[0];

    expect($replyToEmail)->toContain('+triage-');
});

it('does not send when no from address is configured', function (): void {
    Mail::fake();

    config()->set('triage.from_address', null);
    config()->set('triage.reply_to_address', null);

    $ticket = Ticket::factory()->create([
        'submitter_email' => 'customer@example.com',
    ]);

    $manager = app(TriageManager::class);
    $manager->replyToTicket($ticket, 'Here is some help.', 'agent-1');

    Mail::assertNothingQueued();
});
