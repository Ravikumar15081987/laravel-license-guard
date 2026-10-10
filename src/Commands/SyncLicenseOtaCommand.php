<?php

namespace Nirikshaoccesstech\LicenseGuard\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class SyncLicenseOtaCommand extends Command
{
    protected $signature = 'license:sync';
    protected $description = 'Silently checks master server for OTA license renewals';

    public function handle(): int
    {
        $settings = LicenseGuard::getSettings();
        $codeCol = config('license-guard.columns.company_code', 'company_code');
        $keyCol = config('license-guard.columns.license_key', 'license_key');

        if (!$settings || empty($settings->{$codeCol})) {
            return self::FAILURE;
        }

        $url = config('license-guard.master_server.url');
        $timeout = config('license-guard.master_server.timeout', 10);

        try {
            $response = Http::timeout($timeout)->post($url . '/api/ota-sync', [
                'company_code' => $settings->{$codeCol},
                'current_key' => $settings->{$keyCol} ?? null,
            ]);

            if ($response->successful() && $response->json('has_update')) {
                $newKey = $response->json('new_license_key');
                LicenseGuard::verifyAndApplyLicense($newKey);
                $this->info('OTA Sync Successful: License extended in the background.');
            }
        } catch (\Exception $e) {
            $this->error('OTA Sync Failed. Falling back to offline verification.');
        }

        return self::SUCCESS;
    }
}