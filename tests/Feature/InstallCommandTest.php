<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Feature;

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(config_path('triage.php'));
});

it('runs the install command successfully', function (): void {
    $this->artisan('triage:install')->assertExitCode(0);
});

it('publishes the config file', function (): void {
    $this->artisan('triage:install')->assertExitCode(0);

    expect(File::exists(config_path('triage.php')))->toBeTrue();
});

it('displays mailbox configuration guidance when inbound email settings are incomplete', function (): void {
    $this->artisan('triage:install')
        ->expectsOutputToContain('Inbound email remains disabled until mailbox/provider setup is completed')
        ->assertExitCode(0);
});
