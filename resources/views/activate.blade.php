<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate SPOS</title>
    <!-- FAVICON ICON -->
    <link rel="shortcut icon" href="{{ asset('assets/images/nofav.png') }}" type="image/png">
    <!-- BOOTSTRAP CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap/bootstrap.min.css') }}">
    <!-- APP-CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            overflow: hidden; /* Prevent scrolling */
        }
        .activation-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 2rem;
            text-align: center;
        }
        .logo-box {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
        }
        .machine-id-box {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
        .machine-id {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
            color: #495057;
            display: block;
            margin-top: 2px;
            font-size: 0.85rem;
        }
        .btn-activate {
            background-color: #28a745;
            border: none;
            padding: 0.6rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-activate:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        .support-info {
            margin-top: 1.5rem;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .support-info a {
            color: #17a2b8;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="activation-card">
        <div class="logo-box">
             <img src="{{ asset('assets/images/branding/sinyx-slogan.png') }}" width="280px" alt="SINYX Logo">
        </div>

        <div class="mb-3">
            <h3 class="font-weight-bold">Activation</h3>
            <p class="text-muted small">SPOS requires a valid license to operate.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger text-left x-small mb-3 py-2" style="font-size: 0.8rem;">
                <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        <div class="machine-id-box text-left">
            <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Your Machine ID</small>
            <span class="machine-id">{{ $machineId }}</span>
            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Contact SINYX Support with this ID.</small>
        </div>

        <form action="{{ route('license.activate.public') }}" method="POST" class="text-left">
            @csrf
            <div class="form-group mb-3">
                <label for="license_key" class="font-weight-600 mb-1 small">Activation Key</label>
                <textarea class="form-control form-control-sm" name="license_key" id="license_key" rows="2" 
                    placeholder="Enter key here..." required
                    style="border-radius: 8px; border: 1px solid #ced4da; font-size: 0.9rem;"></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-block btn-activate">
                <i class="fas fa-rocket mr-2"></i>Activate
            </button>
        </form>

        <div class="support-info">
            <p class="mb-1">Don't have a license?</p>
            <a href="tel:+923429031328"><i class="fas fa-phone-alt mr-1"></i> +92 342 9031328</a>
        </div>
        
        <div class="mt-3 pt-2 border-top">
            <p style="font-size: 10px; color: #86868b; margin: 0;">
                Developed by <span style="font-weight: 600; color: #1d1d1f;">SINYX</span>
            </p>
        </div>
    </div>

    <!-- BOOTSTRAP JS -->
    <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
