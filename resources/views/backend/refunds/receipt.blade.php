@extends('backend.layouts.receipt-master')
@section('title', 'Return Receipt #'.$return->return_number)
@section('content')

  <style>
    /* General Receipt Styling - Matching POS Invoice */
    .receipt-container {
      width: 100%;
      max-width: 78mm; /* Standard 80mm thermal paper safe width */
      margin: 0 auto;
      background: #fff;
      font-family: 'Courier New', Courier, monospace; /* Monospace for perfect alignment */
      font-size: 13px;
      color: #000;
      line-height: 1.2;
    }
    
    /* Screen-Only Styling (Box effect) */
    @media screen {
      .receipt-container {
        border: 1px dotted #ccc;
        padding: 10px;
        margin-top: 10px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
      }
      .action-buttons {
          max-width: 78mm;
          margin: 10px auto;
          display: flex;
          gap: 10px;
      }
      .btn-action {
          flex: 1;
          padding: 8px;
          border: none;
          cursor: pointer;
          font-weight: bold;
          color: #fff;
          font-family: sans-serif;
          font-size: 14px;
          text-align: center;
      }
      .btn-print { background: #007bff; }
      .btn-close { background: #6c757d; }
    }

    /* Print Styling */
    @media print {
      @page {
        margin: 0;
        size: auto;
      }
      body {
        margin: 0;
        padding: 0;
        background: #fff;
      }
      .receipt-container {
        border: none;
        padding: 0;
        margin: 0;
        box-shadow: none;
        width: 100%;
        max-width: 100%; /* Allow full width of paper */
      }
      .no-print {
        display: none !important;
      }
    }

    /* Helpers */
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    .text-uppercase { text-transform: uppercase; }
    
    /* Separators */
    .dashed-line {
      border: none;
      border-top: 1px dashed #000;
      margin: 6px 0;
      height: 1px;
      width: 100%;
      display: block;
    }

    /* Table Styling */
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 3px 0; vertical-align: top; }
    th { border-bottom: 1px dashed #000; font-weight: bold; text-align: inherit; }

    /* Specific Sections */
    .logo-area img { max-width: 80%; height: auto; display: block; margin: 0 auto 5px; }
    .shop-name { font-size: 16px; font-weight: bold; margin: 5px 0 0; }
    .meta-info { font-size: 11px; margin-bottom: 5px; }
    
    .barcode-area { margin: 10px 0; }
    .footer-note { font-size: 11px; margin-top: 10px; }
    .software-credit { font-size: 10px; margin-top: 5px; border-top: 1px solid #000; padding-top: 4px; }
    
    /* Loading Spinner */
    .spinner {
      display: inline-block;
      width: 12px;
      height: 12px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-right: 5px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>

  <div class="receipt-container" id="printable-section">
    <!-- Header -->
    <div class="text-center">
      @if(readConfig('is_show_logo_invoice') && readConfig('site_logo'))
      <div class="logo-area">
        <img src="{{ assetImage(readConfig('site_logo')) }}" alt="Logo">
      </div>
      @endif
      
      @if(readConfig('is_show_site_invoice'))
      <div class="shop-name">{{ readConfig('site_name') }}</div>
      @endif
      
      <div class="meta-info">
        @if(readConfig('is_show_address_invoice')){{ readConfig('contact_address') }}<br>@endif
        @if(readConfig('is_show_phone_invoice')){{ readConfig('contact_phone') }}<br>@endif
        @if(readConfig('is_show_email_invoice')){{ readConfig('contact_email') }}@endif
      </div>
    </div>

    <div class="dashed-line"></div>
    
    <div class="text-center" style="margin: 10px 0;">
        <span style="border: 1px dashed #000; padding: 6px 12px; font-weight: bold; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">REFUND RECEIPT</span>
    </div>
    <div class="dashed-line"></div>

    <!-- Return Info -->
    <div class="row" style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 5px;">
      <div class="text-left">
        <strong>Return: #{{ $return->return_number }}</strong><br>
        Ref Order: #{{ $return->order_id }}<br>
        User: {{ optional($return->processedBy)->name ?? 'User' }}
      </div>
      <div class="text-right">
        Date: {{ $return->created_at->format('d-M-Y') }}<br>
        Time: {{ $return->created_at->format('h:i A') }}
      </div>
    </div>

    <!-- Customer Info -->
    @if(readConfig('is_show_customer_invoice') && optional(optional($return->order)->customer)->name)
    <div class="dashed-line"></div>
    <div class="text-left" style="font-size: 11px;">
      Client: {{ optional($return->order)->customer->name }}<br>
      @if(optional($return->order->customer)->phone) Phone: {{ $return->order->customer->phone }} @endif
    </div>
    @endif

    <div class="dashed-line"></div>

    <!-- Items Table -->
    <table style="margin-bottom: 5px;">
      <thead>
        <tr>
          <th class="text-left" style="width: 45%;">Item (Returned)</th>
          <th class="text-center" style="width: 15%;">Qty</th>
          <th class="text-right" style="width: 40%;">Refund</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($return->items as $item)
        <tr>
          <!-- Strikethrough requested by user -->
          <td class="text-left" style="text-decoration: line-through;">{{ optional($item->product)->name ?? 'Item' }}</td>
          <td class="text-center">{{ (float)$item->quantity }}</td>
          <td class="text-right" style="text-decoration: line-through;">{{ number_format($item->refund_amount, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="dashed-line"></div>

    <!-- Totals -->
    <table style="font-weight: bold;">
      @if(optional($return->order)->total)
      <tr>
        <td class="text-left">Original Total:</td>
        <td class="text-right" style="text-decoration: line-through;">{{ number_format($return->order->total, 2) }}</td>
      </tr>
      @endif
      
      <tr>
        <td class="text-left">Refund Amount:</td>
        <td class="text-right">-{{ number_format($return->total_refund, 2) }}</td>
      </tr>
      
      @if(optional($return->order)->total)
      <tr style="font-size: 14px; border-top: 1px dashed #000;">
        <td class="text-left" style="padding-top: 5px;">NEW TOTAL:</td>
        <td class="text-right" style="padding-top: 5px;">{{ number_format($return->order->total - $return->total_refund, 2) }}</td>
      </tr>
      @endif
    </table>
    
    <!-- Reason -->
    @if($return->reason)
    <div class="dashed-line"></div>
    <div class="text-left" style="font-size: 11px;">
        <strong>Reason:</strong> {{ $return->reason }}
    </div>
    @endif

    <div class="dashed-line"></div>

    <!-- Footer -->
    <div class="text-center">
      <div class="software-credit">
        <strong>Software by SINYX</strong><br>
        Contact: +92 342 9031328
      </div>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="action-buttons no-print">
      <button onclick="printReceipt()" class="btn-action btn-print">Print Refund</button>
      <button onclick="window.close()" class="btn-action btn-close">Close</button>
  </div>

  @push('script')
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      try {
        JsBarcode("#barcode", "{{ $return->return_number }}", {
          format: "CODE128",
          width: 1.5,
          height: 35,
          displayValue: false,
          margin: 0
        });
      } catch (e) {
        console.error('Barcode error:', e);
      }
    });

    function printReceipt() {
        // Safe selection of the print button
        const btn = document.querySelector('.btn-print');
        // Store original text
        const originalText = btn ? btn.innerHTML : 'Print Refund';
        
        // Context Bridge Fallback: Check local window first, then opener (parent)
        const electronApp = window.electron || (window.opener && window.opener.electron);
        const settings = window.posSettings || (window.opener && window.opener.posSettings) || {};
        
        if (electronApp && electronApp.printSilent) {
             if(btn) {
                 btn.disabled = true;
                 btn.innerHTML = '<span class="spinner"></span> Printing...';
             }
             
             const printerName = settings.receiptPrinter ? settings.receiptPrinter : '';
             
             electronApp.printSilent(window.location.href, printerName)
                .then(res => {
                    if (!res.success) {
                        alert('Print Error: ' + res.error);
                    }
                })
                .catch(err => {
                    alert('System Error: ' + err);
                })
                .finally(() => {
                    if(btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                });
        } else {
            console.warn('Electron API not found in popup or opener. Falling back to browser print.');
            window.print();
        }
    }
  </script>
  @endpush
@endsection
