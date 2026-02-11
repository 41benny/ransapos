<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') - Morest POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .latte-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #fdba74 100%);
        }

        .main-gradient {
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 50%, #fffbeb 100%);
        }
    </style>
</head>

<body class="font-sans antialiased h-screen flex flex-col bg-gray-100 dark:bg-gray-900">
    @php
        $isAttendancePage = request()->routeIs('pos.attendance.*');
    @endphp

    <header class="flex-none px-6 py-3 shadow-lg z-10 relative {{ $isAttendancePage ? 'bg-slate-800' : 'latte-gradient' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg {{ $isAttendancePage ? 'bg-red-600 shadow-lg shadow-red-900/40' : 'bg-white/20 backdrop-blur-sm' }}">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white leading-tight">{{ $isAttendancePage ? 'POS Moresto' : 'POS Latte' }}</h1>
                        <span class="text-xs text-white/90">@yield('page-title', 'Point of Sale')</span>
                    </div>
                </div>

                @if($isAttendancePage)
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="{{ route('pos.sessions.close') }}"
                            class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                            </svg>
                            Shift
                        </a>
                        <a href="{{ route('pos.attendance.index') }}"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-red-200 transition hover:bg-white/5 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                            </svg>
                            Absensi
                        </a>
                    </nav>
                @endif
            </div>

            <div class="flex items-center space-x-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-white/80">{{ auth()->user()->role?->display_name ?? 'Kasir' }} •
                        {{ now()->format('d M, H:i') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('pos.attendance.index') }}"
                        class="p-2 rounded-lg text-white transition flex items-center gap-2 px-3 {{ $isAttendancePage ? 'bg-white/10 hover:bg-white/20 border border-white/10' : 'bg-purple-500/80 hover:bg-purple-600' }}"
                        title="Absensi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="hidden md:inline font-medium text-sm">Absensi</span>
                    </a>

                    <a href="{{ route('pos.sessions.close') }}"
                        class="p-2 rounded-lg text-white transition flex items-center gap-2 px-3 {{ $isAttendancePage ? 'bg-white/10 hover:bg-white/20 border border-white/10' : 'bg-indigo-500/80 hover:bg-indigo-600' }}"
                        title="Tutup Shift">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                transform="rotate(180 12 12)" />
                        </svg>
                        <span class="hidden md:inline font-medium text-sm">Tutup Shift</span>
                    </a>

                    @if(auth()->user()->hasRole(['admin', 'manager']))
                        <a href="{{ route('admin.dashboard') }}"
                            class="p-2 bg-white/20 hover:bg-white/30 rounded-lg text-white transition border border-white/10" title="Back Office">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="p-2 rounded-lg text-white transition {{ $isAttendancePage ? 'bg-red-500 hover:bg-red-600' : 'bg-red-500/80 hover:bg-red-600' }}"
                            title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-auto relative">
        @yield('content')
    </main>

</body>

</html>
