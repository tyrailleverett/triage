<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Jobs;

use HotReloadStudios\Triage\Jobs\ProcessInboundEmailJob;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
    config()->set('triage.from_address', null);
    config()->set('triage.reply_to_address', null);
});

it('creates a new ticket when no reply token is present', function (): void {
    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'New request',
        body: 'I need help.',
        messageId: null,
        rawEmail: null,
        recipientAddress: 'support@example.com',
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    expect(Ticket::where('submitter_email', 'customer@example.com')->exists())->toBeTrue();
});

it('appends a message to an existing ticket when reply token matches', function (): void {
    $token = 'abcdef1234567890abcdef1234567890';

    $ticket = Ticket::factory()->create([
        'reply_token' => $token,
        'submitter_email' => 'customer@example.com',
    ]);

    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'Re: Your ticket',
        body: 'Thank you for your reply.',
        messageId: '<unique-msg-1@mail.example.com>',
        rawEmail: null,
        recipientAddress: "support+triage-{$token}@example.com",
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    expect($ticket->messages()->count())->toBe(1);
});

it('deduplicates by message ID', function (): void {
    $token = 'abcdef1234567890abcdef1234567890';

    $ticket = Ticket::factory()->create(['reply_token' => $token]);

    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'Re: ticket',
        body: 'My follow up.',
        messageId: '<duplicate-msg@mail.example.com>',
        rawEmail: null,
        recipientAddress: "support+triage-{$token}@example.com",
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);
    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    expect(TicketMessage::where('message_id', '<duplicate-msg@mail.example.com>')->count())->toBe(1);
});

it('trims quoted content from the body', function (): void {
    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'Follow up',
        body: "My follow-up message.\n\n> Original quoted text here",
        messageId: null,
        rawEmail: null,
        recipientAddress: 'support@example.com',
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    $ticket = Ticket::where('submitter_email', 'customer@example.com')->firstOrFail();
    $message = $ticket->messages()->first();

    expect($message->body)->not->toContain('> Original quoted text here')
        ->and($message->body)->toContain('My follow-up message.');
});

it('logs a warning for orphaned reply tokens', function (): void {
    Log::shouldReceive('warning')->once()->withArgs(function (string $message, array $context): bool {
        return str_contains($message, 'orphaned reply token');
    });

    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'Re: old ticket',
        body: 'My reply to old ticket.',
        messageId: null,
        rawEmail: null,
        recipientAddress: 'support+triage-'.str_repeat('0', 32).'@example.com',
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    expect(Ticket::count())->toBe(0);
});

it('preserves the existing ticket status on inbound message append', function (): void {
    $token = 'abcdef1234567890abcdef1234567890';

    $ticket = Ticket::factory()->resolved()->create([
        'reply_token' => $token,
        'submitter_email' => 'customer@example.com',
    ]);

    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'Re: ticket',
        body: 'My inbound reply.',
        messageId: null,
        rawEmail: null,
        recipientAddress: "support+triage-{$token}@example.com",
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    expect($ticket->fresh()->status)->toBe(\HotReloadStudios\Triage\Enums\TicketStatus::Resolved);
});

it('stores raw email payload', function (): void {
    $token = 'abcdef1234567890abcdef1234567890';

    $ticket = Ticket::factory()->create(['reply_token' => $token]);

    $rawEmail = 'From: customer@example.com\nTo: support@example.com\nSubject: Re: ticket\n\nBody';

    $job = new ProcessInboundEmailJob(
        senderEmail: 'customer@example.com',
        senderName: 'John Doe',
        subject: 'Re: ticket',
        body: 'Body',
        messageId: '<raw-test@mail.example.com>',
        rawEmail: $rawEmail,
        recipientAddress: "support+triage-{$token}@example.com",
    );

    $job->handle(app(\HotReloadStudios\Triage\TriageManager::class), new \HotReloadStudios\Triage\Support\ReplyAddressFormatter, new \HotReloadStudios\Triage\Support\QuotedReplyTrimmer);

    $message = TicketMessage::where('message_id', '<raw-test@mail.example.com>')->firstOrFail();

    expect($message->raw_email)->toBe($rawEmail);
});
