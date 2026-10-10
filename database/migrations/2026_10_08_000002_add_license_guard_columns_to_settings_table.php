<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('license-guard.settings_table', 'company_settings');

        if (Schema::hasTable($table)) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'company_code')) {
                    $table->string('company_code', 50)->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'license_key')) {
                    $table->text('license_key')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'license_tier')) {
                    $table->string('license_tier', 10)->default('E');
                }
                if (!Schema::hasColumn($table->getTable(), 'license_expires_at')) {
                    $table->timestamp('license_expires_at')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'suspension_mode')) {
                    $table->string('suspension_mode', 30)->default('normal');
                }
                if (!Schema::hasColumn($table->getTable(), 'suspended_until')) {
                    $table->timestamp('suspended_until')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'suspension_reason')) {
                    $table->string('suspension_reason')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Safe down migration: leaves existing application columns intact
    }
};