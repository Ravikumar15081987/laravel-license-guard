<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applied_crm_licenses')) {
            Schema::create('applied_crm_licenses', function (Blueprint $table) {
                $table->id();
                $table->string('license_hash', 64)->unique()->comment('SHA-256 hash of normalized license key');
                $table->string('company_code', 50)->index();
                $table->integer('days_granted')->default(0);
                $table->string('tier', 10)->nullable();
                $table->uuid('applied_by')->nullable();
                $table->timestamp('applied_at')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('applied_crm_licenses');
    }
};