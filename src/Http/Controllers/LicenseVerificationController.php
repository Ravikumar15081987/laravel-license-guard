<?php

namespace Nirikshaoccesstech\LicenseGuard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;

class LicenseVerificationController extends Controller
{
    public function show()
    {
        $settings = LicenseGuard::getSettings();
        return view('license-guard::locked', compact('settings'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        try {
            $result = LicenseGuard::verifyAndApplyLicense($request->license_key);
            $msg = "License verified successfully! Extended for {$result['days']} days until " . $result['expires_at']->format('M d, Y') . ".";
            
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Verification Failed: ' . $e->getMessage());
        }
    }
}