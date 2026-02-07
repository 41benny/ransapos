<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') - Morest POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Tailwind CSS -->


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

    <!-- Top Navigation (Latte Theme) -->
    <header class="flex-none latte-gradient px-6 py-3 shadow-lg z-10 relative">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white leading-tight">POS Latte</h1>
                        <span class="text-xs text-white/90">@yield('page-title', 'Point of Sale')</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-white/80">{{ auth()->user()->role->display_name ?? 'Kasir' }} •
                        {{ now()->format('d M, H:i') }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Attendance Button -->
                    <a href="{{ route('pos.attendance.index') }}"
                        class="p-2 bg-purple-500/80 hover:bg-purple-600 rounded-lg text-white transition flex items-center gap-2 px-3"
                        title="Absensi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="hidden md:inline font-medium text-sm">Absensi</span>
                    </a>

                    <!-- Close Shift Button -->
                    <a href="{{ route('pos.sessions.close') }}"
                        class="p-2 bg-indigo-500/80 hover:bg-indigo-600 rounded-lg text-white transition flex items-center gap-2 px-3"
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
                            class="p-2 bg-white/20 hover:bg-white/30 rounded-lg text-white transition" title="Back Office">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="p-2 bg-red-500/80 hover:bg-red-600 rounded-lg text-white transition" title="Logout">
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

    <!-- Main Content (Fills remaining height) -->
    <main class="flex-1 overflow-hidden relative">
        @yield('content')
    </main>

</body>

</html>