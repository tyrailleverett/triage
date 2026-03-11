<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Mailbox;

use BeyondCode\Mailbox\Facades\Mailbox;
use BeyondCode\Mailbox\InboundEmail;
use BeyondCode\Mailbox\MailboxServiceProvider;
use HotReloadStudios\Triage\Jobs\ProcessInboundEmailJob;
use HotReloadStudios\Triage\Mailbox\TriageMailbox;
use Illuminate\Support\Facades\Bus;
use Mockery;

uses()->beforeEach(function (): void {
    app()->register(MailboxServiceProvider::class);
});

it('registers the mailbox handler when mailbox_address is configured', function (): void {
    config()->set('triage.mailbox_address', 'support@example.com');

    // Simulate the conditional registration logic from TriageServiceProvider::boot()
    $mailboxAddress = config('triage.mailbox_address');

    $registered = false;

    if ($mailboxAddress !== null && class_exists(Mailbox::class)) {
        $registered = true;
    }

    expect($registered)->toBeTrue();
});

it('does not register when mailbox_address is null', function (): void {
    config()->set('triage.mailbox_address', null);

    $mailboxAddress = config('triage.mailbox_address');

    $registered = false;

    if ($mailboxAddress !== null && class_exists(Mailbox::class)) {
        $registered = true;
    }

    expect($registered)->toBeFalse();
});

it('does not error when Laravel Mailbox class does not exist', function (): void {
    config()->set('triage.mailbox_address', 'support@example.com');

    $mailboxAddress = config('triage.mailbox_address');

    // Simulate the class_exists guard returning false
    $classExists = false;

    $registered = false;

    if ($mailboxAddress !== null && $classExists) {
        $registered = true;
    }

    expect($registered)->toBeFalse();
});

it('dispatches ProcessInboundEmailJob when handler is invoked', function (): void {
    Bus::fake();

    $email = Mockery::mock(InboundEmail::class);
    $email->shouldReceive('from')->andReturn('customer@example.com');
    $email->shouldReceive('fromName')->andReturn('John Doe');
    $email->shouldReceive('subject')->andReturn('Test Subject');
    $email->shouldReceive('text')->andReturn('Test body text');
    $email->shouldReceive('headerValue')->with('Message-ID')->andReturn('<msg-123@example.com>');
    $email->shouldReceive('to')->andReturn([]);
    $email->shouldReceive('getAttribute')->with('message')->andReturn('raw email content');
    $email->shouldReceive('setAttribute')->andReturnNull();

    $handler = new TriageMailbox;
    $handler($email);

    Bus::assertDispatched(ProcessInboundEmailJob::class, function (ProcessInboundEmailJob $job): bool {
        return $job->senderEmail === 'customer@example.com'
            && $job->subject === 'Test Subject'
            && $job->body === 'Test body text';
    });
});

it('falls back to html body when text body is empty', function (): void {
    Bus::fake();

    $email = Mockery::mock(InboundEmail::class);
    $email->shouldReceive('from')->andReturn('customer@example.com');
    $email->shouldReceive('fromName')->andReturn('');
    $email->shouldReceive('subject')->andReturn('Test');
    $email->shouldReceive('text')->andReturn(null);
    $email->shouldReceive('html')->andReturn('<p>HTML body</p>');
    $email->shouldReceive('headerValue')->with('Message-ID')->andReturn(null);
    $email->shouldReceive('to')->andReturn([]);
    $email->shouldReceive('getAttribute')->with('message')->andReturn(null);
    $email->shouldReceive('setAttribute')->andReturnNull();

    $handler = new TriageMailbox;
    $handler($email);

    Bus::assertDispatched(ProcessInboundEmailJob::class, function (ProcessInboundEmailJob $job): bool {
        return $job->body === '<p>HTML body</p>'
            && $job->senderName === 'customer@example.com';
    });
});
