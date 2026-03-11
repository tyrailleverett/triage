<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Mailbox;

use BeyondCode\Mailbox\InboundEmail;
use HotReloadStudios\Triage\Jobs\ProcessInboundEmailJob;

final class TriageMailbox
{
    public function __invoke(InboundEmail $email): void
    {
        $senderEmail = $email->from();
        $senderName = $email->fromName() !== '' ? $email->fromName() : $senderEmail;
        $subject = (string) $email->subject();
        $body = $email->text() ?? (string) $email->html();

        $messageId = $email->headerValue('Message-ID');
        $rawEmail = $email->getAttribute('message');

        $recipients = $email->to();
        $recipientAddress = count($recipients) > 0
            ? $recipients[0]->getEmail()
            : null;

        ProcessInboundEmailJob::dispatch(
            senderEmail: $senderEmail,
            senderName: $senderName,
            subject: $subject,
            body: $body,
            messageId: $messageId,
            rawEmail: $rawEmail,
            recipientAddress: $recipientAddress,
        );
    }
}
