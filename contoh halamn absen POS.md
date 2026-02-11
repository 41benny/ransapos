<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Moresto POS Attendance V2</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style type="text/tailwindcss">
        :root {
            --moresto-red: #D32F2F;--moresto-charcoal: #1e293b;--moresto-gold: #c5a065;--bg-soft-grey: #f3f4f6;
            --card-white: #ffffff;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-soft-grey);
            color: #334155;
        }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden">
<header class="bg-[var(--moresto-charcoal)] text-white h-16 flex items-center justify-between px-6 shadow-md z-10">
<div class="flex items-center gap-8">
<div class="flex items-center gap-2">
<div class="bg-red-600 rounded-lg p-1.5 flex items-center justify-center shadow-lg shadow-red-900/50">
<span class="material-symbols-outlined text-white text-[20px]">point_of_sale</span>
</div>
<h1 class="font-bold text-lg tracking-wide">POS <span class="text-red-400">Moresto</span></h1>
</div>
<nav class="hidden md:flex items-center gap-1">
<a class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 text-white font-medium border border-white/10 transition hover:bg-white/20" href="#">
<span class="material-symbols-outlined text-[20px]">calendar_clock</span>
<span>Shift</span>
</a>
<a class="flex items-center gap-2 px-4 py-2 rounded-lg text-gray-300 hover:text-white hover:bg-white/5 transition" href="#">
<span class="material-symbols-outlined text-[20px]">assignment_ind</span>
<span>Absensi</span>
</a>
</nav>
</div>
<div class="flex items-center gap-6">
<div class="text-right hidden sm:block">
<p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Current Session</p>
<p class="text-sm font-medium text-white">Sabtu, 21 Oktober 2023 | 10:45 AM</p>
</div>
<div class="h-8 w-px bg-gray-600 mx-2"></div>
<button class="flex items-center gap-2 text-red-400 hover:text-red-300 transition text-sm font-medium">
<span class="material-symbols-outlined text-[20px]">logout</span>
                Logout
            </button>
</div>
</header>
<main class="flex-1 flex flex-col items-center justify-center p-6 relative">
<div class="w-full max-w-4xl mb-8 animate-fade-in-down">
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r shadow-sm flex items-start gap-4">
<div class="bg-amber-100 p-2 rounded-full text-amber-600 shrink-0">
<span class="material-symbols-outlined">warning</span>
</div>
<div>
<h3 class="font-semibold text-amber-800 text-sm mb-1">Peringatan Penting</h3>
<p class="text-amber-700 text-xs leading-relaxed">
                        Mohon pastikan Anda melakukan Clock In tepat waktu sesuai jadwal shift yang telah ditentukan. Keterlambatan lebih dari 15 menit akan tercatat secara otomatis dan mempengaruhi penilaian performa bulanan. Jika ada kendala teknis, segera hubungi supervisor.
                    </p>
</div>
</div>
</div>
<div class="bg-[var(--card-white)] w-full max-w-md rounded-2xl shadow-xl border border-gray-100 overflow-hidden relative">
<div class="h-2 w-full bg-gradient-to-r from-[var(--moresto-charcoal)] via-red-600 to-[var(--moresto-charcoal)]"></div>
<div class="p-8 flex flex-col items-center text-center">
<div class="relative mb-6">
<div class="w-24 h-24 rounded-full border-4 border-[var(--moresto-gold)] p-1 shadow-lg bg-white mx-auto flex items-center justify-center overflow-hidden">
<img alt="Profile" class="w-full h-full rounded-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBLPn-I86BcRR_vAvusUzvW4GStxjfIUvsxyGCLPi_SqZbaJ9pCiCacLb0mLOOAusqOczU81MSIjePqe9H-5z0HCLls5UMw4SWpAYD4tqUgy0ND-GctWyFZ_oyaDgARmvIWxuP3pk42ae-VFHESsvaiErTi3EUFd1qm0IlzAKDsUBjOJvosjNNmE0AafC65N9qG-L2COpS7vvYJVWnR-PilTWgFCjVE4OhdOfL7Sg1v0KJQO13ERoxG8oGMisbvHYAuJM7MyscB74zg"/>
</div>
<div class="absolute bottom-0 right-0 bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-red-200 shadow-sm uppercase tracking-wide">
                        Belum Absen
                    </div>
</div>
<h2 class="text-2xl font-bold text-gray-800 mb-1">Kasir 1</h2>
<p class="text-sm text-gray-500 font-medium bg-gray-100 px-3 py-1 rounded-full inline-block mb-8">Staff Kasir - Shift Pagi</p>
<form class="w-full space-y-6">
<div class="space-y-2 text-left">
<label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider ml-1" for="pin">Enter Security PIN</label>
<div class="relative">
<span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
<span class="material-symbols-outlined text-[20px]">lock</span>
</span>
<input class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--moresto-red)] focus:border-transparent transition-all sm:text-sm tracking-widest text-center" id="pin" name="pin" placeholder="••••••" type="password"/>
</div>
</div>
<button class="w-full bg-[var(--moresto-red)] hover:bg-red-800 text-white font-semibold py-3.5 px-4 rounded-lg shadow-lg shadow-red-500/30 transition-all duration-200 transform hover:translate-y-[-1px] active:translate-y-[0px] flex items-center justify-center gap-2" type="button">
<span class="material-symbols-outlined">schedule</span>
                        Clock In
                    </button>
</form>
<div class="mt-8 pt-6 border-t border-gray-100 w-full flex justify-between items-center text-xs text-gray-400">
<span>System Version 2.4.1</span>
<a class="hover:text-[var(--moresto-red)] transition" href="#">Need Help?</a>
</div>
</div>
</div>
</main>

</body></html>