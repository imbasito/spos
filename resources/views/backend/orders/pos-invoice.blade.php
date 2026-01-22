@extends('backend.layouts.receipt-master')
@section('title', 'Receipt #'.$order->id)
@section('content')

  <style>
    /* General Receipt Styling */
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
    }

    /* Print Styling */
    @media print {
      @page {
        margin: 0;
        size: 80mm auto; /* Creates a 'Strip' PDF instead of A4 */
      }
      body {
        margin: 0;
        padding: 0;
        background: #fff;
        width: 72mm; /* Content fits safely within 80mm */
      }
      .receipt-container {
        border: none;
        padding: 0;
        margin: 0;
        box-shadow: none;
        width: 100%;
        max-width: 72mm; /* Ensure it fits */
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
      width: 14px;
      height: 14px;
      border: 3px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-right: 5px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
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
  </style>

  <div class="receipt-container" id="printable-section">
    <!-- Header -->
    <div class="text-center">
      @if(readConfig('is_show_logo_invoice'))
      <div class="logo-area">
        <img src="{{ assetImage(readconfig('site_logo')) }}" alt="Logo">
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

    <!-- Order Info -->
    <div class="row" style="display: flex; justify-content: space-between; font-size: 12px;">
      <div class="text-left">
        <strong>Order: #{{ $order->id }}</strong><br>
        Date: {{ date('d-M-Y h:i A') }}<br>
        Cashier: {{ auth()->user()->name }}
      </div>
      <div class="text-right">
        @php
            $transaction = $order->transactions->sortByDesc('id')->first();
        @endphp
        Type: <strong>{{ ucfirst($transaction->paid_by ?? 'Cash') }}</strong>
        @if(!empty($transaction->transaction_id))
        <br>Ref: {{ $transaction->transaction_id }}
        @endif
      </div>
    </div>

    <!-- Customer Info -->
    @if(readConfig('is_show_customer_invoice') && $order->customer)
    <div class="dashed-line"></div>
    <div class="text-left" style="font-size: 12px;">
      Client: {{ $order->customer->name }}<br>
      @if($order->customer->address) Addr: {{ $order->customer->address }}<br> @endif
      @if($order->customer->phone) Phone: {{ $order->customer->phone }} @endif
    </div>
    @endif

    <div class="dashed-line"></div>

    <!-- Items Table -->
    <table>
      <thead>
        <tr>
          <th class="text-left" style="width: 45%;">Item</th>
          <th class="text-center" style="width: 15%;">Qty</th>
          <th class="text-right" style="width: 20%;">Price</th>
          <th class="text-right" style="width: 20%;">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order->products as $item)
        <tr>
          <td class="text-left">{{ $item->product->name }}</td>
          <td class="text-center">{{ $item->quantity }}</td>
          <td class="text-right">{{ number_format($item->discounted_price, 0) }}</td>
          <td class="text-right">{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="dashed-line"></div>

    <!-- Totals -->
    <table style="font-weight: bold;">
      <tr>
        <td class="text-left">Subtotal:</td>
        <td class="text-right">{{ number_format($order->sub_total, 2) }}</td>
      </tr>
      @if($order->discount > 0)
      <tr>
        <td class="text-left">Discount:</td>
        <td class="text-right">-{{ number_format($order->discount, 2) }}</td>
      </tr>
      @endif
      <tr style="font-size: 15px;">
        <td class="text-left">TOTAL:</td>
        <td class="text-right">{{ number_format($order->total, 2) }}</td>
      </tr>
      
      <!-- Payments -->
      <tr>
        <td colspan="2"><div class="dashed-line" style="margin: 3px 0;"></div></td>
      </tr>
      <tr>
        <td class="text-left">Paid:</td>
        <td class="text-right">{{ number_format($order->paid, 2) }}</td>
      </tr>
      @if($order->paid > $order->total)
      <tr>
        <td class="text-left">Change:</td>
        <td class="text-right">{{ number_format($order->paid - $order->total, 2) }}</td>
      </tr>
      @endif
      @if($order->due > 0)
      <tr>
        <td class="text-left">Due Balance:</td>
        <td class="text-right">{{ number_format($order->due, 2) }}</td>
      </tr>
      @endif
    </table>

    <div class="dashed-line"></div>

    <!-- Barcode -->
    <div class="text-center barcode-area">
      <svg id="barcode" style="width: 100%; max-width: 200px; height: 40px;"></svg>
      <div style="font-size: 10px; letter-spacing: 2px;">ORD-{{ str_pad($order->id, 8, '0', STR_PAD_LEFT) }}</div>
    </div>

    <!-- Footer -->
    <div class="text-center">
      @if(readConfig('is_show_note_invoice'))
      <p class="footer-note">{{ readConfig('note_to_customer_invoice') }}</p>
      @endif
      
      <div class="software-credit">
        <strong>Software by SINYX</strong><br>
         Contact: +92 342 9031328
       </div>
     </div>
   </div>
 
   <!-- Action Buttons -->
   <div class="action-buttons no-print">
       <button onclick="printReceipt()" class="btn-action btn-print">Print Receipt</button>
       <button onclick="window.close()" class="btn-action btn-close">Close</button>
   </div>
 
   @push('script')
   <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
   <script>
     function printReceipt() {
        // Safe selection of the print button
        const btn = document.querySelector('.btn-print');
        const originalText = btn ? btn.innerHTML : 'Print Receipt';
        
        // Context Bridge Fallback
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
                        console.error('Print Error: ' + res.error);
                        // Optional: Update button text to show error temp
                        if(btn) btn.innerHTML = '<span style="color:red">Failed</span>';
                    }
                })
                .catch(err => {
                    console.error('System Error: ' + err);
                })
                .finally(() => {
                    if(btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                });
        } else {
             console.warn('Electron API fallbacks failed. Using browser print.');
            window.print();
        }
    }

     document.addEventListener('DOMContentLoaded', function() {
       // SHORTCUTS: Enter=Print, Esc=Close
       document.addEventListener('keydown', function(e) {
           if (e.key === 'Enter') {
               e.preventDefault();
               printReceipt();
           } else if (e.key === 'Escape') {
               e.preventDefault();
               window.close();
           }
       });

       try {
        JsBarcode("#barcode", "ORD{{ str_pad($order->id, 8, '0', STR_PAD_LEFT) }}", {
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
  </script>
  @endpush
@endsection