<?php

namespace Nirikshaoccesstech\LicenseGuard\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class CheckLicenseStatusCommand extends Command
{
    protected $signature = 'license:status';
    protected $description = 'Display comprehensive CRM license status, grace periods, tiers, and security health';

    public function handle(): int
    {
        $status = LicenseGuard::status();
        $appliedCount = DB::table('applied_crm_licenses')->count();

        $this->newLine();
        $this->info("===============================================================================");
        $this->info("                    ENTERPRISE LICENSE GUARD STATUS REPORT                     ");
        $this->info("===============================================================================");

        $statusColor = match ($status['status']) {
            'ACTIVE' => '<fg=green;options=bold>ACTIVE</>',
            'GRACE_PERIOD' => '<fg=yellow;options=bold>GRACE PERIOD</>',
            'EXPIRED' => '<fg=red;options=bold>EXPIRED (READ-ONLY)</>',
            'CLOCK_TAMPERED' => '<fg=red;options=bold>CLOCK TAMPER LOCK</>',
            'SUSPENDED_TEMP' => '<fg=yellow;options=bold>TEMPORARILY SUSPENDED</>',
            'SUSPENDED_BLOCK' => '<fg=red;options=bold>PERMANENTLY BLOCKED</>',
            default => '<fg=gray;options=bold>UNLICENSED</>',
        };

        $expiryDisplay = $status['expires_at'] ? $status['expires_at']->format('Y-m-d H:i:s') : 'None';
        if ($status['is_in_grace_period']) {
            $expiryDisplay .= " (Grace Period until {$status['grace_ends_at']->format('Y-m-d')})";
        }

        $features = config("license-guard.tiers.{$status['tier']}.features", []);
        $featuresDisplay = in_array('*', $features) ? 'All Features (Enterprise)' : implode(', ', $features);

        $this->table(
            ['Diagnostic Property', 'Value'],
            [
                ['Company Code', $status['company_code']],
                ['License Status', $statusColor],
                ['Summary Message', $status['message']],
                ['Active Tier', "{$status['tier']} ({$status['tier_name']})"],
                ['Enabled Entitlements', $featuresDisplay ?: 'None'],
                ['Expiration Date', $expiryDisplay],
                ['Days Remaining', $status['days_remaining']],
                ['Grace Days Remaining', $status['grace_days_remaining']],
                ['Total Activated Keys (Ledger)', $appliedCount],
                ['Clock Integrity Check', LicenseGuard::checkClockIntegrity() ? '<fg=green>PASSED</>' : '<fg=red>FAILED</>'],
                ['Cryptographic Secret Source', config('license-guard.secret_key') ? 'Dedicated LICENSE_GUARD_SECRET' : 'Default APP_KEY (Legacy)'],
            ]
        );
        $this->newLine();

        return self::SUCCESS;
    }
}