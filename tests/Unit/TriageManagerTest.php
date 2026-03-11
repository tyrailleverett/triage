<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit;

use Closure;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Contracts\Auth\Authenticatable;

function makeAuthenticatableUser(): Authenticatable
{
    return new class implements Authenticatable
    {
        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): int
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return 'password';
        }

        public function getRememberToken(): string
        {
            return 'remember_token';
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    };
}

it('stores an auth callback', function (): void {
    $manager = new TriageManager();
    $callback = static fn (Authenticatable $user): bool => true;

    $manager->auth($callback);

    expect($manager->resolveAuthCallback())->toBe($callback);
});

it('returns a default auth callback when none is set', function (): void {
    $manager = new TriageManager();

    expect($manager->resolveAuthCallback())->toBeInstanceOf(Closure::class);
});

it('allows access in local environment by default', function (): void {
    $manager = new TriageManager();
    app()['env'] = 'local';

    $callback = $manager->resolveAuthCallback();

    expect($callback(makeAuthenticatableUser()))->toBeTrue();
});

it('denies access in production environment by default', function (): void {
    $manager = new TriageManager();
    app()['env'] = 'production';

    $callback = $manager->resolveAuthCallback();

    expect($callback(makeAuthenticatableUser()))->toBeFalse();
});
