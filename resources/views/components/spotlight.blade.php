<!-- Sinyx Spotlight: Global Command Center -->
<div id="sinyx-spotlight" class="spotlight-overlay" style="display: none;">
    <div class="spotlight-container animated pulse-subtle">
        <div class="spotlight-search-box">
            <i class="fas fa-search spotlight-icon"></i>
            <input type="text" id="spotlight-input" placeholder="Search commands, pages, or features... (Ctrl+K)" autocomplete="off">
            <div class="spotlight-shortcut">ESC to close</div>
        </div>
        <div id="spotlight-results" class="spotlight-results custom-scroll">
            <!-- Results injected here -->
        </div>
        <div class="spotlight-footer">
            <span><kbd>↑↓</kbd> to navigate</span>
            <span><kbd>↵</kbd> to select</span>
        </div>
    </div>
</div>

<style>
    /* Spotlight Overlay with Apple Blur */
    .spotlight-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 999999;
        display: flex;
        justify-content: center;
        padding-top: 15vh;
        animation: spotlightFadeIn 0.2s cubic-bezier(0.25, 1, 0.5, 1);
    }

    @keyframes spotlightFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .spotlight-container {
        width: 600px;
        max-width: 90vw;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        box-shadow: 0 20px 70px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.3);
        display: flex;
        flex-direction: column;
        max-height: 500px;
    }

    .spotlight-search-box {
        display: flex;
        align-items: center;
        padding: 18px 24px;
        border-bottom: 1px solid #f1f1f1;
        position: relative;
    }

    .spotlight-icon {
        font-size: 1.4rem;
        color: #800000;
        margin-right: 15px;
    }

    #spotlight-input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 1.2rem;
        color: #1a1a1a;
        outline: none;
        font-weight: 500;
    }

    .spotlight-shortcut {
        font-size: 0.75rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .spotlight-results {
        flex: 1;
        overflow-y: auto;
        padding: 10px 0;
    }

    .spotlight-item {
        display: flex;
        align-items: center;
        padding: 12px 24px;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none !important;
        color: #444 !important;
    }

    .spotlight-item:hover, .spotlight-item.active {
        background: #800000;
        color: #fff !important;
    }

    .spotlight-item i {
        width: 30px;
        font-size: 1.1rem;
        margin-right: 15px;
        text-align: center;
    }

    .spotlight-item .item-text {
        font-size: 1rem;
        font-weight: 500;
        flex-grow: 1;
    }

    .spotlight-item .item-breadcrumb {
        font-size: 0.75rem;
        opacity: 0.6;
        margin-left: 20px;
        background: rgba(0,0,0,0.05);
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .spotlight-item:hover .item-breadcrumb, .spotlight-item.active .item-breadcrumb {
        background: rgba(255,255,255,0.2);
        color: #fff;
    }

    .spotlight-footer {
        padding: 10px 24px;
        background: #f8f9fa;
        border-top: 1px solid #f1f1f1;
        display: flex;
        gap: 20px;
        font-size: 0.75rem;
        color: #888;
    }

    .spotlight-footer kbd {
        background: #eee;
        border-radius: 3px;
        padding: 2px 5px;
        color: #444;
        font-family: inherit;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }

    /* Pulse Subtle Animation */
    .pulse-subtle {
        animation: pulseSubtle 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    }

    @keyframes pulseSubtle {
        from { transform: scale(0.98); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    /* Shake Animation for No Results (Re-added per User Request) */
    .shake-error {
        animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
    }

    @keyframes shake {
        10%, 90% { transform: translate3d(-1px, 0, 0); }
        20%, 80% { transform: translate3d(2px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
        40%, 60% { transform: translate3d(4px, 0, 0); }
    }
</style>



<script>
    (function() {
        const spotlightIndex = [
            // --- CORE ---
            { name: 'Dashboard', icon: 'fas fa-tachometer-alt', url: '{{ route("backend.admin.dashboard") }}', breadcrumb: 'System', tags: ['home', 'main', 'start', 'stats', 'analytics'] },
            { name: 'POS (Point of Sale)', icon: 'fas fa-cart-plus', url: '{{ route("backend.admin.cart.index") }}', breadcrumb: 'Sales', tags: ['cart', 'billing', 'billing desk', 'sale', 'checkout', 'mithai'] },
            
            // --- PEOPLE ---
            { name: 'Customer List', icon: 'fas fa-users', url: '{{ route("backend.admin.customers.index") }}', breadcrumb: 'People', tags: ['customers', 'clients', 'due payment', 'customer ledger'] },
            { name: 'Add New Customer', icon: 'fas fa-user-plus', url: '{{ route("backend.admin.customers.create") }}', breadcrumb: 'People', tags: ['new customer', 'register client'] },
            { name: 'Supplier List', icon: 'fas fa-truck', url: '{{ route("backend.admin.suppliers.index") }}', breadcrumb: 'People', tags: ['suppliers', 'vendors', 'purchase from', 'supplier ledger'] },
            { name: 'Add New Supplier', icon: 'fas fa-plus', url: '{{ route("backend.admin.suppliers.create") }}', breadcrumb: 'People', tags: ['new supplier', 'vendor register'] },
            
            // --- PRODUCTS ---
            { name: 'Product List', icon: 'fas fa-box', url: '{{ route("backend.admin.products.index") }}', breadcrumb: 'Items', tags: ['products', 'stock', 'inventory', 'mithai list', 'price list'] },
            { name: 'Add New Product', icon: 'fas fa-plus-circle', url: '{{ route("backend.admin.products.create") }}', breadcrumb: 'Items', tags: ['new item', 'create product', 'add stock'] },
            { name: 'Category Management', icon: 'fas fa-layer-group', url: '{{ route("backend.admin.categories.index") }}', breadcrumb: 'Items', tags: ['categories', 'grouping', 'types'] },
            { name: 'Brand Management', icon: 'fas fa-copyright', url: '{{ route("backend.admin.brands.index") }}', breadcrumb: 'Items', tags: ['brands', 'labels', 'companies'] },
            { name: 'Unit Management', icon: 'fas fa-balance-scale', url: '{{ route("backend.admin.units.index") }}', breadcrumb: 'Items', tags: ['units', 'kg', 'grams', 'pieces', 'weight'] },
            { name: 'Import Products (CSV)', icon: 'fas fa-file-import', url: '{{ route("backend.admin.products.import") }}', breadcrumb: 'Items', tags: ['bulk upload', 'excel', 'csv import'] },
            
            // --- SALES & PURCHASES ---
            { name: 'Sales History', icon: 'fas fa-history', url: '{{ route("backend.admin.orders.index") }}', breadcrumb: 'Sales', tags: ['invoice list', 'past sales', 'receipts', 'previous orders'] },
            { name: 'Purchase List', icon: 'fas fa-shopping-bag', url: '{{ route("backend.admin.purchase.index") }}', breadcrumb: 'Inventory', tags: ['buying', 'stock in', 'vendor purchase'] },
            { name: 'Add New Purchase', icon: 'fas fa-cart-arrow-down', url: '{{ route("backend.admin.purchase.create") }}', breadcrumb: 'Inventory', tags: ['buy stock', 'new purchase', 'refill'] },
            { name: 'Refund Management', icon: 'fas fa-undo', url: '{{ route("backend.admin.refunds.index") }}', breadcrumb: 'Sales', tags: ['return', 'cash back', 'refund list'] },
            
            // --- REPORTS ---
            { name: 'Sales Summary', icon: 'fas fa-calculator', url: '{{ route("backend.admin.sale.summery") }}', breadcrumb: 'Reports', tags: ['profit', 'loss', 'daily summary', 'total sales'] },
            { name: 'Detailed Sales Report', icon: 'fas fa-chart-line', url: '{{ route("backend.admin.sale.report") }}', breadcrumb: 'Reports', tags: ['analytics', 'performance', 'sales chart'] },
            { name: 'Inventory Report', icon: 'fas fa-warehouse', url: '{{ route("backend.admin.inventory.report") }}', breadcrumb: 'Reports', tags: ['stock check', 'low stock', 'warehouse'] },
            { name: 'Daily Closing History', icon: 'fas fa-file-invoice-dollar', url: '{{ route("backend.admin.report.daily.history") }}', breadcrumb: 'Reports', tags: ['closing', 'eod', 'end of day', 'cash register'] },
            
            // --- SYSTEM & SETTINGS ---
            { name: 'General Settings', icon: 'fas fa-cogs', url: '{{ route("backend.admin.settings.website.general") }}', breadcrumb: 'System', tags: ['website name', 'logo', 'contact', 'invoice logo', 'profile'] },
            { name: 'User Management', icon: 'fas fa-user-shield', url: '{{ route("backend.admin.users") }}', breadcrumb: 'System', tags: ['staff', 'employees', 'permissions', 'roles'] },
            { name: 'Backup & Restore', icon: 'fas fa-database', url: '{{ route("backend.admin.settings.backup") }}', breadcrumb: 'System', tags: ['cloud backup', 'security', 'data export'] },
            { name: 'Audit Logs', icon: 'fas fa-user-secret', url: '{{ route("backend.admin.activity.logs.index") }}', breadcrumb: 'System', tags: ['tracking', 'security logs', 'who did what'] },
            { name: 'Barcode Generator', icon: 'fas fa-barcode', url: '{{ route("backend.admin.barcode") }}', breadcrumb: 'System', tags: ['print labels', 'sku', 'stickers'] },
            { name: 'System Updates & License', icon: 'fas fa-sync', url: '{{ route("backend.admin.license") }}', breadcrumb: 'System', tags: ['update', 'version', 'license key'] },
        ];

        let isOpen = false;
        let selectedIndex = 0;
        let filteredIndex = [...spotlightIndex];

        const overlay = document.getElementById('sinyx-spotlight');
        const input = document.getElementById('spotlight-input');
        const resultsBox = document.getElementById('spotlight-results');

        // Audio Haptics (Apple Style)
        const sounds = {
            open: new Audio('{{ asset("sounds/beep-07a.mp3") }}'),
            tick: new Audio('{{ asset("sounds/beep-07a.mp3") }}'), // Reuse soft beep for navigation
            error: new Audio('{{ asset("sounds/beep-02.mp3") }}')
        };
        sounds.tick.volume = 0.1; // Very subtle for ticks

        function toggleSpotlight(forceClose = false) {
            isOpen = forceClose ? false : !isOpen;
            overlay.style.display = isOpen ? 'flex' : 'none';
            if (isOpen) {
                sounds.open.play().catch(() => {});
                input.value = '';
                renderResults(spotlightIndex);
                setTimeout(() => input.focus(), 50);
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function renderResults(data) {
            filteredIndex = data;
            selectedIndex = 0;
            if (data.length === 0) {
                resultsBox.innerHTML = '<div class="p-5 text-center text-muted"><i class="fas fa-search mb-3 d-block" style="font-size: 2rem; opacity: 0.3;"></i>No results found...</div>';
                return;
            }
            resultsBox.innerHTML = data.map((item, idx) => `
                <a href="${item.url}" class="spotlight-item ${idx === 0 ? 'active' : ''}" data-index="${idx}">
                    <i class="${item.icon}"></i>
                    <span class="item-text">${item.name}</span>
                    <span class="item-breadcrumb">${item.breadcrumb}</span>
                </a>
            `).join('');
        }

        // Global Shortcut
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                toggleSpotlight();
            }

            if (!isOpen) return;

            if (e.key === 'Escape') toggleSpotlight(true);

            const items = resultsBox.querySelectorAll('.spotlight-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length === 0) return;
                sounds.tick.currentTime = 0;
                sounds.tick.play().catch(() => {});
                items[selectedIndex]?.classList.remove('active');
                selectedIndex = (selectedIndex + 1) % items.length;
                items[selectedIndex]?.classList.add('active');
                items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length === 0) return;
                sounds.tick.currentTime = 0;
                sounds.tick.play().catch(() => {});
                items[selectedIndex]?.classList.remove('active');
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                items[selectedIndex]?.classList.add('active');
                items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (filteredIndex[selectedIndex]) {
                    window.location.href = filteredIndex[selectedIndex].url;
                } else {
                    sounds.error.play().catch(() => {});
                    const container = document.querySelector('.spotlight-container');
                    container.classList.add('shake-error');
                    setTimeout(() => container.classList.remove('shake-error'), 400);
                }
            }


        });


        // Search Input with Tag Support
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            if(!query) {
                renderResults(spotlightIndex);
                return;
            }
            const filtered = spotlightIndex.filter(item => 
                item.name.toLowerCase().includes(query) || 
                item.breadcrumb.toLowerCase().includes(query) ||
                item.tags.some(tag => tag.includes(query))
            );
            renderResults(filtered);
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) toggleSpotlight(true);
        });
    })();

</script>
