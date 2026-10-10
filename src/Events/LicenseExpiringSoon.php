<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseExpiringSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode,
        public int $daysRemaining,
        public Carbon $expiresAt,
        public string $tier
    ) {}
}