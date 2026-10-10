<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseRenewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode,
        public int $daysGranted,
        public Carbon $expiresAt,
        public string $tier,
        public ?int $appliedBy = null
    ) {}
}