<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dedicated Secret / Cryptographic Master Key
    |--------------------------------------------------------------------------
    | Dedicated signing secret used for HMAC validation. If null, it gracefully
    | falls back to config('app.key'). Setting a dedicated secret prevents APP_KEY
    | rotation from invalidating existing deployed client licenses.
    */
    'secret_key' => env('LICENSE_GUARD_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Settings Model & Table Mapping
    |--------------------------------------------------------------------------
    | Configure the model and columns where your application stores its company
    | or tenant configuration and license keys.
    */
    'settings_model' => \App\Models\CompanySetting::class,
    'settings_table' => 'company_settings',

    'columns' => [
        'company_code' => 'company_code',
        'license_key' => 'license_key',
        'license_tier' => 'license_tier',
        'license_expires_at' => 'license_expires_at',
        'suspension_mode' => 'suspension_mode',
        'suspended_until' => 'suspended_until',
        'suspension_reason' => 'suspension_reason',
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period Configuration
    |--------------------------------------------------------------------------
    | Number of days granted after expiration before hard lock/read-only mode.
    | During this period, the system displays warning banners to administrators
    | while permitting daily business operations to proceed uninterrupted.
    */
    'grace_period_days' => (int) env('LICENSE_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Anti-Tampering & Clock Rollback Integrity
    |--------------------------------------------------------------------------
    | Detects if the host system time was rolled back to artificially extend
    | license expiration dates.
    */
    'enforce_clock_integrity' => env('LICENSE_CLOCK_CHECK', true),
    'clock_tolerance_minutes' => 60, // allow 1-hour NTP skew

    /*
    |--------------------------------------------------------------------------
    | Domain & Hostname Binding (Optional)
    |--------------------------------------------------------------------------
    | List of authorized domains for this deployment (comma-separated in .env).
    | When populated, requests originating from non-whitelisted hostnames will be
    | rejected to prevent database cloning into unauthorized instances.
    */
    'allowed_domains' => env('LICENSE_ALLOWED_DOMAINS') 
        ? array_map('trim', explode(',', env('LICENSE_ALLOWED_DOMAINS'))) 
        : [],

    /*
    |--------------------------------------------------------------------------
    | Tier Hierarchy & Feature Entitlements
    |--------------------------------------------------------------------------
    | Define tier hierarchy and what features are enabled per license tier.
    */
    'tiers' => [
        'B' => [
            'name' => 'Basic',
            'level' => 1,
            'features' => ['core_crm', 'basic_reports'],
        ],
        'P' => [
            'name' => 'Professional',
            'level' => 2,
            'features' => ['core_crm', 'basic_reports', 'advanced_exports', 'api_access', 'custom_fields'],
        ],
        'E' => [
            'name' => 'Enterprise',
            'level' => 3,
            'features' => ['*'], // All features enabled
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Notifications
    |--------------------------------------------------------------------------
    | Automatically dispatch Laravel events on status transitions and renewals.
    */
    'dispatch_events' => true,

    /*
    |--------------------------------------------------------------------------
    | Secure Heartbeat Storage Path
    |--------------------------------------------------------------------------
    | Path to the offline JSON heartbeat snapshot file.
    */
    'heartbeat_path' => storage_path('app/secure/license.json'),

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    | URLs used for the lock screen, verification, and emergency override portal.
    */
    'routes' => [
        'enabled' => true,
        'verification_path' => 'admin/license-verification',
        'override_path' => 'system-override',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Central Server (Optional for Online / OTA sync)
    |--------------------------------------------------------------------------
    */
    'master_server' => [
        'mode' => env('LICENSE_MODE', 'offline'), // 'offline' or 'online'
        'url' => env('LICENSE_SERVER_URL', 'https://your-master-license-server.com'),
        'timeout' => 10,
    ],
];