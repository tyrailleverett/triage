<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HotReloadStudios\Triage\TriageManager
 */
final class Triage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HotReloadStudios\Triage\TriageManager::class;
    }
}
