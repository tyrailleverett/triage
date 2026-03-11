<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Requests;

use HotReloadStudios\Triage\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'submitter_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'submitter_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            'assignee_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
