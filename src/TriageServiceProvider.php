<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class TriageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('triage')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoutes('web')
            ->hasInstallCommand(function (InstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TriageManager::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->publishes([
            $this->package->basePath('/../resources/dist') => public_path('vendor/triage'),
        ], 'triage-assets');

        Gate::define('triage', $this->app->make(TriageManager::class)->resolveAuthCallback());
    }
}
