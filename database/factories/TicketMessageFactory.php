<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Database\Factories;

use HotReloadStudios\Triage\Enums\MessageDirection;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketMessage>
 */
final class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'direction' => MessageDirection::Inbound,
            'author_id' => null,
            'message_id' => null,
            'body' => fake()->paragraphs(2, true),
            'raw_email' => null,
        ];
    }

    public function inbound(): static
    {
        return $this->state(['direction' => MessageDirection::Inbound]);
    }

    public function outbound(): static
    {
        return $this->state(['direction' => MessageDirection::Outbound]);
    }

    public function withAuthor(string $userId): static
    {
        return $this->state(['author_id' => $userId]);
    }

    public function withMessageId(): static
    {
        return $this->state(['message_id' => '<'.fake()->uuid().'@mail.example.com>']);
    }

    public function withRawEmail(string $raw): static
    {
        return $this->state(['raw_email' => $raw]);
    }
}
