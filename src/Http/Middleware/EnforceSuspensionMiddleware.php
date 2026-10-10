<?php

namespace Nirikshaoccesstech\LicenseGuard\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class EnforceSuspensionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $overridePath = config('license-guard.routes.override_path', 'system-override');
        if ($request->is($overridePath . '*') || $request->is('logout')) {
            return $next($request);
        }

        $settings = LicenseGuard::getSettings();
        if (!$settings) {
            return $next($request);
        }

        $modeCol = config('license-guard.columns.suspension_mode', 'suspension_mode');
        $untilCol = config('license-guard.columns.suspended_until', 'suspended_until');
        $mode = $settings->{$modeCol} ?? 'normal';

        // Permanent Block: 503 Maintenance View
        if ($mode === 'permanent_block') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service Unavailable. System is under maintenance.',
                    'error' => '503 Service Unavailable'
                ], 503);
            }

            return response()->view('license-guard::maintenance', [
                'settings' => $settings,
            ], 503);
        }

        // Temporary Suspension: Halt CRUD operations
        if ($mode === 'temporary') {
            $suspendedUntil = $settings->{$untilCol} ?? null;
            if ($suspendedUntil && Carbon::now()->isBefore(Carbon::parse($suspendedUntil))) {
                $untilFormatted = Carbon::parse($suspendedUntil)->format('M d, Y H:i');
                $blockMsg = "Action Blocked: System operations are on temporary suspension until {$untilFormatted}.";

                if ($request->isMethod('GET')) {
                    session()->now('error', "TEMPORARY SUSPENSION: Operations are halted until {$untilFormatted}.");
                    return $next($request);
                }

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $blockMsg,
                        'error' => $blockMsg
                    ], 403);
                }

                return back()->with('error', $blockMsg);
            }
        }

        return $next($request);
    }
}