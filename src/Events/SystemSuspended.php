<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemSuspended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode,
        public string $mode, // 'temporary' or 'permanent_block'
        public ?string $reason = null,
        public ?Carbon $until = null
    ) {}
}