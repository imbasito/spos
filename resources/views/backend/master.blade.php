<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

    {{-- APPLE DESIGN SYSTEM (Core) --}}
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}?v={{ time() }}">




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
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Card entrance animation */
        .card {
            animation: cardSlide 0.2s ease-out;
        }
        
        @keyframes cardSlide {
            from { opacity: 0; }
            to { opacity: 1; }
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

        /* ========= POS APP SHELL (Apple Layout) ========== */
        .pos-app-container {
            height: calc(100vh - 57px);
            overflow: hidden;
            display: flex;
            background-color: var(--system-gray-6);
            zoom: 0.95; /* Premium Sharpness */
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
            border: 1px solid rgba(0,0,0,0.03); 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            height: 100%;
            background: #ffffff;
            border-radius: 12px;
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

        /* ========= APPLE-STANDARD SIDEBAR OVERHAUL ========== */
        .main-sidebar {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(40px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(40px) saturate(180%) !important;
            border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: none !important;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1) !important;
            overflow-x: hidden !important;
        }
        /* Only force 250px when NOT collapsed (or when hovering if mini) */
        body:not(.sidebar-collapse) .main-sidebar,
        body.sidebar-mini.sidebar-collapse .main-sidebar:hover {
            width: 250px !important;
        }

        .main-sidebar::before {
            background: transparent !important;
            box-shadow: none !important;
        }




        /* Removed Logo White Box */
        .brand-link, .main-sidebar .brand-link {
            background: transparent !important;
            border: none !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04) !important;
            padding: 25px 5px !important;
            display: flex !important;
            flex-direction: column;
            align-items: center;
            box-shadow: none !important;
            height: auto !important;
        }
        
        .brand-link img {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }



        .brand-link img.brand-image-refined {
            width: 100px !important;
            height: auto !important;
            border-radius: 8px !important;
            margin-bottom: 12px !important;
            background: rgba(255, 255, 255, 0.4) !important;
            padding: 8px !important;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(0,0,0,0.05) !important;
        }

        .nav-sidebar .nav-item {

            margin: 4px 12px !important; /* Spacing for pills */
        }

        /* Force high contrast for tabs */
        .nav-sidebar .nav-link {
            border-radius: 10px !important; /* Pill shape */
            padding: 10px 16px !important;
            color: #111 !important; /* Pitch black for visibility */
            transition: all 0.2s ease !important;
            border: 1px solid transparent !important;
            font-weight: 500 !important;
        /* SOFT PASTEL SIDEBAR */
        .main-sidebar, 
        .sidebar,
        .brand-link {
            background-color: #fcfcfc !important; /* Slightly dimmer than pure white */
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        
        /* High Contrast Active State */
        .nav-sidebar .nav-link.active {
            background-color: #800000 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(128, 0, 0, 0.2) !important;
        }

        .main-sidebar {
            border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: none !important;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            opacity: 1 !important;
        }

        /* Proximity Reveal Physics */
        .sidebar-hover-trigger {
            position: fixed;
            top: 0;
            left: 0;
            width: 20px;
            height: 100vh;
            z-index: 1039;
            background: transparent;
        }

        /* Brand Text Consistency */
        .brand-text {
            color: #111 !important;
            font-size: 14px !important;
            letter-spacing: 0.5px;
            margin-top: 10px;
            font-weight: 600 !important;
        }
    </style>
    @stack('style')
    
    {{-- High-Priority Apple Global Design System --}}
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}?v={{ time() }}">

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
        <!-- Sidebar Proximity Sentinel -->
        <div class="sidebar-hover-trigger"></div>
        
        <script>
            // Sidebar Logic: Proximity Reveal & Focus Mode
            (function() {
                const isPos = window.location.pathname.includes('/admin/cart'); 
                if (isPos) {
                    document.body.classList.add('pos-focus-mode');
                    document.body.classList.add('sidebar-collapse');
                } 
                // Don't force-open on other pages; let AdminLTE remember state or default
                // else { document.body.classList.remove('sidebar-collapse'); }

                // High-Performance Proximity Detector
                const trigger = document.querySelector('.sidebar-hover-trigger');
                
                document.addEventListener('mousemove', function(e) {
                    // Only active if sidebar is collapsed (POS Mode or Manual)
                    if (document.body.classList.contains('sidebar-collapse')) {
                        if (e.pageX < 20) {
                            // Reveal
                            document.body.classList.add('sidebar-open');
                        } else if (e.pageX > 280 && document.body.classList.contains('sidebar-open')) {
                            // Dismiss
                            document.body.classList.remove('sidebar-open');
                        }
                    }
                });

                // Auto-close when clicking outside on mobile or tablet
                document.addEventListener('click', function(e) {
                    if (document.body.classList.contains('sidebar-open') && e.pageX > 260) {
                        document.body.classList.remove('sidebar-open');
                    }
                });

                // 2. Sidebar Toggle Shortcut (Ctrl+B)
                document.addEventListener('keydown', function(event) {
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'b') {
                        event.preventDefault();
                        const btn = document.querySelector('[data-widget="pushmenu"]');
                        if(btn) {
                            btn.click();
                        } else {
                            document.body.classList.toggle('sidebar-collapse');
                        }
                    }
                });
            })();
        </script>

        <!-- Navbar -->
        @include('backend.layouts.navbar')

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-light-lightblue elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('backend.admin.dashboard') }}" class="brand-link">
                <img src="{{ assetImage(readconfig('site_logo')) }}" alt="Logo" class="brand-image-refined">
                <span class="brand-text">{{ readConfig('site_name') }}</span>
            </a>

            <!-- Sidebar -->
            @include('backend.layouts.sidebar')
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('title')</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div id="initial-skeleton">
                        <div class="skeleton-shimmer mb-4" style="height: 40px; width: 30%; border-radius: 8px;"></div>
                    </div>
                    
                    <div id="actual-page-content" style="opacity: 0; transition: opacity 0.2s ease-in-out;">
                        @yield('content')
                    </div>
                    
                    <script>
                        (function() {
                            function revealContent() {
                                const skel = document.getElementById('initial-skeleton');
                                const cont = document.getElementById('actual-page-content');
                                if(cont) {
                                    if(skel) skel.style.display = 'none';
                                    cont.style.opacity = '1';
                                }
                            }
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', revealContent);
                            } else {
                                revealContent();
                            }
                            setTimeout(revealContent, 2000); 
                        })();
                        
                        // --- BACKGROUND GARBAGE COLLECTION (RAM OPTIMIZATION) ---
                        // For 4GB RAM devices: Cleans memory when user is idle (e.g. talking to customer)
                        (function() {
                            let idleTime = 0;
                            
                            // Reset timer on interaction
                            function resetTimer() { idleTime = 0; }
                            window.onload = resetTimer;
                            window.onmousemove = resetTimer;
                            window.onkeypress = resetTimer;
                            window.ontouchstart = resetTimer;

                            // Check every minute
                            setInterval(function() {
                                idleTime++;
                                if (idleTime >= 1) { // 1 minute of silence
                                    try {
                                        if (window.gc) {
                                            console.log('System Idle: Running Background Cleaning...');
                                            window.gc(); // Force memory release
                                        }
                                    } catch (e) {
                                        // GC not exposed, ignore
                                    }
                                }
                            }, 60000); 
                        })();
                    </script>

                    <!-- /.content -->

                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.Main content -->
        </div>
        <!-- /.content-wrapper -->


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

    <x-spotlight />

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