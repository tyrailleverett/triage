<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Http;

use HotReloadStudios\Triage\Models\Ticket;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

function makeNoteApiUser(string $email = 'note-agent@example.com'): User
{
    $user = new User;
    $user->name = 'Note Agent';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

it('creates a note on a ticket', function (): void {
    $user = makeNoteApiUser();
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/notes', [
            'body' => 'This is an internal note.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.body', 'This is an internal note.');

    $this->assertDatabaseHas('ticket_notes', [
        'ticket_id' => $ticket->id,
        'body' => 'This is an internal note.',
    ]);
});

it('validates body is required on notes', function (): void {
    $user = makeNoteApiUser('note-validation@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/notes', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

it('denies access to unauthorized users on note store', function (): void {
    $user = makeNoteApiUser('note-unauth@example.com');
    $ticket = Ticket::factory()->create();

    Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/notes', ['body' => 'Note'])
        ->assertForbidden();
});

it('sets the authenticated user as note author', function (): void {
    $user = makeNoteApiUser('note-author@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->postJson('/triage/api/tickets/'.$ticket->id.'/notes', [
            'body' => 'Author note.',
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('ticket_notes', [
        'ticket_id' => $ticket->id,
        'author_id' => (string) $user->getKey(),
        'body' => 'Author note.',
    ]);
});
