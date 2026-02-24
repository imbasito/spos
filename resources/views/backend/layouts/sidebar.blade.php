@php
$route = request()->route()->getName();
@endphp
<div class="sidebar">
    <!-- Apple-Standard Flat Command Center -->
    <nav class="mt-2" id="apple-sidebar-nav">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
            
            @can('dashboard_view')
            <li class="nav-item">
                <a href="{{ route('backend.admin.dashboard') }}"
                    class="nav-link {{ $route === 'backend.admin.dashboard' ? 'active' : '' }}">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                </a>
            </li>
            @endcan

            @can('sale_create')
            <li class="nav-item">
                <a href="{{ route('backend.admin.cart.index') }}"
                    class="nav-link {{ $route === 'backend.admin.cart.index' ? 'active' : '' }}">
                    <i class="nav-icon fas fa-cart-plus"></i>
                    <p>POS</p>
                </a>
            </li>
            @endcan

            <li class="nav-header">CORE</li>

            @if (auth()->user()->hasAnyPermission(['product_view','category_view']))
            <li class="nav-item">
                <a href="{{route('backend.admin.products.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.products.*']) ? 'active' : '' }}">
                    <i class="fas fa-box nav-icon"></i>
                    <p>Products</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route('backend.admin.categories.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.categories.*']) ? 'active' : '' }}">
                    <i class="fas fa-sitemap nav-icon"></i>
                    <p>Categories</p>
                </a>
            </li>
             <li class="nav-item">
                <a href="{{route('backend.admin.brands.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.brands.*']) ? 'active' : '' }}">
                    <i class="fas fa-star nav-icon"></i>
                    <p>Brands</p>
                </a>
            </li>
            @endif

            @can('unit_view')
            <li class="nav-item">
                <a href="{{route('backend.admin.units.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.units.*']) ? 'active' : '' }}">
                    <i class="fas fa-ruler nav-icon"></i>
                    <p>Units</p>
                </a>
            </li>
            @endcan

            @if (auth()->user()->hasAnyPermission(['customer_view','supplier_view']))
            <li class="nav-item">
                <a href="{{route('backend.admin.customers.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.customers.*']) ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon"></i>
                    <p>Customers</p>
                </a>
            </li>
            @endif

            @if (auth()->user()->hasAnyPermission(['supplier_view']))
            <li class="nav-item">
                <a href="{{route('backend.admin.suppliers.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.suppliers.*']) ? 'active' : '' }}">
                    <i class="fas fa-truck nav-icon"></i>
                    <p>Suppliers</p>
                </a>
            </li>
            @endif

            <li class="nav-header">MANAGEMENT</li>

            @can('sale_view')
            <li class="nav-item">
                <a href="{{ route('backend.admin.orders.index') }}"
                    class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice nav-icon"></i>
                    <p>Sales</p>
                </a>
            </li>
            @endcan
            
            <li class="nav-item">
                <a href="{{route('backend.admin.refunds.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.refunds.*']) ? 'active' : '' }}">
                     <i class="fas fa-undo nav-icon"></i>
                    <p>Refunds</p>
                </a>
            </li>

            @can('purchase_view')
            <li class="nav-item">
                <a href="{{route('backend.admin.purchase.index')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.purchase.*']) ? 'active' : '' }}">
                    <i class="fas fa-shopping-bag nav-icon"></i>
                    <p>Purchases</p>
                </a>
            </li>
            @endcan

            @can('reports_summary')
            <li class="nav-item">
                <a href="{{route('backend.admin.sale.summery')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.sale.*']) ? 'active' : '' }}">
                    <i class="fas fa-chart-pie nav-icon"></i>
                    <p>Sales Summary</p>
                </a>
            </li>
            @endcan
            
            @can('reports_inventory')
            <li class="nav-item">
                <a href="{{route('backend.admin.inventory.report')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.inventory.report']) ? 'active' : '' }}">
                    <i class="fas fa-boxes nav-icon"></i>
                    <p>Inventory</p>
                </a>
            </li>
            @endcan
            
            @can('reports_summary')
            <li class="nav-item">
                <a href="{{route('backend.admin.report.daily.history')}}"
                    class="nav-link {{ request()->routeIs(['backend.admin.report.daily.*']) ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar nav-icon"></i>
                    <p>Closing</p>
                </a>
            </li>
            @endcan

            <li class="nav-header">SETTINGS</li>

            @if (auth()->user()->hasAnyPermission(['website_settings','role_view','user_view']))
            <li class="nav-item">
                <a href="{{ route('backend.admin.settings.website.general') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.settings.website.*']) ? 'active' : '' }}">
                    <i class="fas fa-cog nav-icon"></i>
                    <p>General</p>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="{{ route('backend.admin.roles') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.roles', 'backend.admin.permissions']) ? 'active' : '' }}">
                    <i class="fas fa-user-shield nav-icon"></i>
                    <p>Roles</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('backend.admin.users') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.users', 'backend.admin.user.*']) ? 'active' : '' }}">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <p>User Management</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('backend.admin.currencies.index') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.currencies.*']) ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave nav-icon"></i>
                    <p>Currency</p>
                </a>
            </li>
            @endif

            @role('Admin')
            <li class="nav-header">SYSTEM</li>
            
            <li class="nav-item">
                <a href="{{ route('backend.admin.activity.logs.index') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.activity.logs.*']) ? 'active' : '' }}">
                    <i class="nav-icon fas fa-history"></i>
                    <p>Audit Logs</p>
                </a>
            </li>

            <li class="nav-item">
                 <a href="{{ route('backend.admin.barcode') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.barcode']) ? 'active' : '' }}">
                    <i class="nav-icon fas fa-barcode"></i>
                    <p>Barcode Generator</p>
                </a>
            </li>

             <li class="nav-item">
                <a href="{{ route('backend.admin.settings.backup') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.settings.backup']) ? 'active' : '' }}">
                    <i class="fas fa-hdd nav-icon"></i>
                    <p>Backup</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('backend.admin.license') }}"
                    class="nav-link {{ request()->routeIs(['backend.admin.license']) ? 'active' : '' }}">
                    <i class="fas fa-sync-alt nav-icon"></i>
                    <p>System Updates</p>
                </a>
            </li>
            @endrole

        </ul>
    </nav>
</div>

<script>
    /* Focal Cursor Reveal Tracking */
    document.getElementById('apple-sidebar-nav').addEventListener('mousemove', function(e) {
        const link = e.target.closest('.nav-link');
        if (link) {
            const rect = link.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            link.style.setProperty('--x', `${x}px`);
            link.style.setProperty('--y', `${y}px`);
        }
    });

    /* Persistence State (localStorage) */
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarState = localStorage.getItem('apple_sidebar_collapsed');
        if (sidebarState === 'true') {
            document.body.classList.add('sidebar-collapse');
        }

        /* Spotlight Search Logic */
        const searchInput = document.getElementById('sidebar-search-input');
        const menuList = document.getElementById('sidebar-menu-list');
        const navItems = menuList.querySelectorAll('.nav-item');
        const navHeaders = menuList.querySelectorAll('.nav-header');

        // Hotkey: Ctrl+K / Cmd+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();

            // Filter Items
            navItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });

            // Handle Headers (Hide if all children are hidden)
            // Note: This is a simple implementation. For perfect header logic, we'd need to group them.
            // For now, if searching, we hide headers to reduce clutter.
            if (query.length > 0) {
                navHeaders.forEach(header => header.style.display = 'none');
            } else {
                navHeaders.forEach(header => header.style.display = '');
            }
        });
    });
</script>