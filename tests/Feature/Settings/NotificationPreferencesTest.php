<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Settings;

use HotReloadStudios\Triage\Models\AgentPreference;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

function makeNotificationPrefUser(string $email = 'pref-agent@example.com'): User
{
    $user = new User;
    $user->name = 'Pref Agent';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

it('saves notification preferences for the authenticated agent', function (): void {
    $user = makeNotificationPrefUser();

    $this->actingAs($user)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_ticket_assigned' => true,
            'notify_ticket_replied' => false,
            'notify_note_added' => true,
            'notify_status_changed' => false,
            'daily_digest' => true,
            'email_notifications' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.notify_ticket_assigned', true)
        ->assertJsonPath('data.notify_ticket_replied', false)
        ->assertJsonPath('data.notify_note_added', true)
        ->assertJsonPath('data.notify_status_changed', false)
        ->assertJsonPath('data.daily_digest', true)
        ->assertJsonPath('data.email_notifications', false);
});

it('returns current preferences for the authenticated agent', function (): void {
    $user = makeNotificationPrefUser('pref-fetch@example.com');

    AgentPreference::create([
        'user_id' => (string) $user->getAuthIdentifier(),
        'notify_ticket_assigned' => false,
        'notify_ticket_replied' => true,
        'notify_note_added' => false,
        'notify_status_changed' => false,
        'daily_digest' => true,
        'email_notifications' => true,
    ]);

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertSuccessful()
        ->assertJsonPath('data.notify_ticket_assigned', false)
        ->assertJsonPath('data.notify_ticket_replied', true)
        ->assertJsonPath('data.daily_digest', true);
});

it('creates default preferences on first fetch if none exist', function (): void {
    $user = makeNotificationPrefUser('pref-firstfetch@example.com');

    expect(AgentPreference::where('user_id', (string) $user->getAuthIdentifier())->exists())->toBeFalse();

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertSuccessful()
        ->assertJsonPath('data.notify_ticket_assigned', true)
        ->assertJsonPath('data.notify_ticket_replied', true)
        ->assertJsonPath('data.notify_note_added', false)
        ->assertJsonPath('data.notify_status_changed', true)
        ->assertJsonPath('data.daily_digest', false)
        ->assertJsonPath('data.email_notifications', true);

    expect(AgentPreference::where('user_id', (string) $user->getAuthIdentifier())->exists())->toBeTrue();
});

it('returns 403 when the triage gate denies access', function (): void {
    $user = makeNotificationPrefUser('pref-denied@example.com');

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->getJson('/triage/api/settings/notifications')
        ->assertForbidden();
});

it('validates that all preference fields must be boolean', function (): void {
    $user = makeNotificationPrefUser('pref-validation@example.com');

    $this->actingAs($user)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_ticket_assigned' => 'not-a-boolean',
            'notify_ticket_replied' => true,
            'notify_note_added' => false,
            'notify_status_changed' => true,
            'daily_digest' => false,
            'email_notifications' => true,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['notify_ticket_assigned']);
});

it('creates separate preferences per user', function (): void {
    $userA = makeNotificationPrefUser('pref-user-a@example.com');
    $userB = makeNotificationPrefUser('pref-user-b@example.com');

    $this->actingAs($userA)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_ticket_assigned' => true,
            'notify_ticket_replied' => true,
            'notify_note_added' => false,
            'notify_status_changed' => true,
            'daily_digest' => false,
            'email_notifications' => false,
        ])
        ->assertSuccessful();

    $this->actingAs($userB)
        ->patchJson('/triage/api/settings/notifications', [
            'notify_ticket_assigned' => false,
            'notify_ticket_replied' => false,
            'notify_note_added' => true,
            'notify_status_changed' => false,
            'daily_digest' => true,
            'email_notifications' => true,
        ])
        ->assertSuccessful();

    expect(AgentPreference::count())->toBe(2);

    $prefA = AgentPreference::where('user_id', (string) $userA->getAuthIdentifier())->first();
    $prefB = AgentPreference::where('user_id', (string) $userB->getAuthIdentifier())->first();

    expect($prefA->notify_ticket_assigned)->toBeTrue()
        ->and($prefB->notify_ticket_assigned)->toBeFalse();
});
