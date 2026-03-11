<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Http;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

function makeSettingsApiUser(string $email = 'settings-agent@example.com'): User
{
    $user = new User;
    $user->name = 'Settings Agent';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

it('returns dashboard notification preferences in the expected shape', function (): void {
    $user = makeSettingsApiUser();

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertSuccessful()
        ->assertJsonPath('data.notify_on_new_ticket', false)
        ->assertJsonPath('data.notify_on_reply', false)
        ->assertJsonPath('data.notify_on_assignment', false)
        ->assertJsonPath('data.notify_on_status_change', false);
});

it('echoes validated notification updates for the dashboard client', function (): void {
    $user = makeSettingsApiUser('settings-update-agent@example.com');

    $this->actingAs($user)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_on_new_ticket' => true,
            'notify_on_reply' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.notify_on_new_ticket', true)
        ->assertJsonPath('data.notify_on_reply', true)
        ->assertJsonPath('data.notify_on_assignment', false)
        ->assertJsonPath('data.notify_on_status_change', false);
});

it('validates notification preference values', function (): void {
    $user = makeSettingsApiUser('settings-validation-agent@example.com');

    $this->actingAs($user)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_on_new_ticket' => 'sometimes',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['notify_on_new_ticket']);
});

it('denies access to unauthorized users on notification settings', function (): void {
    $user = makeSettingsApiUser('settings-unauthorized-agent@example.com');

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertForbidden();
});
