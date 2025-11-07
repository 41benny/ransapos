<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Morest POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-indigo-600 to-indigo-800 text-white flex-shrink-0 hidden md:flex flex-col">
            <div class="p-6 border-b border-indigo-500">
                <h1 class="text-2xl font-bold">Morest POS</h1>
                <p class="text-indigo-200 text-sm mt-1">Back Office</p>
            </div>
            
            <nav class="flex-1 p-4 overflow-y-auto">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>

                    <!-- Master Data -->
                    <li class="pt-4 pb-2">
                        <span class="text-indigo-300 text-xs font-semibold uppercase tracking-wider px-4">Master Data</span>
                    </li>
                    <li>
                        <a href="{{ route('admin.products.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.products.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Produk
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.outlets.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.outlets.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Outlet
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.suppliers.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.suppliers.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Supplier
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.purchases.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.purchases.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Pembelian
                        </a>
                    </li>

                    <!-- Keuangan -->
                    <li class="pt-4 pb-2">
                        <span class="text-indigo-300 text-xs font-semibold uppercase tracking-wider px-4">Keuangan</span>
                    </li>
                    <li>
                        <a href="{{ route('admin.cash-accounts.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.cash-accounts.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Kas & Bank
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.cash-transactions.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.cash-transactions.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Transaksi Kas
                        </a>
                    </li>

                    <!-- Akuntansi -->
                    <li class="pt-4 pb-2">
                        <span class="text-indigo-300 text-xs font-semibold uppercase tracking-wider px-4">Akuntansi</span>
                    </li>
                    <li>
                        <a href="{{ route('admin.coa-accounts.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.coa-accounts.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Chart of Accounts
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports.profit-loss.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.reports.profit-loss.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0h2a2 2 0 012-2h2a2 2 0 012 2v6a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Laporan Laba Rugi
                        </a>
                    </li>

                    <!-- Operasional -->
                    <li class="pt-4 pb-2">
                        <span class="text-indigo-300 text-xs font-semibold uppercase tracking-wider px-4">Operasional</span>
                    </li>
                    <li>
                        <a href="{{ route('admin.purchases.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.purchases.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Pembelian
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.cash-sessions.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.cash-sessions.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Shift Kasir
                        </a>
                    </li>

                    <!-- Laporan -->
                    <li class="pt-4 pb-2">
                        <span class="text-indigo-300 text-xs font-semibold uppercase tracking-wider px-4">Laporan</span>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports.sales.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.reports.sales.index') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Laporan Penjualan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports.sales.products') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.reports.sales.products') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Penjualan per Produk
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports.shifts.index') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition {{ request()->routeIs('admin.reports.shifts.*') ? 'bg-indigo-700' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Laporan Shift
                        </a>
                    </li>

                    <!-- POS -->
                    <li class="pt-4 pb-2">
                        <span class="text-indigo-300 text-xs font-semibold uppercase tracking-wider px-4">Point of Sale</span>
                    </li>
                    <li>
                        <a href="{{ route('pos.dashboard') }}" 
                           class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            POS Kasir
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile & Logout -->
            <div class="p-4 border-t border-indigo-500">
                <div class="flex items-center px-4 py-3 rounded-lg bg-indigo-700">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white font-semibold mr-3">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-indigo-200 truncate">{{ auth()->user()->role->display_name ?? 'User' }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-3 rounded-lg hover:bg-indigo-700 transition text-white">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Topbar -->
            <header class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-sm text-gray-500 mt-1">@yield('page-subtitle', 'Selamat datang di sistem back office')</p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->role->display_name ?? 'User' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

