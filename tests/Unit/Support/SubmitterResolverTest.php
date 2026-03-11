<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Support;

use HotReloadStudios\Triage\Support\SubmitterResolver;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

it('returns null when no user matches the email', function (): void {
    $resolver = new SubmitterResolver;

    $result = $resolver->resolve('nonexistent@example.com');

    expect($result)->toBeNull();
});

it('returns the user when email matches', function (): void {
    $user = new User;
    $user->name = 'Test User';
    $user->email = 'found@example.com';
    $user->password = Hash::make('password');
    $user->save();

    $resolver = new SubmitterResolver;

    $result = $resolver->resolve('found@example.com');

    expect($result)->not->toBeNull()
        ->and($result->getKey())->toBe($user->getKey());
});

it('returns the user ID as a string', function (): void {
    $user = new User;
    $user->name = 'Test User';
    $user->email = 'strid@example.com';
    $user->password = Hash::make('password');
    $user->save();

    $resolver = new SubmitterResolver;

    $result = $resolver->resolveId('strid@example.com');

    expect($result)->toBeString()
        ->and($result)->toBe((string) $user->getKey());
});
