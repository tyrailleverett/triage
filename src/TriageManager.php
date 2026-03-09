<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

use Closure;

final class TriageManager
{
    private ?Closure $authCallback = null;

    public function auth(Closure $callback): void
    {
        $this->authCallback = $callback;
    }

    public function resolveAuthCallback(): Closure
    {
        return $this->authCallback ?? static fn (mixed $user = null): bool => app()->environment('local', 'testing');
    }
}
