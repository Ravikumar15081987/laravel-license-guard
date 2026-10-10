<?php

namespace Nirikshaoccesstech\LicenseGuard\Commands;

use Illuminate\Console\Command;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class ActivateLicenseCommand extends Command
{
    protected $signature = 'license:activate {key : The 16-character license key issued by Niriksha Occesstech}';
    protected $description = 'Activate or renew the application using an official license key issued by Niriksha Occesstech';

    public function handle(): int
    {
        $key = trim($this->argument('key'));

        try {
            $result = LicenseGuard::verifyAndApplyLicense($key);

            $this->newLine();
            $this->info("==========================================================");
            $this->info("             LICENSE ACTIVATED SUCCESSFULLY               ");
            $this->info("==========================================================");
            $this->line("Days Granted     : {$result['days']} Days");
            $this->line("Tier Level       : [Tier {$result['tier']}]");
            $this->line("New Expiration   : {$result['expires_at']->format('Y-m-d H:i:s')}");
            $this->info("==========================================================");
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Activation Failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}