<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Receipt #{{ $return->return_number }}</title>
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
            overflow-x: hidden;
        }
        
        body::-webkit-scrollbar { display: none; }

        /* Receipt Container (Thermal 80mm) */
        .receipt-container {
            width: 100%;
            max-width: 80mm; /* Standard POS Width */
            margin: 0 auto;
            background: #fff;
            padding: 15px 10px;
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
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; padding-bottom: 6px; border-bottom: 1.5px solid #000; }
        td { padding: 6px 0; vertical-align: top; }
        
        .qty-cell { text-align: center; line-height: 1.2; padding-right: 5px; }
        .new-qty { font-weight: bold; font-size: 13px; display: block; }

        .price-col { padding-right: 20px; padding-left: 10px; }
        .amt-col { padding-left: 15px; }

        .grand-total { 
            font-size: 16px; 
            font-weight: 700; 
            border-top: 1.5px solid #000; 
            border-bottom: 1.5px solid #000; 
            padding: 8px 0; 
            margin-top: 6px; 
        }
    </style>
</head>
<body>

  <div class="receipt-container">
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
      @if(readConfig('tax_ntn'))
      <div class="text-xs font-bold mt-1">NTN: {{ readConfig('tax_ntn') }}</div>
      @endif
      @if(readConfig('tax_strn'))
      <div class="text-xs font-bold">STRN: {{ readConfig('tax_strn') }}</div>
      @endif
    </div>

    <!-- Refund Banner -->
    <div class="text-center font-bold" style="margin: 5px 0; font-size: 12px; letter-spacing: 2px;">==========================================</div>
    <div class="text-center font-bold" style="margin: 2px 0; font-size: 15px;">REFUND RECEIPT</div>
    <div class="text-center font-bold" style="margin: 5px 0; font-size: 12px; letter-spacing: 2px;">==========================================</div>

    <!-- Metadata -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px;">
      <div class="text-left">
        Return: <strong>#{{ $return->return_number }}</strong><br>
        Order: #{{ $return->order_id }}
      </div>
      <div class="text-right">
        Date: {{ $return->created_at->format('d/m/Y') }}<br>
        Pay: {{ $return->order->payment_method ?? 'Cash' }}
      </div>
    </div>
    
    <div class="divider"></div>

    <!-- Items Table -->
    <table>
      <thead>
        <tr>
          <th width="45%">ITEM</th>
          <th width="20%" class="text-center">QTY</th>
          <th width="15%" class="text-right price-col">PRICE</th>
          <th width="20%" class="text-right amt-col">AMT</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($return->items as $item)
        @php
            $unitPrice = ($item->quantity > 0) ? round($item->refund_amount / $item->quantity, 2) : 0;
            $displayQty = number_format($item->quantity, 2);
        @endphp
        <tr>
          <td>
            <div style="line-height: 1.2;">
                {{ optional($item->product)->name ?? 'Item' }}
            </div>
          </td>
          <td class="qty-cell">
            <span class="new-qty">x{{ $displayQty }}</span>
          </td>
          <td class="text-right price-col">{{ number_format($unitPrice, 2) }}</td>
          <td class="text-right amt-col">
            <span class="new-qty font-bold">{{ number_format($item->refund_amount, 2) }}</span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals -->
    <table>
      <tr>
        <td class="text-right" width="60%">Refund Total:</td>
        <td class="text-right font-bold" width="40%">{{ number_format($return->total_refund, 2) }}</td>
      </tr>
    </table>
    
    <div class="divider"></div>
    <div class="text-center font-bold mb-2" style="font-size: 11px; letter-spacing: 1px;">TRANSACTION SUMMARY</div>

    <table style="font-size: 11px;">
        <tr>
            <td class="text-right" width="60%">Original Order Total:</td>
            <td class="text-right font-bold" width="40%">{{ number_format($return->order->sub_total, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td class="text-left" style="font-size: 13px;">TOTAL REFUND</td>
            <td class="text-right" style="font-size: 16px; color: #d00;">-{{ number_format($return->order_total_refunded, 2) }}</td>
        </tr>
        <tr style="border-top: 1.5px solid #000; padding-top: 6px;">
            <td class="text-left font-bold" style="font-size: 13px; padding-top: 8px;">ADJUSTED TOTAL</td>
            <td class="text-right font-bold" style="font-size: 16px; padding-top: 8px;">{{ number_format($return->order->total, 2) }}</td>
        </tr>
        @if($return->order->due > 0)
        <tr>
            <td class="text-right text-xs" style="color: #666; padding-top: 4px;">Customer Due:</td>
            <td class="text-right text-xs" style="color: #d00; padding-top: 4px;">{{ number_format($return->order->due, 2) }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>
    
    @php
        // Calculate cash back information
        $cashBack = 0;
        $debtCleared = 0;
        
        // Get current due and total refunded
        $currentDue = $return->order->due;
        $totalRefunded = $return->order_total_refunded;
        
        // Logic: If there's no debt now, the refund was returned as cash
        if ($currentDue <= 0) {
            $cashBack = $totalRefunded;
        } else {
            // There's still debt, so refund went to clearing debt
            $debtCleared = $totalRefunded;
        }
    @endphp
    
    @if($cashBack > 0)
    <div class="text-center font-bold" style="margin: 8px 0; padding: 8px; background: #f0f0f0; border-radius: 4px;">
        CASH RETURNED: <span style="font-size: 15px; color: #d00;">{{ number_format($cashBack, 2) }}</span>
    </div>
    @endif
    
    @if($debtCleared > 0 && $currentDue > 0)
    <div class="text-center text-xs" style="margin: 4px 0; color: #666;">
        (Refund applied to outstanding balance)
    </div>
    @endif
    
    <div class="text-center text-xs" style="margin-top: 12px; line-height: 1.4;">
        Processed by: <strong>{{ optional($return->processedBy)->name ?? 'Admin' }}</strong><br>
        Date: {{ $return->created_at->format('d M Y, h:i A') }}
    </div>
    
    <div class="divider"></div>
    
    <div class="text-center text-xs" style="margin-top: 8px; line-height: 1.5;">
        Thank you for your understanding.<br>
        Please keep this receipt for your records.
    </div>
    
    <div class="text-center font-bold text-xs" style="margin-top: 15px;">Software by SINYX<br>Contact: +92 342 9031328</div>
  </div>

  <script>
    // Notify Parent to Hide Spinner
    (function() {
        try {
            if (window.parent) {
                if (window.parent.finalizeRefundReceiptLoad) window.parent.finalizeRefundReceiptLoad();
                window.parent.postMessage('receipt-loaded', '*');
            }
        } catch(e) { console.error('Parent notify failed', e); }
    })();
  </script>
</body>
</html>
