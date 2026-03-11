<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit;

use Closure;
use HotReloadStudios\Triage\TriageManager;

it('stores an auth callback', function (): void {
    $manager = app(TriageManager::class);
    $callback = static fn (mixed $user = null): bool => true;

    $manager->auth($callback);

    expect($manager->resolveAuthCallback())->toBe($callback);
});

it('returns a default auth callback when none is set', function (): void {
    $manager = app(TriageManager::class);

    expect($manager->resolveAuthCallback())->toBeInstanceOf(Closure::class);
});

it('allows access in local environment by default', function (): void {
    $manager = app(TriageManager::class);
    app()['env'] = 'local';

    $callback = $manager->resolveAuthCallback();

    expect($callback())->toBeTrue();
});

it('denies access in production environment by default', function (): void {
    $manager = app(TriageManager::class);
    app()['env'] = 'production';

    $callback = $manager->resolveAuthCallback();

    expect($callback())->toBeFalse();
});
