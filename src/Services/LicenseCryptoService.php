<?php

namespace Nirikshaoccesstech\LicenseGuard\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nirikshaoccesstech\LicenseGuard\Events\ClockTamperDetected;
use Nirikshaoccesstech\LicenseGuard\Events\LicenseRenewed;

class LicenseCryptoService
{
    protected ?array $cachedStatus = null;

    public function getSettings()
    {
        $table = config('license-guard.settings_table', 'company_settings');
        return DB::table($table)->first();
    }

    public function getSigningKey(): string
    {
        return config('license-guard.secret_key') ?: (string) config('app.key');
    }

    /**
     * Check if system clock has been rolled back.
     */
    public function checkClockIntegrity(): bool
    {
        if (!config('license-guard.enforce_clock_integrity', true)) {
            return true;
        }

        $toleranceSeconds = (int) config('license-guard.clock_tolerance_minutes', 60) * 60;
        $now = Carbon::now();

        // 1. Check cache timestamp
        $lastCheckedCache = Cache::get('license_guard_last_checked');
        if ($lastCheckedCache) {
            $lastCheckedTime = Carbon::parse($lastCheckedCache);
            if ($now->timestamp < ($lastCheckedTime->timestamp - $toleranceSeconds)) {
                $this->triggerTamperEvent($now, $lastCheckedTime);
                return false;
            }
        }

        // 2. Check offline json file snapshot
        $jsonPath = config('license-guard.heartbeat_path', storage_path('app/secure/license.json'));
        if (file_exists($jsonPath)) {
            $data = json_decode(@file_get_contents($jsonPath), true);
            if (!empty($data['last_checked'])) {
                $lastCheckedFile = Carbon::parse($data['last_checked']);
                if ($now->timestamp < ($lastCheckedFile->timestamp - $toleranceSeconds)) {
                    $this->triggerTamperEvent($now, $lastCheckedFile);
                    return false;
                }
            }
        }

        // Update heartbeat
        $this->recordHeartbeat($now);
        return true;
    }

    protected function triggerTamperEvent(Carbon $now, Carbon $lastRecorded): void
    {
        if (config('license-guard.dispatch_events', true)) {
            $settings = $this->getSettings();
            $codeCol = config('license-guard.columns.company_code', 'company_code');
            $companyCode = $settings->{$codeCol} ?? 'UNKNOWN';
            event(new ClockTamperDetected($companyCode, $now, $lastRecorded));
        }
    }

    public function recordHeartbeat(?Carbon $time = null): void
    {
        $time = $time ?: Carbon::now();
        Cache::put('license_guard_last_checked', $time->toIso8601String(), 86400 * 30);

        $jsonPath = config('license-guard.heartbeat_path', storage_path('app/secure/license.json'));
        if (file_exists($jsonPath)) {
            $data = json_decode(@file_get_contents($jsonPath), true) ?: [];
            $data['last_checked'] = $time->toIso8601String();
            @file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Full diagnostic license state report.
     */
    public function status(): array
    {
        if ($this->cachedStatus !== null) {
            return $this->cachedStatus;
        }

        $settings = $this->getSettings();
        $codeCol = config('license-guard.columns.company_code', 'company_code');
        $expiresCol = config('license-guard.columns.license_expires_at', 'license_expires_at');
        $tierCol = config('license-guard.columns.license_tier', 'license_tier');
        $modeCol = config('license-guard.columns.suspension_mode', 'suspension_mode');
        $untilCol = config('license-guard.columns.suspended_until', 'suspended_until');

        $companyCode = $settings->{$codeCol} ?? 'UNCONFIGURED';
        $tier = strtoupper(substr($settings->{$tierCol} ?? 'B', 0, 1));
        $suspensionMode = $settings->{$modeCol} ?? 'normal';
        $suspendedUntil = !empty($settings->{$untilCol}) ? Carbon::parse($settings->{$untilCol}) : null;

        $expiresAt = !empty($settings->{$expiresCol}) ? Carbon::parse($settings->{$expiresCol}) : null;
        $now = Carbon::now();

        $clockValid = $this->checkClockIntegrity();
        if (!$clockValid) {
            return $this->cachedStatus = [
                'status' => 'CLOCK_TAMPERED',
                'is_valid' => false,
                'is_in_grace_period' => false,
                'is_expired' => true,
                'is_suspended' => false,
                'company_code' => $companyCode,
                'tier' => $tier,
                'tier_name' => config("license-guard.tiers.{$tier}.name", 'Standard'),
                'expires_at' => $expiresAt,
                'days_remaining' => 0,
                'grace_days_remaining' => 0,
                'grace_ends_at' => null,
                'message' => 'System clock rollback detected. Cryptographic verification locked.',
            ];
        }

        // Suspension checks
        if ($suspensionMode === 'permanent_block') {
            return $this->cachedStatus = [
                'status' => 'SUSPENDED_BLOCK',
                'is_valid' => false,
                'is_in_grace_period' => false,
                'is_expired' => false,
                'is_suspended' => true,
                'company_code' => $companyCode,
                'tier' => $tier,
                'tier_name' => config("license-guard.tiers.{$tier}.name", 'Standard'),
                'expires_at' => $expiresAt,
                'days_remaining' => 0,
                'grace_days_remaining' => 0,
                'grace_ends_at' => null,
                'message' => 'Application permanently locked under maintenance mode.',
            ];
        }

        if ($suspensionMode === 'temporary' && $suspendedUntil && $now->isBefore($suspendedUntil)) {
            return $this->cachedStatus = [
                'status' => 'SUSPENDED_TEMP',
                'is_valid' => false,
                'is_in_grace_period' => false,
                'is_expired' => false,
                'is_suspended' => true,
                'suspended_until' => $suspendedUntil,
                'company_code' => $companyCode,
                'tier' => $tier,
                'tier_name' => config("license-guard.tiers.{$tier}.name", 'Standard'),
                'expires_at' => $expiresAt,
                'days_remaining' => 0,
                'grace_days_remaining' => 0,
                'grace_ends_at' => null,
                'message' => "Application operations temporarily suspended until {$suspendedUntil->format('M d, Y H:i')}.",
            ];
        }

        if (!$expiresAt) {
            return $this->cachedStatus = [
                'status' => 'UNLICENSED',
                'is_valid' => false,
                'is_in_grace_period' => false,
                'is_expired' => true,
                'is_suspended' => false,
                'company_code' => $companyCode,
                'tier' => $tier,
                'tier_name' => 'Unlicensed',
                'expires_at' => null,
                'days_remaining' => 0,
                'grace_days_remaining' => 0,
                'grace_ends_at' => null,
                'message' => 'No active license key found.',
            ];
        }

        $graceDays = (int) config('license-guard.grace_period_days', 7);
        $graceEndsAt = $expiresAt->copy()->addDays($graceDays);

        if ($now->isBefore($expiresAt)) {
            $daysRemaining = (int) floor($now->diffInDays($expiresAt, false));
            return $this->cachedStatus = [
                'status' => 'ACTIVE',
                'is_valid' => true,
                'is_in_grace_period' => false,
                'is_expired' => false,
                'is_suspended' => false,
                'company_code' => $companyCode,
                'tier' => $tier,
                'tier_name' => config("license-guard.tiers.{$tier}.name", 'Standard'),
                'expires_at' => $expiresAt,
                'days_remaining' => $daysRemaining,
                'grace_days_remaining' => 0,
                'grace_ends_at' => null,
                'message' => "License is active ({$daysRemaining} days remaining).",
            ];
        }

        if ($now->isBefore($graceEndsAt)) {
            $graceDaysRemaining = (int) ceil($now->diffInDays($graceEndsAt, false));
            return $this->cachedStatus = [
                'status' => 'GRACE_PERIOD',
                'is_valid' => true, // Still permits execution during grace period
                'is_in_grace_period' => true,
                'is_expired' => false,
                'is_suspended' => false,
                'company_code' => $companyCode,
                'tier' => $tier,
                'tier_name' => config("license-guard.tiers.{$tier}.name", 'Standard'),
                'expires_at' => $expiresAt,
                'days_remaining' => 0,
                'grace_days_remaining' => $graceDaysRemaining,
                'grace_ends_at' => $graceEndsAt,
                'message' => "License expired on {$expiresAt->format('M d, Y')}. In grace period ({$graceDaysRemaining} days remaining).",
            ];
        }

        $daysAgo = (int) floor($expiresAt->diffInDays($now));
        return $this->cachedStatus = [
            'status' => 'EXPIRED',
            'is_valid' => false,
            'is_in_grace_period' => false,
            'is_expired' => true,
            'is_suspended' => false,
            'company_code' => $companyCode,
            'tier' => $tier,
            'tier_name' => config("license-guard.tiers.{$tier}.name", 'Standard'),
            'expires_at' => $expiresAt,
            'days_remaining' => 0,
            'grace_days_remaining' => 0,
            'grace_ends_at' => $graceEndsAt,
            'message' => "License expired {$daysAgo} days ago on {$expiresAt->format('M d, Y')}. Read-only lock active.",
        ];
    }

    /**
     * Check if current license matches or exceeds required tier level.
     */
    public function hasTier(string $requiredTier): bool
    {
        $status = $this->status();
        if (!$status['is_valid']) {
            return false;
        }

        $currentTier = $status['tier'];
        $currentLevel = config("license-guard.tiers.{$currentTier}.level", 1);
        $reqLevel = config("license-guard.tiers.{$requiredTier}.level", 1);

        return $currentLevel >= $reqLevel;
    }

    /**
     * Check if a specific feature entitlement is enabled.
     */
    public function allows(string $feature): bool
    {
        $status = $this->status();
        if (!$status['is_valid']) {
            return false;
        }

        $tier = $status['tier'];
        $features = config("license-guard.tiers.{$tier}.features", []);

        if (in_array('*', $features)) {
            return true;
        }

        return in_array($feature, $features);
    }

    /**
     * Verify and atomically apply a 16-character license key.
     */
    public function verifyAndApplyLicense(string $key): array
    {
        $cleanKey = strtoupper(str_replace(['-', ' '], '', trim($key)));

        if (strlen($cleanKey) !== 16) {
            throw new \Exception("Invalid key length. Expected 16 characters, got " . strlen($cleanKey));
        }

        $settings = $this->getSettings();
        if (!$settings) {
            throw new \Exception("No settings record found in the database.");
        }

        $companyCodeCol = config('license-guard.columns.company_code', 'company_code');
        $companyCode = strtoupper(trim($settings->{$companyCodeCol} ?? ''));

        if (empty($companyCode)) {
            throw new \Exception("No company code configured in settings.");
        }

        // Single-Use Check 1: Currently active key
        $keyCol = config('license-guard.columns.license_key', 'license_key');
        $rawActiveKey = $settings->{$keyCol} ?? null;
        if ($rawActiveKey) {
            try { $rawActiveKey = decrypt($rawActiveKey); } catch (\Exception $e) {}
            if (strtoupper(str_replace(['-', ' '], '', $rawActiveKey)) === $cleanKey) {
                throw new \Exception("This license key is currently active in the system and cannot be applied again.");
            }
        }

        // Single-Use Check 2: Database ledger
        $keyHash = hash('sha256', $cleanKey);
        if (DB::table('applied_crm_licenses')->where('license_hash', $keyHash)->exists()) {
            throw new \Exception("This license key has already been used and cannot be applied again.");
        }

        // Single-Use Check 3: JSON file history
        $jsonPath = config('license-guard.heartbeat_path', storage_path('app/secure/license.json'));
        $localData = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
        $usedHashes = (array) ($localData['used_key_hashes'] ?? []);

        if (in_array($keyHash, $usedHashes)) {
            throw new \Exception("This license key has already been used according to local security logs.");
        }

        // Extract components & verify signature using dedicated secret
        $daysStr = substr($cleanKey, 0, 4);
        $tierChar = substr($cleanKey, 4, 1);
        $salt = substr($cleanKey, 5, 3);
        $providedSignature = substr($cleanKey, 8, 8);

        $signingKey = $this->getSigningKey();
        $expectedSignature = substr(strtoupper(hash_hmac('sha256', $daysStr . $tierChar . $salt . $companyCode, $signingKey)), 0, 8);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new \Exception("Signature mismatch. This key belongs to a different company or is forged.");
        }

        // Cumulative date calculation
        $daysToAdd = (int) $daysStr;
        $expiresCol = config('license-guard.columns.license_expires_at', 'license_expires_at');
        $currentExpiry = !empty($settings->{$expiresCol}) ? Carbon::parse($settings->{$expiresCol}) : null;
        $baseDate = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : Carbon::now();
        $expirationDate = $baseDate->copy()->addDays($daysToAdd);

        $table = config('license-guard.settings_table', 'company_settings');
        $tierCol = config('license-guard.columns.license_tier', 'license_tier');

        DB::table($table)->where('id', $settings->id)->update([
            $keyCol => encrypt($key),
            $tierCol => $tierChar,
            $expiresCol => $expirationDate->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now(),
        ]);

        // Record in applied_crm_licenses ledger
        $appliedBy = auth()->id() ?? null;
        DB::table('applied_crm_licenses')->insert([
            'license_hash' => $keyHash,
            'company_code' => $companyCode,
            'days_granted' => $daysToAdd,
            'tier' => $tierChar,
            'applied_by' => $appliedBy,
            'applied_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Cache::flush();
        $this->cachedStatus = null;

        // Update local JSON snapshot
        $secureDir = dirname($jsonPath);
        if (!file_exists($secureDir)) {
            mkdir($secureDir, 0755, true);
        }

        $usedHashes[] = $keyHash;
        $usedHashes = array_values(array_unique($usedHashes));

        $jsonPayload = [
            'company_code' => $companyCode,
            'license_key' => $key,
            'tier' => $tierChar,
            'expires_at' => $expirationDate->toIso8601String(),
            'last_checked' => Carbon::now()->toIso8601String(),
            'used_key_hashes' => $usedHashes,
            'token_sig' => hash_hmac('sha256', $key . $tierChar . $companyCode . $expirationDate->toIso8601String(), $signingKey),
        ];

        file_put_contents($jsonPath, json_encode($jsonPayload, JSON_PRETTY_PRINT));

        // Dispatch LicenseRenewed event
        if (config('license-guard.dispatch_events', true)) {
            event(new LicenseRenewed($companyCode, $daysToAdd, $expirationDate, $tierChar, $appliedBy));
        }

        return [
            'days' => $daysToAdd,
            'tier' => $tierChar,
            'expires_at' => $expirationDate,
        ];
    }
}