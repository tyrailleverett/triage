<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Enums;

enum MessageDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
