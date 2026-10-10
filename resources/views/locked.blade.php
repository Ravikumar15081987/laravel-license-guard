<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Expired</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .lock-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); max-width: 480px; width: 100%; padding: 2.5rem; text-align: center; }
        .icon-circle { width: 80px; height: 80px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem; }
        .form-control { background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 0.75rem 1rem; border-radius: 8px; text-align: center; font-family: monospace; letter-spacing: 2px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="lock-card">
        <div class="icon-circle"><i class="ri-lock-2-fill"></i></div>
        <h3 class="fw-bold text-dark mb-2">License Expired</h3>
        <p class="text-secondary small mb-3">Admin access has been suspended. Please apply a valid license key.</p>

        @if(!empty($settings->company_code))
        <div class="d-inline-flex align-items-center bg-light border border-light-subtle px-3 py-1 rounded-pill mb-4">
            <span class="text-secondary small me-2">Company Code:</span>
            <strong class="font-monospace text-dark" id="company_code_text">{{ $settings->company_code }}</strong>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4 text-start">{{ session('error') }}</div>
        @endif

        @if(session('success'))
        <div class="alert alert-success py-2 px-3 small rounded-3 mb-4 text-start">{{ session('success') }}</div>
        @endif

        <form action="{{ route('license-guard.verify') }}" method="POST">
            @csrf
            <div class="mb-4">
                <input type="text" name="license_key" id="license_input" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19" required autocomplete="off">
            </div>
            <button type="submit" class="btn btn-dark w-100 py-2 fw-medium rounded-3 shadow-none">
                <i class="ri-key-2-line me-1"></i> Verify & Unlock
            </button>
        </form>
    </div>
    <script>
        const input = document.getElementById('license_input');
        if (input) {
            input.addEventListener('input', function(e) {
                let val = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 16);
                let formatted = val.match(/.{1,4}/g)?.join('-') || val;
                e.target.value = formatted;
            });
        }
    </script>
</body>
</html>