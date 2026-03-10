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
            $command->call('vendor:publish', ['--tag' => 'triage-assets', '--force' => true]);

            $mailboxAddress = config('triage.mailbox_address');
            $replyToAddress = config('triage.reply_to_address');

            if (blank($mailboxAddress) || blank($replyToAddress)) {
                $command->info('Inbound email remains disabled until mailbox/provider setup is completed and triage.mailbox_address and triage.reply_to_address are configured.');
            }

            $command->info('Add Triage::auth(fn (User $user): bool => $user->isAdmin()) in AppServiceProvider::boot() for production access control.');
            $command->info('Triage installed successfully.');
        });
    }
}
