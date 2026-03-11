<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Support;

use HotReloadStudios\Triage\Support\ReplyTokenGenerator;

it('generates a 32-character hex string', function (): void {
    $generator = new ReplyTokenGenerator;

    $token = $generator->generate();

    expect($token)->toBeString()
        ->toHaveLength(32)
        ->toMatch('/^[a-f0-9]{32}$/');
});

it('generates unique tokens', function (): void {
    $generator = new ReplyTokenGenerator;

    $tokens = array_map(fn (): string => $generator->generate(), range(1, 100));

    expect(array_unique($tokens))->toHaveCount(100);
});
