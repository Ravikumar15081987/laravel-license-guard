<?php

namespace Nirikshaoccesstech\LicenseGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class CheckLicenseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $verificationPath = config('license-guard.routes.verification_path', 'admin/license-verification');
        $overridePath = config('license-guard.routes.override_path', 'system-override');

        // Allow verification and recovery endpoints
        if ($request->is($verificationPath . '*') || $request->is($overridePath . '*') || $request->is('logout')) {
            return $next($request);
        }

        // Domain / Hostname whitelist verification
        $allowedDomains = config('license-guard.allowed_domains', []);
        if (!empty($allowedDomains)) {
            $host = $request->getHost();
            if (!in_array($host, $allowedDomains) && !in_array('localhost', $allowedDomains) && $host !== '127.0.0.1') {
                $hostMsg = "License Security Alert: Hostname '{$host}' is unauthorized for this licensed instance.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'error' => $hostMsg], 403);
                }
                abort(403, $hostMsg);
            }
        }

        $status = LicenseGuard::status();

        // Clock Tamper Lock
        if ($status['status'] === 'CLOCK_TAMPERED') {
            $tamperMsg = 'System Security Hold: System clock tampering was detected. Please restore the system time and re-verify.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $tamperMsg], 403);
            }
            if ($request->isMethod('GET')) {
                session()->now('license_error', $tamperMsg);
            } else {
                return back()->with('error', $tamperMsg);
            }
        }

        // Grace Period (Active execution permitted with non-intrusive warning)
        if ($status['is_in_grace_period']) {
            $graceMsg = "Your CRM license expired on {$status['expires_at']->format('M d, Y')}. You are currently in a grace period ({$status['grace_days_remaining']} days remaining). Please renew promptly.";
            if ($request->isMethod('GET')) {
                session()->now('license_warning', $graceMsg);
            }
            return $next($request);
        }

        // Hard Expiry (Read-only mode: block write mutations)
        if ($status['is_expired']) {
            $expireMsg = 'Action Denied: Your CRM license has expired. The system is in Read-Only mode. Please apply a new license key to restore full write-access.';

            if ($request->isMethod('GET')) {
                session()->now('license_error', 'SYSTEM READ-ONLY: Your CRM license has expired. Data modifications are disabled.');
                return $next($request);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $expireMsg,
                    'error' => $expireMsg
                ], 403);
            }

            return back()->with('error', $expireMsg);
        }

        return $next($request);
    }
}