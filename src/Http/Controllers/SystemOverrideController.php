<?php

namespace Nirikshaoccesstech\LicenseGuard\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Nirikshaoccesstech\LicenseGuard\Events\SystemRestored;
use Nirikshaoccesstech\LicenseGuard\Events\SystemSuspended;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class SystemOverrideController extends Controller
{
    public function show()
    {
        $settings = LicenseGuard::getSettings();
        return view('license-guard::override', compact('settings'));
    }

    public function execute(Request $request)
    {
        $request->validate([
            'override_code' => 'required|string',
        ]);

        $rawCode = strtoupper(trim($request->override_code));
        $settings = LicenseGuard::getSettings();

        if (!$settings) {
            return back()->with('error', 'System Error: No settings found.');
        }

        $companyCodeCol = config('license-guard.columns.company_code', 'company_code');
        $currentCompanyCode = strtoupper(trim($settings->{$companyCodeCol} ?? ''));
        $table = config('license-guard.settings_table', 'company_settings');
        $modeCol = config('license-guard.columns.suspension_mode', 'suspension_mode');
        $untilCol = config('license-guard.columns.suspended_until', 'suspended_until');
        $reasonCol = config('license-guard.columns.suspension_reason', 'suspension_reason');
        $signingKey = LicenseGuard::getSigningKey();

        try {
            $parts = explode('-', $rawCode);

            // Temporary Suspension
            if (str_starts_with($rawCode, 'TEMP-')) {
                if (count($parts) === 5) {
                    [$prefix, $daysSegment, $codeCompany, $salt, $providedSig] = $parts;
                } elseif (count($parts) === 4) {
                    [$prefix, $daysSegment, $salt, $providedSig] = $parts;
                    $codeCompany = $currentCompanyCode;
                } else {
                    throw new \Exception("Invalid code format.");
                }

                if ($codeCompany !== $currentCompanyCode) {
                    throw new \Exception("Company Mismatch: This code was issued for '{$codeCompany}', but this instance is '{$currentCompanyCode}'.");
                }

                $days = (int) str_replace('D', '', $daysSegment);
                $rawPayload = (count($parts) === 5)
                    ? "TEMP" . $daysSegment . $codeCompany . $salt
                    : "TEMP" . $daysSegment . $salt . $codeCompany;

                $expectedSig = substr(strtoupper(hash_hmac('sha256', $rawPayload, $signingKey)), 0, 6);
                if (!hash_equals($expectedSig, $providedSig)) {
                    throw new \Exception("Signature mismatch. Invalid or forged code.");
                }

                $until = Carbon::now()->addDays($days);
                $reason = "Billing Hold: CRUD operations halted for {$days} days.";
                DB::table($table)->where('id', $settings->id)->update([
                    $modeCol => 'temporary',
                    $untilCol => $until->format('Y-m-d H:i:s'),
                    $reasonCol => $reason,
                    'updated_at' => Carbon::now(),
                ]);

                Cache::flush();

                if (config('license-guard.dispatch_events', true)) {
                    event(new SystemSuspended($currentCompanyCode, 'temporary', $reason, $until));
                }

                return back()->with('success', "TEMPORARY SUSPENSION ACTIVATED: CRUD write operations halted for {$days} days until " . $until->format('M d, Y H:i') . ".");
            }

            // Permanent Block (Under Maintenance)
            if (str_starts_with($rawCode, 'PERM-KILL-')) {
                if (count($parts) === 5) {
                    [$p1, $p2, $codeCompany, $salt, $providedSig] = $parts;
                } elseif (count($parts) === 4) {
                    [$p1, $p2, $salt, $providedSig] = $parts;
                    $codeCompany = $currentCompanyCode;
                } else {
                    throw new \Exception("Invalid code format.");
                }

                if ($codeCompany !== $currentCompanyCode) {
                    throw new \Exception("Company Mismatch: Code belongs to '{$codeCompany}', but this instance is '{$currentCompanyCode}'.");
                }

                $rawPayload = (count($parts) === 5)
                    ? "PERMKILL" . $codeCompany . $salt
                    : "PERMKILL" . $salt . $codeCompany;

                $expectedSig = substr(strtoupper(hash_hmac('sha256', $rawPayload, $signingKey)), 0, 6);
                if (!hash_equals($expectedSig, $providedSig)) {
                    throw new \Exception("Signature mismatch. Invalid code.");
                }

                $reason = "Account Terminated: Application permanently locked due to non-payment.";
                DB::table($table)->where('id', $settings->id)->update([
                    $modeCol => 'permanent_block',
                    $untilCol => null,
                    $reasonCol => $reason,
                    'updated_at' => Carbon::now(),
                ]);

                Cache::flush();

                if (config('license-guard.dispatch_events', true)) {
                    event(new SystemSuspended($currentCompanyCode, 'permanent_block', $reason, null));
                }

                return back()->with('success', "PERMANENT BLOCK ACTIVATED: The entire site is now locked in Under Maintenance mode.");
            }

            // Restore System
            if (str_starts_with($rawCode, 'REST-FULL-')) {
                if (count($parts) === 5) {
                    [$p1, $p2, $codeCompany, $salt, $providedSig] = $parts;
                } elseif (count($parts) === 4) {
                    [$p1, $p2, $salt, $providedSig] = $parts;
                    $codeCompany = $currentCompanyCode;
                } else {
                    throw new \Exception("Invalid code format.");
                }

                if ($codeCompany !== $currentCompanyCode) {
                    throw new \Exception("Company Mismatch: Code belongs to '{$codeCompany}', but this instance is '{$currentCompanyCode}'.");
                }

                $rawPayload = (count($parts) === 5)
                    ? "RESTFULL" . $codeCompany . $salt
                    : "RESTFULL" . $salt . $codeCompany;

                $expectedSig = substr(strtoupper(hash_hmac('sha256', $rawPayload, $signingKey)), 0, 6);
                if (!hash_equals($expectedSig, $providedSig)) {
                    throw new \Exception("Signature mismatch. Invalid code.");
                }

                DB::table($table)->where('id', $settings->id)->update([
                    $modeCol => 'normal',
                    $untilCol => null,
                    $reasonCol => null,
                    'updated_at' => Carbon::now(),
                ]);

                Cache::flush();

                if (config('license-guard.dispatch_events', true)) {
                    event(new SystemRestored($currentCompanyCode));
                }

                return back()->with('success', "SYSTEM RESTORED: All suspensions cleared. Normal operations resumed.");
            }

            throw new \Exception("Unrecognized code structure.");
        } catch (\Exception $e) {
            return back()->with('error', 'Execution Failed: ' . $e->getMessage());
        }
    }
}