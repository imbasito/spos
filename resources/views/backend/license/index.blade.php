@extends('backend.master')

@section('title', 'System & Updates')

@section('content')
<div class="row animate__animated animate__fadeIn">
    {{-- License Section --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 border-radius-15 h-100">
            <div class="card-header bg-gradient-maroon py-3">
                <h5 class="card-title text-white font-weight-bold mb-0">
                    <i class="fas fa-key mr-2"></i> License Information
                </h5>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-times-circle mr-2"></i>{{ session('error') }}
                    </div>
                @endif

                @if($licenseInfo && $licenseInfo['valid'])
                    <div class="text-center mb-5 mt-3">
                        <div class="mb-3">
                            <i class="fas fa-certificate text-success" style="font-size: 64px;"></i>
                        </div>
                        <h3 class="text-success font-weight-bold">License Active</h3>
                        <p class="text-muted">Your copy of SPOS is fully activated.</p>
                    </div>

                    <div class="card bg-light shadow-none border border-radius-10">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <span class="text-muted">Licensed To:</span>
                                <span class="font-weight-bold ml-auto">{{ $licenseInfo['shop'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <span class="text-muted">Expires:</span>
                                @if($licenseInfo['lifetime'])
                                    <span class="badge badge-success px-3 py-1">Lifetime</span>
                                @else
                                    <span class="font-weight-bold text-danger">{{ $licenseInfo['expiry'] }}</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Machine ID:</span>
                                <code class="bg-white px-2 py-1 rounded border">{{ $machineId }}</code>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center mb-4 mt-3">
                        <div class="mb-3">
                            <i class="fas fa-lock text-warning" style="font-size: 64px;"></i>
                        </div>
                        <h3 class="text-warning font-weight-bold">License Required</h3>
                        <p class="text-muted">Please activate your software to continue.</p>
                    </div>

                    <form action="{{ route('backend.admin.license.activate') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="license_key" class="font-weight-bold">License Key</label>
                            <textarea class="form-control border-radius-10" id="license_key" name="license_key" rows="3" 
                                placeholder="Paste your license key here..." required 
                                style="font-family: monospace; background-color: #f8f9fa;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-maroon btn-block py-2 font-weight-bold shadow-sm border-radius-25">
                            <i class="fas fa-unlock mr-2"></i> Activate License
                        </button>
                    </form>

                    <div class="text-center pt-3 mt-3 border-top">
                        <p class="text-muted small mb-1">Share this Machine ID for activation:</p>
                        <code class="bg-light px-3 py-1 rounded border d-inline-block">{{ $machineId }}</code>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Update Section --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100 win-update-card">
            <div class="card-body p-4 d-flex flex-column">
                
                <div class="win-header mb-4">
                    <div class="win-header-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" style="color:#111; font-size:16px;">System Updates</h5>
                        <p class="text-muted small mb-0">Current Version: v{{ config('app.version', '1.1.0') }}</p>
                    </div>
                </div>

                <div id="update-status-container" class="win-status-area flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center p-4 mb-4">
                    <i id="update-icon" class="fas fa-cloud win-status-icon mb-3"></i>
                    <h6 id="update-text" class="font-weight-bold mb-1" style="color:#111; font-size:14px;">Ready to Check</h6>
                    <p id="update-subtext" class="text-muted small mb-0">Querying update servers for the latest release.</p>
                    
                    <div id="update-progress-container" class="d-none w-100 mt-4 text-left">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-bold" style="font-size:12px; color:#111;">Downloading payload...</span>
                            <span id="update-progress-text" class="font-weight-bold" style="font-size:12px; color:#0078D4;">0%</span>
                        </div>
                        <div class="win-progress-track">
                            <div id="update-progress-bar" class="win-progress-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div id="update-actions" class="mt-auto w-100">
                    <button id="btn-check-update" class="win-btn win-btn-primary w-100 justify-content-center">
                        <i class="fas fa-search"></i> Check for Updates
                    </button>
                    
                    <div id="post-check-actions" class="d-none mt-2">
                        <button id="btn-download-update" class="win-btn win-btn-success w-100 justify-content-center">
                            <i class="fas fa-download"></i> Download Update
                        </button>
                    </div>

                    <div id="ready-actions" class="d-none mt-2">
                        <button id="btn-install-update" class="win-btn win-btn-danger w-100 justify-content-center">
                            <i class="fas fa-power-off"></i> Install & Restart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>

{{-- Danger Zone: Factory Reset --}}
<div class="row justify-content-center mt-4">
    <div class="col-md-12">
        <div class="win-update-card" style="border: 1px solid rgba(209, 52, 56, 0.3) !important;">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="flex-grow-1">
                    <h6 class="font-weight-bold mb-1" style="color: #D13438;"><i class="fas fa-exclamation-triangle mr-1"></i> Factory Reset / Wipe Data</h6>
                    <p class="text-muted small mb-0" style="max-width: 600px;">Permanently delete all transactional data to start fresh. An automatic silent backup is taken before execution.</p>
                </div>
                <div class="ml-auto">
                    <button type="button" class="win-btn win-btn-danger" onclick="triggerFactoryReset()">
                        <i class="fas fa-trash-alt"></i> Wipe System Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Contact Info Card --}}
<div class="row justify-content-center mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 border-radius-10 bg-white">
            <div class="card-body py-2 text-center text-muted small">
                <strong>Need help?</strong> <a href="tel:+923429031328" class="text-maroon font-weight-bold mx-2 hover-underline"><i class="fas fa-phone-alt"></i> +92 342 9031328</a>
                <span class="mx-2">|</span>
                <a href="#" class="text-maroon font-weight-bold hover-underline">Support Center</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Windows 11 Update Card Styles */
    .win-update-card {
        border-radius: 12px !important;
        background: #ffffff;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02) !important;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        font-family: "Segoe UI Variable", "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .win-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .win-header-icon {
        color: #0078D4;
        background: rgba(0, 120, 212, 0.1);
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .win-status-area {
        background: #fafafa;
        border: 1px dashed rgba(0, 0, 0, 0.12);
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .win-status-icon {
        font-size: 32px;
        color: #8e8e8e;
    }

    /* Win Actions */
    .win-btn {
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 600;
        font-family: inherit;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.1s ease;
    }

    .win-btn-primary { background: #0078D4; color: white; }
    .win-btn-primary:not(:disabled):hover { background: #006CBE; }
    
    .win-btn-success { background: #107C41; color: white; }
    .win-btn-success:not(:disabled):hover { background: #0E6C39; }
    
    .win-btn-danger { background: #D13438; color: white; }
    .win-btn-danger:not(:disabled):hover { background: #BA2D31; }

    .win-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Progress Track */
    .win-progress-track {
        height: 6px;
        background: #e1dfdd;
        border-radius: 3px;
        overflow: hidden;
    }

    .win-progress-fill {
        height: 100%;
        background: #0078D4;
        border-radius: 3px;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Existing Legacy Styles */
    .bg-gradient-maroon { background: linear-gradient(45deg, #800000, #A01010) !important; }
    .text-maroon { color: #800000 !important; }
    .btn-maroon { background-color: #800000; color: white; transition: all 0.3s; }
    .btn-maroon:hover { background-color: #600000; color: white; transform: translateY(-2px); }
    .border-radius-10 { border-radius: 10px; }
    .border-radius-15 { border-radius: 15px; }
    .border-radius-25 { border-radius: 25px; }
    .hover-underline:hover { text-decoration: underline; }
    
    .win-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: winSpin 1s linear infinite;
        display: inline-block;
    }

    @keyframes winSpin { to { transform: rotate(360deg); } }
</style>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnCheck = document.getElementById('btn-check-update');
        const btnDownload = document.getElementById('btn-download-update');
        const btnInstall = document.getElementById('btn-install-update');
        const updateText = document.getElementById('update-text');
        const updateSubtext = document.getElementById('update-subtext');
        const updateIcon = document.getElementById('update-icon');
        const progressContainer = document.getElementById('update-progress-container');
        const progressBar = document.getElementById('update-progress-bar');
        const progressText = document.getElementById('update-progress-text');
        const postCheckActions = document.getElementById('post-check-actions');
        const readyActions = document.getElementById('ready-actions');
        const statusArea = document.getElementById('update-status-container');

        if (typeof window.updater === 'undefined') {
            btnCheck.disabled = true;
            statusArea.style.border = "none";
            statusArea.style.background = "rgba(209, 52, 56, 0.05)";
            updateIcon.className = 'fas fa-exclamation-triangle win-status-icon mb-3';
            updateIcon.style.color = '#D13438';
            updateText.innerText = 'Environment Mismatch';
            updateSubtext.innerText = "The update engine runs via Desktop App only. Web instances cannot update binaries.";
            return;
        }

        btnCheck.addEventListener('click', () => {
            btnCheck.disabled = true;
            btnCheck.innerHTML = '<span class="win-spinner mr-2"></span> Checking...';
            updateIcon.className = 'fas fa-sync win-status-icon mb-3 fa-spin';
            updateIcon.style.color = '#0078D4';
            updateText.innerText = 'Checking Servers';
            updateSubtext.innerText = 'Looking for the latest stable release...';
            
            window.updater.check();
        });

        btnDownload.addEventListener('click', () => {
            postCheckActions.classList.add('d-none');
            progressContainer.classList.remove('d-none');
            updateIcon.className = 'fas fa-cloud-download-alt win-status-icon mb-3';
            updateIcon.style.color = '#0078D4';
            updateText.innerText = 'Downloading Payload';
            updateSubtext.innerText = 'Fetching files silently in the background.';
            
            window.updater.download();
        });

        btnInstall.addEventListener('click', () => {
            if(confirm("The application will immediately close to apply the update.\n\nPlease ensure all work is saved.")) {
                btnInstall.disabled = true;
                btnInstall.innerHTML = '<span class="win-spinner mr-2"></span> Restarting...';
                window.updater.install();
            }
        });

        window.updater.onStatus((status, info) => {
            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-search"></i> Check Again';
            updateIcon.classList.remove('fa-spin');

            if (status === 'available') {
                statusArea.style.background = "rgba(16, 124, 65, 0.05)";
                statusArea.style.border = "1px solid rgba(16, 124, 65, 0.15)";
                updateIcon.className = 'fas fa-arrow-alt-circle-down mb-3';
                updateIcon.style.color = '#107C41';
                updateIcon.style.fontSize = '36px';
                
                updateText.innerText = 'New Version Available!';
                updateSubtext.innerHTML = `Version <strong style="color:#107C41;">v${info.version}</strong> is ready to cache.`;
                
                btnCheck.classList.add('d-none'); // Hide just the check button
                postCheckActions.classList.remove('d-none');
                
            } else if (status === 'latest') {
                statusArea.style.background = "rgba(0, 120, 212, 0.05)";
                statusArea.style.border = "1px solid rgba(0, 120, 212, 0.15)";
                updateIcon.className = 'fas fa-check-circle mb-3';
                updateIcon.style.color = '#0078D4';
                
                updateText.innerText = 'System is Up to Date';
                updateSubtext.innerText = 'You are already running the latest optimized version.';
                
            } else if (status === 'error') {
                statusArea.style.background = "rgba(209, 52, 56, 0.05)";
                statusArea.style.border = "1px solid rgba(209, 52, 56, 0.15)";
                updateIcon.className = 'fas fa-times-circle mb-3';
                updateIcon.style.color = '#D13438';
                
                updateText.innerText = 'Connection Error';
                updateSubtext.innerText = 'Could not reach the update server securely.';
                
                progressContainer.classList.add('d-none');
                postCheckActions.classList.remove('d-none'); // Allow retry download
                btnDownload.innerHTML = '<i class="fas fa-redo"></i> Retry Download';
            }
        });

        window.updater.onProgress((progress) => {
            const percent = Math.floor(progress.percent);
            progressBar.style.width = percent + '%';
            progressText.innerText = `${percent}%`;
        });

        window.updater.onReady((info) => {
            progressContainer.classList.add('d-none');
            readyActions.classList.remove('d-none');
            
            statusArea.style.background = "rgba(16, 124, 65, 0.05)";
            statusArea.style.border = "1px solid rgba(16, 124, 65, 0.15)";
            updateIcon.className = 'fas fa-box-open mb-3';
            updateIcon.style.color = '#107C41';
            
            updateText.innerText = 'Payload Cached Successfully';
            updateSubtext.innerText = 'Click Install & Restart to safely apply the binary swap.';
        });
    });

    // Factory Reset Feature
    async function triggerFactoryReset() {
        const { value: formValues } = await Swal.fire({
            title: '<h3 class="mb-0" style="color: #D13438;"><i class="fas fa-exclamation-triangle"></i> Factory Reset</h3>',
            html: `
                <div class="text-left mb-4 mt-2" style="font-size: 14px; color: #444;">
                    <p class="mb-2"><strong>Warning:</strong> This action permanently wipes products, sales, customers, returns, and transaction logs.</p>
                    <p class="mb-0">An automatic system backup will be generated silently before data is erased. <strong>Administrators and System Settings will be preserved.</strong></p>
                </div>
                <div class="form-group text-left mb-3">
                    <label style="font-weight:600; font-size:13px; color:#111;">Administrator Password</label>
                    <input id="swal-password" type="password" class="form-control" placeholder="Enter your current password">
                </div>
                <div class="form-group text-left mb-0">
                    <label style="font-weight:600; font-size:13px; color:#111;">Type <strong class="text-danger">CONFIRM</strong></label>
                    <input id="swal-confirm" type="text" class="form-control text-danger font-weight-bold" style="text-transform: uppercase" placeholder="CONFIRM">
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonColor: '#D13438',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Wipe & Reset',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            preConfirm: () => {
                const pwd = document.getElementById('swal-password').value;
                const cfm = document.getElementById('swal-confirm').value;
                if (!pwd) {
                    Swal.showValidationMessage('Administrator password is required');
                    return false;
                }
                if (cfm !== 'CONFIRM') {
                    Swal.showValidationMessage('You must type CONFIRM exactly to proceed');
                    return false;
                }
                return { password: pwd, confirm: cfm };
            }
        });

        if (formValues) {
            Swal.fire({
                title: 'Wiping Data...',
                html: `
                    <div class="mt-3 mb-3 text-center">
                        <span class="win-spinner" style="border-top-color:#0078D4; border-right-color:#e1dfdd; border-bottom-color:#e1dfdd; border-left-color:#e1dfdd; width:34px; height:34px; border-width: 3px;"></span>
                    </div>
                    <p class="mb-1 text-muted" style="font-size: 14px;">Creating automatic backup and dropping operational data bounds.</p>
                    <p class="text-muted" style="font-size: 12px;">This may take a moment. Please do not close the window.</p>
                `,
                allowOutsideClick: false,
                showConfirmButton: false
            });

            try {
                const formData = new FormData();
                formData.append('admin_password', formValues.password);
                formData.append('confirm_text', formValues.confirm);
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route("backend.admin.system.reset") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'System Reset Successful',
                        text: result.message,
                        confirmButtonColor: '#107C41'
                    }).then(() => {
                        window.location.href = "{{ route('backend.admin.dashboard') }}";
                    });
                } else {
                    throw new Error(result.message || 'An unknown server error occurred');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Data Wipe Failed',
                    text: error.message,
                    confirmButtonColor: '#D13438'
                });
            }
        }
    }
</script>
@endpush
