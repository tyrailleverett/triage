<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Commands;

use Illuminate\Console\Command;

final class TriageInstallCommand extends Command
{
    protected $signature = 'triage:install';

    protected $description = 'Install the Triage package';

    public function handle(): int
    {
        $this->info('Triage — Installing...');

        $this->call('vendor:publish', ['--tag' => 'triage-config']);
        $this->call('vendor:publish', ['--tag' => 'triage-migrations']);
        $this->call('migrate');
        $this->call('vendor:publish', ['--tag' => 'triage-assets', '--force' => true]);

        if (config('triage.mailbox_address') === null || config('triage.mailbox_address') === '' || config('triage.reply_to_address') === null || config('triage.reply_to_address') === '') {
            $this->info('Inbound email remains disabled until mailbox/provider setup is completed and triage.mailbox_address and triage.reply_to_address are configured.');
        }

        $this->info('Add Triage::auth(fn (User $user): bool => $user->isAdmin()) in AppServiceProvider::boot() for production access control.');
        $this->info('Triage installed successfully.');

        return self::SUCCESS;
    }
}
