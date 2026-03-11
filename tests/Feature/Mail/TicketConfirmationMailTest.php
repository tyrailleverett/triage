<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Mail;

use HotReloadStudios\Triage\Mail\TicketConfirmationMail;
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

it('sends a confirmation email when a ticket is created', function (): void {
    Mail::fake();

    $manager = app(TriageManager::class);

    $manager->createTicket(
        subject: 'Help needed',
        body: 'I need help with something.',
        submitterEmail: 'customer@example.com',
        submitterName: 'John Doe',
    );

    Mail::assertQueued(
        TicketConfirmationMail::class,
        fn (TicketConfirmationMail $mail): bool => $mail->hasTo('customer@example.com'),
    );
});

it('includes the correct reply-to address', function (): void {
    $ticket = Ticket::factory()->create([
        'submitter_email' => 'customer@example.com',
        'reply_token' => mb_str_pad('abc123', 32, '0'),
    ]);

    $mailable = new TicketConfirmationMail($ticket);

    $envelope = $mailable->envelope();

    $replyTo = $envelope->replyTo;

    expect($replyTo)->not->toBeEmpty();

    $replyToEmail = $replyTo[0]->address ?? (string) $replyTo[0];

    expect($replyToEmail)->toContain('+triage-');
});

it('includes the ticket subject in the email subject', function (): void {
    $ticket = Ticket::factory()->create([
        'subject' => 'My Support Request',
    ]);

    $mailable = new TicketConfirmationMail($ticket);

    expect($mailable->envelope()->subject)->toBe('Re: My Support Request');
});

it('does not send when no from address is configured', function (): void {
    Mail::fake();

    config()->set('triage.from_address', null);
    config()->set('triage.reply_to_address', null);

    $manager = app(TriageManager::class);

    $manager->createTicket(
        subject: 'Help needed',
        body: 'I need help.',
        submitterEmail: 'customer@example.com',
        submitterName: 'John Doe',
    );

    Mail::assertNothingQueued();
});
