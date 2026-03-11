<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Http\Middleware;

use HotReloadStudios\Triage\TriageManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

function makeUser(string $email = 'agent@example.com'): User
{
    $user = new User;
    $user->name = 'Test Agent';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

it('allows access when gate passes', function (): void {
    $user = makeUser();

    $this->actingAs($user)
        ->get('/triage')
        ->assertSuccessful();
});

it('denies access when gate fails', function (): void {
    $user = makeUser();

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->get('/triage')
        ->assertForbidden();
});

it('denies access to unauthenticated users', function (): void {
    $this->get('/triage')
        ->assertForbidden();
});

it('uses the default gate in local environment', function (): void {
    $manager = app(TriageManager::class);
    app()['env'] = 'local';

    $callback = $manager->resolveAuthCallback();

    expect($callback())->toBeTrue();
});

it('uses the default gate in production environment', function (): void {
    $manager = app(TriageManager::class);
    app()['env'] = 'production';

    $callback = $manager->resolveAuthCallback();

    expect($callback())->toBeFalse();
});
