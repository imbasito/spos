<form action="{{ route('backend.admin.settings.website.invoice.update') }}" method="post">
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
                                   value="{{ readConfig('tax_gst_enabled') == 1 ? 1 : '0' }}">
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
                                   value="{{ readConfig('tax_show_on_receipt') == 1 ? 1 : '0' }}">
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
                                   value="{{ readConfig('fbr_integration_enabled') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">FBR Environment</label>
                        <select class="form-control custom-select fbr-field" name="fbr_environment" 
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

                    <div class="form-group">
                        <label class="font-weight-bold">FBR API URL</label>
                        <input type="url" class="form-control fbr-field" name="fbr_api_url" 
                               value="{{ readConfig('fbr_api_url') ?? 'https://gw.fbr.gov.pk/imsp/v1/api/' }}" 
                               placeholder="https://gw.fbr.gov.pk/imsp/v1/api/"
                               {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                        <small class="text-muted">FBR will provide this URL upon registration</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">API Key (Username)</label>
                        <input type="text" class="form-control fbr-field" name="fbr_api_key" 
                               value="{{ readConfig('fbr_api_key') }}" 
                               placeholder="Your FBR API key"
                               {{ readConfig('fbr_integration_enabled') != 1 ? 'disabled' : '' }}>
                        <small class="text-muted">Provided by FBR after POS registration</small>
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
                                   value="{{ readConfig('fbr_store_forward', '1') == 1 ? 1 : '0' }}"
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
function toggleFbrFields(enabled) {
    document.querySelectorAll('.fbr-field').forEach(field => {
        field.disabled = !enabled;
    });
    document.getElementById('fbr_store_forward').disabled = !enabled;
}

function toggleSecretVisibility() {
    const secretField = document.getElementById('fbr_api_secret');
    const eyeIcon = document.getElementById('secret-eye-icon');
    if (secretField.type === 'password') {
        secretField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        secretField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>

