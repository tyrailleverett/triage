<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Support;

use HotReloadStudios\Triage\Support\QuotedReplyTrimmer;

it('returns the body unchanged when no quoted content is detected', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "Hello,\n\nThis is my message.\n\nThanks!";

    expect($trimmer->trim($body))->toBe($body);
});

it('trims lines starting with greater-than signs', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "Original text\n\n> This is quoted content\n> More quoted content";

    expect($trimmer->trim($body))->toBe('Original text');
});

it('trims On ... wrote: patterns', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "Original text\n\nOn Mon, Jan 1 2024 at 10:00 AM, user@example.com wrote:\n> quoted reply";

    expect($trimmer->trim($body))->toBe('Original text');
});

it('trims Outlook original message separators', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "My reply\n\n-------- Original Message --------\nFrom: someone@example.com\nSubject: Test";

    expect($trimmer->trim($body))->toBe('My reply');
});

it('trims From: header patterns', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "My reply text\nFrom: sender@example.com\nSent: Monday, January 1, 2024";

    expect($trimmer->trim($body))->toBe('My reply text');
});

it('returns original body when trimming would produce empty string', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "> This entire email is quoted content\n> Line 2\n> Line 3";

    expect($trimmer->trim($body))->toBe($body);
});

it('trims trailing whitespace', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = "My message   \n\n  ";

    expect($trimmer->trim($body))->not->toMatch('/\s+$/');
});

it('normalizes html to plain text', function (): void {
    $trimmer = new QuotedReplyTrimmer;

    $body = '<p>Hello <strong>world</strong></p>';

    $result = $trimmer->trim($body);

    expect($result)->not->toContain('<')
        ->and($result)->toContain('Hello');
});
