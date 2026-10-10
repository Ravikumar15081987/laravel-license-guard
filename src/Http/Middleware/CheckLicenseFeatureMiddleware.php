<?php

namespace Nirikshaoccesstech\LicenseGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class CheckLicenseFeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        if (!LicenseGuard::allows($feature)) {
            $featureLabel = ucwords(str_replace('_', ' ', $feature));
            $msg = "Feature Not Entitled: [{$featureLabel}] is not active in your current license.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $msg,
                    'feature' => $feature
                ], 403);
            }

            return redirect()->back()->with('error', $msg);
        }

        return $next($request);
    }
}