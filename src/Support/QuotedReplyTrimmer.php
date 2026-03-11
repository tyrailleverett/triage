<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Support;

final class QuotedReplyTrimmer
{
    /**
     * Strip quoted reply content from an inbound email body on a best-effort basis.
     *
     * If the input contains HTML it is first normalized to plain text. Quoted
     * reply patterns are then detected and the body is truncated at the first
     * occurrence. If trimming would produce an empty string the original body
     * is returned unchanged.
     */
    public function trim(string $body): string
    {
        $normalized = $this->normalize($body);

        $trimmed = $this->stripQuotedContent($normalized);

        if ($trimmed === '') {
            return $normalized === '' ? $body : $normalized;
        }

        return $trimmed;
    }

    /**
     * Normalize HTML to plain text when HTML tags are detected.
     */
    private function normalize(string $body): string
    {
        if (! $this->containsHtml($body)) {
            return $body;
        }

        $decoded = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse multiple blank lines into a single blank line and trim
        return (string) preg_replace('/\n{3,}/', "\n\n", mb_trim($decoded));
    }

    private function containsHtml(string $body): bool
    {
        return $body !== strip_tags($body);
    }

    /**
     * Detect and truncate quoted reply patterns in a plain-text body.
     */
    private function stripQuotedContent(string $body): string
    {
        $lines = explode("\n", $body);
        $cutAt = null;

        foreach ($lines as $index => $line) {
            if ($this->isQuotedLine($line)) {
                $cutAt = $index;
                break;
            }
        }

        if ($cutAt === null) {
            return mb_rtrim($body);
        }

        $result = mb_rtrim(implode("\n", array_slice($lines, 0, $cutAt)));

        return $result;
    }

    /**
     * Determine whether a given line begins a quoted reply block.
     */
    private function isQuotedLine(string $line): bool
    {
        $trimmed = mb_ltrim($line);

        // Lines starting with `>` characters (standard quoting)
        if (str_starts_with($trimmed, '>')) {
            return true;
        }

        // "On ... wrote:" pattern (Gmail, Apple Mail)
        if ((bool) preg_match('/^On .+ wrote:\s*$/i', $trimmed)) {
            return true;
        }

        // Outlook original message separator
        if (str_starts_with($trimmed, '-------- Original Message --------')) {
            return true;
        }

        // From: header-like content
        if ((bool) preg_match('/^From:\s+\S+@\S+/i', $trimmed)) {
            return true;
        }

        return false;
    }
}
