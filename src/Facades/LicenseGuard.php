<?php

namespace Nirikshaoccesstech\LicenseGuard\Facades;

use Illuminate\Support\Facades\Facade;

class LicenseGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'license-guard';
    }
}