<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Enums;

use HotReloadStudios\Triage\Enums\MessageDirection;

it('has exactly two cases', function (): void {
    expect(MessageDirection::cases())->toHaveCount(2);
});

it('has the correct values', function (): void {
    expect(MessageDirection::Inbound->value)->toBe('inbound');
    expect(MessageDirection::Outbound->value)->toBe('outbound');
});
