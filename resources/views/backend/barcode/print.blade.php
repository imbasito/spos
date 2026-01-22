<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif; /* Clean sans-serif for labels */
            background-color: #fff;
        }

        /* Container Size */
        .barcode-wrapper {
            width: {{ $size == 'large' ? '50mm' : '40mm' }};
            height: {{ $size == 'large' ? '30mm' : '20mm' }};
            margin: 0 auto; /* Center on screen */
            text-align: center;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #fff;
            
            /* Border for screen preview only */
            border: 1px dotted #ccc; 
            box-sizing: border-box;
            padding: 1mm;
        }

        .product-name {
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            margin-bottom: 2px;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .price-tag {
            font-size: 14px;
            font-weight: 900;
            color: #000;
            line-height: 1;
            margin-bottom: 2px;
        }

        .barcode-svg {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }

        .meta-dates {
            font-size: 8px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 1px;
            font-weight: 600;
            padding: 0 2px;
        }

        /* Print Specifics */
        @media print {
            @page {
                margin: 0;
                padding: 0;
                size: {{ $size == 'large' ? '50mm 30mm' : '40mm 20mm' }};
            }
            body {
                margin: 0;
                padding: 0;
            }
            .barcode-wrapper {
                border: none; /* Remove preview border */
                margin: 0;
                width: 100%;
                height: 100%;
                page-break-after: always;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body>

    <div class="barcode-wrapper">
        <!-- Product Name -->
        <div class="product-name">{{ $label }}</div>

        <!-- Price (if available) -->
        @if(!empty($price) && $price > 0)
        <div class="price-tag">Rs. {{ number_format($price, 0) }}</div>
        @endif
        
        <!-- Barcode -->
        <svg id="barcode" class="barcode-svg"></svg>

        <!-- Dates (Large Size Only) -->
        @if($size == 'large' && ($mfg || $exp))
        <div class="meta-dates">
            @if($mfg) <span>MFG:{{ \Carbon\Carbon::parse($mfg)->format('dMy') }}</span> @endif
            @if($exp) <span>EXP:{{ \Carbon\Carbon::parse($exp)->format('dMy') }}</span> @endif
        </div>
        @endif
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button id="btnPrint" onclick="printBarcode()" style="padding: 10px 20px; background: #007bff; color: white; border: none; font-weight: bold; cursor: pointer;">Print</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; font-weight: bold; cursor: pointer;">Close</button>
    </div>

    <script>
        var isLarge = "{{ $size }}" === "large";
        var hasPrice = {{ !empty($price) && $price > 0 ? 'true' : 'false' }};
        
        // Dynamic sizing
        var barcodeHeight = isLarge ? (hasPrice ? 25 : 35) : 18; 
        var barcodeWidth = isLarge ? 1.5 : 1.2;
        var fontSize = isLarge ? 12 : 10;
        
        if (!isLarge) {
            barcodeHeight = 22;
            fontSize = 9;
        }

        JsBarcode("#barcode", "{{ $barcode }}", {
            format: "EAN13",
            lineColor: "#000",
            width: barcodeWidth,
            height: barcodeHeight,
            displayValue: true,
            fontSize: fontSize,
            font: "Arial",
            textMargin: 0,
            margin: 0,
            flat: true
        });
        
        function printBarcode() {
            const btn = document.getElementById('btnPrint');
            const originalText = btn.innerText;
            
            if (window.electron && window.electron.printSilent) {
                btn.disabled = true;
                btn.innerText = 'Printing...';
                
                // Use 'tagPrinter' setting if available, else fallback
                const printerName = window.posSettings && window.posSettings.tagPrinter ? window.posSettings.tagPrinter : '';
                
                window.electron.printSilent(window.location.href, printerName)
                    .then(res => {
                        if(!res.success) alert('Print Error: ' + res.error);
                    })
                    .catch(e => alert(e))
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerText = originalText;
                    });
            } else {
                window.print();
            }
        }
        
        window.onload = function() {
            setTimeout(function() {
                printBarcode();
            }, 500);
        }
    </script>
</body>
</html>
