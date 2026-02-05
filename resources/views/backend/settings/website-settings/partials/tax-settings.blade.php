<form action="{{ route('backend.admin.settings.website.tax.update') }}" method="post">
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-landmark mr-2"></i> Tax & FBR Settings
            </h5>
            <button type="submit" class="btn btn-light text-maroon font-weight-bold shadow-sm ml-auto">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
        <div class="card-body p-4">
            
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-maroon font-weight-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-id-card mr-1"></i> Business Registration
                    </h6>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">NTN (National Tax Number) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="tax_ntn" 
                               value="{{ readConfig('tax_ntn') }}" 
                               placeholder="e.g., 1234567-8">
                        <small class="text-muted">Your business NTN for tax compliance</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">STRN (Sales Tax Registration Number)</label>
                        <input type="text" class="form-control" name="tax_strn" 
                               value="{{ readConfig('tax_strn') }}" 
                               placeholder="e.g., 3277876543210">
                        <small class="text-muted">Required for GST-registered businesses</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">FBR POS ID</label>
                        <input type="text" class="form-control" name="fbr_pos_id" 
                               value="{{ readConfig('fbr_pos_id') }}" 
                               placeholder="Assigned by FBR">
                        <small class="text-muted">For Tier-1 retailers with FBR integration</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-maroon font-weight-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-percent mr-1"></i> Tax Configuration
                    </h6>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">GST Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="tax_gst_rate" 
                               value="{{ readConfig('tax_gst_rate') ?? '17' }}" 
                               placeholder="17">
                        <small class="text-muted">Standard GST rate in Pakistan is 17%</small>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="tax_gst_enabled" class="mb-0 font-weight-bold">Enable GST on Sales</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="tax_gst_enabled" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" 
                                   {{ readConfig('tax_gst_enabled') == 1 ? 'checked' : '' }} 
                                   name="tax_gst_enabled" id="tax_gst_enabled" 
                                   value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="tax_show_on_receipt" class="mb-0 font-weight-bold">Show Tax on Receipt</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="tax_show_on_receipt" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" 
                                   {{ readConfig('tax_show_on_receipt') == 1 ? 'checked' : '' }} 
                                   name="tax_show_on_receipt" id="tax_show_on_receipt" 
                                   value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <h6 class="text-maroon font-weight-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-plug mr-1"></i> FBR API Integration
                    </h6>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-3 rounded bg-light border">
                        <div>
                            <label for="fbr_integration_enabled" class="mb-0 font-weight-bold">Enable FBR Integration</label>
                            <br><small class="text-muted">Send invoices to FBR in real-time</small>
                        </div>
                        <label class="switch mb-0">
                            <input type="hidden" name="fbr_integration_enabled" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this); toggleFbrFields(this.checked);" 
                                   {{ readConfig('fbr_integration_enabled') == 1 ? 'checked' : '' }} 
                                   name="fbr_integration_enabled" id="fbr_integration_enabled" 
                                   value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Integration Mode</label>
                        <select class="form-control custom-select fbr-field" name="fbr_integration_mode" id="fbr_integration_mode"
                                onchange="toggleIntegrationMode()" 
                                {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                            <option value="direct_api" {{ readConfig('fbr_integration_mode', 'direct_api') == 'direct_api' ? 'selected' : '' }}>
                                🌐 Direct API (Digital Invoicing)
                            </option>
                            <option value="legacy_ims" {{ readConfig('fbr_integration_mode') == 'legacy_ims' ? 'selected' : '' }}>
                                🖥️ Legacy IMS (Fiscal Component)
                            </option>
                        </select>
                        <small class="text-muted">Direct API = Cloud-based (2026), Legacy IMS = Localhost service</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">FBR Environment</label>
                        <select class="form-control custom-select fbr-field" name="fbr_environment" id="fbr_environment"
                                onchange="updateApiUrl()"
                                {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                            <option value="sandbox" {{ readConfig('fbr_environment') == 'sandbox' ? 'selected' : '' }}>
                                🧪 Sandbox (Testing)
                            </option>
                            <option value="production" {{ readConfig('fbr_environment') == 'production' ? 'selected' : '' }}>
                                🚀 Production (Live)
                            </option>
                        </select>
                        <small class="text-muted">Use Sandbox for testing before going live</small>
                    </div>

                    <div class="form-group" id="api_url_group">
                        <label class="font-weight-bold">FBR API URL</label>
                        <input type="url" class="form-control fbr-field" name="fbr_api_url" id="fbr_api_url"
                               value="{{ readConfig('fbr_api_url') ?? 'https://gw.fbr.gov.pk/pdi/v1/api/DigitalInvoicing/PostInvoiceData_v1' }}" 
                               placeholder="Auto-set based on environment"
                               {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                        <small class="text-muted">Auto-filled based on Environment selection</small>
                    </div>

                    {{-- Legacy IMS Endpoint (only shown for legacy_ims mode) --}}
                    <div class="form-group" id="ims_endpoint_group" style="display: none;">
                        <label class="font-weight-bold">IMS Localhost Endpoint</label>
                        <input type="text" class="form-control fbr-field" name="fbr_ims_endpoint"
                               value="{{ readConfig('fbr_ims_endpoint') ?? 'http://localhost:8585' }}" 
                               placeholder="http://localhost:8585"
                               {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                        <small class="text-muted">Local FBR IMS service address (usually http://localhost:8585)</small>
                    </div>
                </div>

                <div class="col-md-6">
                    {{-- Direct API: Bearer Token --}}
                    <div class="form-group" id="bearer_token_group">
                        <label class="font-weight-bold">Security Token (Bearer)</label>
                        <div class="input-group">
                            <textarea class="form-control fbr-field" name="fbr_security_token" id="fbr_security_token"
                                   rows="3" placeholder="Paste your FBR Security Token here (starts with ey...)"
                                   {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>{{ readConfig('fbr_security_token') }}</textarea>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility()">
                                    <i class="fas fa-eye" id="token-eye-icon"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Generated from FBR IRIS portal (valid 5 years)</small>
                    </div>

                    {{-- Legacy IMS: API Key/Secret (hidden by default) --}}
                    <div id="legacy_auth_group" style="display: none;">
                        <div class="form-group">
                            <label class="font-weight-bold">API Key (Username)</label>
                            <input type="text" class="form-control fbr-field" name="fbr_api_key" 
                                   value="{{ readConfig('fbr_api_key') }}" 
                                   placeholder="Your FBR API key"
                                   {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                            <small class="text-muted">For Legacy IMS only</small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">API Secret (Password)</label>
                            <div class="input-group">
                                <input type="password" class="form-control fbr-field" name="fbr_api_secret" 
                                       id="fbr_api_secret"
                                       value="{{ readConfig('fbr_api_secret') }}" 
                                       placeholder="Your FBR API secret"
                                       {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleSecretVisibility()">
                                        <i class="fas fa-eye" id="secret-eye-icon"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Keep this confidential</small>
                        </div>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <div>
                            <label for="fbr_store_forward" class="mb-0 font-weight-bold">Store & Forward Mode</label>
                            <br><small class="text-muted">Queue invoices if internet is down</small>
                        </div>
                        <label class="switch mb-0">
                            <input type="hidden" name="fbr_store_forward" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" 
                                   {{ readConfig('fbr_store_forward', '1') == 1 ? 'checked' : '' }} 
                                   name="fbr_store_forward" id="fbr_store_forward" 
                                   value="1"
                                   {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning mb-0" id="fbr-status-alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-2x mr-3 text-warning"></i>
                            <div>
                                <strong>FBR Integration Status:</strong>
                                @if(readConfig('fbr_integration_enabled') == 1 && readConfig('fbr_api_key'))
                                    <span class="badge badge-success ml-2">Configured</span>
                                    <br><small>Environment: {{ readConfig('fbr_environment') == 'production' ? 'Production' : 'Sandbox' }}</small>
                                @else
                                    <span class="badge badge-secondary ml-2">Not Configured</span>
                                    <br><small>Register at <a href="https://pos.fbr.gov.pk/" target="_blank">pos.fbr.gov.pk</a> to get API credentials</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<script>
// Update checkbox hidden value on toggle
function updateCheckboxValue(checkbox) {
    checkbox.value = checkbox.checked ? 1 : 0;
}

const FBR_URLS = {
    direct_api: {
        sandbox: 'https://esp.fbr.gov.pk:8244/DigitalInvoicing/v1/PostInvoiceData_v1',
        production: 'https://gw.fbr.gov.pk/pdi/v1/api/DigitalInvoicing/PostInvoiceData_v1'
    },
    legacy_ims: {
        sandbox: 'http://localhost:8585',
        production: 'http://localhost:8585'
    }
};

function toggleFbrFields(enabled) {
    document.querySelectorAll('.fbr-field').forEach(field => {
        field.disabled = !enabled;
    });
    document.getElementById('fbr_store_forward').disabled = !enabled;
    if (enabled) toggleIntegrationMode();
}

function toggleIntegrationMode() {
    const mode = document.getElementById('fbr_integration_mode').value;
    const isDirectApi = mode === 'direct_api';
    
    // Show/hide fields based on mode
    document.getElementById('bearer_token_group').style.display = isDirectApi ? 'block' : 'none';
    document.getElementById('legacy_auth_group').style.display = isDirectApi ? 'none' : 'block';
    document.getElementById('api_url_group').style.display = isDirectApi ? 'block' : 'none';
    document.getElementById('ims_endpoint_group').style.display = isDirectApi ? 'none' : 'block';
    
    // Update URL based on mode + environment
    updateApiUrl();
}

function updateApiUrl() {
    const mode = document.getElementById('fbr_integration_mode').value;
    const env = document.getElementById('fbr_environment').value;
    const urlField = document.getElementById('fbr_api_url');
    
    if (FBR_URLS[mode] && FBR_URLS[mode][env]) {
        urlField.value = FBR_URLS[mode][env];
    }
}

function toggleSecretVisibility() {
    const secretField = document.getElementById('fbr_api_secret');
    const eyeIcon = document.getElementById('secret-eye-icon');
    if (secretField && secretField.type === 'password') {
        secretField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else if (secretField) {
        secretField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

function toggleTokenVisibility() {
    const tokenField = document.getElementById('fbr_security_token');
    const eyeIcon = document.getElementById('token-eye-icon');
    if (tokenField.type === 'textarea') {
        tokenField.type = 'password'; // Note: textarea doesn't support type, this is a fallback
    }
    // Toggle visibility via CSS
    tokenField.style.webkitTextSecurity = tokenField.style.webkitTextSecurity === 'disc' ? 'none' : 'disc';
    eyeIcon.classList.toggle('fa-eye');
    eyeIcon.classList.toggle('fa-eye-slash');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleIntegrationMode();
});
</script>

