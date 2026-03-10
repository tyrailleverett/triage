<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature;

it('has all expected config keys', function (): void {
    $config = config('triage');

    expect($config)
        ->toBeArray()
        ->toHaveKeys([
            'path',
            'middleware',
            'mailbox_address',
            'reply_to_address',
            'from_name',
            'from_address',
            'user_model',
        ]);

    expect(config('triage.path'))->toBe('triage');
    expect(config('triage.middleware'))->toBe(['web']);
    expect(config('triage.mailbox_address'))->toBeNull();
    expect(config('triage.reply_to_address'))->toBeNull();
    expect(config('triage.from_name'))->toBe((string) config('app.name'));
    expect(config('triage.from_address'))->toBe((string) config('mail.from.address'));
});

it('resolves the user model config', function (): void {
    expect(config('triage.user_model'))->toBe('App\\Models\\User');
});
