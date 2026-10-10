<?php

namespace Nirikshaoccesstech\LicenseGuard\Commands;

use Illuminate\Console\Command;

class InstallLicenseGuardCommand extends Command
{
    protected $signature = 'license:install';
    protected $description = 'Publish configuration and execute migrations for Niriksha Occesstech License Guard';

    public function handle(): int
    {
        $this->info('Installing Niriksha Occesstech License Guard...');

        $this->call('vendor:publish', [
            '--tag' => 'license-guard-config',
            '--force' => false,
        ]);

        $this->call('migrate');

        $this->newLine();
        $this->info('License Guard successfully installed!');
        $this->line('1. Set your Company Code in config/license-guard.php or your settings table.');
        $this->line('2. Register middleware in bootstrap/app.php.');
        $this->line('3. Activate using an official key: php artisan license:activate {key}');
        $this->newLine();

        return self::SUCCESS;
    }
}