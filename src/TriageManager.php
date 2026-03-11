<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

use Closure;
use HotReloadStudios\Triage\Enums\MessageDirection;
use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use HotReloadStudios\Triage\Events\TicketClosed;
use HotReloadStudios\Triage\Events\TicketCreated;
use HotReloadStudios\Triage\Events\TicketMessageReceived;
use HotReloadStudios\Triage\Events\TicketNoteAdded;
use HotReloadStudios\Triage\Events\TicketReplied;
use HotReloadStudios\Triage\Events\TicketResolved;
use HotReloadStudios\Triage\Events\TicketUpdated;
use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Models\TicketMessage;
use HotReloadStudios\Triage\Models\TicketNote;
use HotReloadStudios\Triage\Support\ReplyTokenGenerator;
use HotReloadStudios\Triage\Support\SubmitterResolver;
use Illuminate\Database\Eloquent\Model;

final class TriageManager
{
    private ?Closure $authCallback = null;

    public function __construct(
        private readonly SubmitterResolver $submitterResolver,
        private readonly ReplyTokenGenerator $replyTokenGenerator,
    ) {}

    public function auth(Closure $callback): void
    {
        $this->authCallback = $callback;
    }

    public function resolveAuthCallback(): Closure
    {
        return $this->authCallback ?? static fn (mixed $user = null): bool => app()->environment('local', 'testing');
    }

    public function createTicket(
        string $subject,
        string $body,
        string $submitterEmail,
        string $submitterName,
        TicketPriority $priority = TicketPriority::Normal,
        ?string $assigneeId = null,
    ): Ticket {
        $submitterId = $this->submitterResolver->resolveId($submitterEmail);
        $replyToken = $this->replyTokenGenerator->generate();

        $ticket = Ticket::create([
            'subject' => $subject,
            'status' => TicketStatus::Open,
            'priority' => $priority,
            'submitter_id' => $submitterId,
            'submitter_name' => $submitterName,
            'submitter_email' => $submitterEmail,
            'assignee_id' => $assigneeId,
            'reply_token' => $replyToken,
        ]);

        $ticket->messages()->create([
            'direction' => MessageDirection::Inbound,
            'author_id' => $submitterId,
            'body' => $body,
            'message_id' => null,
            'raw_email' => null,
        ]);

        TicketCreated::dispatch($ticket);

        return $ticket->load('messages');
    }

    public function replyToTicket(
        Ticket $ticket,
        string $body,
        Model|string $agent,
    ): TicketMessage {
        $message = $ticket->messages()->create([
            'direction' => MessageDirection::Outbound,
            'author_id' => $this->resolveUserId($agent),
            'body' => $body,
            'message_id' => null,
            'raw_email' => null,
        ]);

        TicketReplied::dispatch($ticket, $message);

        return $message;
    }

    public function addNote(
        Ticket $ticket,
        string $body,
        Model|string $agent,
    ): TicketNote {
        $note = $ticket->notes()->create([
            'author_id' => $this->resolveUserId($agent),
            'body' => $body,
        ]);

        TicketNoteAdded::dispatch($ticket, $note);

        return $note;
    }

    public function updateTicket(
        Ticket $ticket,
        ?TicketStatus $status = null,
        ?TicketPriority $priority = null,
        Model|string|null $assignee = null,
    ): Ticket {
        $changes = [];

        if ($status !== null && $status !== $ticket->status) {
            $changes['status'] = ['old' => $ticket->status, 'new' => $status];
        }

        if ($priority !== null && $priority !== $ticket->priority) {
            $changes['priority'] = ['old' => $ticket->priority, 'new' => $priority];
        }

        if ($assignee !== null) {
            $resolvedAssigneeId = $this->resolveUserId($assignee);

            if ($resolvedAssigneeId !== $ticket->assignee_id) {
                $changes['assignee_id'] = ['old' => $ticket->assignee_id, 'new' => $resolvedAssigneeId];
            }
        }

        if ($changes === []) {
            return $ticket;
        }

        if (isset($changes['status'])) {
            $ticket->status = $changes['status']['new'];
        }

        if (isset($changes['priority'])) {
            $ticket->priority = $changes['priority']['new'];
        }

        if (isset($changes['assignee_id'])) {
            $ticket->assignee_id = $changes['assignee_id']['new'];
        }

        $ticket->save();

        TicketUpdated::dispatch($ticket, $changes);

        if (isset($changes['status']) && $changes['status']['new'] === TicketStatus::Resolved) {
            TicketResolved::dispatch($ticket);
        }

        if (isset($changes['status']) && $changes['status']['new'] === TicketStatus::Closed) {
            TicketClosed::dispatch($ticket);
        }

        return $ticket->fresh();
    }

    public function assignTicket(Ticket $ticket, Model|string $agent): Ticket
    {
        return $this->updateTicket($ticket, assignee: $agent);
    }

    public function resolveTicket(Ticket $ticket): Ticket
    {
        return $this->updateTicket($ticket, status: TicketStatus::Resolved);
    }

    public function closeTicket(Ticket $ticket): Ticket
    {
        return $this->updateTicket($ticket, status: TicketStatus::Closed);
    }

    public function addInboundMessage(
        Ticket $ticket,
        string $body,
        string $senderEmail,
        ?string $messageId = null,
        ?string $rawEmail = null,
    ): TicketMessage {
        if ($messageId !== null) {
            $existing = TicketMessage::where('message_id', $messageId)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $senderId = $this->submitterResolver->resolveId($senderEmail);

        $message = $ticket->messages()->create([
            'direction' => MessageDirection::Inbound,
            'author_id' => $senderId,
            'body' => $body,
            'message_id' => $messageId,
            'raw_email' => $rawEmail,
        ]);

        TicketMessageReceived::dispatch($ticket, $message);

        return $message;
    }

    private function resolveUserId(Model|string|null $user): ?string
    {
        if ($user instanceof Model) {
            return (string) $user->getKey();
        }

        if (is_string($user) && $user !== '') {
            return $user;
        }

        return null;
    }
}
