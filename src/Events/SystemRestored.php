<?php

namespace Nirikshaoccesstech\LicenseGuard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $companyCode
    ) {}
}