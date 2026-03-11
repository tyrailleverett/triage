<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Http;

use HotReloadStudios\Triage\Enums\MessageDirection;
use HotReloadStudios\Triage\Models\Ticket;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

function makeMessageApiUser(string $email = 'message-agent@example.com'): User
{
    $user = new User;
    $user->name = 'Message Agent';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

it('creates a reply on a ticket', function (): void {
    $user = makeMessageApiUser();
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/messages', [
            'body' => 'This is my reply.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.body', 'This is my reply.');

    $this->assertDatabaseHas('ticket_messages', [
        'ticket_id' => $ticket->id,
        'body' => 'This is my reply.',
    ]);
});

it('validates body is required', function (): void {
    $user = makeMessageApiUser('msg-validation@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/messages', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

it('validates body max length', function (): void {
    $user = makeMessageApiUser('msg-maxlength@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/messages', [
            'body' => str_repeat('a', 10001),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

it('denies access to unauthorized users on message store', function (): void {
    $user = makeMessageApiUser('msg-unauth@example.com');
    $ticket = Ticket::factory()->create();

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/messages', ['body' => 'Reply'])
        ->assertForbidden();
});

it('sets the authenticated user as author', function (): void {
    $user = makeMessageApiUser('msg-author@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/messages', [
            'body' => 'Author reply.',
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('ticket_messages', [
        'ticket_id' => $ticket->id,
        'author_id' => (string) $user->getKey(),
        'direction' => MessageDirection::Outbound->value,
    ]);
});
