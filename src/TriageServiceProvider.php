<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

use HotReloadStudios\Triage\Commands\TriageInstallCommand;
use Illuminate\Contracts\Auth\Authenticatable;
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
            ->hasInstallCommand(function (InstallCommand $command): void {
                TriageInstallCommand::configure($command);
            });
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

        Gate::define('triage', static function (Authenticatable $user): bool {
            $callback = app(TriageManager::class)->resolveAuthCallback();

            return $callback($user);
        });
    }
}
