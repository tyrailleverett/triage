<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Database\Factories;

use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Normal,
            'submitter_id' => null,
            'submitter_name' => fake()->name(),
            'submitter_email' => fake()->safeEmail(),
            'assignee_id' => null,
            'reply_token' => Str::random(32),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => TicketStatus::Open]);
    }

    public function pending(): static
    {
        return $this->state(['status' => TicketStatus::Pending]);
    }

    public function resolved(): static
    {
        return $this->state(['status' => TicketStatus::Resolved]);
    }

    public function closed(): static
    {
        return $this->state(['status' => TicketStatus::Closed]);
    }

    public function lowPriority(): static
    {
        return $this->state(['priority' => TicketPriority::Low]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => TicketPriority::High]);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => TicketPriority::Urgent]);
    }

    public function assigned(string $userId): static
    {
        return $this->state(['assignee_id' => $userId]);
    }

    public function withSubmitter(string $userId): static
    {
        return $this->state(['submitter_id' => $userId]);
    }
}
