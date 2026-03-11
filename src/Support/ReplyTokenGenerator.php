<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Support;

final class ReplyTokenGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
