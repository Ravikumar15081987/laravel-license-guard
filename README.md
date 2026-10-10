# Laravel License Guard

Enterprise cryptographic licensing, single-use key enforcement, cumulative renewals, anti-tampering, grace periods, and emergency vendor override (kill-switch) for Laravel applications.

Developed by **Niriksha Occesstech**.

---

## Architecture Overview

This package is designed exclusively for **Client Projects**.

- **No Code Generation In Client Package**: Code generation logic is strictly excluded from this package. Only your company (**Niriksha Occesstech**) generates official license keys and emergency action codes from your internal tools.
- **Client Application Role**: When this package is installed into a client project, it strictly handles **cryptographic verification, single-use ledger enforcement, grace period management, activation, and read-only lockdown**.

---

## How It Works

```
+------------------------------------------+
|       NIRIKSHA OCCESSTECH (Vendor)       |
|  Generates official keys & action codes  |
+------------------------------------------+
                     |
                     | Issues official key (e.g. 0365-ER0C-89F7-FBB1)
                     v
+------------------------------------------+
|          CLIENT LARAVEL PROJECT          |
|  Package: laravel-license-guard          |
|                                          |
|  1. php artisan license:activate {key}   |
|     OR web at /admin/license-verification|
|  2. Cryptographic signature verified     |
|  3. Atomic single-use ledger updated     |
|  4. Full application access unlocked!    |
+------------------------------------------+
```

---

## Installation in Client Projects

### Step 1: Add Package via Composer
In the client application's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "../sandbox/laravel-license-guard"
    }
],
"require": {
    "nirikshaoccesstech/laravel-license-guard": "@dev"
}
```

Run:
```bash
composer update nirikshaoccesstech/laravel-license-guard
```

---

### Step 2: One-Step Package Installation
Run the installer command in the client project:

```bash
php artisan license:install
```

*(This automatically publishes `config/license-guard.php` and executes the database ledger migrations).*

---

### Step 3: Register Protection Middleware
In `bootstrap/app.php` (Laravel 11/12):

```php
use Nirikshaoccesstech\LicenseGuard\Http\Middleware\CheckLicenseMiddleware;
use Nirikshaoccesstech\LicenseGuard\Http\Middleware\EnforceSuspensionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnforceSuspensionMiddleware::class,
            CheckLicenseMiddleware::class,
        ]);
    })
    ->create();
```

---

## Activating the Client Project

Once Niriksha Occesstech issues an official license key for the client, activate the application using either method:

### Option A: Via Artisan CLI
```bash
php artisan license:activate {key}

# Example:
php artisan license:activate 0365-ER0C-89F7-FBB1
```

### Option B: Via Web Interface
Navigate in the browser to:
```
http://client-domain.test/admin/license-verification
```
Enter the official license key and submit to unlock full access.

---

## Client Artisan Commands

```bash
# 1. Setup & publish configuration:
php artisan license:install

# 2. Activate or renew using an official key:
php artisan license:activate {key}

# 3. Comprehensive diagnostic status & health inspection:
php artisan license:status

# 4. Daily automated heartbeat check & milestone notifier:
php artisan license:heartbeat

# 5. Over-the-air (OTA) license renewal polling:
php artisan license:sync
```

*(Tip: Schedule `php artisan license:heartbeat` daily in `routes/console.php`)*:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('license:heartbeat')->daily();
```

---

## Emergency Override Portal (`/system-override`)

In emergency circumstances (e.g., unpaid invoices or contract termination), Niriksha Occesstech can provide an emergency action code to be submitted at:

```
http://client-domain.test/system-override
```

- **Temporary Suspension** (`TEMP-...`): Halts all CRUD write operations for X days.
- **Permanent Block** (`PERM-KILL-...`): Locks the application in HTTP 503 Under Maintenance mode.
- **Instant Restore** (`REST-FULL-...`): Clears holds and restores standard operation.

---

## Route Middleware & Feature Gates

Protect specific premium routes or features with auto-registered aliases:

```php
// Require minimum tier (B, P, E)
Route::middleware(['auth', 'license.tier:E'])->group(function () {
    Route::get('/admin/audit-logs', [AuditController::class, 'index']);
});

// Require specific feature entitlement
Route::middleware(['auth', 'license.feature:api_access'])->group(function () {
    Route::get('/api/v1/export', [ExportController::class, 'export']);
});
```

---

## Blade Directives & Drop-in Alert Ribbon

### Blade Directives
```blade
{{-- Check if license is active --}}
@licenseActive
    <span class="badge bg-success">Active Subscription</span>
@endlicenseActive

{{-- Check if in grace period --}}
@licenseInGracePeriod
    <span class="badge bg-warning">In Grace Period</span>
@endlicenseInGracePeriod

{{-- Check tier level --}}
@licenseTier('E')
    <a href="/admin/advanced-metrics" class="btn btn-primary">Enterprise Metrics</a>
@endlicenseTier

{{-- Check specific feature entitlement --}}
@licenseFeature('custom_fields')
    <button class="btn btn-outline-secondary">Add Custom Field</button>
@endlicenseFeature
```

### Drop-in Alert Ribbon
Include the dynamic status banner in your main layout (`layouts/app.blade.php`):

```blade
@include('license-guard::partials.license-alert')
```

---

## License
Proprietary software engineered by Niriksha Occesstech. Distributed under MIT License for authorized deployments.