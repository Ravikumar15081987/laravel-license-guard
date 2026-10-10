<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseGracePeriodEntered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode,
        public int $graceDaysRemaining,
        public Carbon $expiresAt,
        public Carbon $graceEndsAt
    ) {}
}