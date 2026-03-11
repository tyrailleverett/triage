<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Enums;

use HotReloadStudios\Triage\Enums\TicketStatus;

it('has exactly four cases', function (): void {
    expect(TicketStatus::cases())->toHaveCount(4);
});

it('has the correct values', function (): void {
    expect(TicketStatus::Open->value)->toBe('open');
    expect(TicketStatus::Pending->value)->toBe('pending');
    expect(TicketStatus::Resolved->value)->toBe('resolved');
    expect(TicketStatus::Closed->value)->toBe('closed');
});
