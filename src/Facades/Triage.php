<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Facades;

use Closure;
use HotReloadStudios\Triage\TriageManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see TriageManager
 */
final class Triage extends Facade
{
    public static function auth(Closure $callback): void
    {
        /** @var TriageManager $triageManager */
        $triageManager = self::getFacadeRoot();

        $triageManager->auth($callback);
    }

    protected static function getFacadeAccessor(): string
    {
        return TriageManager::class;
    }
}
