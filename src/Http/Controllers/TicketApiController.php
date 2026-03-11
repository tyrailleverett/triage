<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Http\Requests\CreateTicketRequest;
use HotReloadStudios\Triage\Http\Requests\UpdateTicketRequest;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TicketApiController
{
    public function __construct(private readonly TriageManager $triage) {}

    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->value());
        }

        if ($request->filled('assignee_id')) {
            $query->assignedTo($request->string('assignee_id')->value());
        }

        if ($request->filled('search')) {
            $term = $request->string('search')->value();
            $query->where(function ($q) use ($term): void {
                $q->where('subject', 'LIKE', "%{$term}%")
                    ->orWhere('submitter_email', 'LIKE', "%{$term}%");
            });
        }

        $query->withCount('messages')->orderByDesc('created_at');

        $tickets = $query->paginate(25);

        return response()->json([
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'filters' => $request->only(['status', 'priority', 'assignee_id', 'search']),
        ]);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load([
            'messages' => fn ($q) => $q->orderBy('created_at'),
            'notes' => fn ($q) => $q->orderBy('created_at'),
            'submitter',
            'assignee',
        ]);

        return response()->json(['data' => $ticket]);
    }

    public function store(CreateTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $authenticatedUser = $request->user();

        $priority = isset($validated['priority'])
            ? TicketPriority::from($validated['priority'])
            : TicketPriority::Normal;

        $submitterEmail = $validated['submitter_email'] ?? (string) data_get($authenticatedUser, 'email', '');
        $submitterName = $validated['submitter_name'] ?? (string) data_get($authenticatedUser, 'name', 'Authenticated User');

        $ticket = $this->triage->createTicket(
            subject: $validated['subject'],
            body: $validated['body'],
            submitterEmail: $submitterEmail,
            submitterName: $submitterName,
            priority: $priority,
            assigneeId: $validated['assignee_id'] ?? null,
        );

        return response()->json([
            'data' => $ticket,
            'location' => route('triage.dashboard').'/tickets/'.$ticket->id,
        ], 201);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validated();

        $status = isset($validated['status']) ? TicketStatus::from($validated['status']) : null;
        $priority = isset($validated['priority']) ? TicketPriority::from($validated['priority']) : null;
        $assignee = array_key_exists('assignee_id', $validated) ? ($validated['assignee_id'] ?? '') : null;

        $this->triage->updateTicket(
            ticket: $ticket,
            status: $status,
            priority: $priority,
            assignee: $assignee,
        );

        $ticket->refresh();

        return response()->json(['data' => $ticket]);
    }
}
