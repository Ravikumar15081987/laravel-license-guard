<?php

namespace Nirikshaoccesstech\LicenseGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class CheckLicenseTierMiddleware
{
    public function handle(Request $request, Closure $next, string $tier = 'E')
    {
        if (!LicenseGuard::hasTier($tier)) {
            $tierName = config("license-guard.tiers.{$tier}.name", $tier);
            $msg = "Upgrade Required: This module requires a [{$tierName}] tier license.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $msg,
                    'required_tier' => $tier
                ], 403);
            }

            return redirect()->route(config('license-guard.routes.verification_path', 'license-guard.show'))
                ->with('error', $msg);
        }

        return $next($request);
    }
}