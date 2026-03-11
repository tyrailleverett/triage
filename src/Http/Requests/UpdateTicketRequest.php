<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Requests;

use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            'assignee_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(Validator $validator): void
    {
        $keys = ['status', 'priority', 'assignee_id'];
        $hasAtLeastOne = collect($keys)->contains(fn (string $key): bool => $this->has($key));

        if (! $hasAtLeastOne) {
            $validator->errors()->add('general', 'At least one of status, priority, or assignee_id must be provided.');
        }
    }
}
