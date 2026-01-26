<form action="{{ route('backend.admin.settings.website.invoice.update') }}" method="post">
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-file-invoice mr-2"></i> Invoice Configuration
            </h5>
            <button type="submit" class="btn btn-light text-maroon font-weight-bold shadow-sm ml-auto">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
        <div class="card-body p-4">
            
            <div class="row">
                <div class="col-md-6 border-right">
                    <h6 class="text-maroon font-weight-bold mb-3 border-bottom pb-2">Header Elements</h6>
                    
                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_logo_invoice" class="mb-0 font-weight-bold">Show Logo</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_logo_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_logo_invoice') == 1 ? 'checked' : '' }} name="is_show_logo_invoice" id="is_show_logo_invoice" value="{{ readConfig('is_show_logo_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_site_invoice" class="mb-0 font-weight-bold">Show Site Name</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_site_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_site_invoice') == 1 ? 'checked' : '' }} name="is_show_site_invoice" id="is_show_site_invoice" value="{{ readConfig('is_show_site_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_phone_invoice" class="mb-0 font-weight-bold">Show Phone Number</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_phone_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_phone_invoice') == 1 ? 'checked' : '' }} name="is_show_phone_invoice" id="is_show_phone_invoice" value="{{ readConfig('is_show_phone_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>
                     <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_email_invoice" class="mb-0 font-weight-bold">Show Email</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_email_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_email_invoice') == 1 ? 'checked' : '' }} name="is_show_email_invoice" id="is_show_email_invoice" value="{{ readConfig('is_show_email_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-maroon font-weight-bold mb-3 border-bottom pb-2">Body & Footer</h6>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_address_invoice" class="mb-0 font-weight-bold">Show Address</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_address_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_address_invoice') == 1 ? 'checked' : '' }} name="is_show_address_invoice" id="is_show_address_invoice" value="{{ readConfig('is_show_address_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    
                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_customer_invoice" class="mb-0 font-weight-bold">Show Customer Info</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_customer_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_customer_invoice') == 1 ? 'checked' : '' }} name="is_show_customer_invoice" id="is_show_customer_invoice" value="{{ readConfig('is_show_customer_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="form-group d-flex align-items-center justify-content-between mb-3 p-2 rounded bg-light">
                        <label for="is_show_note_invoice" class="mb-0 font-weight-bold">Show Footer Note</label>
                        <label class="switch mb-0">
                            <input type="hidden" name="is_show_note_invoice" value="0">
                            <input type="checkbox" onclick="updateCheckboxValue(this)" {{ readConfig('is_show_note_invoice') == 1 ? 'checked' : '' }} name="is_show_note_invoice" id="is_show_note_invoice" value="{{ readConfig('is_show_note_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="col-md-12 mt-3">
                    <div class="form-group">
                        <label class="font-weight-bold">Footer Note Content</label>
                        <textarea class="form-control" name="note_to_customer_invoice" placeholder="Thank you for shopping with us!">{{ readConfig('note_to_customer_invoice') }}</textarea>
                    </div>
                </div>

                 <div class="col-md-12 mt-2">
                    <label class="font-weight-bold text-muted">POS Receipt Width <small>(Managed by System)</small></label>
                    <select name="receiptMaxwidth" class="form-control custom-select" disabled style="background-color: #e9ecef; cursor: not-allowed;">
                        <option value="400px" selected>Professional Standard (80mm) - Fixed</option>
                    </select>
                    <small class="text-muted"><i class="fas fa-lock mr-1"></i> Locked for 1:1 Thermal Image Accuracy</small>
                </div>
            </div>

        </div>
    </div>
</form>
