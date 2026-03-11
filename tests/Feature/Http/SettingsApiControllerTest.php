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

it('returns notification preferences in the expected shape', function (): void {
    $user = makeSettingsApiUser();

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'notify_ticket_assigned',
                'notify_ticket_replied',
                'notify_note_added',
                'notify_status_changed',
                'daily_digest',
                'email_notifications',
            ],
        ]);
});

it('saves notification updates for the agent', function (): void {
    $user = makeSettingsApiUser('settings-update-agent@example.com');

    $this->actingAs($user)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_ticket_assigned' => true,
            'notify_ticket_replied' => true,
            'notify_note_added' => false,
            'notify_status_changed' => true,
            'daily_digest' => false,
            'email_notifications' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.notify_ticket_assigned', true)
        ->assertJsonPath('data.notify_ticket_replied', true)
        ->assertJsonPath('data.notify_note_added', false);
});

it('validates notification preference values', function (): void {
    $user = makeSettingsApiUser('settings-validation-agent@example.com');

    $this->actingAs($user)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_ticket_assigned' => 'not-valid',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['notify_ticket_assigned']);
});

it('denies access to unauthorized users on notification settings', function (): void {
    $user = makeSettingsApiUser('settings-unauthorized-agent@example.com');

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertForbidden();
});
