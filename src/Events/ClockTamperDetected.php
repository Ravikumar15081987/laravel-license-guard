<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClockTamperDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode,
        public Carbon $systemTime,
        public Carbon $lastKnownRecordedTime
    ) {}
}