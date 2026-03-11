<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Http;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

function makeAuthUser(string $email = 'agent@example.com'): User
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

it('returns the dashboard shell for the root route', function (): void {
    $user = makeAuthUser();

    $this->actingAs($user)
        ->get('/triage')
        ->assertSuccessful()
        ->assertViewIs('triage::app');
});

it('returns the dashboard shell for deep links such as tickets and settings', function (): void {
    $user = makeAuthUser();

    $this->actingAs($user)
        ->get('/triage/tickets')
        ->assertSuccessful()
        ->assertViewIs('triage::app');

    $this->actingAs($user)
        ->get('/triage/settings')
        ->assertSuccessful()
        ->assertViewIs('triage::app');

    $this->actingAs($user)
        ->get('/triage/settings/notifications')
        ->assertSuccessful()
        ->assertViewIs('triage::app');
});

it('denies access to unauthorized users', function (): void {
    $user = makeAuthUser('unauth@example.com');

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->get('/triage')
        ->assertForbidden();
});

it('denies access to unauthenticated users', function (): void {
    $this->get('/triage')
        ->assertForbidden();
});
