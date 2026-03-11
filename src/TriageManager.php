<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

final class TriageManager
{
    private ?Closure $authCallback = null;

    public function auth(Closure $callback): void
    {
        $this->authCallback = $callback;
    }

    public function resolveAuthCallback(): Closure
    {
        return $this->authCallback ?? static fn (Authenticatable $user): bool => app()->environment('local', 'testing');
    }
}
