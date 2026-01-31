<div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
    <div class="card-header bg-gradient-maroon py-3">
        <h5 class="card-title text-white font-weight-bold mb-0">
            <i class="fas fa-sync-alt mr-2"></i> System Updates
        </h5>
    </div>
    <div class="card-body p-4">
        <!-- Current Version Info -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-info border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x mr-3"></i>
                        <div>
                            <h6 class="mb-1 font-weight-bold">Current Version</h6>
                            <p class="mb-0">SPOS v1.0.5 (Build: {{ date('Y-m-d') }})</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Area -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div id="update-status-area">
                    <div class="text-center py-4">
                        <i class="fas fa-cloud-download-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Click "Check for Updates" to see if a new version is available.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-md-12">
                <button type="button" id="btn-check-updates" class="btn btn-primary btn-lg px-5 shadow-sm">
                    <i class="fas fa-search mr-2"></i> Check for Updates
                </button>
                <button type="button" id="btn-download-update" class="btn btn-success btn-lg px-5 shadow-sm ml-2" style="display: none;">
                    <i class="fas fa-download mr-2"></i> Download Update
                </button>
                <button type="button" id="btn-install-update" class="btn btn-maroon btn-lg px-5 shadow-sm ml-2" style="display: none;">
                    <i class="fas fa-rocket mr-2"></i> Install & Restart
                </button>
            </div>
        </div>

        <!-- Progress Bar (Hidden by default) -->
        <div class="row mt-4" id="download-progress-area" style="display: none;">
            <div class="col-md-12">
                <h6 class="mb-2">Downloading Update...</h6>
                <div class="progress" style="height: 30px;">
                    <div id="download-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                         role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <span id="download-progress-text">0%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Box -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-question-circle mr-2"></i> About Manual Updates</h6>
                        <ul class="mb-0 pl-3">
                            <li>✅ Updates are checked manually - no automatic interruptions</li>
                            <li>✅ Your data is safe - updates only replace app files</li>
                            <li>✅ Download happens in background - you can continue working</li>
                            <li>✅ Installation requires restart - save your work first</li>
                            <li>⚠️ Internet connection required for checking & downloading</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCheck = document.getElementById('btn-check-updates');
    const btnDownload = document.getElementById('btn-download-update');
    const btnInstall = document.getElementById('btn-install-update');
    const statusArea = document.getElementById('update-status-area');
    const progressArea = document.getElementById('download-progress-area');
    const progressBar = document.getElementById('download-progress-bar');
    const progressText = document.getElementById('download-progress-text');

    // Check if running in Electron
    if (!window.updater) {
        statusArea.innerHTML = `
            <div class="alert alert-warning border-0">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Update system is only available in the desktop app.
            </div>
        `;
        btnCheck.disabled = true;
        return;
    }

    // Check for Updates
    btnCheck.addEventListener('click', async function() {
        btnCheck.disabled = true;
        btnCheck.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Checking...';
        
        statusArea.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="text-muted mt-3">Checking for updates...</p>
            </div>
        `;

        try {
            const result = await window.updater.check();
            
            if (result.error) {
                statusArea.innerHTML = `
                    <div class="alert alert-danger border-0 shadow-sm">
                        <i class="fas fa-times-circle mr-2"></i>
                        <strong>Error:</strong> ${result.error}
                    </div>
                `;
            } else if (result.available) {
                statusArea.innerHTML = `
                    <div class="alert alert-success border-0 shadow-sm">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-3x mr-3"></i>
                            <div>
                                <h5 class="mb-1 font-weight-bold">Update Available!</h5>
                                <p class="mb-0">New version <strong>${result.version}</strong> is ready to download.</p>
                                ${result.releaseNotes ? `<small class="text-muted">Release notes: ${result.releaseNotes.substring(0, 100)}...</small>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                btnDownload.style.display = 'inline-block';
            } else {
                statusArea.innerHTML = `
                    <div class="alert alert-info border-0 shadow-sm">
                        <i class="fas fa-check mr-2"></i>
                        You're running the latest version! No updates available.
                    </div>
                `;
            }
        } catch (error) {
            statusArea.innerHTML = `
                <div class="alert alert-danger border-0 shadow-sm">
                    <i class="fas fa-times-circle mr-2"></i>
                    <strong>Error:</strong> ${error.message}
                </div>
            `;
        } finally {
            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-search mr-2"></i> Check Again';
        }
    });

    // Download Update
    btnDownload.addEventListener('click', function() {
        btnDownload.disabled = true;
        btnDownload.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Starting Download...';
        progressArea.style.display = 'block';
        
        window.updater.download();
        
        statusArea.innerHTML = `
            <div class="alert alert-info border-0 shadow-sm">
                <i class="fas fa-download mr-2"></i>
                Downloading update in background... You can continue working.
            </div>
        `;
    });

    // Install Update
    btnInstall.addEventListener('click', function() {
        if (confirm('Application will restart to install the update. Make sure all work is saved!\n\nContinue?')) {
            btnInstall.disabled = true;
            btnInstall.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Installing...';
            window.updater.install();
        }
    });

    // Listen for update events
    window.updater.onProgress((percent) => {
        const rounded = Math.round(percent);
        progressBar.style.width = rounded + '%';
        progressBar.setAttribute('aria-valuenow', rounded);
        progressText.textContent = rounded + '%';
    });

    window.updater.onReady(() => {
        progressArea.style.display = 'none';
        statusArea.innerHTML = `
            <div class="alert alert-success border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-3x mr-3"></i>
                    <div>
                        <h5 class="mb-1 font-weight-bold">Update Downloaded!</h5>
                        <p class="mb-0">Click "Install & Restart" to complete the update.</p>
                    </div>
                </div>
            </div>
        `;
        btnDownload.style.display = 'none';
        btnInstall.style.display = 'inline-block';
    });

    window.updater.onStatus((status, ...args) => {
        if (status === 'error') {
            progressArea.style.display = 'none';
            statusArea.innerHTML = `
                <div class="alert alert-danger border-0 shadow-sm">
                    <i class="fas fa-times-circle mr-2"></i>
                    <strong>Error:</strong> ${args[0] || 'Unknown error occurred'}
                </div>
            `;
            btnDownload.disabled = false;
            btnDownload.innerHTML = '<i class="fas fa-download mr-2"></i> Retry Download';
        }
    });
});
</script>

<style>
.btn-maroon {
    background: linear-gradient(180deg, #800000, #600000) !important;
    border-color: #800000 !important;
    color: #ffffff !important;
}
.btn-maroon:hover {
    background: linear-gradient(180deg, #a00000, #800000) !important;
    border-color: #a00000 !important;
}
</style>
