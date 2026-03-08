<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Commands;

use Spatie\LaravelPackageTools\Commands\InstallCommand;

final class TriageInstallCommand
{
    public static function configure(InstallCommand $command): void
    {
        $command->startWith(function (InstallCommand $command): void {
            $command->info('Triage — Installing...');

            $command->call('vendor:publish', ['--tag' => 'triage-config']);
            $command->call('vendor:publish', ['--tag' => 'triage-migrations']);
            $command->call('migrate');
            $command->call('vendor:publish', ['--tag' => 'triage-assets', '--force' => true]);

            if (config('triage.mailbox_address') === null || config('triage.mailbox_address') === '' || config('triage.reply_to_address') === null || config('triage.reply_to_address') === '') {
                $command->info('Inbound email remains disabled until mailbox/provider setup is completed and triage.mailbox_address and triage.reply_to_address are configured.');
            }

            $command->info('Add Triage::auth(fn (User $user): bool => $user->isAdmin()) in AppServiceProvider::boot() for production access control.');
            $command->info('Triage installed successfully.');
        });
    }
}
