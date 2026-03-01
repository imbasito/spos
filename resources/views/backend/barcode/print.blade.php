<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', 'Arial', sans-serif;
            background-color: #f4f6f9; /* Light background for preview */
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            padding: 8px;
        }

        /* Container Size */
        .barcode-wrapper {
            width: {{ $size == 'large' ? '50mm' : '38mm' }};
            height: {{ $size == 'large' ? '30mm' : '25mm' }};
            margin: 20px auto;
            text-align: center;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #fff;
            
            /* Preview Border */
            border: 1px dashed #ccc; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            box-sizing: border-box;
            padding: 1mm;
            position: relative;
        }

        .product-name {
            font-size: {{ $size == 'large' ? '10px' : '9px' }};
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 95%;
            margin-bottom: 2px;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
        }

        .price-tag {
            font-size: {{ $size == 'large' ? '12px' : '11px' }};
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
            font-size: 7px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 1px;
            font-weight: 600;
            padding: 0 2px;
            color: #333;
        }

        /* Print Specifics */
        @media print {
            @page {
                margin: 0;
                padding: 0;
                size: {{ $size == 'large' ? '50mm 30mm' : '38mm 25mm' }};
            }
            body {
                margin: 0;
                padding: 0;
                background-color: #fff;
            }
            .barcode-wrapper {
                border: none;
                box-shadow: none;
                margin: 0;
                width: 100%;
                height: 100%;
                /* Slight adjustment for thermal printer margins */
                padding-top: 1mm; 
            }
            .no-print {
                display: none !important;
            }
        }
        
        /* Interactive Controls */
        .controls {
            text-align: center;
            margin-top: 20px;
            font-family: sans-serif;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0 5px;
        }
        .btn-primary { background: #800000; color: white; } /* Maroon */
        .btn-primary:hover { background: #600000; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body>

    @php
        $labels = [];
        $baseBarcode = preg_replace('/\D/', '', (string) $barcode);
        $baseNumber = is_numeric($baseBarcode) ? (int) $baseBarcode : 0;
        $barcodeLength = max(strlen((string) $baseBarcode), 12);
        for ($i = 0; $i < ($quantity ?? 1); $i++) {
            $labels[] = str_pad((string) ($baseNumber + $i), $barcodeLength, '0', STR_PAD_LEFT);
        }
    @endphp

    <div class="labels-container">
        @foreach($labels as $index => $barcodeValue)
        <div class="barcode-wrapper">
            <div class="product-name">{{ $label }}</div>

            @if(!empty($price) && $price > 0)
            <div class="price-tag">Rs. {{ number_format($price, 0) }}</div>
            @endif

            <svg id="barcode-{{ $index }}" class="barcode-svg" style="width: 100%; height: auto;"></svg>

            @if($size == 'large' && ($mfg || $exp))
            <div class="meta-dates">
                @if($mfg) <span>MFG:{{ \Carbon\Carbon::parse($mfg)->format('d/m/y') }}</span> @endif
                @if($exp) <span>EXP:{{ \Carbon\Carbon::parse($exp)->format('d/m/y') }}</span> @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="no-print controls">
        <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Preview (Check size matches your paper)</p>
        <button id="btnPrint" onclick="window.print()" class="btn btn-primary">
            Print Label
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            Close Config
        </button>
    </div>

    <script>
        var isLarge = "{{ $size }}" === "large";
        var hasPrice = {{ !empty($price) && $price > 0 ? 'true' : 'false' }};
        
        // Fine-tuned for Thermal Printers (203 DPI usually)
        var barcodeHeight = isLarge ? (hasPrice ? 40 : 50) : 30; 
        var barcodeWidth = isLarge ? 1.6 : 1.3;
        var fontSize = isLarge ? 12 : 10;
        
        if (!isLarge) {
            barcodeHeight = 25;
            fontSize = 9;
        }

        const barcodeValues = @json($labels);
        barcodeValues.forEach((value, index) => {
            JsBarcode(`#barcode-${index}`, value, {
                format: "EAN13",
                lineColor: "#000",
                width: barcodeWidth,
                height: barcodeHeight,
                displayValue: true,
                fontSize: fontSize,
                font: "Roboto, Arial, sans-serif",
                textMargin: 0,
                margin: 0,
                flat: true
            });
        });
        
        // Auto-print option layout check
        window.onload = function() {
            // Optional: Auto-trigger print dialog
            // setTimeout(() => window.print(), 500);
        }
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
           if (e.key === 'Enter') {
               e.preventDefault();
               window.print();
           } else if (e.key === 'Escape') {
               e.preventDefault();
               window.close();
           }
        });
    </script>
</body>
</html>
