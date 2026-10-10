<?php

namespace Nirikshaoccesstech\LicenseGuard;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nirikshaoccesstech\LicenseGuard\Commands\ActivateLicenseCommand;
use Nirikshaoccesstech\LicenseGuard\Commands\CheckLicenseStatusCommand;
use Nirikshaoccesstech\LicenseGuard\Commands\HeartbeatLicenseCommand;
use Nirikshaoccesstech\LicenseGuard\Commands\InstallLicenseGuardCommand;
use Nirikshaoccesstech\LicenseGuard\Commands\SyncLicenseOtaCommand;
use Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard;
use Nirikshaoccesstech\LicenseGuard\Http\Middleware\CheckLicenseFeatureMiddleware;
use Nirikshaoccesstech\LicenseGuard\Http\Middleware\CheckLicenseMiddleware;
use Nirikshaoccesstech\LicenseGuard\Http\Middleware\CheckLicenseTierMiddleware;
use Nirikshaoccesstech\LicenseGuard\Http\Middleware\EnforceSuspensionMiddleware;
use Nirikshaoccesstech\LicenseGuard\Services\LicenseCryptoService;

class LicenseGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/license-guard.php', 'license-guard');

        $this->app->singleton('license-guard', function ($app) {
            return new LicenseCryptoService();
        });
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views under namespace 'license-guard'
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'license-guard');

        // Load routes if enabled
        if (config('license-guard.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('license.check', CheckLicenseMiddleware::class);
        $router->aliasMiddleware('license.suspension', EnforceSuspensionMiddleware::class);
        $router->aliasMiddleware('license.tier', CheckLicenseTierMiddleware::class);
        $router->aliasMiddleware('license.feature', CheckLicenseFeatureMiddleware::class);

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ActivateLicenseCommand::class,
                CheckLicenseStatusCommand::class,
                HeartbeatLicenseCommand::class,
                InstallLicenseGuardCommand::class,
                SyncLicenseOtaCommand::class,
            ]);

            // Publishing config
            $this->publishes([
                __DIR__ . '/../config/license-guard.php' => config_path('license-guard.php'),
            ], 'license-guard-config');

            // Publishing views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/license-guard'),
            ], 'license-guard-views');

            // Publishing migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'license-guard-migrations');
        }

        // Register Blade Directives
        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives(): void
    {
        Blade::if('licenseActive', function () {
            return LicenseGuard::status()['status'] === 'ACTIVE';
        });

        Blade::if('licenseInGracePeriod', function () {
            return LicenseGuard::status()['is_in_grace_period'];
        });

        Blade::if('licenseExpired', function () {
            return LicenseGuard::status()['is_expired'];
        });

        Blade::if('licenseTier', function (string $tier) {
            return LicenseGuard::hasTier($tier);
        });

        Blade::if('licenseFeature', function (string $feature) {
            return LicenseGuard::allows($feature);
        });
    }
}