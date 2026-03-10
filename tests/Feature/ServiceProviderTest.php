<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature;

use HotReloadStudios\Triage\Facades\Triage;
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

it('gate consults the current auth callback', function (): void {
    $this->app['env'] = 'production';

    expect(Gate::allows('triage'))->toBeFalse();

    Triage::auth(fn (mixed $user = null): bool => true);

    expect(Gate::allows('triage'))->toBeTrue();

    Triage::auth(fn (mixed $user = null): bool => false);

    expect(Gate::allows('triage'))->toBeFalse();
});
