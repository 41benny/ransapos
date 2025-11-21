<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') - Morest POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .latte-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #fdba74 100%);
        }
        .main-gradient {
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 50%, #fffbeb 100%);
        }
    </style>
</head>
<body class="font-sans antialiased">
    
    <!-- Top Navigation (Latte Theme) -->
    <header class="latte-gradient px-6 py-3 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold text-white">POS Latte</h1>
                <span class="text-white opacity-70">|</span>
                <span class="text-sm text-white opacity-90">@yield('page-title', 'Point of Sale')</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400">{{ auth()->user()->role->display_name ?? 'Kasir' }} • {{ now()->format('d M Y, H:i') }}</p>
                </div>
                @if(auth()->user()->hasRole(['admin', 'manager']))
                <a href="{{ route('admin.dashboard') }}" 
                   class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Back Office
                </a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm transition flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content (Fullscreen) -->
    <main class="h-[calc(100vh-64px)] overflow-hidden">
        @yield('content')
    </main>

</body>
</html>

