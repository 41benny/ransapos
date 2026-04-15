<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') - Morest POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap" rel="stylesheet">

    <style>
        .latte-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #fdba74 100%);
        }

        .main-gradient {
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 50%, #fffbeb 100%);
        }

        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24;
            line-height: 1;
        }
    </style>
</head>

<body class="antialiased h-screen flex flex-col bg-gray-100 dark:bg-gray-900" style="font-family: 'Inter', sans-serif;">
    @php
        $isAttendancePage = request()->routeIs('pos.attendance.*');
    @endphp

    <header class="flex-none px-4 md:px-6 py-3 shadow-lg z-10 relative {{ $isAttendancePage ? 'bg-slate-800' : 'latte-gradient' }}">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl {{ $isAttendancePage ? 'bg-red-600 shadow-lg shadow-red-900/40' : 'bg-white/20 backdrop-blur-sm' }}">
                        <span class="material-symbols-outlined text-white text-[20px]">point_of_sale</span>
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-bold text-white leading-tight">
                            <a href="{{ route('pos.sales.create') }}" class="hover:text-white/90 transition">
                                {{ $isAttendancePage ? 'POS Moresto' : 'POS Latte' }}
                            </a>
                        </h1>
                        <span class="text-xs text-white/90">@yield('page-title', 'Point of Sale')</span>
                    </div>
                </div>

                @if($isAttendancePage)
                    <nav class="hidden sm:flex items-center gap-1">
                        <a href="{{ route('pos.sales.create') }}"
                            class="flex h-11 items-center gap-2 rounded-lg border border-white/10 bg-white/10 px-4 text-sm font-medium text-white transition hover:bg-white/20">
                            <span class="material-symbols-outlined text-[20px]">point_of_sale</span>
                            Kasir
                        </a>
                        <a href="{{ route('pos.sessions.close') }}"
                            class="flex h-11 items-center gap-2 rounded-lg border border-white/10 bg-white/10 px-4 text-sm font-medium text-white transition hover:bg-white/20">
                            <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                            Shift
                        </a>
                        <a href="{{ route('pos.attendance.index') }}"
                            class="flex h-11 items-center gap-2 rounded-lg px-4 text-sm font-medium text-red-200 transition hover:bg-white/5 hover:text-white">
                            <span class="material-symbols-outlined text-[20px]">group</span>
                            Absensi
                        </a>
                    </nav>
                @endif
            </div>

            <div class="flex items-center space-x-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-white/80">{{ auth()->user()->role?->display_name ?? 'Kasir' }} -
                        {{ now()->format('d M, H:i') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if(!$isAttendancePage)
                        <a href="{{ route('pos.attendance.index') }}"
                            class="h-11 rounded-lg text-white transition flex items-center gap-2 px-3 bg-purple-500/80 hover:bg-purple-600"
                            title="Absensi">
                            <span class="material-symbols-outlined text-[20px]">group</span>
                            <span class="hidden md:inline font-medium text-sm">Absensi</span>
                        </a>

                        <a href="{{ route('pos.sessions.close') }}"
                            class="h-11 rounded-lg text-white transition flex items-center gap-2 px-3 bg-indigo-500/80 hover:bg-indigo-600"
                            title="Tutup Shift">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                            <span class="hidden md:inline font-medium text-sm">Tutup Shift</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasRole(['admin', 'manager', 'superadmin']))
                        <a href="{{ route('admin.dashboard') }}"
                            class="h-11 w-11 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded-lg text-white transition border border-white/10" title="Back Office">
                            <span class="material-symbols-outlined text-[20px]">space_dashboard</span>
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="h-11 w-11 flex items-center justify-center rounded-lg text-white transition {{ $isAttendancePage ? 'bg-red-500 hover:bg-red-600' : 'bg-red-500/80 hover:bg-red-600' }}"
                            title="Logout">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-auto relative">
        @yield('content')
    </main>

    @include('partials.footer', [
        'footerClass' => 'border-t border-gray-200 bg-white/90 px-4 py-2 text-[11px] text-gray-500'
    ])

</body>

</html>
