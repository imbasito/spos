@extends('backend.master')

@section('title', 'General Settings')

@section('content')

<div class="row animate__animated animate__fadeIn">
    <!-- Left Navigation -->
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
            <div class="card-header bg-gradient-maroon py-3">
                <h5 class="card-title text-white font-weight-bold mb-0">
                    <i class="fas fa-cogs mr-2"></i> Settings Menu
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="nav flex-column nav-pills custom-settings-nav" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">
                    
                    @can('website_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'website-info' ? 'active' : '' }}" id="vert-tabs-1"
                        data-toggle="pill" href="#tabs-1" role="tab" aria-controls="tabs-1" aria-selected="true">
                        <i class="fas fa-desktop mr-2"></i> Website Info
                    </a>
                    @endcan

                    @can('contact_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'contacts' ? 'active' : '' }}" id="vert-tabs-2"
                        data-toggle="pill" href="#tabs-2" role="tab" aria-controls="tabs-2" aria-selected="false">
                        <i class="fas fa-address-book mr-2"></i> Contacts
                    </a>
                    @endcan

                    @can('socials_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'social-links' ? 'active' : '' }}" id="vert-tabs-3"
                        data-toggle="pill" href="#tabs-3" role="tab" aria-controls="tabs-3" aria-selected="false">
                        <i class="fas fa-share-alt mr-2"></i> Social Links
                    </a>
                    @endcan

                    @can('style_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'style-settings' ? 'active' : '' }}" id="vert-tabs-4"
                        data-toggle="pill" href="#tabs-4" role="tab" aria-controls="tabs-4" aria-selected="false">
                        <i class="fas fa-swatchbook mr-2"></i> Branding & Visuals
                    </a>
                    @endcan

                    @can('custom_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'custom-css' ? 'active' : '' }}" id="vert-tabs-5"
                        data-toggle="pill" href="#tabs-5" role="tab" aria-controls="tabs-5" aria-selected="false">
                        <i class="fas fa-code mr-2"></i> Custom CSS
                    </a>
                    @endcan

                    @can('notification_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'notification-settings' ? 'active' : '' }}" id="vert-tabs-6"
                        data-toggle="pill" href="#tabs-6" role="tab" aria-controls="tabs-6" aria-selected="false">
                        <i class="fas fa-bell mr-2"></i> Notifications
                    </a>
                    @endcan

                    @can('website_status_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'website-status' ? 'active' : '' }}" id="vert-tabs-7"
                        data-toggle="pill" href="#tabs-7" role="tab" aria-controls="tabs-7" aria-selected="false">
                        <i class="fas fa-power-off mr-2"></i> Maintenance Mode
                    </a>
                    @endcan

                    @can('invoice_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'invoice-settings' ? 'active' : '' }}" id="vert-tabs-8"
                        data-toggle="pill" href="#tabs-8" role="tab" aria-controls="tabs-8" aria-selected="false">
                        <i class="fas fa-file-invoice mr-2"></i> Invoice Config
                    </a>
                    @endcan

                    @can('website_settings')
                    <a class="nav-link {{ @$_GET['active-tab'] == 'printer-settings' ? 'active' : '' }}" id="vert-tabs-9"
                        data-toggle="pill" href="#tabs-9" role="tab" aria-controls="tabs-9" aria-selected="false">
                        <i class="fas fa-print mr-2"></i> Printer Config
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Right Content Area -->
    <div class="col-lg-9 col-md-8">
        <div class="tab-content" id="vert-tabs-tabContent">
            
            @can('website_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'website-info' ? 'active show' : '' }}" id="tabs-1" role="tabpanel" aria-labelledby="vert-tabs-1">
                @include('backend.settings.website-settings.partials.website-info')
            </div>
            @endcan

            @can('contact_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'contacts' ? 'active show' : '' }}" id="tabs-2" role="tabpanel" aria-labelledby="vert-tabs-2">
                @include('backend.settings.website-settings.partials.contacts')
            </div>
            @endcan

            @can('socials_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'social-links' ? 'active show' : '' }}" id="tabs-3" role="tabpanel" aria-labelledby="vert-tabs-3">
                 @include('backend.settings.website-settings.partials.social-links')
            </div>
            @endcan

            @can('style_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'style-settings' ? 'active show' : '' }}" id="tabs-4" role="tabpanel" aria-labelledby="vert-tabs-4">
                 @include('backend.settings.website-settings.partials.style-settings')
            </div>
            @endcan

            @can('custom_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'custom-css' ? 'active show' : '' }}" id="tabs-5" role="tabpanel" aria-labelledby="vert-tabs-5">
                 @include('backend.settings.website-settings.partials.custom-css')
            </div>
            @endcan

            @can('notification_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'notification-settings' ? 'active show' : '' }}" id="tabs-6" role="tabpanel" aria-labelledby="vert-tabs-6">
                 @include('backend.settings.website-settings.partials.notification-settings')
            </div>
            @endcan

            @can('website_status_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'website-status' ? 'active show' : '' }}" id="tabs-7" role="tabpanel" aria-labelledby="vert-tabs-7">
                 @include('backend.settings.website-settings.partials.website-status')
            </div>
            @endcan

            @can('invoice_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'invoice-settings' ? 'active show' : '' }}" id="tabs-8" role="tabpanel" aria-labelledby="vert-tabs-8">
                 @include('backend.settings.website-settings.partials.invoice-settings')
            </div>
            @endcan

            @can('website_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'printer-settings' ? 'active show' : '' }}" id="tabs-9" role="tabpanel" aria-labelledby="vert-tabs-9">
                 @include('backend.settings.website-settings.partials.printer-settings')
            </div>
            @endcan

        </div>
    </div>
</div>

<style>
    /* Premium Vertical Tabs Styling */
    .custom-settings-nav .nav-link {
        color: #495057;
        font-weight: 600;
        padding: 15px 20px;
        border-bottom: 1px solid #f4f6f9;
        transition: all 0.3s ease;
        border-radius: 0;
    }
    .custom-settings-nav .nav-link:hover {
        background-color: #f8f9fa;
        color: #800000;
        padding-left: 25px; /* Slide effect */
    }
    .custom-settings-nav .nav-link.active {
        background-color: #800000;
        color: #ffffff !important;
        border-left: 5px solid #FFD700;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .custom-settings-nav .nav-link i {
        width: 25px;
        text-align: center;
    }
    
    .bg-gradient-maroon {
        background: linear-gradient(45deg, #800000, #A01010) !important;
    }
    .text-maroon {
        color: #800000 !important;
    }
    .btn-light {
        background: #fff;
        border: 1px solid #dee2e6;
    }
    .btn-light:hover {
        background: #f8f9fa;
        color: #600000 !important;
    }
    .border-radius-15 { border-radius: 15px; }
    .border-radius-10 { border-radius: 10px; }
</style>
@endsection

@push('script')
<script>
    $('input[type=radio][name=is_live]').on("change", function() {
        if (this.value == '0') {
            $("#close_msg_div").removeClass('d-none').addClass('animate__animated animate__fadeIn');
        } else {
            $("#close_msg_div").addClass('d-none');
        }
    });

    // Printer Discovery Logic (Preserved from original)
    function loadPrinters() {
        // ... (Existing printer logic is perfectly fine, handled in partial script if needed, or we keep here)
        const statusMsg = document.getElementById('printer_status_msg');
        const receiptSelect = document.getElementById('receipt_printer_select');
        const tagSelect = document.getElementById('tag_printer_select');
        
        if (!statusMsg || !receiptSelect || !tagSelect) return;

        const currentReceipt = String(@json(readConfig('receipt_printer')) || "");
        const currentTag = String(@json(readConfig('tag_printer')) || "");

        statusMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning for connected printers...';
        statusMsg.className = 'text-primary font-italic small';

        if (window.electron && window.electron.getPrinters) {
            window.electron.getPrinters().then(prList => {
                if (prList.length === 0) {
                     statusMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No printers found.';
                     statusMsg.className = 'text-warning font-weight-bold small';
                     return;
                }
                const populateSelect = (selectEl, currentVal) => {
                    selectEl.innerHTML = '<option value="">System Default</option>';
                    let foundCurrent = false;

                    prList.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.name;
                        let label = p.name;
                        if (p.isDefault) label += ' (OS Default)';
                        opt.textContent = label;
                        if (p.name.trim() === currentVal.trim()) {
                            opt.selected = true;
                            foundCurrent = true;
                        }
                        selectEl.appendChild(opt);
                    });

                    if (currentVal && !foundCurrent) {
                        const opt = document.createElement('option');
                        opt.value = currentVal;
                        opt.textContent = currentVal + ' (Saved - Not Found)';
                        opt.selected = true;
                        opt.style.color = 'red';
                        selectEl.appendChild(opt);
                    }
                    selectEl.value = currentVal;
                };

                populateSelect(receiptSelect, currentReceipt);
                populateSelect(tagSelect, currentTag);

                statusMsg.innerHTML = `<i class="fas fa-check-circle"></i> Loaded ${prList.length} printers.`;
                statusMsg.className = 'text-success font-weight-bold small';

            }).catch(err => {
                console.error(err);
                statusMsg.innerHTML = `<i class="fas fa-times-circle"></i> Service unavailable: ${err.message || err}`;
                statusMsg.className = 'text-danger small';
            });
        } else {
            statusMsg.innerHTML = '<i class="fas fa-desktop"></i> Desktop App required for scanning.';
            statusMsg.className = 'text-muted small';
        }
    }
    
    // PDF Warning
    function checkPrinterSelection(select) {
        const val = select.value.toLowerCase();
        if (val.includes('pdf') || val.includes('xps') || val.includes('one note')) {
             alert("⚠️ WARNING: PDF/Virtual Printers may hang POS Silent Printing.");
        }
    }
    
    // Attach Listeners
    if(document.getElementById('receipt_printer_select')) {
        document.getElementById('receipt_printer_select').addEventListener('change', function() { checkPrinterSelection(this); });
        document.getElementById('tag_printer_select').addEventListener('change', function() { checkPrinterSelection(this); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPrinters();
    });
</script>
@endpush