<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use HotReloadStudios\Triage\Http\Requests\ReplyToTicketRequest;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Http\JsonResponse;

final class TicketMessageApiController
{
    public function __construct(private readonly TriageManager $triage) {}

    public function store(ReplyToTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $message = $this->triage->replyToTicket(
            ticket: $ticket,
            body: $request->validated('body'),
            agent: $request->user(),
        );

        return response()->json(['data' => $message->load('author')], 201);
    }
}
