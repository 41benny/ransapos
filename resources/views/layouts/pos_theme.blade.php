<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Moresto POS Dashboard</title>
    {{-- Utilizing Tailwind CDN for specific custom theme configuration as requested --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#D32F2F", // Moresto Red accent
                        secondary: "#7C4DFF", // Light purple accent for 'Absensi'
                        "background-light": "#F3F4F6", // Light gray background
                        "background-dark": "#121212", // Very dark background
                        "surface-light": "#FFFFFF",
                        "surface-dark": "#1E1E1E",
                        "surface-accent-dark": "#2D2D2D",
                        "text-main-light": "#1F2937",
                        "text-main-dark": "#E5E7EB",
                        "text-muted-light": "#6B7280",
                        "text-muted-dark": "#9CA3AF",
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        'glow': '0 0 15px rgba(211, 47, 47, 0.3)',
                    }
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-background-light text-text-main-light min-h-screen transition-colors duration-300">

    <nav class="bg-surface-light border-b border-gray-200 sticky top-0 z-50 transition-colors duration-300 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="{{ route('pos.dashboard') }}"
                    class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="bg-primary/10 p-2 rounded-lg">
                        <span class="material-icons-round text-primary text-2xl">point_of_sale</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 leading-tight tracking-tight">POS Latte</h1>
                        <p class="text-xs text-text-muted-light font-medium">Moresto Dimsum</p>
                    </div>
                </a>
                <div class="flex items-center gap-3">
                    <div class="hidden md:flex flex-col items-end mr-3">
                        <span class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</span>
                        <span class="text-xs text-text-muted-light">{{ auth()->user()->role?->display_name ?? 'Kasir' }}
                            - {{ now()->format('d M, H:i') }}</span>
                    </div>

                    @if(!request()->routeIs('pos.dashboard'))
                        <a href="{{ route('pos.dashboard') }}"
                            class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-md font-medium transition-all duration-200 text-sm">
                            <span class="material-icons-round text-base">dashboard</span>
                            <span class="hidden sm:inline">Dashboard</span>
                        </a>
                    @endif

                    <a href="{{ route('pos.attendance.index') }}"
                        class="flex items-center gap-2 bg-secondary/10 hover:bg-secondary/20 text-secondary px-3 py-2 rounded-md font-medium transition-all duration-200 text-sm">
                        <span class="material-icons-round text-base">people</span>
                        <span class="hidden sm:inline">Absensi</span>
                    </a>

                    <a href="{{ route('pos.sessions.close') }}"
                        class="flex items-center gap-2 bg-primary/10 hover:bg-primary/20 text-primary px-3 py-2 rounded-md font-medium transition-all duration-200 text-sm">
                        <span class="material-icons-round text-base">logout</span>
                        <span class="hidden sm:inline">Tutup Shift</span>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" aria-label="Logout"
                            class="flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-600 w-9 h-9 rounded-md transition-all duration-200">
                            <span class="material-icons-round text-lg">exit_to_app</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">
        @yield('content')
    </main>

</body>

</html>