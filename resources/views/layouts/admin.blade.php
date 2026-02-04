<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - PT Dua Putra Sekincau</title>

    {{-- Tailwind & Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-premium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }

        .gradient-gold {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }

        .gradient-ocean {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .gradient-sunset {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .shadow-premium {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
        }

        .sidebar-shine {
            position: relative;
            overflow: hidden;
        }

        .sidebar-shine::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            animation: shine 8s infinite;
        }

        @keyframes shine {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(30%, 30%);
            }
        }

        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .scrollbar-custom::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-custom::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .scrollbar-custom::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .scrollbar-custom::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* ============================================
           THEME PREMIUM - CONSISTENT DESIGN SYSTEM
           ============================================ */

        /* Premium Cards */
        .card-premium {
            background: white;
            border-radius: 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-premium:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* (Legacy btn-* removed; using imperial-btn-* variants in app.css) */

        /* Premium Tables */
        .table-premium {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-premium thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .table-premium thead th {
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem;
            text-align: left;
            border: none;
        }

        .table-premium thead th:first-child {
            border-top-left-radius: 1rem;
        }

        .table-premium thead th:last-child {
            border-top-right-radius: 1rem;
        }

        .table-premium tbody tr {
            background: white;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-premium tbody tr:hover {
            background: #f8fafc;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table-premium tbody td {
            padding: 1rem;
            color: #475569;
        }

        /* Premium Form Inputs */
        .input-premium {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .input-premium:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-premium:hover {
            border-color: #cbd5e1;
        }

        /* Premium Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
        }

        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .badge-gray {
            background: #e2e8f0;
            color: #475569;
        }

        /* Premium Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 1px solid #6ee7b7;
            color: #065f46;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
    </style>
</head>
<script>
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-collapsed');
    }
    function toggleMobileSidebar() {
        document.body.classList.toggle('sidebar-mobile-open');
    }
    function toggleSubmenu(id) {
        const submenu = document.getElementById(id);
        const arrow = document.getElementById('arrow-' + id);
        if (submenu) {
            if (submenu.style.maxHeight && submenu.style.maxHeight !== '0px') {
                submenu.style.maxHeight = '0px';
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            } else {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            }
        }
    }
</script>

<body class="imperial-body text-slate-900">

    <style>
        .sidebar-transition {
            transition: width 0.3s cubic-bezier(.4, 0, .2, 1), min-width 0.3s cubic-bezier(.4, 0, .2, 1);
        }

        body.sidebar-collapsed .imperial-sidebar {
            width: 4.5rem !important;
            min-width: 4.5rem !important;
        }

        body.sidebar-collapsed .imperial-sidebar .px-6,
        body.sidebar-collapsed .imperial-sidebar .pt-6,
        body.sidebar-collapsed .imperial-sidebar .pb-5,
        body.sidebar-collapsed .imperial-sidebar .nav-label,
        body.sidebar-collapsed .imperial-sidebar .leading-tight,
        body.sidebar-collapsed .imperial-sidebar .mt-auto,
        body.sidebar-collapsed .imperial-sidebar .flex-1,
        body.sidebar-collapsed .imperial-sidebar .text-xs,
        body.sidebar-collapsed .imperial-sidebar .text-lg,
        body.sidebar-collapsed .imperial-sidebar .text-sm,
        body.sidebar-collapsed .imperial-sidebar .text-indigo-100,
        body.sidebar-collapsed .imperial-sidebar .text-indigo-200,
        body.sidebar-collapsed .imperial-sidebar .text-white,
        body.sidebar-collapsed .imperial-sidebar .text-emerald-300,
        body.sidebar-collapsed .imperial-sidebar .text-amber-300,
        body.sidebar-collapsed .imperial-sidebar .fa-chevron-right,
        body.sidebar-collapsed .imperial-sidebar .imperial-badge {
            display: none !important;
        }

        body.sidebar-collapsed .imperial-sidebar {
            overflow: visible;
        }

        body.sidebar-mobile-open .imperial-sidebar {
            position: fixed !important;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 50;
        }

        body.sidebar-mobile-open #sidebarOverlay {
            display: block !important;
        }

        @media (max-width: 1024px) {
            .imperial-sidebar {
                position: fixed !important;
                left: -100%;
                top: 0;
                height: 100vh;
                z-index: 50;
                box-shadow: none;
                transition: left 0.3s cubic-bezier(.4, 0, .2, 1);
            }

            body.sidebar-mobile-open .imperial-sidebar {
                left: 0 !important;
                box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.4);
            }
        }
    </style>
    <div class="flex h-screen overflow-hidden font-sans"
        style="font-family: 'Inter Tight', 'Roboto Condensed', 'Montserrat', 'Inter', sans-serif;">
        {{-- APP SHELL --}}
        <div class="flex flex-1 overflow-hidden shadow-premium border border-gray-200/50 bg-white/60 backdrop-blur-md">

            {{-- SIDEBAR PREMIUM --}}
            <aside class="w-64 shrink-0 imperial-sidebar text-white relative sidebar-transition">
                <!-- Desktop Collapse Button -->
                <button onclick="toggleSidebar()"
                    class="hidden lg:flex absolute top-4 right-4 z-20 bg-white/20 hover:bg-white/40 text-amber-400 rounded-full p-2 shadow-lg transition-all"
                    title="Collapse Sidebar">
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <!-- Mobile Hamburger -->
                <button onclick="toggleMobileSidebar()"
                    class="lg:hidden flex absolute top-4 left-4 z-20 bg-white/20 hover:bg-white/40 text-amber-400 rounded-full p-2 shadow-lg transition-all"
                    title="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="relative flex flex-col h-full z-10">
                    {{-- Brand / Logo --}}
                    <div class="px-4 pt-5 pb-4 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-bowl-food text-xl text-amber-400"></i>
                            <p class="text-lg font-bold text-white">Moresto</p>
                        </div>
                    </div>

                    {{-- NAV PREMIUM --}}
                    @php
                        $mainNav = [
                            [
                                'label' => 'Dashboard',
                                'desc' => 'Ringkasan bisnis',
                                'icon' => 'fas fa-chart-line',
                                'route' => 'admin.dashboard',
                                'match' => 'admin.dashboard',
                                'gradient' => 'from-blue-500 to-cyan-500'
                            ], // Master Data Group
                            [
                                'label' => 'Master Data',
                                'desc' => 'Data referensi sistem',
                                'icon' => 'fas fa-database',
                                'route' => null, // Parent menu has no direct route
                                'match' => 'admin.products.*|admin.outlets.*|admin.suppliers.*|admin.customers.*|admin.coa-accounts.*|admin.cash-accounts.*|admin.expense-categories.*',
                                'gradient' => 'from-purple-500 to-pink-500',
                                'children' => [
                                    ['label' => 'Produk', 'route' => 'admin.products.index', 'match' => 'admin.products.*'],
                                    ['label' => 'Outlet', 'route' => 'admin.outlets.index', 'match' => 'admin.outlets.*'],
                                    ['label' => 'Supplier', 'route' => 'admin.suppliers.index', 'match' => 'admin.suppliers.*'],
                                    ['label' => 'Customer', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*'],
                                    ['label' => 'Chart of Accounts', 'route' => 'admin.coa-accounts.index', 'match' => 'admin.coa-accounts.*'],
                                    ['label' => 'Akun Kas & Bank', 'route' => 'admin.cash-accounts.index', 'match' => 'admin.cash-accounts.*'],
                                    ['label' => 'Kategori Biaya', 'route' => 'admin.expense-categories.index', 'match' => 'admin.expense-categories.*'],
                                ]
                            ],
                            // Produksi Group
                            [
                                'label' => 'Produksi',
                                'desc' => 'Manufaktur & Resep',
                                'icon' => 'fas fa-industry',
                                'route' => null,
                                'match' => 'admin.boms.*',
                                'gradient' => 'from-orange-500 to-red-500',
                                'children' => [
                                    ['label' => 'Bill of Materials', 'route' => 'admin.boms.index', 'match' => 'admin.boms.*'],
                                ]
                            ],
                            [
                                'label' => 'Inventory & Stok',
                                'desc' => 'Stok, mutasi, transfer',
                                'icon' => 'fas fa-boxes',
                                'route' => 'admin.stocks.index',
                                'match' => 'admin.stocks.*|admin.stock-transfers.*',
                                'gradient' => 'from-teal-500 to-cyan-500'
                            ],
                            [
                                'label' => 'Pembelian',
                                'desc' => 'Purchase & pembayaran',
                                'icon' => 'fas fa-shopping-cart',
                                'route' => 'admin.purchases.index',
                                'match' => 'admin.purchases.*',
                                'gradient' => 'from-green-500 to-emerald-500'
                            ],
                            [
                                'label' => 'Keuangan',
                                'desc' => 'Transaksi & Arus Kas',
                                'icon' => 'fas fa-wallet',
                                'route' => 'admin.cash-transactions.index',
                                'match' => 'admin.cash-transactions.*',
                                'gradient' => 'from-yellow-500 to-amber-500'
                            ],
                            [
                                'label' => 'Request Expense',
                                'desc' => 'Pengajuan biaya & approval',
                                'icon' => 'fas fa-receipt',
                                'route' => 'admin.expenses.index',
                                'match' => 'admin.expenses.*',
                                'gradient' => 'from-purple-500 to-pink-500'
                            ],
                            [
                                'label' => 'Laporan',
                                'desc' => 'Sales, shift, profit/loss',
                                'icon' => 'fas fa-file-invoice-dollar',
                                'route' => 'admin.reports.sales.index',
                                'match' => 'admin.reports.*',
                                'gradient' => 'from-pink-500 to-rose-500'
                            ],
                        ];
                    @endphp

                    <nav class="px-3 pb-3 space-y-1 text-sm relative overflow-y-auto flex-1 pt-4">
                        @foreach ($mainNav as $index => $item)
                            @php
                                // Check if active (parent or single link)
                                $isActive = $item['match']
                                    ? request()->routeIs($item['match'])
                                    : false;

                                // Specific logic: if item has children
                                $hasChildren = isset($item['children']) && count($item['children']) > 0;
                                $menuId = 'submenu-' . $index;

                                // Base classes
                                $linkClasses = $isActive
                                    ? 'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-white group cursor-pointer'
                                    : 'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 hover:bg-white/5 hover:text-white transition-all duration-200 group cursor-pointer';
                            @endphp

                            @if($hasChildren)
                                <!-- Parent Menu -->
                                <div>
                                    <div class="{{ $linkClasses }}" onclick="toggleSubmenu('{{ $menuId }}')">
                                        <i class="{{ $item['icon'] }} text-base w-5 text-center"></i>
                                        <div class="flex-1 flex justify-between items-center">
                                            <span class="text-sm font-medium">{{ $item['label'] }}</span>
                                            <i id="arrow-{{ $menuId }}"
                                                class="fas fa-chevron-down text-xs transition-transform duration-300 {{ $isActive ? 'rotate-180' : '' }}"></i>
                                        </div>
                                    </div>
                                    <!-- Submenu -->
                                    <div id="{{ $menuId }}"
                                        class="overflow-hidden transition-all duration-300 ease-in-out pl-14 space-y-1"
                                        style="{{ $isActive ? 'max-height: 1000px;' : 'max-height: 0px;' }}">
                                        @foreach($item['children'] as $child)
                                            @php
                                                $isChildActive = $child['match'] ? request()->routeIs($child['match']) : false;
                                                $childClasses = $isChildActive
                                                    ? 'block px-3 py-2 rounded-lg text-amber-400 text-xs font-bold'
                                                    : 'block px-3 py-2 rounded-lg text-white/60 hover:text-white hover:bg-white/5 text-xs transition-colors';
                                            @endphp
                                            <a href="{{ route($child['route']) }}" class="{{ $childClasses }}">
                                                {{ $child['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <!-- Single Menu -->
                                <a href="{{ $item['route'] ? route($item['route']) : '#' }}" class="{{ $linkClasses }}">
                                    <i class="{{ $item['icon'] }} text-base w-5 text-center"></i>
                                    <span class="text-sm font-medium">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endforeach

                        <div class="pt-3 mt-3 border-t border-white/10">
                            <a href="{{ route('pos.dashboard') }}"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 hover:bg-white/5 hover:text-white transition-all duration-200">
                                <i class="fas fa-cash-register text-base w-5 text-center"></i>
                                <span class="text-sm font-medium">POS Kasir</span>
                            </a>

                            <a href="{{ route('admin.cash-sessions.index') }}"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 hover:bg-white/5 hover:text-white transition-all duration-200">
                                <i class="fas fa-history text-base w-5 text-center"></i>
                                <span class="text-sm font-medium">History Shift</span>
                            </a>
                        </div>
                    </nav>
                </div>
            </aside>

            {{-- MAIN CONTENT WRAPPER PREMIUM --}}
            <div
                class="flex-1 bg-gradient-to-br from-gray-50 via-white to-slate-50 flex flex-col relative overflow-hidden">
                <!-- Mobile Sidebar Overlay -->
                <div class="fixed inset-0 bg-black/40 z-40 transition-opacity duration-300 lg:hidden"
                    style="display:none" id="sidebarOverlay" onclick="toggleMobileSidebar()"></div>
                {{-- Background Pattern --}}
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0"
                        style="background-image: radial-gradient(circle at 1px 1px, rgb(148 163 184) 1px, transparent 0); background-size: 40px 40px;">
                    </div>
                </div>

                {{-- TOP HEADER - USER PROFILE ONLY --}}
                <header class="imperial-header relative z-10 border-b border-white/10">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <button onclick="toggleMobileSidebar()"
                            class="lg:hidden inline-flex items-center justify-center p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white border border-white/20">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div
                                    class="h-9 w-9 rounded-lg bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-sm font-bold text-amber-400">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                            <div class="leading-tight hidden md:block">
                                <p class="font-semibold text-white text-sm">{{ auth()->user()->name ?? 'User' }}</p>
                                <p class="text-xs text-white/60">
                                    {{ auth()->user()->role->display_name ?? 'Super Admin' }}
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-sm text-white transition-all duration-200 flex items-center gap-2"
                                title="Logout">
                                <i class="fas fa-sign-out-alt text-sm"></i>
                                <span class="hidden md:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </header> {{-- CONTENT AREA PREMIUM --}}
                <main class="flex-1 overflow-y-auto p-8 md:p-12 lg:p-16 relative z-10">
                    @yield('content')
                </main>

                {{-- FOOTER --}}
                <footer class="relative z-10 px-6 py-4 bg-white/50 backdrop-blur-sm border-t border-gray-200/50">
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 text-sm text-gray-600">
                        <p class="flex items-center gap-2">
                            <i class="fas fa-copyright text-gray-400"></i>
                            <span>2025 Authorize of Moresto. All rights reserved.</span>
                        </p>
                        <div class="flex items-center gap-4">
                            <a href="#" class="hover:text-indigo-200 transition-colors flex items-center gap-1">
                                <i class="fas fa-life-ring"></i>
                                <span>Support</span>
                            </a>
                            <a href="#" class="hover:text-indigo-200 transition-colors flex items-center gap-1">
                                <i class="fas fa-book"></i>
                                <span>Documentation</span>
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    {{-- Tempat menyuntikkan script halaman --}}
    @stack('scripts')
</body>

</html>