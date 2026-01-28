@extends('backend.layouts.receipt-master')
@section('title', 'Return Receipt #'.$return->return_number)
@section('content')

  <!-- IMMEDIATE OVERRIDE: Kill the parent spinner -->
  <script>
      (function() {
          try {
              if (window.parent && window.parent.finalizeReceiptLoad) {
                  window.parent.finalizeReceiptLoad();
              }
              if (window.parent && window.parent.postMessage) {
                  window.parent.postMessage('receipt-loaded', '*');
              }
          } catch(e) {}
      })();
  </script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap');

    /* Reset & Base */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: #fff;
      font-family: 'Roboto Mono', monospace;
      font-size: 12px;
      color: #000;
      padding: 0;
    }
    
    body::-webkit-scrollbar { display: none; }

    /* Receipt Container (Thermal 80mm) */
    .receipt-container {
      width: 100%;
      max-width: 80mm;
      margin: 0 auto;
      background: #fff;
      padding: 15px 10px;
    }

    /* Print Overrides */
    @media print {
      body { background: #fff; overflow: visible !important; }
      .receipt-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        border: none;
      }
      .no-print { display: none !important; }
      @page { margin: 0; size: auto; }
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .font-bold { font-weight: 700; }
    .text-uppercase { text-transform: uppercase; }
    .text-xs { font-size: 11px; }

    /* Layout Elements */
    .logo-area img { max-width: 150px; height: auto; margin-bottom: 8px; }
    .header-info { margin-bottom: 12px; }
    
    .divider { border-top: 1px dashed #000; margin: 6px 0; }
    .double-divider { border-top: 2px dashed #000; margin: 8px 0; }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 11px; text-transform: uppercase; padding-bottom: 6px; border-bottom: 1.5px solid #000; }
    td { padding: 6px 0; vertical-align: top; }
    
    .qty-cell { text-align: center; line-height: 1.2; padding-right: 5px; }
    .original-qty { text-decoration: line-through; color: #777; font-size: 10px; display: block; }
    .new-qty { font-weight: bold; font-size: 13px; display: block; }

    /* Column Gaps - Professional Spacing */
    .price-col { padding-right: 20px; padding-left: 10px; }
    .amt-col { padding-left: 15px; }

    .totals-table td { padding: 3px 0; }
    .grand-total { 
        font-size: 16px; 
        font-weight: 700; 
        border-top: 1.5px solid #000; 
        border-bottom: 1.5px solid #000; 
        padding: 8px 0; 
        margin-top: 6px; 
    }
  </style>

  <div class="receipt-container" id="printable-section">
    <!-- Header / Logo -->
    <div class="text-center header-info">
      @if(readConfig('is_show_logo_invoice'))
      <div class="logo-area">
          <img src="{{ assetImage(readConfig('site_logo')) }}" alt="Store Logo">
      </div>
      @endif
      
      @if(readConfig('is_show_site_invoice'))
      <div class="font-bold text-uppercase" style="font-size: 19px; margin-bottom: 2px;">{{ readConfig('site_name') }}</div>
      @endif
      
      <div class="text-xs" style="line-height: 1.4;">
        @if(readConfig('is_show_address_invoice')){{ readConfig('contact_address') }}<br>@endif
        @if(readConfig('is_show_phone_invoice'))Tel: {{ readConfig('contact_phone') }}<br>@endif
        @if(readConfig('is_show_email_invoice')){{ readConfig('contact_email') }}@endif
      </div>
      <div class="text-xs font-bold mt-1">NTN: 1620237071939</div>
    </div>

    <!-- Refund Banner -->
    <div class="text-center font-bold" style="margin: 5px 0; font-size: 12px; letter-spacing: 2px;">==========================================</div>
    <div class="text-center font-bold" style="margin: 2px 0; font-size: 15px;">REVISED REFUND RECEIPT</div>
    <div class="text-center font-bold" style="margin: 5px 0; font-size: 12px; letter-spacing: 2px;">==========================================</div>

    <!-- Metadata -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px;">
      <div class="text-left">
        Refund: <strong>#{{ $return->return_number }}</strong><br>
        Ref Order: #{{ $return->order_id }}
      </div>
      <div class="text-right">
        Date: {{ $return->created_at->format('d/m/Y') }}<br>
        Pay: {{ $return->order->payment_method ?? 'Cash' }}
      </div>
    </div>
    
    <div class="divider"></div>

    <!-- Items Table (MATCHING PHYSICAL RECEIPT LOOP) -->
    <table>
      <thead>
        <tr>
          <th width="45%">RETURNED ITEM</th>
          <th width="20%" class="text-center">QTY</th>
          <th width="15%" class="text-right price-col">PRICE</th>
          <th width="20%" class="text-right amt-col">AMT</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($return->items as $item)
        <tr>
          <td>
            <div style="line-height: 1.2;">
                {{ optional($item->product)->name ?? 'Item' }}
            </div>
          </td>
          <td class="qty-cell">
            <span class="new-qty">x{{ number_format($item->quantity, 0) }}</span>
          </td>
          <td class="text-right price-col">{{ number_format($item->refund_amount / $item->quantity, 2) }}</td>
          <td class="text-right amt-col">
            <span class="new-qty font-bold">{{ number_format($item->refund_amount, 2) }}</span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals Area -->
    <table class="totals-table">
      <tr>
        <td class="text-right" width="60%">Refund Total:</td>
        <td class="text-right font-bold" width="40%">{{ number_format($return->total_refund, 2) }}</td>
      </tr>
    </table>
    
    <div class="divider"></div>
    <div class="text-center font-bold mb-2">ORDER SUMMARY (ADJUSTED)</div>

    <table class="totals-table">
        <tr>
            <td class="text-right" width="60%">Original Total:</td>
            <td class="text-right" width="40%">{{ number_format($return->original_order_total + $return->order->discount, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right">Total Refunded:</td>
            <td class="text-right">{{ number_format($return->order_total_refunded, 2) }}</td>
        </tr>
        <tr class="grand-total" style="border-top: 1.5px solid #000; border-bottom: 1.5px solid #000;">
            <td class="text-left" style="font-size: 13px;">FINAL BALANCE</td>
            <td class="text-right" style="font-size: 16px;">{{ number_format($return->order->total, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>
    
    <!-- Footer Note -->
    @if(readConfig('invoice_description'))
    <div class="text-center text-xs" style="margin-top: 10px;">
        {!! nl2br(readConfig('invoice_description')) !!}
    </div>
    @endif
    
    <div class="text-center font-bold text-xs" style="margin-top: 15px;">Software by SINYX<br>Contact: +92 342 9031328</div>
  </div>

  @push('script')
  <script>
    function notifyParent() {
        try {
            if (window.parent && window.parent.finalizeReceiptLoad) { window.parent.finalizeReceiptLoad(); }
            if (window.parent && window.parent.postMessage) { window.parent.postMessage('receipt-loaded', '*'); }
        } catch(e) {}
    }

    notifyParent();
    document.addEventListener('DOMContentLoaded', notifyParent);
    window.onload = notifyParent;

    function printReceipt() {
       if (window.electron && window.electron.printSilent) {
           window.electron.printSilent(window.location.href);
           return;
       }
       window.print();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            printReceipt();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            try { window.parent.postMessage('close-modal', '*'); } catch(e) {}
        }
    });
  </script>
  @endpush
@endsection
