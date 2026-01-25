@extends('backend.layouts.receipt-master')
@section('title', 'Receipt #'.$order->id)
@section('content')

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap');

    /* Reset & Base */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: #f0f2f5;
      font-family: 'Roboto Mono', monospace;
      font-size: 12px;
      color: #000;
      overflow-y: auto; /* Enable scroll while keeping it hidden via CSS below */
    }
    
    /* Scrollbar Hiding for Webkit */
    body::-webkit-scrollbar { display: none; }

    /* Receipt Container (Thermal 80mm) */
    .receipt-container {
      width: 100%;
      max-width: 80mm;
      margin: 20px auto;
      background: #fff;
      padding: 15px 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Print Overrides */
    @media print {
      body { background: #fff; }
      .receipt-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none;
        border: none;
      }
      .no-print { display: none !important; }
      @page { margin: 0; size: auto; }
    }

    /* Typography helpers */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .font-bold { font-weight: 700; }
    .text-uppercase { text-transform: uppercase; }
    .text-sm { font-size: 11px; }
    .text-xs { font-size: 10px; }

    /* Layout Elements */
    .logo-area img { max-width: 60%; height: auto; margin-bottom: 8px; }
    .header-info { margin-bottom: 15px; }
    
    .divider {
      border-top: 1px dashed #000;
      margin: 8px 0;
    }
    
    .double-divider {
      border-top: 2px dashed #000;
      margin: 10px 0;
    }

    /* Tables */
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 11px; text-transform: uppercase; padding-bottom: 4px; border-bottom: 1px solid #000; }
    td { padding: 4px 0; vertical-align: top; }
    
    .totals-table td { padding: 2px 0; }
    .grand-total { font-size: 16px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 8px 0; margin-top: 5px; }

    .footer { margin-top: 20px; text-align: center; }
    .barcode-container { margin: 15px 0; display: flex; justify-content: center; }
  </style>

  <div class="receipt-container" id="printable-section">
    <!-- Header / Logo -->
    <div class="text-center header-info">
      @if(readConfig('is_show_logo_invoice'))
      <div class="logo-area">
        <img src="{{ assetImage(readconfig('site_logo')) }}" alt="Store Logo">
      </div>
      @endif
      
      @if(readConfig('is_show_site_invoice'))
      <div class="font-bold text-uppercase" style="font-size: 16px; margin-bottom: 4px;">{{ readConfig('site_name') }}</div>
      @endif
      
      <div class="text-xs">
        @if(readConfig('is_show_address_invoice')){{ readConfig('contact_address') }}<br>@endif
        @if(readConfig('is_show_phone_invoice'))Tel: {{ readConfig('contact_phone') }}<br>@endif
        @if(readConfig('is_show_email_invoice')){{ readConfig('contact_email') }}@endif
      </div>
    </div>

    <div class="divider"></div>

    <!-- Order Metadata -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
      <div class="text-left">
        Inv: <strong>#{{ $order->id }}</strong><br>
        {{ date('d/m/Y h:i A') }}
      </div>
      <div class="text-right">
        NTN: <strong>00000000</strong><br>
        @php $transaction = $order->transactions->sortByDesc('id')->first(); @endphp
        Method: <strong>{{ ucfirst(optional($transaction)->paid_by ?? 'Other') }}</strong>
      </div>
    </div>

    <!-- Customer / Staff -->
    <div class="divider"></div>
    <div style="display: flex; justify-content: space-between;">
      <div class="text-left text-sm">
        @if(readConfig('is_show_customer_invoice') && $order->customer)
          <strong>Cust:</strong> {{ $order->customer->name }}
        @else
          <strong>Cust:</strong> Walk-in
        @endif
      </div>
      <div class="text-right text-sm">
        <strong>Station:</strong> {{ Str::limit(auth()->user()->name, 10) }}
      </div>
    </div>

    <div class="double-divider"></div>

    <!-- Items -->
    <table>
      <thead>
        <tr>
          <th width="45%">Item</th>
          <th width="15%" class="text-center">Qty</th>
          <th width="20%" class="text-right">Price</th>
          <th width="20%" class="text-right">Amt</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order->products as $item)
        <tr>
          <td>
            <div style="line-height: 1.2;">{{ $item->product->name }}</div>
          </td>
          <td class="text-center">x{{ $item->quantity }}</td>
          <td class="text-right">{{ number_format($item->price, 2) }}</td>
          <td class="text-right font-bold">{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals -->
    <table class="totals-table">
      <tr>
        <td class="text-right" width="60%">Gross Total:</td>
        <td class="text-right font-bold" width="40%">{{ number_format($order->sub_total, 2) }}</td>
      </tr>
      <tr>
        <td class="text-right">Total Discount:</td>
        <td class="text-right">(-{{ number_format($order->discount, 2) }})</td>
      </tr>

      <tr><td colspan="2" style="height: 5px;"></td></tr>

      <tr class="grand-total">
        <td class="text-left" style="font-size: 14px;">NET PAYABLE</td>
        <td class="text-right" style="font-size: 18px;">{{ number_format($order->total, 2) }}</td>
      </tr>
      
      <tr><td colspan="2" style="height: 5px;"></td></tr>
      
      @if($order->due > 0)
      <tr>
        <td class="text-right text-uppercase" style="color: #000; font-size: 13px;">DUE BALANCE:</td>
        <td class="text-right font-bold" style="font-size: 14px;">{{ number_format($order->due, 2) }}</td>
      </tr>
      @endif

      @if($order->paid > $order->total)
      <tr>
        <td class="text-right text-uppercase" style="font-size: 13px;">CHANGE:</td>
        <td class="text-right font-bold" style="font-size: 14px;">{{ number_format($order->paid - $order->total, 2) }}</td>
      </tr>
      @endif
    </table>

    <div class="divider"></div>

    <!-- Barcode (Moved Up) -->
    <div class="barcode-container">
      <svg id="barcode"></svg>
    </div>

    <!-- Footer Note -->
    @if(readConfig('is_show_note_invoice'))
    <div class="text-center text-sm" style="margin-top: 5px; margin-bottom: 5px;">
      {{ readConfig('note_to_customer_invoice') }}
    </div>
    @endif

    <div class="divider"></div>

    <!-- Software Credit -->
    <div class="text-center text-xs" style="margin-top: 5px; color: #666;">
      Software by <strong>SINYX</strong><br>
      Contact: +92 342 9031328
    </div>

  </div>

  @push('script')
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <script>
    function printReceipt() {
       // Using browser print or Custom Electron Bridge
       if (window.electron && window.electron.printSilent) {
           // Provide feedback?
           window.electron.printSilent(window.location.href);
           return;
       }
       window.print();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Keyboard Shortcuts: Enter to Print, Esc to Close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                printReceipt();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                window.parent.postMessage('close-modal', '*');
                window.close();
            }
        });

        // Generate Barcode
        try {
            JsBarcode("#barcode", "ORD{{ str_pad($order->id, 8, '0', STR_PAD_LEFT) }}", {
                format: "CODE128",
                width: 1.5,
                height: 40,
                displayValue: true,
                fontSize: 12,
                margin: 0
            });
        } catch (e) { console.error(e); }
    });
  </script>
  @endpush
@endsection