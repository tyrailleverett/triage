<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature;

use HotReloadStudios\Triage\Facades\Triage;
use HotReloadStudios\Triage\TriageManager;
use HotReloadStudios\Triage\TriageServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

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

it('registers the service provider', function (): void {
    expect($this->app->providerIsLoaded(TriageServiceProvider::class))->toBeTrue();
});

it('binds triage manager as singleton', function (): void {
    expect(app(TriageManager::class))->toBe(app(TriageManager::class));
});

it('registers the triage gate', function (): void {
    expect(Gate::has('triage'))->toBeTrue();
});

it('uses the latest triage auth callback after boot', function (): void {
    $user = makeAuthenticatableUser();

    expect(Gate::forUser($user)->allows('triage'))->toBeTrue();

    Triage::auth(static fn (Authenticatable $user): bool => false);

    expect(Gate::forUser($user)->allows('triage'))->toBeFalse();

    Triage::auth(static fn (Authenticatable $user): bool => true);

    expect(Gate::forUser($user)->allows('triage'))->toBeTrue();
});

it('denies guest access to the triage gate by default', function (): void {
    expect(Gate::allows('triage'))->toBeFalse();
});
