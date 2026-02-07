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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Sidebar Transitions */
        .sidebar {
            transition: all 0.3s ease-in-out;
        }

        body.sidebar-collapsed .sidebar {
            width: 4rem;
        }

        body.sidebar-collapsed .sidebar-text {
            display: none;
        }

        body.sidebar-collapsed .logo-text {
            display: none;
        }

        /* Mobile Sidebar */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 50;
            }

            body.sidebar-mobile-open .sidebar {
                left: 0;
            }

            body.sidebar-mobile-open .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 font-sans antialiased">

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

    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Overlay -->
        <div class="sidebar-overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden"
            onclick="toggleMobileSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar w-64 sidebar-gradient text-white flex flex-col shrink-0 h-full">
            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-white/10">
                <i class="fas fa-bowl-food text-2xl text-blue-400 mr-3"></i>
                <span class="logo-text font-bold text-lg tracking-wide">Moresto</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-4 scrollbar-hide">
                <div class="px-3 space-y-1">
                    @php
                        $mainNav = [
                            [
                                'label' => 'Dashboard',
                                'icon' => 'fas fa-chart-line',
                                'route' => 'admin.dashboard',
                                'match' => 'admin.dashboard',
                            ],
                            [
                                'label' => 'Master Data',
                                'icon' => 'fas fa-database',
                                'route' => null,
                                'match' => 'admin.products.*|admin.outlets.*|admin.suppliers.*|admin.customers.*|admin.coa-accounts.*|admin.cash-accounts.*|admin.expense-categories.*',
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
                            [
                                'label' => 'Produksi',
                                'icon' => 'fas fa-industry',
                                'route' => null,
                                'match' => 'admin.boms.*',
                                'children' => [
                                    ['label' => 'Bill of Materials', 'route' => 'admin.boms.index', 'match' => 'admin.boms.*'],
                                ]
                            ],
                            [
                                'label' => 'Inventory & Stok',
                                'icon' => 'fas fa-boxes',
                                'route' => 'admin.stocks.index',
                                'match' => 'admin.stocks.*|admin.stock-transfers.*',
                            ],
                            [
                                'label' => 'Pembelian',
                                'icon' => 'fas fa-shopping-cart',
                                'route' => 'admin.purchases.index',
                                'match' => 'admin.purchases.*',
                            ],
                            [
                                'label' => 'Keuangan',
                                'icon' => 'fas fa-wallet',
                                'route' => 'admin.cash-transactions.index',
                                'match' => 'admin.cash-transactions.*',
                            ],
                            [
                                'label' => 'Request Expense',
                                'icon' => 'fas fa-receipt',
                                'route' => 'admin.expenses.index',
                                'match' => 'admin.expenses.*',
                            ],
                            [
                                'label' => 'Laporan',
                                'icon' => 'fas fa-file-invoice-dollar',
                                'route' => 'admin.reports.sales.index',
                                'match' => 'admin.reports.*',
                            ],
                        ];
                    @endphp

                    @foreach ($mainNav as $index => $item)
                        @php
                            $isActive = $item['match'] ? request()->routeIs($item['match']) : false;
                            $hasChildren = isset($item['children']) && count($item['children']) > 0;
                            $menuId = 'submenu-' . $index;

                            $baseClass = "flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors duration-150 group cursor-pointer";
                            $activeClass = "bg-white/10 text-white shadow-sm border border-white/5";
                            $inactiveClass = "text-slate-400 hover:text-white hover:bg-white/5";

                            $linkClass = $isActive ? "$baseClass $activeClass" : "$baseClass $inactiveClass";
                        @endphp

                        @if($hasChildren)
                            <div>
                                <div class="{{ $linkClass }}" onclick="toggleSubmenu('{{ $menuId }}')">
                                    <i class="{{ $item['icon'] }} w-5 text-center mr-3"></i>
                                    <span class="sidebar-text flex-1">{{ $item['label'] }}</span>
                                    <i id="arrow-{{ $menuId }}"
                                        class="fas fa-chevron-down text-xs transition-transform duration-200 {{ $isActive ? 'rotate-180' : '' }} sidebar-text"></i>
                                </div>
                                <div id="{{ $menuId }}"
                                    class="overflow-hidden transition-all duration-300 ease-in-out space-y-1 mt-1"
                                    style="{{ $isActive ? 'max-height: 1000px;' : 'max-height: 0px;' }}">
                                    @foreach($item['children'] as $child)
                                        @php
                                            $isChildActive = $child['match'] ? request()->routeIs($child['match']) : false;
                                            $childClass = $isChildActive
                                                ? 'block pl-11 pr-3 py-2 text-sm font-medium text-blue-400'
                                                : 'block pl-11 pr-3 py-2 text-sm font-medium text-slate-400 hover:text-white transition-colors';
                                        @endphp
                                        <a href="{{ route($child['route']) }}" class="{{ $childClass }} sidebar-text">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}" class="{{ $linkClass }}">
                                <i class="{{ $item['icon'] }} w-5 text-center mr-3"></i>
                                <span class="sidebar-text">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>

                <div class="px-6 py-4 mt-4 border-t border-white/10">
                    <p class="text-xs uppercase text-slate-500 font-semibold mb-2 sidebar-text">Kasir</p>
                    <a href="{{ route('pos.dashboard') }}"
                        class="flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-white/5 rounded-lg transition-colors mb-1">
                        <i class="fas fa-cash-register w-5 text-center mr-3"></i>
                        <span class="sidebar-text">POS Kasir</span>
                    </a>
                    @php
                        $posDeviceActive = request()->routeIs('admin.pos-devices.*');
                        $posDeviceClass = $posDeviceActive
                            ? 'flex items-center px-3 py-2 text-sm font-medium text-white bg-white/10 rounded-lg transition-colors mb-1'
                            : 'flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-white/5 rounded-lg transition-colors mb-1';
                    @endphp
                    <a href="{{ route('admin.pos-devices.index') }}" class="{{ $posDeviceClass }}">
                        <i class="fas fa-tablet-screen-button w-5 text-center mr-3"></i>
                        <span class="sidebar-text">Perangkat POS</span>
                    </a>
                </div>
            </nav>

            <!-- User Profile -->
            <div class="p-4 border-t border-white/10 bg-black/20">
                <div class="flex items-center">
                    <div
                        class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="ml-3 sidebar-text overflow-hidden">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ auth()->user()->role?->display_name ?? 'Admin' }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 relative">

            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 z-10 shrink-0">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()"
                        class="hidden lg:flex text-slate-500 hover:text-blue-600 focus:outline-none mr-4">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <button onclick="toggleMobileSidebar()"
                        class="lg:hidden text-slate-500 hover:text-blue-600 focus:outline-none mr-4">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-slate-800">
                        @yield('page-title', '')
                    </h1>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Profile Dropdown or Actions could go here -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="text-sm text-slate-500 hover:text-red-600 font-medium transition-colors">
                            Sign Out
                        </button>
                    </form>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6 scrollbar-hide">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
