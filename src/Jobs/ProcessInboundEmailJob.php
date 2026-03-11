<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Jobs;

use HotReloadStudios\Triage\Models\Ticket;
use HotReloadStudios\Triage\Support\QuotedReplyTrimmer;
use HotReloadStudios\Triage\Support\ReplyAddressFormatter;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessInboundEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(
        public readonly string $senderEmail,
        public readonly string $senderName,
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $messageId,
        public readonly ?string $rawEmail,
        public readonly ?string $recipientAddress,
    ) {}

    public function handle(TriageManager $triage, ReplyAddressFormatter $formatter, QuotedReplyTrimmer $trimmer): void
    {
        $trimmedBody = $trimmer->trim($this->body);

        $token = $formatter->extractToken($this->recipientAddress ?? '');

        if ($token !== null) {
            $ticket = Ticket::where('reply_token', $token)->first();

            if ($ticket === null) {
                Log::warning('Triage: orphaned reply token — no matching ticket found.', [
                    'token' => $token,
                    'sender' => $this->senderEmail,
                    'subject' => $this->subject,
                ]);

                return;
            }

            $triage->addInboundMessage($ticket, $trimmedBody, $this->senderEmail, $this->messageId, $this->rawEmail);

            return;
        }

        $ticket = $triage->createTicket(
            subject: $this->subject,
            body: $trimmedBody,
            submitterEmail: $this->senderEmail,
            submitterName: $this->senderName !== '' ? $this->senderName : $this->senderEmail,
        );

        if ($this->messageId !== null) {
            $ticket->messages()->oldest()->first()?->update([
                'message_id' => $this->messageId,
                'raw_email' => $this->rawEmail,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Triage: ProcessInboundEmailJob failed.', [
            'sender' => $this->senderEmail,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
