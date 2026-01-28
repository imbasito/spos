@extends('backend.layouts.receipt-master')
@section('title', 'POS Receipt Preview')
@section('content')

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap');

    /* Thermal Simulator Reset */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      background: #e0e0e0; /* Tabletop look */
      font-family: 'Roboto Mono', monospace; /* Best match for thermal font A */
      color: #000;
      display: flex;
      justify-content: center;
      padding: 20px 0;
    }

    /* The "Paper" */
    .thermal-paper {
      width: 302px; /* 80mm equivalent @ 96dpi */
      background: #fff;
      padding: 10px 15px; /* Simulating the margin-centered look (GS L 36) */
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      position: relative;
      min-height: 400px;
    }

    /* Top Paper Edge */
    .thermal-paper::before {
      content: "";
      position: absolute;
      top: -5px;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.05));
    }

    /* Print Overrides */
    @media print {
      body { background: #fff; padding: 0; }
      .thermal-paper { box-shadow: none; width: 100%; padding: 0; margin: 0; }
      .no-print { display: none !important; }
    }

    /* Typography */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: 700; }
    .uppercase { text-transform: uppercase; }
    
    .size-large { font-size: 22px; line-height: 1.1; margin: 5px 0; }
    .size-normal { font-size: 11.5px; line-height: 1.3; }
    
    /* Elements */
    .logo-container {
        text-align: center;
        margin-bottom: 2px; /* Tight top space */
    }
    .logo-container img {
        max-width: 180px;
        height: auto;
        filter: grayscale(100%) contrast(150%);
    }

    .divider {
      border-top: 1px dashed #000; /* Matching ASCII dash */
      margin: 6px 0;
    }

    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
    }
    .items-table th {
      text-align: left;
      border-bottom: 1px dashed #000;
      padding-bottom: 4px;
      text-transform: uppercase;
    }
    .items-table td {
      padding: 4px 0;
      vertical-align: top;
    }

    .totals-area {
      margin-top: 5px;
      font-size: 11.5px;
    }
    .total-row {
      display: flex;
      justify-content: space-between;
      padding: 2px 0;
    }
    .net-payable {
      border-top: 1px dashed #000;
      border-bottom: 1px dashed #000;
      padding: 8px 0;
      margin: 5px 0;
      font-size: 14px;
      font-weight: 700;
    }

    .footer {
      margin-top: 15px;
      text-align: center;
      font-size: 10px;
      color: #333;
    }

    /* Toolbar for Preview */
    .preview-toolbar {
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 1000;
    }
    .btn-print {
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .btn-print:hover { background: #218838; }
  </style>

  <div class="no-print preview-toolbar">
      <button class="btn-print" onclick="printThermalManual()">
          <i class="fas fa-print"></i> PRINT RECEIPT
      </button>
  </div>

  <div class="thermal-paper">
    <!-- Header -->
    <div class="logo-container">
      @if(readConfig('is_show_logo_invoice'))
        <img src="{{ assetImage(readconfig('site_logo')) }}" alt="Logo">
      @endif
    </div>

    <div class="text-center size-normal">
      @if(readConfig('is_show_site_invoice'))
        <div class="bold size-large uppercase">{{ readConfig('site_name') }}</div>
      @endif
      
      @if(readConfig('is_show_address_invoice'))<div>{{ readConfig('contact_address') }}</div>@endif
      @if(readConfig('is_show_phone_invoice'))<div>Tel: {{ readConfig('contact_phone') }}</div>@endif
      @if(readConfig('is_show_email_invoice'))<div>{{ readConfig('contact_email') }}</div>@endif
      
      <div style="margin-top: 4px;">NTN: 1620237071939</div>
    </div>

    <div class="divider"></div>

    <!-- Metadata -->
    <div class="size-normal">
      <div style="display: flex; justify-content: space-between;">
        <span>Inv: <b>#{{ $order->id }}</b></span>
        <span>Pay: <b>{{ ucfirst($order->transactions->first()->paid_by ?? 'Cash') }}</b></span>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span>{{ date('d-m-Y H:i:s') }}</span>
        <span>Trx: <b>{{ $order->transactions->first()->trx_id ?? '---' }}</b></span>
      </div>
    </div>

    <div class="divider"></div>

    <!-- Customer/Staff -->
    <div class="size-normal" style="display: flex; justify-content: space-between;">
      <span>Cust: <b>{{ $order->customer->name ?? 'Walk-in' }}</b></span>
      <span>STN: <b>{{ Str::limit(auth()->user()->name, 10) }}</b></span>
    </div>

    <div class="divider"></div>

    <!-- Items -->
    <table class="items-table">
      <thead>
        <tr>
          <th width="45%">ITEM</th>
          <th width="10%" class="text-right">QTY</th>
          <th width="20%" class="text-right">PRICE</th>
          <th width="25%" class="text-right">AMT</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order->products as $item)
        <tr>
          <td>{{ $item->product->name }}</td>
          <td class="text-right">x{{ $item->quantity }}</td>
          <td class="text-right">{{ number_format($item->price, 0) }}</td>
          <td class="text-right bold">{{ number_format($item->total, 0) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals -->
    <div class="totals-area">
      <div class="total-row">
        <span>Gross Total:</span>
        <span class="bold">{{ number_format($order->sub_total, 2) }}</span>
      </div>
      <div class="total-row">
        <span>Discount:</span>
        <span>(-{{ number_format($order->discount, 2) }})</span>
      </div>
      
      <div class="net-payable">
        <div class="total-row">
          <span>NET PAYABLE</span>
          <span>{{ number_format($order->total, 2) }}</span>
        </div>
      </div>

      <div class="total-row" style="margin-top: 5px;">
        <span>Paid Amount:</span>
        <span>{{ number_format($order->paid, 2) }}</span>
      </div>

      @if($order->paid > $order->total)
      <div class="total-row bold">
        <span>CHANGE:</span>
        <span>{{ number_format($order->paid - $order->total, 2) }}</span>
      </div>
      @endif

      @if($order->due > 0)
      <div class="total-row bold">
        <span>DUE BALANCE:</span>
        <span>{{ number_format($order->due, 2) }}</span>
      </div>
      @endif
    </div>

    <div class="divider"></div>

    <!-- Footer -->
    <div class="footer">
      @if(readConfig('is_show_note_invoice'))
        <div style="margin-bottom: 5px;">{{ readConfig('note_to_customer_invoice') }}</div>
      @endif
      <div>Software by <b>SINYX</b></div>
      <div>Contact: +92 342 9031328</div>
    </div>

    <!-- Simulated Cut line -->
    <div class="no-print" style="margin-top: 30px; border-top: 2px dashed #ccc; text-align: center; color: #999; font-size: 10px; padding-top: 5px;">
        SCISSOR CUT LINE
    </div>

  </div>

  <script>
    async function printThermalManual() {
        if (window.electron && window.electron.printSilent) {
            window.parent.postMessage({ action: 'print-raw-from-preview' }, '*');
        } else {
            window.print();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Notify parent that receipt is loaded to hide spinner
        window.parent.postMessage('receipt-loaded', '*');
    });

    window.addEventListener('message', (e) => {
        if(e.data === 'close-modal') {
            // handle close if needed
        }
    });
  </script>

@endsection