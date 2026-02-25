<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @yield('title', 'Dashboard') | {{ readConfig('site_name') }}
    </title>

    <!-- FAVICON ICON -->
    <link rel="shortcut icon" href="{{ assetImage(readconfig('favicon_icon')) }}" type="image/svg+xml">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#800000">

    <!-- FAVICON ICON APPLE -->
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="72x72">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="114x114">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="144x144">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- iCheck -->
    <link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- JQVMap -->
    <link rel="stylesheet" href="{{ asset('plugins/jqvmap/jqvmap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{ asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- dropzonejs -->
    <link rel="stylesheet" href="{{ asset('plugins/dropzone/min/dropzone.min.css') }}">
    {{-- datatable --}}
    <link rel="stylesheet" href="{{ asset('assets/css/datatable/datatable.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatable/buttons.dataTables.min.css') }}">
    {{-- custom style --}}
    <link rel="stylesheet" href="{{ asset('css/custom-style.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .image-upload-container {
            border: 2px dashed #8b9ee9;
            /* Dashed border color */
            border-radius: 8px;
            background-color: #f8f9fa;
            /* Light background color */
            display: flex;
            justify-content: center;
            /* Center the content */
            align-items: center;
            /* Center the content vertically */
            width: 100%;
            /* Make the container full width of its parent */
            height: 200px;
            /* Fixed height */
            cursor: pointer;
            /* Indicate clickability */
        }

        .thumb-preview {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            /* Prevents overflow */
        }

        #thumbnailPreview {
            max-width: 100%;
            max-height: 100%;
            /* Ensure it fits within the container */
            object-fit: cover;
            /* Maintain aspect ratio while covering the box */
        }

        .upload-text {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #8b9ee9;
            /* Text color */
            text-align: center;
        }

        .upload-text i {
            font-size: 24px;
            /* Icon size */
            margin-bottom: 5px;
            /* Space between icon and text */
        }
        
        /* ========= SMOOTH PAGE TRANSITIONS ========== */
        .content-wrapper {
            animation: fadeIn 0.15s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Card entrance animation */
        .card {
            animation: cardSlide 0.2s ease-out;
        }
        
        @keyframes cardSlide {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Stagger card animations */
        .card:nth-child(1) { animation-delay: 0s; }
        .card:nth-child(2) { animation-delay: 0.03s; }
        .card:nth-child(3) { animation-delay: 0.06s; }
        .card:nth-child(4) { animation-delay: 0.09s; }
        
        /* Smooth table row hover */
        .table tbody tr {
            transition: background-color 0.15s ease;
        }
        
        /* Button press feedback */
        .btn {
            transition: transform 0.1s ease, box-shadow 0.15s ease;
        }
        
        .btn:active {
            transform: scale(0.97);
        }

        /* ========= PROFESSIONAL APP MODAL ========== */
        .professional-blur.modal-backdrop {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .professional-modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .receipt-iframe {
            width: 100%;
            height: 600px;
            border: none;
            display: block;
        }

        /* ========= POS APP SHELL (Fixed Layout) ========== */
        .pos-app-container {
            height: calc(100vh - 57px);
            overflow: hidden;
            display: flex;
            zoom: 0.9; /* Fixed 90% Zoom for Desktop Experience */
        }

        /* Prevent page-level scroll when POS is active */
        body:has(.pos-app-container) {
            overflow: hidden !important;
            height: 100vh !important;
        }

        /* Custom Scrollbar */
        .custom-scroll {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #adb5bd #f1f1f1;
        }

        /* Hidden Scrollbar while keeping scroll enabled */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
            overflow-y: auto;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 8px; /* Slightly thicker for ease of use */
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f8f9fa;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #ced4da;
            border-radius: 4px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #adb5bd;
        }

        /* Professional Product Grid Styles */
        .pos-product-card {
            transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid transparent; /* Cleaner look */
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            height: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        .pos-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            z-index: 10;
        }
        .pos-product-img-wrapper {
            height: 140px; /* Slightly taller */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            padding: 10px;
            border-bottom: 1px solid #f1f1f1;
        }
        .pos-product-img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s;
        }
        .pos-product-card:hover .pos-product-img {
            transform: scale(1.05); /* Subtle zoom on hover */
        }

        /* POS Premium Refinements */
        .pos-search-group {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #eee;
            background: #fff;
        }
        .pos-search-group:focus-within {
            border-color: #007bff;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.25);
            transform: translateY(-1px);
        }
        .pos-search-input {
            border: none !important;
            font-size: 1.1rem !important;
        }
        .pos-search-icon {
            border: none !important;
            background: #fff !important;
            padding-right: 0 !important;
        }

        .btn-pay-premium {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            border: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            color: white !important;
        }
        .btn-pay-premium:hover:not(:disabled) {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        .btn-pay-premium:active:not(:disabled) {
            transform: scale(0.98);
        }
        .btn-pay-premium::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
            transition: 0.5s;
            pointer-events: none;
        }
        .btn-pay-premium:hover::after {
            left: 120%;
        }

        .empty-state-container {
            padding: 40px;
            background: rgba(255,255,255,0.5);
            border-radius: 20px;
            border: 2px dashed #dee2e6;
            margin-top: 50px;
            text-align: center;
        }
        
        /* BUTTON COLOR FIX */
        .btn-primary, .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: #800000 !important;
            border-color: #800000 !important;
            color: #ffffff !important;
        }
        .btn-success, .btn-success:hover, .btn-success:focus {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: #ffffff !important;
        }
        .btn-danger, .btn-danger:hover, .btn-danger:focus {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
        }
        .btn-info, .btn-info:hover, .btn-info:focus {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
            color: #ffffff !important;
        }
        .btn-warning, .btn-warning:hover, .btn-warning:focus {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #212529 !important;
        }
        
        /* DataTables export buttons (Excel, CSV, PDF, Print) */
        .dt-button, .dt-buttons .btn, .buttons-html5, .buttons-print, .buttons-csv, .buttons-excel, .buttons-pdf {
            background-color: #f8f9fa !important;
            background: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            color: #333333 !important;
        }
        .dt-button:hover, .dt-buttons .btn:hover {
            background-color: #e2e6ea !important;
            background: #e2e6ea !important;
            color: #000000 !important;
        }
        
        /* Custom Apple-style buttons */
        .btn-apple, .btn-apple-primary {
            background-color: #800000 !important;
            background: #800000 !important;
            border-color: #800000 !important;
            color: #ffffff !important;
        }
        .btn-apple:hover, .btn-apple-primary:hover {
            background-color: #5C0000 !important;
            background: #5C0000 !important;
            color: #ffffff !important;
        }
    </style>
    @stack('style')
    @viteReactRefresh
    @vite('resources/js/app.jsx')

    <script>
        window.posSettings = {
            receiptPrinter: @json(readConfig('receipt_printer')),
            tagPrinter: @json(readConfig('tag_printer')),
            siteName: @json(readConfig('site_name')),
            siteLogo: @json(assetImage(readConfig('site_logo')))
        };
    </script>
</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <x-simple-alert />

    <div class="wrapper">

        <!-- Professional Top Loading Bar -->
        <div id="page-loading-bar" style="display: none;">
            <div class="loading-bar-progress"></div>
        </div>
        <style>
            #page-loading-bar {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 3px;
                z-index: 9999;
                background: rgba(0,0,0,0.1);
            }
            .loading-bar-progress {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, var(--primary-color, #800000), #ff4444);
                animation: loadingProgress 1.5s ease-in-out infinite;
                box-shadow: 0 0 10px rgba(128, 0, 0, 0.5);
            }
            @keyframes loadingProgress {
                0% { width: 0%; margin-left: 0%; }
                50% { width: 30%; margin-left: 35%; }
                100% { width: 0%; margin-left: 100%; }
            }
            
            /* Sidebar Glass Effect on Hover */
            .main-sidebar {
                transition: all 0.3s ease;
            }
            .main-sidebar:hover {
                backdrop-filter: blur(20px);
                background: rgba(255, 255, 255, 0.95) !important;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }
        </style>
        <script>
            // Show loading bar on page navigation
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (link && link.href && !link.href.includes('#') && !link.target && !e.ctrlKey && !e.metaKey) {
                    document.getElementById('page-loading-bar').style.display = 'block';
                }
            });
            // Hide on page load
            window.addEventListener('load', function() {
                document.getElementById('page-loading-bar').style.display = 'none';
            });
            
            // Global Keyboard Shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+B: Toggle Sidebar
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    document.body.classList.toggle('sidebar-collapse');
                    localStorage.setItem('apple_sidebar_collapsed', document.body.classList.contains('sidebar-collapse'));
                }
                
                // Ctrl+K: Open Spotlight Search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    document.getElementById('sinyx-spotlight').style.display = 'flex';
                    setTimeout(() => document.getElementById('spotlight-input').focus(), 100);
                }
            });
            
            // Spotlight trigger button click
            document.addEventListener('DOMContentLoaded', function() {
                const spotlightTrigger = document.getElementById('spotlight-trigger');
                if (spotlightTrigger) {
                    spotlightTrigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        document.getElementById('sinyx-spotlight').style.display = 'flex';
                        setTimeout(() => document.getElementById('spotlight-input').focus(), 100);
                    });
                }
            });
        </script>

        <!-- Navbar -->
        @include('backend.layouts.navbar')
        <!-- /.navbar -->
        
        <!-- Spotlight Search -->
        @include('components.spotlight')

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar elevation-4 sidebar-light-lightblue">
            <!-- Brand Logo -->
            <a href="{{ route('backend.admin.dashboard') }}" class="brand-link text-center" style="display: flex; flex-direction: column; align-items: center; padding: 15px 5px; padding-top: 50px; height: auto !important; border-bottom: 1px solid #dee2e6;">
                <img src="{{ assetImage(readconfig('site_logo')) }}" alt="Logo"
                    style="border-radius: 4px; object-fit: cover; width: 120px; height: auto; margin-bottom: 10px;">
                <span class="brand-text font-weight-bold" style="font-size: 15px; word-wrap: break-word; text-align: center; line-height: 1.2; color: #D4AF37 !important; text-shadow: 0.5px 0.5px 1px rgba(0,0,0,0.1);">{{ readConfig('site_name') }}</span>
            </a>

            <!-- Sidebar -->
            @include('backend.layouts.sidebar')
            <!-- /.sidebar -->

        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                @yield('title')
                            </h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->


            <!-- Main content -->
            <section class="content">
                <!-- container-fluid -->
                <div class="container-fluid">

                    <!-- content -->
                    @yield('content')
                    <!-- /.content -->

                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.Main content -->
        </div>
        <!-- /.content-wrapper -->

        @include('backend.layouts.footer')

    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- ChartJS -->
    <script src="{{ asset('plugins/chart.js/Chart.min.js') }}"></script>
    <!-- Sparkline -->
    <script src="{{ asset('plugins/sparklines/sparkline.js') }}"></script>
    <!-- JQVMap -->
    <script src="{{ asset('plugins/jqvmap/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
    <!-- jQuery Knob Chart -->
    <script src="{{ asset('plugins/jquery-knob/jquery.knob.min.js') }}"></script>
    <!-- daterangepicker -->
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- Summernote -->
    <script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
    <!-- overlayScrollbars -->
    <script src="{{ asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    {{-- custom script --}}
    <script src="{{ asset('js/custom-script.js') }}"></script>
    <!-- dropzonejs -->
    <script src="{{ asset('plugins/dropzone/min/dropzone.min.js') }}"></script>

    {{-- datatable --}}
    <script src="{{ asset('assets/js/datatable/datatable.js') }}"></script>
    <script src="{{ asset('assets/js/datatable/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatable/dataTables.buttons.min.js') }}"></script>

    <!-- Fullscreen Persistence - Remember state across page navigation -->
    <script>
        (function() {
            const FULLSCREEN_KEY = 'pos_fullscreen_enabled';
            
            // Check if we should be in fullscreen (saved from previous page)
            function shouldBeFullscreen() {
                return localStorage.getItem(FULLSCREEN_KEY) === 'true';
            }
            
            // Enter fullscreen
            function enterFullscreen() {
                const elem = document.documentElement;
                if (elem.requestFullscreen) {
                    elem.requestFullscreen().catch(err => console.log('Fullscreen error:', err));
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen();
                }
            }
            
            // Save fullscreen state when changed
            document.addEventListener('fullscreenchange', function() {
                const isFullscreen = !!document.fullscreenElement;
                localStorage.setItem(FULLSCREEN_KEY, isFullscreen);
                
                // Update icon
                const icon = document.querySelector('[data-widget="fullscreen"] i');
                if (icon) {
                    icon.className = isFullscreen ? 'fas fa-compress-arrows-alt' : 'fas fa-expand-arrows-alt';
                }
            });
            
            // Restore fullscreen on page load
            document.addEventListener('DOMContentLoaded', function() {
                if (shouldBeFullscreen()) {
                    // Small delay to ensure page is ready
                    setTimeout(enterFullscreen, 100);
                }
            });
            
            // F11 shortcut to toggle fullscreen
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F11') {
                    e.preventDefault();
                    const fullscreenBtn = document.querySelector('[data-widget="fullscreen"]');
                    if (fullscreenBtn) fullscreenBtn.click();
                }
            });
        })();
    </script>

    @stack('script')
    <script>
        // FORCE UNREGISTER to fix caching issues for new menu items
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                } 
            });
        }
    </script>
</body>

</html>