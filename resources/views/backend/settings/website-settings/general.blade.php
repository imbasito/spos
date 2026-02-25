@extends('backend.master')

@section('title', 'General Settings')

@push('style')
<style>
/* ── Premium Settings Styles ─────────────────────────────────────── */
.settings-search-bar .input-group-text { background: #fff; border-right: none; border-radius: 10px 0 0 10px; }
.settings-search-bar .form-control     { border-left: none; border-radius: 0 10px 10px 0; }

.custom-settings-nav .nav-link {
    color: #495057;
    font-weight: 600;
    padding: 13px 18px;
    border-bottom: 1px solid #f4f6f9;
    transition: all 0.25s ease;
    border-radius: 0;
}
.custom-settings-nav .nav-link:hover {
    background-color: #f8f9fa;
    color: #800000;
    padding-left: 24px;
}
.custom-settings-nav .nav-link.active {
    background-color: #800000;
    color: #ffffff !important;
    border-left: 4px solid #FFD700;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.custom-settings-nav .nav-link i { width: 22px; text-align: center; }
.custom-settings-nav .nav-link.filtered-out { display: none; }



/* Spinner overlay on save */
.saving-overlay {
    pointer-events: none;
    opacity: 0.7;
}

.bg-gradient-maroon { background: linear-gradient(45deg, #800000, #A01010) !important; }
.text-maroon { color: #800000 !important; }
.btn-apple-primary {
    background-color: #800000 !important;
    border-color: #800000 !important;
    color: #ffffff !important;
    font-weight: 600;
    padding: 10px 25px;
    border-radius: 8px;
    transition: all 0.2s;
}
.btn-apple-primary:hover {
    background-color: #600000 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(128,0,0,0.2);
}
.border-radius-15 { border-radius: 15px; }
.border-radius-10 { border-radius: 10px; }
.apple-card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
</style>
@endpush

@section('content')

{{-- ── Global Settings Search ──────────────────────────────────────────────── --}}
<div class="row mb-3 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="settings-search-bar input-group shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="input-group-prepend">
                <span class="input-group-text border-0 pl-3" style="background: #fff;">
                    <i class="fas fa-search text-maroon"></i>
                </span>
            </div>
            <input type="text" id="settingsGlobalSearch"
                   class="form-control border-0 apple-input py-3"
                   placeholder="Search all settings here."
                   autocomplete="off"
                   style="font-size: 1rem; box-shadow: none;">
            <div class="input-group-append">
                <span class="input-group-text border-0 pr-3 text-muted small" style="background: #fff;">
                    <kbd style="border-radius: 5px; background: #eee; color: #555; font-size: .7rem;">Ctrl+/</kbd>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row animate__animated animate__fadeIn">
    {{-- ── Left Navigation ──────────────────────────────────────────────────── --}}
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
            <div class="card-header bg-gradient-maroon py-3">
                <h5 class="card-title text-white font-weight-bold mb-0">
                    <i class="fas fa-cogs mr-2"></i> Settings
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="nav flex-column nav-pills custom-settings-nav" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">

                    @can('website_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'website-info' ? 'active' : '' }}"
                       id="vert-tabs-1" data-toggle="pill" href="#tabs-1" role="tab"
                       data-tab="website-info" data-keywords="website title url meta description keywords site name">
                        <i class="fas fa-desktop mr-2"></i> Website Info
                    </a>
                    @endcan

                    @can('contact_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'contacts' ? 'active' : '' }}"
                       id="vert-tabs-2" data-toggle="pill" href="#tabs-2" role="tab"
                       data-tab="contacts" data-keywords="address phone mobile fax email working hours contact">
                        <i class="fas fa-address-book mr-2"></i> Contacts
                    </a>
                    @endcan

                    @can('socials_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'social-links' ? 'active' : '' }}"
                       id="vert-tabs-3" data-toggle="pill" href="#tabs-3" role="tab"
                       data-tab="social-links" data-keywords="facebook twitter linkedin youtube instagram whatsapp pinterest snapchat social">
                        <i class="fas fa-share-alt mr-2"></i> Social Links
                    </a>
                    @endcan

                    @can('style_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'style-settings' ? 'active' : '' }}"
                       id="vert-tabs-4" data-toggle="pill" href="#tabs-4" role="tab"
                       data-tab="style-settings" data-keywords="logo favicon branding visuals icon image upload newsletter">
                        <i class="fas fa-swatchbook mr-2"></i> Branding &amp; Visuals
                    </a>
                    @endcan

                    @can('custom_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'custom-css' ? 'active' : '' }}"
                       id="vert-tabs-5" data-toggle="pill" href="#tabs-5" role="tab"
                       data-tab="custom-css" data-keywords="custom css style code stylesheet">
                        <i class="fas fa-code mr-2"></i> Custom CSS
                    </a>
                    @endcan

                    @can('notification_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'notification-settings' ? 'active' : '' }}"
                       id="vert-tabs-6" data-toggle="pill" href="#tabs-6" role="tab"
                       data-tab="notification-settings" data-keywords="notification email alert contact messages comments">
                        <i class="fas fa-bell mr-2"></i> Notifications
                    </a>
                    @endcan

                    @can('website_status_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'website-status' ? 'active' : '' }}"
                       id="vert-tabs-7" data-toggle="pill" href="#tabs-7" role="tab"
                       data-tab="website-status" data-keywords="maintenance mode live offline close message status">
                        <i class="fas fa-power-off mr-2"></i> Maintenance Mode
                    </a>
                    @endcan

                    @can('invoice_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'invoice-settings' ? 'active' : '' }}"
                       id="vert-tabs-8" data-toggle="pill" href="#tabs-8" role="tab"
                       data-tab="invoice-settings" data-keywords="invoice receipt logo show hide header footer note customer address phone">
                        <i class="fas fa-file-invoice mr-2"></i> Invoice Config
                    </a>
                    @endcan

                    @can('website_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'printer-settings' ? 'active' : '' }}"
                       id="vert-tabs-9" data-toggle="pill" href="#tabs-9" role="tab"
                       data-tab="printer-settings" data-keywords="printer receipt barcode label tag thermal usb">
                        <i class="fas fa-print mr-2"></i> Printer Config
                    </a>
                    @endcan

                    @can('invoice_settings')
                    <a class="nav-link settings-tab-link {{ $activeTab === 'tax-settings' ? 'active' : '' }}"
                       id="vert-tabs-10" data-toggle="pill" href="#tabs-10" role="tab"
                       data-tab="tax-settings" data-keywords="tax fbr gst ntn strn api integration token environment production sandbox rate">
                        <i class="fas fa-landmark mr-2"></i> Tax &amp; FBR
                    </a>
                    @endcan

                </div>
            </div>
        </div>
    </div>

    {{-- ── Right Content Area ───────────────────────────────────────────────── --}}
    <div class="col-lg-9 col-md-8">

        {{-- Flash success (fallback for file-upload non-AJAX saves) --}}
        @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px;">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
        @endif

        {{-- Search result hint --}}
        <div id="searchResultHint" class="alert alert-info border-0 shadow-sm py-2 small d-none" style="border-radius: 10px;">
            <i class="fas fa-lightbulb mr-1"></i>
            <span id="searchHintText"></span>
        </div>

        <div class="tab-content" id="vert-tabs-tabContent">

            @can('website_settings')
            <div class="tab-pane fade {{ $activeTab === 'website-info' ? 'active show' : '' }}" id="tabs-1" role="tabpanel" aria-labelledby="vert-tabs-1">
                @include('backend.settings.website-settings.partials.website-info')
            </div>
            @endcan

            @can('contact_settings')
            <div class="tab-pane fade {{ $activeTab === 'contacts' ? 'active show' : '' }}" id="tabs-2" role="tabpanel" aria-labelledby="vert-tabs-2">
                @include('backend.settings.website-settings.partials.contacts')
            </div>
            @endcan

            @can('socials_settings')
            <div class="tab-pane fade {{ $activeTab === 'social-links' ? 'active show' : '' }}" id="tabs-3" role="tabpanel" aria-labelledby="vert-tabs-3">
                @include('backend.settings.website-settings.partials.social-links')
            </div>
            @endcan

            @can('style_settings')
            <div class="tab-pane fade {{ $activeTab === 'style-settings' ? 'active show' : '' }}" id="tabs-4" role="tabpanel" aria-labelledby="vert-tabs-4">
                @include('backend.settings.website-settings.partials.style-settings')
            </div>
            @endcan

            @can('custom_settings')
            <div class="tab-pane fade {{ $activeTab === 'custom-css' ? 'active show' : '' }}" id="tabs-5" role="tabpanel" aria-labelledby="vert-tabs-5">
                @include('backend.settings.website-settings.partials.custom-css')
            </div>
            @endcan

            @can('notification_settings')
            <div class="tab-pane fade {{ $activeTab === 'notification-settings' ? 'active show' : '' }}" id="tabs-6" role="tabpanel" aria-labelledby="vert-tabs-6">
                @include('backend.settings.website-settings.partials.notification-settings')
            </div>
            @endcan

            @can('website_status_settings')
            <div class="tab-pane fade {{ $activeTab === 'website-status' ? 'active show' : '' }}" id="tabs-7" role="tabpanel" aria-labelledby="vert-tabs-7">
                @include('backend.settings.website-settings.partials.website-status')
            </div>
            @endcan

            @can('invoice_settings')
            <div class="tab-pane fade {{ $activeTab === 'invoice-settings' ? 'active show' : '' }}" id="tabs-8" role="tabpanel" aria-labelledby="vert-tabs-8">
                @include('backend.settings.website-settings.partials.invoice-settings')
            </div>
            @endcan

            @can('website_settings')
            <div class="tab-pane fade {{ $activeTab === 'printer-settings' ? 'active show' : '' }}" id="tabs-9" role="tabpanel" aria-labelledby="vert-tabs-9">
                @include('backend.settings.website-settings.partials.printer-settings')
            </div>
            @endcan

            @can('invoice_settings')
            <div class="tab-pane fade {{ $activeTab === 'tax-settings' ? 'active show' : '' }}" id="tabs-10" role="tabpanel" aria-labelledby="vert-tabs-10">
                @include('backend.settings.website-settings.partials.tax-settings')
            </div>
            @endcan

        </div>
    </div>
</div>

{{-- Toast container (dynamically injected by JS, mirrors simple-alert.blade.php) --}}
<div id="settings-toast-container"></div>

@endsection

@push('script')
<script>
(function () {
    'use strict';

    // ── CSRF header for AJAX ────────────────────────────────────────────────
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ── Toast helper — mirrors simple-alert.blade.php exactly ─────────────
    function showToast(message, success) {
        // Build the same HTML structure + classes as simple-alert.blade.php
        var bgClass  = success ? 'bg-success' : 'bg-danger';
        var svgIcon  = success
            ? '<svg height="25" width="25" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><circle cx="64" cy="64" r="64" fill="#fff"/><path d="M54.3 97.2 24.8 67.7c-.4-.4-.4-1 0-1.4l8.5-8.5c.4-.4 1-.4 1.4 0L55 78.1l38.2-38.2c.4-.4 1-.4 1.4 0l8.5 8.5c.4.4.4 1 0 1.4L55.7 97.2c-.4.4-1 .4-1.4 0z" fill="#28a745"/></svg>'
            : '<svg height="25" width="25" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#fff"/><path d="M14.5 25h3v-3h-3v3zm0-19v13h3V6h-3z" fill="#DC3545"/></svg>';

        var toastHtml =
            '<div style="position:fixed;top:1rem;right:1rem;z-index:9999;"' +
            '     class="toast align-items-center ' + bgClass + ' border-0 text-white"' +
            '     role="alert" aria-live="assertive" data-delay="3000">' +
            '  <div class="toast-body d-flex align-items-center gap-2">' +
            '    ' + svgIcon +
            '    <span class="ml-2">' + message + '</span>' +
            '  </div>' +
            '</div>';

        var $container = $('#settings-toast-container');
        $container.html(toastHtml);

        var el = $container.find('.toast')[0];
        el.addEventListener('hidden.bs.toast', function () { $container.empty(); });

        var toastInstance = new bootstrap.Toast(el);
        toastInstance.show();
    }

    // ── AJAX form save (all non-file-upload tabs) ───────────────────────────
    $(document).on('submit', 'form[data-ajax-save]', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn  = $form.find('[type=submit]');
        var origHtml = $btn.html();

        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving…');

        $.ajax({
            url    : $form.attr('action'),
            method : 'POST',
            data   : $form.serialize(),
            success: function (res) {
                showToast(res.message || 'Settings updated successfully', true);
            },
            error  : function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                var msg    = errors
                    ? Object.values(errors).flat().join(' ')
                    : ((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
                showToast(msg, false);
            },
            complete: function () {
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // ── Global Settings Search ──────────────────────────────────────────────
    // Searches tab labels + their data-keywords for a match.
    // When found, clicks the matching tab and scrolls it into view.

    let searchDebounce = null;

    $('#settingsGlobalSearch').on('input', function () {
        const query = $(this).val().trim().toLowerCase();
        clearTimeout(searchDebounce);

        if (!query) {
            $('#settingsGlobalSearch').css('border-color', '');
            $('#searchResultHint').addClass('d-none');
            return;
        }

        searchDebounce = setTimeout(function () {
            let matched = null;

            $('.settings-tab-link').each(function () {
                const label    = $(this).text().trim().toLowerCase();
                const keywords = ($(this).data('keywords') || '').toLowerCase();
                if ((label.includes(query) || keywords.includes(query)) && !matched) {
                    matched = $(this);
                }
            });

            if (matched) {
                $('#settingsGlobalSearch').css('border-color', '#28a745');
                matched[0].click();
                matched[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                $('#searchHintText').text('Jumped to: "' + matched.text().trim() + '"');
                $('#searchResultHint').removeClass('d-none');
            } else {
                $('#settingsGlobalSearch').css('border-color', '#dc3545');
                $('#searchHintText').text('No setting found for "' + query + '"');
                $('#searchResultHint').removeClass('d-none');
            }
        }, 300);
    });

    // Clear search on Escape
    $('#settingsGlobalSearch').on('keydown', function (e) {
        if (e.key === 'Escape' || e.key === 'Enter') {
            $(this).val('').trigger('input');
            if (e.key === 'Enter') $(this).blur();
        }
    });

    // Hotkey: Ctrl+/
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            document.getElementById('settingsGlobalSearch').focus();
        }
    });

    // ── Maintenance mode toggle (show/hide close message) ──────────────────
    $('input[type=radio][name=is_live]').on('change', function () {
        if (this.value == '0') {
            $('#close_msg_div').removeClass('d-none').addClass('animate__animated animate__fadeIn');
        } else {
            $('#close_msg_div').addClass('d-none');
        }
    });

    // ── Printer Discovery ───────────────────────────────────────────────────
    window.loadPrinters = function () {
        const statusMsg    = document.getElementById('printer_status_msg');
        const receiptSelect = document.getElementById('receipt_printer_select');
        const tagSelect    = document.getElementById('tag_printer_select');

        if (!statusMsg || !receiptSelect || !tagSelect) return;

        const currentReceipt = String(@json(readConfig('receipt_printer')) ?? '');
        const currentTag     = String(@json(readConfig('tag_printer')) ?? '');

        statusMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning for connected printers...';
        statusMsg.className = 'text-primary font-italic small';

        if (window.electron && window.electron.getPrinters) {
            window.electron.getPrinters().then(prList => {
                if (!prList.length) {
                    statusMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No printers found.';
                    statusMsg.className = 'text-warning font-weight-bold small';
                    return;
                }

                const populate = (sel, curVal) => {
                    sel.innerHTML = '<option value="">System Default</option>';
                    let found = false;
                    prList.forEach(p => {
                        const opt  = document.createElement('option');
                        opt.value  = p.name;
                        opt.text   = p.name + (p.isDefault ? ' (OS Default)' : '');
                        if (p.name.trim() === curVal.trim()) { opt.selected = true; found = true; }
                        sel.appendChild(opt);
                    });
                    if (curVal && !found) {
                        const opt = document.createElement('option');
                        opt.value = curVal; opt.text = curVal + ' (Saved – Not Found)';
                        opt.selected = true; opt.style.color = 'red';
                        sel.appendChild(opt);
                    }
                    sel.value = curVal;
                };

                populate(receiptSelect, currentReceipt);
                populate(tagSelect, currentTag);
                statusMsg.innerHTML = '<i class="fas fa-check-circle"></i> Loaded ' + prList.length + ' printers.';
                statusMsg.className = 'text-success font-weight-bold small';
            }).catch(err => {
                statusMsg.innerHTML = '<i class="fas fa-times-circle"></i> Service unavailable: ' + (err.message || err);
                statusMsg.className = 'text-danger small';
            });
        } else {
            statusMsg.innerHTML = '<i class="fas fa-desktop"></i> Desktop App required for scanning.';
            statusMsg.className = 'text-muted small';
        }
    };

    function checkPrinterSelection(sel) {
        const val = sel.value.toLowerCase();
        if (val.includes('pdf') || val.includes('xps') || val.includes('one note')) {
            alert('⚠️ WARNING: PDF/Virtual Printers may hang POS Silent Printing.');
        }
    }

    const rp = document.getElementById('receipt_printer_select');
    const tp = document.getElementById('tag_printer_select');
    if (rp) rp.addEventListener('change', function () { checkPrinterSelection(this); });
    if (tp) tp.addEventListener('change', function () { checkPrinterSelection(this); });

    document.addEventListener('DOMContentLoaded', function () {
        loadPrinters();
    });

})();
</script>
@endpush