<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use HotReloadStudios\Triage\Http\Requests\AddNoteRequest;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Http\JsonResponse;

final class TicketNoteApiController
{
    public function __construct(private readonly TriageManager $triage) {}

    public function store(AddNoteRequest $request, Ticket $ticket): JsonResponse
    {
        $note = $this->triage->addNote(
            ticket: $ticket,
            body: $request->validated('body'),
            agent: $request->user(),
        );

        return response()->json(['data' => $note], 201);
    }
}
