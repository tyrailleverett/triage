<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Facades;

use Closure;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see TriageManager
 *
 * @method static void auth(Closure $callback)
 */
final class Triage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TriageManager::class;
    }
}
