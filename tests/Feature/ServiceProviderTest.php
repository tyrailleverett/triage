<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature;

use HotReloadStudios\Triage\TriageManager;
use HotReloadStudios\Triage\TriageServiceProvider;
use Illuminate\Support\Facades\Gate;

it('registers the service provider', function (): void {
    expect($this->app->providerIsLoaded(TriageServiceProvider::class))->toBeTrue();
});

it('binds triage manager as singleton', function (): void {
    expect(app(TriageManager::class))->toBe(app(TriageManager::class));
});

it('registers the triage gate', function (): void {
    expect(Gate::has('triage'))->toBeTrue();
});
