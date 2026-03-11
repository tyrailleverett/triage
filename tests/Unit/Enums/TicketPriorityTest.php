<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Enums;

use HotReloadStudios\Triage\Enums\TicketPriority;

it('has exactly four cases', function (): void {
    expect(TicketPriority::cases())->toHaveCount(4);
});

it('has the correct values', function (): void {
    expect(TicketPriority::Low->value)->toBe('low');
    expect(TicketPriority::Normal->value)->toBe('normal');
    expect(TicketPriority::High->value)->toBe('high');
    expect(TicketPriority::Urgent->value)->toBe('urgent');
});
