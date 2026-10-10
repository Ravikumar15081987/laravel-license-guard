<?php

use Illuminate\Support\Facades\Route;
use Nirikshaoccesstech\LicenseGuard\Http\Controllers\LicenseVerificationController;
use Nirikshaoccesstech\LicenseGuard\Http\Controllers\SystemOverrideController;

$verificationPath = config('license-guard.routes.verification_path', 'admin/license-verification');
$overridePath = config('license-guard.routes.override_path', 'system-override');
$middleware = config('license-guard.routes.middleware', ['web']);

Route::middleware($middleware)->group(function () use ($verificationPath, $overridePath) {
    // License Lockout & Verification Routes
    Route::get($verificationPath, [LicenseVerificationController::class, 'show'])->name('license-guard.show');
    Route::post($verificationPath, [LicenseVerificationController::class, 'verify'])->name('license-guard.verify');

    // Vendor Emergency Override & Kill-Switch Routes
    Route::prefix($overridePath)->name('license-guard.override.')->group(function () {
        Route::get('/', [SystemOverrideController::class, 'show'])->name('show');
        Route::post('/execute', [SystemOverrideController::class, 'execute'])->name('execute');
    });
});