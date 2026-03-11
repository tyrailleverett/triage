<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

use HotReloadStudios\Triage\Commands\TriageInstallCommand;
use Illuminate\Support\Facades\Gate;
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
            ->hasMigrations([
                'create_tickets_table',
                'create_ticket_messages_table',
                'create_ticket_notes_table',
            ])
            ->hasCommands([TriageInstallCommand::class]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SubmitterResolver::class);
        $this->app->bind(ReplyTokenGenerator::class);
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
