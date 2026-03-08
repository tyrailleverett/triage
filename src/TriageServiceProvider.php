<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage;

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
            ->hasMigration('create_migration_table_name_table');
    }
}
