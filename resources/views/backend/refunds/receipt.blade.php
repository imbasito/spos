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
      <div class="font-bold text-uppercase" style="font-size: 17px; margin-bottom: 3px;">{{ readConfig('site_name') }}</div>
      @endif
      
      <div class="text-xs">
        @if(readConfig('is_show_address_invoice')){{ readConfig('contact_address') }}<br>@endif
        @if(readConfig('is_show_phone_invoice'))Tel: {{ readConfig('contact_phone') }}<br>@endif
        @if(readConfig('is_show_email_invoice')){{ readConfig('contact_email') }}@endif
      </div>
    </div>

    <div class="divider"></div>

    <!-- Refund Badge -->
    <div class="text-center" style="margin: 12px 0;">
      <span style="border: 2px solid #000; padding: 6px 15px; font-weight: bold; font-size: 14px; text-transform: uppercase;">Revised Refund Receipt</span>
    </div>

    <!-- Metadata -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px;">
      <div class="text-left">
        Return: <strong>#{{ $return->return_number }}</strong><br>
        Date: {{ $return->created_at->format('d/m/Y h:i A') }}
      </div>
      <div class="text-right">
        Ref Inv: #{{ $return->order_id }}<br>
        Staff: {{ Str::limit(optional($return->processedBy)->name ?? 'Admin', 10) }}
      </div>
    </div>

    <!-- Customer -->
    @if($return->order->customer)
    <div class="divider"></div>
    <div class="text-left text-sm" style="margin-bottom: 5px;">
      <strong>Customer:</strong> {{ $return->order->customer->name }}
    </div>
    @endif

    <div class="double-divider"></div>

    <!-- Items Table -->
    <table>
      <thead>
        <tr>
          <th width="40%">Item</th>
          <th width="20%" class="text-center">Qty</th>
          <th width="20%" class="text-right price-col">Price</th>
          <th width="20%" class="text-right amt-col">Amt</th>
        </tr>
      </thead>
      <tbody>
        @php 
            $thisReturnItemIds = $return->items->pluck('order_product_id')->toArray(); 
            $allReturnedQuantities = \App\Models\ReturnItem::whereIn('return_id', $return->order->returns->pluck('id'))
                ->groupBy('order_product_id')
                ->selectRaw('order_product_id, sum(quantity) as total_qty')
                ->pluck('total_qty', 'order_product_id')
                ->toArray();
        @endphp
        @foreach ($return->order->products as $item)
        @php
            $totalReturnedQty = isset($allReturnedQuantities[$item->id]) ? $allReturnedQuantities[$item->id] : 0;
            $unitPrice = $item->quantity > 0 ? ($item->total / $item->quantity) : 0;
            
            $newQuantity = $item->quantity - $totalReturnedQty;
            $hasReturn = ($totalReturnedQty > 0);
            $isFullyReturned = ($newQuantity <= 0);
        @endphp
        <tr>
          <td>
            <div style="line-height: 1.2; {{ $isFullyReturned ? 'text-decoration: line-through; color: #777;' : '' }}">
                {{ optional($item->product)->name ?? 'Item' }}
            </div>
          </td>
          <td class="qty-cell">
            @if($hasReturn)
                <span class="original-qty">{{ number_format($item->quantity, 0) }}</span>
                @if(!$isFullyReturned)
                    <span class="new-qty">{{ number_format($newQuantity, 0) }}</span>
                @endif
            @else
                <span class="new-qty">{{ number_format($item->quantity, 0) }}</span>
            @endif
          </td>
          <td class="text-right price-col">{{ number_format($unitPrice, 0) }}</td>
          <td class="text-right amt-col">
            @if($hasReturn)
                <span class="original-qty">{{ number_format($item->total, 2) }}</span>
                @if(!$isFullyReturned)
                    <span class="new-qty font-bold">{{ number_format($unitPrice * $newQuantity, 2) }}</span>
                @endif
            @else
                <span class="new-qty font-bold">{{ number_format($item->total, 2) }}</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals Area -->
    <table class="totals-table">
      <tr>
        <td class="text-right" width="60%">Gross Sale Value:</td>
        <td class="text-right font-bold" width="40%">{{ number_format($return->original_order_total + $return->order->discount, 2) }}</td>
      </tr>
      @if($return->order->discount > 0)
      <tr>
        <td class="text-right">Original Discount:</td>
        <td class="text-right">(-{{ number_format($return->order->discount, 2) }})</td>
      </tr>
      @endif
      
      <tr style="color: #d9534f;">
        <td class="text-right">Cumulative Returns:</td>
        <td class="text-right font-bold">-{{ number_format($return->order_total_refunded, 2) }}</td>
      </tr>

      <tr class="grand-total">
        <td class="text-left" style="font-size: 14px;">ADJUSTED SALE TOTAL</td>
        <td class="text-right" style="font-size: 18px;">{{ number_format($return->order->total, 2) }}</td>
      </tr>
    </table>

    <div class="divider"></div>

    <!-- Software Credit -->
    <div class="text-center text-xs" style="margin-top: 15px; color: #666;">
      Software by <strong>SINYX</strong><br>
      Contact: +92 342 9031328
    </div>
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
