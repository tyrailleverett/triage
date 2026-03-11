<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Support;

use HotReloadStudios\Triage\Support\ReplyAddressFormatter;

beforeEach(function (): void {
    config()->set('triage.reply_to_address', 'support@example.com');
    config()->set('triage.from_address', 'noreply@example.com');
});

it('formats a reply-to address with token', function (): void {
    $formatter = new ReplyAddressFormatter;

    $token = str_repeat('a', 32);

    expect($formatter->format($token))->toBe("support+triage-{$token}@example.com");
});

it('falls back to from_address when reply_to_address is null', function (): void {
    config()->set('triage.reply_to_address', null);

    $formatter = new ReplyAddressFormatter;

    $token = str_repeat('b', 32);

    expect($formatter->format($token))->toBe("noreply+triage-{$token}@example.com");
});

it('extracts a token from a valid reply address', function (): void {
    $formatter = new ReplyAddressFormatter;

    $token = 'abc123def456789012345678abcdef01';

    $address = "support+triage-{$token}@example.com";

    expect($formatter->extractToken($address))->toBe($token);
});

it('returns null for addresses without a triage token', function (): void {
    $formatter = new ReplyAddressFormatter;

    expect($formatter->extractToken('support@example.com'))->toBeNull();
});

it('returns null for malformed tokens', function (): void {
    $formatter = new ReplyAddressFormatter;

    expect($formatter->extractToken('support+triage-short@example.com'))->toBeNull();
});
