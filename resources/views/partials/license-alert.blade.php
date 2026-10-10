@php
    $licenseStatus = \Nirikshaoccesstech\LicenseGuard\Facades\LicenseGuard::status();
@endphp

@if($licenseStatus['status'] === 'CLOCK_TAMPERED')
    <div class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm border-0 mb-4 px-4 py-3" role="alert" style="background-color: #fee2e2; color: #991b1b; border-radius: 12px;">
        <div class="d-flex align-items-center">
            <i class="ri-alarm-warning-fill fs-4 me-2 text-danger"></i>
            <div>
                <strong>SECURITY HOLD:</strong> Clock rollback detected on the host server. License verification is temporarily locked.
            </div>
        </div>
        <a href="{{ route('license-guard.show') }}" class="btn btn-sm btn-danger rounded-pill fw-medium text-nowrap ms-3 shadow-none">
            <i class="ri-refresh-line me-1"></i> Verify System Time
        </a>
    </div>
@elseif($licenseStatus['is_in_grace_period'])
    <div class="alert alert-warning d-flex align-items-center justify-content-between shadow-sm border-0 mb-4 px-4 py-3" role="alert" style="background-color: #fef3c7; color: #92400e; border-radius: 12px;">
        <div class="d-flex align-items-center">
            <i class="ri-time-line fs-4 me-2 text-warning"></i>
            <div>
                <strong>LICENSE GRACE PERIOD:</strong> License expired on <strong>{{ $licenseStatus['expires_at']->format('M d, Y') }}</strong>. You have <strong>{{ $licenseStatus['grace_days_remaining'] }} days</strong> remaining before operations switch to read-only mode.
            </div>
        </div>
        <a href="{{ route('license-guard.show') }}" class="btn btn-sm btn-warning text-dark rounded-pill fw-medium text-nowrap ms-3 shadow-none">
            <i class="ri-key-2-line me-1"></i> Renew License
        </a>
    </div>
@elseif($licenseStatus['is_expired'])
    <div class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm border-0 mb-4 px-4 py-3" role="alert" style="background-color: #fee2e2; color: #991b1b; border-radius: 12px;">
        <div class="d-flex align-items-center">
            <i class="ri-error-warning-fill fs-4 me-2 text-danger"></i>
            <div>
                <strong>SYSTEM READ-ONLY:</strong> License expired on <strong>{{ $licenseStatus['expires_at'] ? $licenseStatus['expires_at']->format('M d, Y') : 'Unknown' }}</strong>. Data creation and modifications are disabled.
            </div>
        </div>
        <a href="{{ route('license-guard.show') }}" class="btn btn-sm btn-danger rounded-pill fw-medium text-nowrap ms-3 shadow-none">
            <i class="ri-key-2-line me-1"></i> Apply License Key
        </a>
    </div>
@endif