<?php

namespace Nirikshaoccesstech\LicenseGuard\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Nirikshaoccesstech\LicenseGuard\Events\LicenseExpired;
use Nirikshaoccesstech\LicenseGuard\Events\LicenseExpiringSoon;
use Nirikshaoccesstech\LicenseGuard\Events\LicenseGracePeriodEntered;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class HeartbeatLicenseCommand extends Command
{
    protected $signature = 'license:heartbeat';
    protected $description = 'Scheduled cryptographic heartbeat check, clock verification, and milestone notification dispatcher';

    public function handle(): int
    {
        $status = LicenseGuard::status();
        $this->info("Running License Guard Heartbeat check...");

        if ($status['status'] === 'CLOCK_TAMPERED') {
            $this->error("CRITICAL: Clock tampering detected. Verification locked.");
            return self::FAILURE;
        }

        if ($status['status'] === 'ACTIVE') {
            $days = $status['days_remaining'];
            $this->info("License is ACTIVE. {$days} days remaining [Expires: {$status['expires_at']->format('Y-m-d')}].");

            // Milestone notifications (e.g., 30, 14, 7, 3, 1 day)
            $milestones = [30, 14, 7, 3, 1];
            if (in_array($days, $milestones) && config('license-guard.dispatch_events', true)) {
                event(new LicenseExpiringSoon(
                    $status['company_code'],
                    $days,
                    $status['expires_at'],
                    $status['tier']
                ));
                $this->warn("Milestone reached ({$days} days). LicenseExpiringSoon event dispatched.");
            }
        } elseif ($status['status'] === 'GRACE_PERIOD') {
            $this->warn("License in GRACE PERIOD. {$status['grace_days_remaining']} grace days remaining.");
            if (config('license-guard.dispatch_events', true)) {
                event(new LicenseGracePeriodEntered(
                    $status['company_code'],
                    $status['grace_days_remaining'],
                    $status['expires_at'],
                    $status['grace_ends_at']
                ));
            }
        } elseif ($status['status'] === 'EXPIRED') {
            $this->error("License is EXPIRED. Read-only lock active.");
            if (config('license-guard.dispatch_events', true)) {
                event(new LicenseExpired(
                    $status['company_code'],
                    $status['expires_at'],
                    $status['tier']
                ));
            }
        }

        LicenseGuard::recordHeartbeat();
        $this->info("Heartbeat recorded successfully.");

        return self::SUCCESS;
    }
}