<html lang="en"><head></head><body class="bg-background-light dark:bg-background-dark text-text-main-light dark:text-text-main-dark min-h-screen transition-colors duration-300">```html



<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Domsteak POS Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
<script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#D32F2F", // Domsteak Red accent
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
        }::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }
    </style>


<nav class="bg-surface-light dark:bg-surface-dark border-b border-gray-200 dark:border-gray-800 sticky top-0 z-50 transition-colors duration-300">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="flex justify-between h-20 items-center">
<div class="flex items-center gap-4">
<div class="bg-primary/10 p-2.5 rounded-xl">
<span class="material-icons-round text-primary text-3xl">point_of_sale</span>
</div>
<div>
<h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight tracking-tight">POS Latte</h1>
<p class="text-sm text-text-muted-light dark:text-text-muted-dark font-medium">Domsteak Dimsum</p>
</div>
</div>
<div class="flex items-center gap-4">
<div class="hidden md:flex flex-col items-end mr-4">
<span class="text-sm font-semibold text-gray-900 dark:text-white">Naila Safitri</span>
<span class="text-xs text-text-muted-light dark:text-text-muted-dark">Kasir - 11 Feb, 22:17</span>
</div>
<button class="flex items-center gap-2 bg-secondary/10 hover:bg-secondary/20 text-secondary dark:text-purple-300 px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
<span class="material-icons-round text-lg">people</span>
<span>Absensi</span>
</button>
<button class="flex items-center gap-2 bg-primary/10 hover:bg-primary/20 text-primary dark:text-red-300 px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
<span class="material-icons-round text-lg">logout</span>
<span>Tutup Shift</span>
</button>
<button aria-label="Logout" class="flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-surface-accent-dark dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 w-11 h-11 rounded-lg transition-all duration-200">
<span class="material-icons-round">exit_to_app</span>
</button>
<button class="ml-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" onclick="document.documentElement.classList.toggle('dark')">
<span class="material-icons-round dark:hidden">dark_mode</span>
<span class="material-icons-round hidden dark:block">light_mode</span>
</button>
</div>
</div>
</div>
</nav>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
<div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-soft dark:border dark:border-gray-800 overflow-hidden relative group">
<div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
<div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
<div>
<div class="flex items-center gap-2 mb-2">
<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
<h2 class="text-sm uppercase tracking-wider font-semibold text-emerald-600 dark:text-emerald-400">Sesi Aktif</h2>
</div>
<h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white font-mono tracking-tight mb-2">CS-0UT06-20260211-001</h3>
<p class="text-text-muted-light dark:text-text-muted-dark flex items-center gap-1.5 text-sm">
<span class="material-icons-round text-base">schedule</span>
                        Dibuka: 11 Feb 2026, 22:17
                    </p>
</div>
<div class="flex flex-col md:flex-row items-start md:items-center gap-8 w-full md:w-auto">
<div class="text-left md:text-right">
<p class="text-sm text-text-muted-light dark:text-text-muted-dark font-medium mb-1">Saldo Awal</p>
<p class="text-3xl font-bold text-gray-900 dark:text-white">Rp 0</p>
</div>
<button class="w-full md:w-auto bg-primary hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg shadow-red-500/20 hover:shadow-red-500/40 transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
<span class="material-icons-round">lock_clock</span>
                        Tutup Shift
                    </button>
</div>
</div>
</div>
<div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-soft dark:border dark:border-gray-800 min-h-[500px] flex flex-col relative overflow-hidden">
<div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-white/5">
<h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
<span class="material-icons-round text-primary">receipt_long</span>
                    Transaksi Hari Ini
                </h2>
<div class="flex gap-2">
<span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full text-xs font-semibold">0 Transaksi</span>
</div>
</div>
<div class="flex-1 flex flex-col items-center justify-center p-8 text-center relative z-10">
<div class="w-48 h-48 rounded-full bg-gray-50 dark:bg-surface-accent-dark flex items-center justify-center mb-8 relative">
<div class="absolute inset-0 border border-dashed border-gray-200 dark:border-gray-700 rounded-full animate-[spin_10s_linear_infinite]"></div>
<span class="material-icons-round text-8xl text-gray-300 dark:text-gray-600">assignment_late</span>
</div>
<h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Belum ada transaksi hari ini</h3>
<p class="text-text-muted-light dark:text-text-muted-dark max-w-md mb-8">
                    Data penjualan akan muncul di sini setelah Anda memulai transaksi pertama. Siap melayani pelanggan?
                </p>
<button class="bg-secondary hover:bg-violet-700 text-white px-8 py-3.5 rounded-xl font-bold text-lg shadow-lg shadow-violet-500/20 hover:shadow-violet-500/40 transition-all duration-300 transform hover:scale-105 flex items-center gap-3 group">
<span class="material-icons-round group-hover:rotate-12 transition-transform">add_shopping_cart</span>
                    Mulai Transaksi
                </button>
</div>
<div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#6B7280 1px, transparent 1px); background-size: 24px 24px;"></div>
</div>
</main>
<div class="fixed bottom-8 right-8 z-50">
<button class="bg-secondary hover:bg-violet-700 text-white w-14 h-14 rounded-full shadow-xl shadow-violet-500/30 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 group">
<span class="material-icons-round text-3xl group-hover:rotate-90 transition-transform duration-300">add</span>
</button>
</div>

</body></html>