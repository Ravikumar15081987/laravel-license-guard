<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode,
        public Carbon $expiresAt,
        public string $tier
    ) {}
}