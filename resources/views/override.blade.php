<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Emergency Override</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0f172a 100%); color: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .portal-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 24px; max-width: 620px; width: 100%; overflow: hidden; }
        .code-input { background: rgba(15, 23, 42, 0.8) !important; border: 1px solid rgba(148, 163, 184, 0.25) !important; color: #38bdf8 !important; font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; letter-spacing: 2px; text-transform: uppercase; padding: 0.9rem 1.25rem; border-radius: 12px; text-align: center; }
    </style>
</head>
<body>
<div class="portal-card">
    <div class="p-4 p-md-5 pb-4 border-bottom border-light border-opacity-10">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-white"><i class="ri-shield-keyhole-line text-primary me-2"></i> Vendor System Control</h5>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{{ strtoupper($settings->suspension_mode ?? 'normal') }}</span>
        </div>
        <div class="d-flex align-items-center justify-content-between bg-dark bg-opacity-50 px-3 py-2 rounded-3 border border-secondary border-opacity-25 mt-3">
            <span class="small text-secondary">Target Company Code:</span>
            <span class="font-monospace fw-bold text-info">{{ $settings->company_code ?? 'Not Configured' }}</span>
        </div>
    </div>
    <div class="p-4 p-md-5 pt-4">
        @if(session('success'))
        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success small rounded-3 mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger small rounded-3 mb-4">{{ session('error') }}</div>
        @endif

        <form action="{{ route('license-guard.override.execute') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold mb-2">Enter Action Code</label>
                <input type="text" name="override_code" class="form-control code-input shadow-none" placeholder="XXXX-XXXX-XXXX-XXXX" required autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-none">
                <i class="ri-flashlight-line me-1"></i> Verify & Execute
            </button>
        </form>
    </div>
</div>
</body>
</html>