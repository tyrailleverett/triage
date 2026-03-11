<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Support;

final class ReplyAddressFormatter
{
    /**
     * Build a reply-to email address with an embedded reply token.
     *
     * Example: support@example.com + token → support+triage-{token}@example.com
     */
    public function format(string $replyToken): string
    {
        $base = config('triage.reply_to_address') ?: config('triage.from_address');

        [$local, $domain] = explode('@', (string) $base, 2);

        return "{$local}+triage-{$replyToken}@{$domain}";
    }

    /**
     * Extract a reply token from an inbound recipient address.
     *
     * Returns the 32-character hex token if found, or null if the address
     * does not contain a triage reply token.
     */
    public function extractToken(string $emailAddress): ?string
    {
        if ((bool) preg_match('/\+triage-([a-f0-9]{32})@/i', $emailAddress, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
