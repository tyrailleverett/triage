<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature\Http;

use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Models\Ticket;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;

function makeApiUser(string $email = 'api-agent@example.com'): User
{
    $user = new User;
    $user->name = 'API Agent';
    $user->email = $email;
    $user->password = Hash::make('password');
    $user->save();

    return $user;
}

beforeEach(function (): void {
    config()->set('triage.user_model', User::class);
});

// ─── index ───────────────────────────────────────────────────────────────────

it('returns a successful response for the ticket list', function (): void {
    $user = makeApiUser();
    Ticket::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson('/triage/api/tickets')
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'meta', 'filters']);
});

it('paginates tickets', function (): void {
    $user = makeApiUser();
    Ticket::factory()->count(30)->create();

    $response = $this->actingAs($user)
        ->getJson('/triage/api/tickets')
        ->assertSuccessful();

    expect($response->json('meta.per_page'))->toBe(25)
        ->and($response->json('meta.total'))->toBe(30);
});

it('filters tickets by status', function (): void {
    $user = makeApiUser();
    Ticket::factory()->count(2)->create(['status' => TicketStatus::Open]);
    Ticket::factory()->count(3)->create(['status' => TicketStatus::Resolved]);

    $response = $this->actingAs($user)
        ->getJson('/triage/api/tickets?status=open')
        ->assertSuccessful();

    expect($response->json('meta.total'))->toBe(2);
});

it('filters tickets by priority', function (): void {
    $user = makeApiUser();
    Ticket::factory()->count(1)->create(['priority' => TicketPriority::Urgent]);
    Ticket::factory()->count(4)->create(['priority' => TicketPriority::Normal]);

    $response = $this->actingAs($user)
        ->getJson('/triage/api/tickets?priority=urgent')
        ->assertSuccessful();

    expect($response->json('meta.total'))->toBe(1);
});

it('filters tickets by assignee', function (): void {
    $user = makeApiUser();
    Ticket::factory()->count(2)->assigned((string) $user->getKey())->create();
    Ticket::factory()->count(3)->create();

    $response = $this->actingAs($user)
        ->getJson('/triage/api/tickets?assignee_id='.$user->getKey())
        ->assertSuccessful();

    expect($response->json('meta.total'))->toBe(2);
});

it('searches tickets by subject', function (): void {
    $user = makeApiUser();
    Ticket::factory()->create(['subject' => 'Need help with login']);
    Ticket::factory()->create(['subject' => 'Billing question']);

    $response = $this->actingAs($user)
        ->getJson('/triage/api/tickets?search=login')
        ->assertSuccessful();

    expect($response->json('meta.total'))->toBe(1);
});

it('denies access to unauthorized users on index', function (): void {
    $user = makeApiUser('unauth-idx@example.com');
    \Illuminate\Support\Facades\Gate::define('triage', fn (): bool => false);

    $this->actingAs($user)
        ->getJson('/triage/api/tickets')
        ->assertForbidden();
});

it('denies access to unauthenticated users on index', function (): void {
    $this->getJson('/triage/api/tickets')
        ->assertForbidden();
});

// ─── show ─────────────────────────────────────────────────────────────────────

it('returns a successful response for a ticket detail', function (): void {
    $user = makeApiUser('show-agent@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->getJson('/triage/api/tickets/'.$ticket->id)
        ->assertSuccessful()
        ->assertJsonPath('data.id', $ticket->id);
});

it('eager loads messages and notes', function (): void {
    $user = makeApiUser('eager-agent@example.com');
    $ticket = Ticket::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/triage/api/tickets/'.$ticket->id)
        ->assertSuccessful();

    expect($response->json('data'))->toHaveKeys(['messages', 'notes']);
});

it('returns 404 for nonexistent ticket', function (): void {
    $user = makeApiUser('notfound-agent@example.com');

    $this->actingAs($user)
        ->getJson('/triage/api/tickets/nonexistent-uuid')
        ->assertNotFound();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('creates a ticket with valid data', function (): void {
    $user = makeApiUser('store-agent@example.com');

    $this->actingAs($user)
        ->postJson('/triage/api/tickets', [
            'subject' => 'New ticket',
            'body' => 'This is the ticket body',
            'submitter_email' => 'customer@example.com',
            'submitter_name' => 'Customer Name',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.subject', 'New ticket');

    $this->assertDatabaseHas('tickets', ['subject' => 'New ticket']);
});

it('validates required fields', function (): void {
    $user = makeApiUser('validation-agent@example.com');

    $this->actingAs($user)
        ->postJson('/triage/api/tickets', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['subject', 'body', 'submitter_email', 'submitter_name']);
});

it('validates email format', function (): void {
    $user = makeApiUser('email-agent@example.com');

    $this->actingAs($user)
        ->postJson('/triage/api/tickets', [
            'subject' => 'Test',
            'body' => 'Body',
            'submitter_email' => 'not-an-email',
            'submitter_name' => 'Name',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['submitter_email']);
});

it('validates priority enum value', function (): void {
    $user = makeApiUser('priority-agent@example.com');

    $this->actingAs($user)
        ->postJson('/triage/api/tickets', [
            'subject' => 'Test',
            'body' => 'Body',
            'submitter_email' => 'customer@example.com',
            'submitter_name' => 'Name',
            'priority' => 'invalid-priority',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
});

it('defaults priority to Normal when not provided', function (): void {
    $user = makeApiUser('default-priority-agent@example.com');

    $response = $this->actingAs($user)
        ->postJson('/triage/api/tickets', [
            'subject' => 'Test',
            'body' => 'Body',
            'submitter_email' => 'customer@example.com',
            'submitter_name' => 'Name',
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('tickets', [
        'subject' => 'Test',
        'priority' => 'normal',
    ]);
});

it('assigns a ticket when assignee_id is provided', function (): void {
    $user = makeApiUser('assignee-agent@example.com');

    $this->actingAs($user)
        ->postJson('/triage/api/tickets', [
            'subject' => 'Assigned Ticket',
            'body' => 'Body',
            'submitter_email' => 'customer@example.com',
            'submitter_name' => 'Customer',
            'assignee_id' => (string) $user->getKey(),
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('tickets', [
        'subject' => 'Assigned Ticket',
        'assignee_id' => (string) $user->getKey(),
    ]);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('updates ticket status', function (): void {
    $user = makeApiUser('update-status-agent@example.com');
    $ticket = Ticket::factory()->open()->create();

    $this->actingAs($user)
        ->patchJson('/triage/api/tickets/'.$ticket->id, ['status' => 'resolved'])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'resolved');
});

it('updates ticket priority', function (): void {
    $user = makeApiUser('update-priority-agent@example.com');
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Normal]);

    $this->actingAs($user)
        ->patchJson('/triage/api/tickets/'.$ticket->id, ['priority' => 'urgent'])
        ->assertSuccessful()
        ->assertJsonPath('data.priority', 'urgent');
});

it('updates assignee', function (): void {
    $user = makeApiUser('update-assignee-agent@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->patchJson('/triage/api/tickets/'.$ticket->id, ['assignee_id' => (string) $user->getKey()])
        ->assertSuccessful();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'assignee_id' => (string) $user->getKey(),
    ]);
});

it('validates status enum value on update', function (): void {
    $user = makeApiUser('update-status-invalid-agent@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->patchJson('/triage/api/tickets/'.$ticket->id, ['status' => 'invalid-status'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('validates priority enum value on update', function (): void {
    $user = makeApiUser('update-priority-invalid-agent@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->patchJson('/triage/api/tickets/'.$ticket->id, ['priority' => 'super-urgent'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
});

it('rejects empty update', function (): void {
    $user = makeApiUser('empty-update-agent@example.com');
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->patchJson('/triage/api/tickets/'.$ticket->id, [])
        ->assertStatus(422);
});
