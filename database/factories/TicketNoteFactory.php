<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Database\Factories;

use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketNote>
 */
final class TicketNoteFactory extends Factory
{
    protected $model = TicketNote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'author_id' => fake()->uuid(),
            'body' => fake()->paragraph(),
        ];
    }
}
