<style>
    /* System Updates - Windows 11 Professional Dashboard UI */
    .win-card-container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
        border: 1px solid rgba(0, 0, 0, 0.06);
        font-family: "Segoe UI Variable", "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
        overflow: hidden;
    }

    .win-header {
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        padding: 20px 24px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .win-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #111111;
        letter-spacing: -0.2px;
    }

    .win-header-icon {
        color: #0078D4; /* Windows Blue */
        background: rgba(0, 120, 212, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .win-body {
        padding: 24px;
    }

    /* Version / Info Blocks */
    .win-info-box {
        background: #fbfbfb;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        transition: background 0.2s ease;
    }

    .win-info-box:hover {
        background: #f5f5f5;
    }

    .win-info-icon {
        color: #5c5c5c;
        font-size: 20px;
    }

    .win-info-content h6 {
        margin: 0 0 4px 0;
        font-size: 13px;
        font-weight: 600;
        color: #111111;
    }

    .win-info-content p {
        margin: 0;
        font-size: 12px;
        color: #5c5c5c;
    }

    /* Status Area */
    .win-status-area {
        min-height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgba(0, 0, 0, 0.12);
        border-radius: 8px;
        margin-bottom: 24px;
        background: #fafafa;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .win-status-area i {
        font-size: 32px;
        color: #8e8e8e;
        margin-bottom: 12px;
    }

    .win-status-area p {
        margin: 0;
        font-size: 13px;
        color: #5c5c5c;
        font-weight: 500;
    }

    /* Dynamic Alerts inside Status Area */
    .win-alert {
        width: 100%;
        text-align: left;
        padding: 16px 20px;
        border-radius: 8px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        border: 1px solid transparent;
    }

    .win-alert-success {
        background: rgba(16, 124, 65, 0.05); /* Windows Success Green */
        border-color: rgba(16, 124, 65, 0.15);
    }
    .win-alert-success i { color: #107C41; font-size: 24px; margin: 0; }
    
    .win-alert-info {
        background: rgba(0, 120, 212, 0.05);
        border-color: rgba(0, 120, 212, 0.15);
    }
    .win-alert-info i { color: #0078D4; font-size: 24px; margin: 0; }

    .win-alert-danger {
        background: rgba(209, 52, 56, 0.05);
        border-color: rgba(209, 52, 56, 0.15);
    }
    .win-alert-danger i { color: #D13438; font-size: 24px; margin: 0; }

    .win-alert h5 {
        margin: 0 0 6px 0;
        font-size: 15px;
        font-weight: 600;
        color: #111111;
    }

    .win-alert p {
        color: #3b3b3b;
        font-size: 13px;
        line-height: 1.5;
    }

    /* Buttons */
    .win-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-start;
        padding-top: 10px;
        border-top: 1px solid rgba(0,0,0,0.05);
        margin-top: 24px;
        padding-top: 24px;
    }

    .win-btn {
        padding: 8px 20px;
        font-size: 13px;
        font-weight: 600;
        font-family: inherit;
        border-radius: 4px;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.1s ease;
    }

    .win-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .win-btn-primary {
        background: #0078D4;
        color: white;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .win-btn-primary:not(:disabled):hover {
        background: #006CBE;
    }

    .win-btn-success {
        background: #107C41;
        color: white;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .win-btn-success:not(:disabled):hover {
        background: #0E6C39;
    }

    .win-btn-danger {
        background: #D13438;
        color: white;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .win-btn-danger:not(:disabled):hover {
        background: #BA2D31;
    }

    /* Sleek Progress Bar */
    .win-progress-wrapper {
        margin-top: 20px;
    }

    .win-progress-label {
        font-size: 12px;
        font-weight: 600;
        color: #111111;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
    }

    .win-progress-track {
        height: 6px;
        background: #f0f0f0;
        border-radius: 3px;
        overflow: hidden;
    }

    .win-progress-fill {
        height: 100%;
        background: #0078D4; /* Windows Blue */
        width: 0%;
        border-radius: 3px;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* About Box */
    .win-about-box {
        margin-top: 32px;
        padding: 20px;
        background: #fafafa;
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.04);
    }

    .win-about-box h6 {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .win-about-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .win-about-box li {
        font-size: 12px;
        color: #5c5c5c;
        margin-bottom: 8px;
        padding-left: 18px;
        position: relative;
    }

    .win-about-box li::before {
        content: '•';
        position: absolute;
        left: 4px;
        color: #0078D4;
        font-weight: bold;
    }

    .win-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(0, 120, 212, 0.2);
        border-top-color: #0078D4;
        border-radius: 50%;
        animation: winSpin 1s linear infinite;
        display: inline-block;
    }

    @keyframes winSpin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="win-card-container">
    <div class="win-header">
        <div class="win-header-icon">
            <i class="fas fa-sync-alt"></i>
        </div>
        <h5>System Updates</h5>
    </div>

    <div class="win-body">
        
        <!-- Current Version Info -->
        <div class="win-info-box">
            <i class="fas fa-desktop win-info-icon"></i>
            <div class="win-info-content">
                <h6>Current Application Version</h6>
                <p>SPOS v{{ config('app.version', '1.1.0') }} &nbsp;|&nbsp; Channel: Stable &nbsp;|&nbsp; Target: Windows</p>
            </div>
        </div>

        <!-- Dynamic Update Status Area -->
        <div id="update-status-area" class="win-status-area">
            <i class="fas fa-cloud"></i>
            <p>Ready to check for the latest reliable release.</p>
        </div>

        <!-- Sleek Progress Bar (Hidden by default) -->
        <div id="download-progress-area" class="win-progress-wrapper" style="display: none;">
            <div class="win-progress-label">
                <span>Downloading update...</span>
                <span id="download-progress-text">0%</span>
            </div>
            <div class="win-progress-track">
                <div id="download-progress-bar" class="win-progress-fill"></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="win-actions">
            <button type="button" id="btn-check-updates" class="win-btn win-btn-primary">
                <i class="fas fa-search"></i> Check for Updates
            </button>
            <button type="button" id="btn-download-update" class="win-btn win-btn-success" style="display: none;">
                <i class="fas fa-download"></i> Download Update
            </button>
            <button type="button" id="btn-install-update" class="win-btn win-btn-danger" style="display: none;">
                <i class="fas fa-power-off"></i> Install & Restart
            </button>
        </div>

        <!-- Professional Information Box -->
        <div class="win-about-box">
            <h6><i class="fas fa-shield-alt" style="color:#0078D4;"></i> Update Lifecycle Safety</h6>
            <ul>
                <li>Updates are fetched entirely in the background allowing uninterrupted workflow.</li>
                <li>Your databases and configuration logs are inherently preserved—updates only swap active binaries.</li>
                <li>An active internet connection is strictly required for version resolution.</li>
                <li>Applying the update will initiate an application restart. Please commit pending actions beforehand.</li>
            </ul>
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

    // Check if running in Electron environment
    if (!window.updater) {
        statusArea.innerHTML = `
            <div class="win-alert win-alert-danger" style="border:none; background:transparent; padding:0;">
                <i class="fas fa-exclamation-triangle" style="margin-bottom:0; font-size:24px;"></i>
                <div style="text-align:left;">
                    <h5 style="margin-bottom:4px;">Execution Environment Mismatch</h5>
                    <p style="margin:0;">The automated update engine requires the native Windows desktop client. Web-browser instances cannot execute binary updates.</p>
                </div>
            </div>
        `;
        statusArea.style.border = "none";
        statusArea.style.background = "#fff";
        btnCheck.disabled = true;
        return;
    }

    // Check for Updates
    btnCheck.addEventListener('click', async function() {
        btnCheck.disabled = true;
        btnCheck.innerHTML = '<span class="win-spinner"></span> Checking...';
        
        statusArea.innerHTML = `
            <span class="win-spinner" style="width:24px; height:24px; margin-bottom:12px; border-width:3px;"></span>
            <p>Querying update servers for the latest release...</p>
        `;
        statusArea.style.border = "1px dashed rgba(0, 0, 0, 0.12)";
        statusArea.style.background = "#fafafa";
        statusArea.style.padding = "30px";

        try {
            const result = await window.updater.check();
            
            // Clean padding for alert render
            statusArea.style.border = "none";
            statusArea.style.background = "transparent";
            statusArea.style.padding = "0";

            if (result.error) {
                statusArea.innerHTML = `
                    <div class="win-alert win-alert-danger">
                        <i class="fas fa-times-circle"></i>
                        <div>
                            <h5>Update Resolution Failed</h5>
                            <p>${result.error}</p>
                        </div>
                    </div>
                `;
            } else if (result.available) {
                statusArea.innerHTML = `
                    <div class="win-alert win-alert-success">
                        <i class="fas fa-arrow-alt-circle-down"></i>
                        <div>
                            <h5>New Version Available: v${result.version}</h5>
                            <p>A newer stable release has been found. Click download below to cache it.</p>
                            ${result.releaseNotes ? `<p style="margin-top:8px; font-size:11px; opacity:0.8; font-family:monospace;">Notes: ${result.releaseNotes.substring(0, 80)}...</p>` : ''}
                        </div>
                    </div>
                `;
                btnDownload.style.display = 'inline-flex';
            } else {
                statusArea.innerHTML = `
                    <div class="win-alert win-alert-info">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h5>System is Up to Date</h5>
                            <p>You are already running the latest optimized version.</p>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            statusArea.style.border = "none";
            statusArea.style.background = "transparent";
            statusArea.style.padding = "0";
            statusArea.innerHTML = `
                <div class="win-alert win-alert-danger">
                    <i class="fas fa-times-circle"></i>
                    <div>
                        <h5>Connection Error</h5>
                        <p>${error.message}</p>
                    </div>
                </div>
            `;
        } finally {
            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-search"></i> Check Again';
        }
    });

    // Download Update
    btnDownload.addEventListener('click', function() {
        btnDownload.disabled = true;
        btnDownload.innerHTML = '<span class="win-spinner" style="border-top-color:#fff; border-right-color:rgba(255,255,255,0.3); border-bottom-color:rgba(255,255,255,0.3); border-left-color:rgba(255,255,255,0.3);"></span> Downloading...';
        progressArea.style.display = 'block';
        
        window.updater.download();
        
        statusArea.innerHTML = `
            <div class="win-alert win-alert-info">
                <i class="fas fa-cloud-download-alt"></i>
                <div>
                    <h5>Caching Update Payload</h5>
                    <p>Fetching files silently in the background. You may safely resume other operations.</p>
                </div>
            </div>
        `;
    });

    // Install Update
    btnInstall.addEventListener('click', function() {
        if (confirm('The POS application will immediately close to execute the binary swap.\n\nPlease confirm all critical data is saved.')) {
            btnInstall.disabled = true;
            btnInstall.innerHTML = '<span class="win-spinner" style="border-top-color:#fff; border-right-color:rgba(255,255,255,0.3); border-bottom-color:rgba(255,255,255,0.3); border-left-color:rgba(255,255,255,0.3);"></span> Restarting...';
            window.updater.install();
        }
    });

    // IPC Progress Events
    window.updater.onProgress((percent) => {
        const rounded = Math.round(percent);
        progressBar.style.width = rounded + '%';
        progressText.textContent = rounded + '%';
    });

    window.updater.onReady(() => {
        progressArea.style.display = 'none';
        btnDownload.style.display = 'none';
        btnInstall.style.display = 'inline-flex';

        statusArea.innerHTML = `
            <div class="win-alert win-alert-success">
                <i class="fas fa-box-open"></i>
                <div>
                    <h5>Payload Ready</h5>
                    <p>The update binary is successfully cached. Click Install & Restart to apply.</p>
                </div>
            </div>
        `;
    });

    window.updater.onStatus((status, ...args) => {
        if (status === 'error') {
            progressArea.style.display = 'none';
            btnDownload.disabled = false;
            btnDownload.innerHTML = '<i class="fas fa-download"></i> Retry Download';

            statusArea.innerHTML = `
                <div class="win-alert win-alert-danger">
                    <i class="fas fa-times-circle"></i>
                    <div>
                        <h5>Download Interrupted</h5>
                        <p>${args[0] || 'Unknown network error occurred.'}</p>
                    </div>
                </div>
            `;
        }
    });
});
</script>
