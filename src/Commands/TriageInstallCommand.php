<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Commands;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

final class TriageInstallCommand
{
    public static function configure(InstallCommand $command): void
    {
        $command
            ->startWith(function (InstallCommand $command): void {
                $command->info('Triage — Installing...');

                static::publishMigrationsIfAvailable($command);
            })
            ->publishConfigFile()
            ->publishAssets()
            ->endWith(function (InstallCommand $command): void {
                if (static::mailboxConfigurationIsIncomplete()) {
                    $command->info('Inbound email remains disabled until mailbox/provider setup is completed and triage.mailbox_address and triage.reply_to_address are configured.');
                }

                $command->info('Add Triage::auth(fn (User $user): bool => $user->isAdmin()) in AppServiceProvider::boot() for production access control.');
                $command->info('Triage installed successfully.');
            });
    }

    private static function publishMigrationsIfAvailable(InstallCommand $command): void
    {
        if (ServiceProvider::pathsToPublish(group: 'triage-migrations') === []) {
            return;
        }

        $command->call('vendor:publish', ['--tag' => 'triage-migrations']);
        $command->call('migrate');
    }

    private static function mailboxConfigurationIsIncomplete(): bool
    {
        $mailboxAddress = config('triage.mailbox_address');
        $replyToAddress = config('triage.reply_to_address');

        return blank($mailboxAddress) || blank($replyToAddress);
    }
}
