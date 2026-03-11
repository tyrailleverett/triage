<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeTriage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            abort(403);
        }

        if (! Gate::check('triage', [$request->user()])) {
            abort(403);
        }

        return $next($request);
    }
}
