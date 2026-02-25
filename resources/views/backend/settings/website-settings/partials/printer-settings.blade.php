<form action="{{ route('backend.admin.settings.website.printer.update') }}" method="post" data-ajax-save>
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-print mr-2"></i> Printer Configuration
            </h5>
        </div>
        <div class="card-body p-4">
            
            <div class="alert alert-light border shadow-sm mb-4">
                <h6 class="text-primary font-weight-bold"><i class="fas fa-info-circle mr-1"></i> How it works</h6>
                <p class="mb-0 text-muted small">
                    Select the specific thermal printers for your Receipts and Barcode Tags. 
                    <br>If you leave these as <strong>System Default</strong>, the system will use your computer's default printer.
                </p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-none border bg-light h-100">
                        <div class="card-body text-center">
                            <div class="mb-3 text-maroon">
                                <i class="fas fa-receipt fa-3x"></i>
                            </div>
                            <h6 class="font-weight-bold">Sales Receipt Printer</h6>
                             <small class="text-muted d-block mb-3">For customer invoices & refunds</small>
                            
                            <div class="form-group text-left">
                                <select name="receipt_printer" id="receipt_printer_select" class="form-control custom-select shadow-sm">
                                    <option value="">System Default</option>
                                    @if(readConfig('receipt_printer'))
                                        <option value="{{ readConfig('receipt_printer') }}" selected>{{ readConfig('receipt_printer') }} (Saved)</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-none border bg-light h-100">
                        <div class="card-body text-center">
                            <div class="mb-3 text-maroon">
                                <i class="fas fa-tags fa-3x"></i>
                            </div>
                            <h6 class="font-weight-bold">Barcode Label Printer</h6>
                            <small class="text-muted d-block mb-3">For product adhesive labels</small>

                            <div class="form-group text-left">
                                <select name="tag_printer" id="tag_printer_select" class="form-control custom-select shadow-sm">
                                    <option value="">System Default</option>
                                    @if(readConfig('tag_printer'))
                                        <option value="{{ readConfig('tag_printer') }}" selected>{{ readConfig('tag_printer') }} (Saved)</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <span id="printer_status_msg" class="text-muted font-italic small"><i class="fas fa-spinner fa-spin mr-1"></i> Waiting for check...</span>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 px-4 pb-4 pt-0 d-flex align-items-center">
            <button type="button" class="btn btn-secondary mr-2" onclick="loadPrinters()">
                <i class="fas fa-sync-alt mr-1"></i> Refresh Printers
            </button>
            <button type="submit" class="btn bg-gradient-primary">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
    </div>
</form>
