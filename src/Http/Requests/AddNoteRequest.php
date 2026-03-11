<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddNoteRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
