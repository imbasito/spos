<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Receipt')</title>
    <title>@yield('title', 'Receipt')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            background-color: transparent !important;
        }
        @media print {
            @page {
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
    @stack('style')
</head>
<body class="hold-transition">
    <div class="receipt-root">
        @yield('content')
    </div>
    @stack('script')
</body>
</html>
