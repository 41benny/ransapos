<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Moresto</title>

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
    @stack('styles')
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
        <aside
            class="sidebar w-68 bg-[#0B0F1A] text-white flex flex-col shrink-0 h-full border-r border-white/5 relative z-50 shadow-2xl">
            <!-- Logo area -->
            <div class="h-20 flex items-center px-7 mb-4">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/20 text-indigo-400 ring-1 ring-indigo-400/30">
                    <i class="fas fa-bowl-food text-xl"></i>
                </div>
                <div class="ml-4 logo-text">
                    <span class="block text-sm font-black uppercase tracking-[0.2em] text-white/90">Moresto</span>
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">Business
                        Suite</span>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-4 py-2 scrollbar-hide space-y-8">
                {{-- Group: Main Navigation --}}
                <div class="space-y-1">
                    <p class="px-4 text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-3 sidebar-text">
                        General</p>
                    @php
                        $currentUser = auth()->user();
                        $currentUser?->loadMissing('role.permissions', 'customPermissions');

                        $canAccess = function (?string $permission) use ($currentUser): bool {
                            if (!$permission) {
                                return true;
                            }

                            return (bool) ($currentUser && $currentUser->hasPermission($permission));
                        };

                        $mainNav = [
                            [
                                'label' => 'Dashboard',
                                'icon' => 'fas fa-grid-2',
                                'alt_icon' => 'fas fa-chart-pie',
                                'route' => 'admin.dashboard',
                                'match' => 'admin.dashboard',
                                'permission' => 'dashboard.view',
                            ],
                            [
                                'label' => 'Master Data',
                                'icon' => 'fas fa-layer-group',
                                'route' => null,
                                'match' => 'admin.products.*|admin.outlets.*|admin.suppliers.*|admin.payment-methods.*|admin.sales-types.*|admin.customers.*|admin.coa-accounts.*|admin.cash-accounts.*|admin.expense-categories.*|admin.users.*',
                                'children' => [
                                    ['label' => 'Produk', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'permission' => 'products.view'],
                                    ['label' => 'Outlet', 'route' => 'admin.outlets.index', 'match' => 'admin.outlets.*', 'permission' => 'outlets.view'],
                                    ['label' => 'Users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'permission' => 'users.view'],
                                    ['label' => 'Supplier', 'route' => 'admin.suppliers.index', 'match' => 'admin.suppliers.*', 'permission' => 'suppliers.view'],
                                    ['label' => 'Metode Bayar', 'route' => 'admin.payment-methods.index', 'match' => 'admin.payment-methods.*', 'permission' => 'payment-methods.view'],
                                    ['label' => 'Metode Penjualan', 'route' => 'admin.sales-types.index', 'match' => 'admin.sales-types.*', 'permission' => 'sales-types.view'],
                                    ['label' => 'Customer', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'permission' => 'customers.view'],
                                    ['label' => 'Kas & Bank', 'route' => 'admin.cash-accounts.index', 'match' => 'admin.cash-accounts.*', 'permission' => 'cash-accounts.view'],
                                    ['label' => 'Akunting & Biaya', 'route' => 'admin.coa-accounts.index', 'match' => 'admin.coa-accounts.*|admin.expense-categories.*', 'permission' => 'coa-accounts.view'],
                                ]
                            ],
                            [
                                'label' => 'Inventory',
                                'icon' => 'fas fa-box-archive',
                                'alt_icon' => 'fas fa-boxes',
                                'route' => 'admin.stocks.index',
                                'match' => 'admin.stocks.*|admin.stock-transfers.*',
                                'permission' => 'stocks.view',
                            ],
                            [
                                'label' => 'Produksi',
                                'icon' => 'fas fa-flask',
                                'route' => 'admin.boms.index',
                                'match' => 'admin.boms.*',
                                'permission' => 'boms.view',
                            ],
                            [
                                'label' => 'Purchasing',
                                'icon' => 'fas fa-cart-shopping',
                                'route' => null,
                                'match' => 'admin.purchases.*|admin.reports.debts.*',
                                'children' => [
                                    ['label' => 'Pembelian (PO)', 'route' => 'admin.purchases.index', 'match' => 'admin.purchases.*', 'permission' => 'purchases.view'],
                                    ['label' => 'Buku Hutang', 'route' => 'admin.reports.debts.index', 'match' => 'admin.reports.debts.*', 'permission' => 'reports.debts.view'],
                                ]
                            ],
                            [
                                'label' => 'Finance',
                                'icon' => 'fas fa-wallet',
                                'route' => 'admin.cash-transactions.index',
                                'match' => 'admin.cash-transactions.*|admin.expenses.*',
                                'permission' => 'cash-transactions.view',
                            ],
                            [
                                'label' => 'Marketing',
                                'icon' => 'fas fa-bullhorn',
                                'alt_icon' => 'fas fa-tags',
                                'route' => 'admin.promo-vouchers.index',
                                'match' => 'admin.promo-vouchers.*',
                                'permission' => 'promo-vouchers.view',
                            ],
                            [
                                'label' => 'Reports',
                                'icon' => 'fas fa-chart-column',
                                'alt_icon' => 'fas fa-file-invoice-dollar',
                                'route' => 'admin.reports.index',
                                'match' => 'admin.reports.*',
                                'permission' => 'reports.view',
                            ],
                        ];

                        $mainNav = collect($mainNav)
                            ->map(function (array $item) use ($canAccess) {
                                if (isset($item['children'])) {
                                    $item['children'] = array_values(array_filter($item['children'], function (array $child) use ($canAccess): bool {
                                        return $canAccess($child['permission'] ?? null);
                                    }));

                                    if (count($item['children']) === 0) {
                                        return null;
                                    }
                                } elseif (!$canAccess($item['permission'] ?? null)) {
                                    return null;
                                }

                                return $item;
                            })
                            ->filter()
                            ->values()
                            ->all();
                    @endphp

                    @foreach ($mainNav as $index => $item)
                        @php
                            $isActive = $item['match'] ? request()->routeIs(explode('|', $item['match'])) : false;
                            $hasChildren = isset($item['children']) && count($item['children']) > 0;
                            $menuId = 'submenu-' . $index;

                            $baseClass = "flex items-center px-4 py-3 text-xs font-bold rounded-xl transition-all duration-200 group cursor-pointer relative overflow-hidden";
                            $activeClass = "bg-indigo-600/10 text-indigo-400 ring-1 ring-indigo-500/20";
                            $inactiveClass = "text-slate-500 hover:text-white hover:bg-white/[0.03]";

                            $linkClass = $isActive ? "$baseClass $activeClass" : "$baseClass $inactiveClass";
                        @endphp

                        @if($hasChildren)
                            <div class="space-y-1">
                                <div class="{{ $linkClass }}" onclick="toggleSubmenu('{{ $menuId }}')">
                                    <div class="relative z-10 flex items-center w-full">
                                        <i
                                            class="{{ $item['icon'] ?? $item['alt_icon'] }} w-5 text-center mr-3 scale-110 {{ $isActive ? 'text-indigo-400' : 'text-slate-600 group-hover:text-slate-400' }}"></i>
                                        <span class="sidebar-text flex-1 uppercase tracking-wider">{{ $item['label'] }}</span>
                                        <i id="arrow-{{ $menuId }}"
                                            class="fas fa-chevron-down text-[10px] transition-transform duration-200 {{ $isActive ? 'rotate-180' : '' }} sidebar-text opacity-50"></i>
                                    </div>
                                    @if($isActive)
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                                    @endif
                                </div>
                                <div id="{{ $menuId }}"
                                    class="overflow-hidden transition-all duration-300 ease-in-out pl-4 space-y-1"
                                    style="{{ $isActive ? 'max-height: 1000px;' : 'max-height: 0px;' }}">
                                    @foreach($item['children'] as $child)
                                        @php
                                            $isChildActive = $child['match'] ? request()->routeIs(explode('|', $child['match'])) : false;
                                            $childClass = $isChildActive
                                                ? 'flex items-center pl-8 pr-3 py-2 text-[11px] font-bold text-indigo-400 border-l border-indigo-500/50 bg-indigo-500/5'
                                                : 'flex items-center pl-8 pr-3 py-2 text-[11px] font-bold text-slate-500 hover:text-white border-l border-white/5 hover:border-white/20 transition-all';
                                        @endphp
                                        <a href="{{ route($child['route']) }}"
                                            class="{{ $childClass }} sidebar-text uppercase tracking-widest">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}" class="{{ $linkClass }}">
                                <div class="relative z-10 flex items-center">
                                    <i
                                        class="{{ $item['icon'] ?? $item['alt_icon'] }} w-5 text-center mr-3 scale-110 {{ $isActive ? 'text-indigo-400' : 'text-slate-600 group-hover:text-slate-400' }}"></i>
                                    <span class="sidebar-text uppercase tracking-wider">{{ $item['label'] }}</span>
                                </div>
                                @if($isActive)
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Group: System Utilities --}}
                @php
                    $extras = [
                        ['label' => 'Perangkat POS', 'icon' => 'fas fa-tablet-screen-button', 'route' => 'admin.pos-devices.index', 'match' => 'admin.pos-devices.*', 'permission' => 'pos-devices.view'],
                        ['label' => 'Token Void', 'icon' => 'fas fa-key', 'route' => 'admin.void-tokens.index', 'match' => 'admin.void-tokens.*', 'permission' => 'void-tokens.view'],
                    ];

                    if ($currentUser?->hasRole('superadmin')) {
                        $extras[] = [
                            'label' => 'Hak Akses',
                            'icon' => 'fas fa-user-shield',
                            'route' => 'admin.permissions.index',
                            'match' => 'admin.permissions.*',
                            'permission' => 'permissions.manage',
                        ];
                    }

                    $extras = array_values(array_filter($extras, function (array $extra) use ($canAccess): bool {
                        return $canAccess($extra['permission'] ?? null);
                    }));

                    $canAccessPos = $currentUser?->hasRole(['superadmin', 'admin', 'manager', 'kasir']);
                @endphp

                @if($canAccessPos || count($extras) > 0)
                <div class="space-y-1">
                    <p class="px-4 text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-3 sidebar-text">
                        POS & Service</p>

                    @if($canAccessPos)
                    <a href="{{ route('pos.dashboard') }}"
                        class="flex items-center px-4 py-3 text-xs font-bold text-slate-500 hover:text-white hover:bg-white/[0.03] rounded-xl transition-all group uppercase tracking-wider">
                        <i
                            class="fas fa-cash-register w-5 text-center mr-3 scale-110 text-slate-600 group-hover:text-slate-400"></i>
                        <span class="sidebar-text">POS Kasir</span>
                    </a>
                    @endif
                    
                    @foreach($extras as $extra)
                        @php
                            $isExtraActive = request()->routeIs(explode('|', $extra['match']));
                            $extraClass = $isExtraActive
                                ? 'flex items-center px-4 py-3 text-xs font-bold text-indigo-400 bg-indigo-600/10 ring-1 ring-indigo-500/20 rounded-xl transition-all group uppercase tracking-wider relative'
                                : 'flex items-center px-4 py-3 text-xs font-bold text-slate-500 hover:text-white hover:bg-white/[0.03] rounded-xl transition-all group uppercase tracking-wider';
                        @endphp
                        <a href="{{ route($extra['route']) }}" class="{{ $extraClass }}">
                            <i
                                class="{{ $extra['icon'] }} w-5 text-center mr-3 scale-110 {{ $isExtraActive ? 'text-indigo-400' : 'text-slate-600 group-hover:text-slate-400' }}"></i>
                            <span class="sidebar-text">{{ $extra['label'] }}</span>
                            @if($isExtraActive)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                            @endif
                        </a>
                    @endforeach
                </div>
                @endif
            </nav>

            <!-- User Profile (Bottom) -->
            <div class="p-6">
                <div class="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/5">
                    <div class="flex items-center">
                        <div class="relative">
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-sm shadow-lg shadow-indigo-500/20 ring-2 ring-white/10">
                                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                            </div>
                            <div
                                class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-[#0B0F1A]">
                            </div>
                        </div>
                        <div class="ml-3 sidebar-text overflow-hidden">
                            <p class="text-xs font-black text-white truncate uppercase tracking-wider">
                                {{ auth()->user()->name ?? 'User' }}
                            </p>
                            <p class="text-[9px] font-bold text-slate-500 truncate uppercase tracking-[0.1em] mt-0.5">
                                {{ auth()->user()->role?->display_name ?? 'Administrator' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50/50 relative">

            <!-- Header -->
            <header
                class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-8 z-40 shrink-0 sticky top-0">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()"
                        class="hidden lg:flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all focus:outline-none mr-6 shadow-sm ring-1 ring-slate-200">
                        <i class="fas fa-bars-staggered text-sm"></i>
                    </button>
                    <button onclick="toggleMobileSidebar()"
                        class="lg:hidden h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all focus:outline-none mr-4 shadow-sm ring-1 ring-slate-200">
                        <i class="fas fa-bars text-sm"></i>
                    </button>

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-black text-slate-800 tracking-tight">
                                @yield('page-title', 'Dashboard')
                            </h1>
                            @hasSection('page-badge')
                                @yield('page-badge')
                            @endif
                        </div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                            @yield('page-subtitle', 'Overview & Business Insights')
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="hidden md:flex flex-col items-end">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Server Time</p>
                        <p class="text-xs font-bold text-slate-600">{{ now()->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="h-8 w-px bg-slate-200"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="group flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all shadow-sm ring-1 ring-rose-200"
                            title="Sign Out">
                            <i class="fas fa-power-off text-sm"></i>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 pb-20 scrollbar-hide">
                <div class="animate-in fade-in duration-500">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>