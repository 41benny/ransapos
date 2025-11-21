<!-- Sidebar POS Latte -->
<aside class="w-64 min-h-screen bg-gradient-to-b from-amber-500 via-amber-400 to-orange-300 text-brown-950 relative shadow-xl">
    <!-- Glow overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.26),_transparent_60%)] mix-blend-soft-light pointer-events-none"></div>

    <div class="relative flex flex-col h-full">
        {{-- Brand / Logo --}}
        <div class="px-4 pt-4 pb-3 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase tracking-[0.2em] text-amber-100/90">Tomorrow Coffee</p>
                <p class="text-sm font-semibold text-brown-900">Kasir Resto</p>
            </div>
            <span class="px-2 py-1 rounded-full text-[10px] bg-brown-900/90 text-amber-100">
                Shift A
            </span>
        </div>

        {{-- NAV --}}
        <nav class="px-3 pb-3 space-y-2 text-[13px]">
            {{-- Kasir --}}
            <a
                href="{{ route('pos.index') }}"
                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-2xl bg-white text-brown-900 shadow-md
                       {{ request()->routeIs('pos.index') ? '' : '' }}"
            >
                <span class="flex items-center gap-2">
                    <span class="h-6 w-6 rounded-xl bg-amber-500 text-[11px] flex items-center justify-center text-white">
                        ⏱
                    </span>
                    <span class="text-[12px] font-semibold">Kasir</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                    ACTIVE
                </span>
            </a>

            {{-- Meja & Antrian --}}
            <a
                href="{{ route('pos.tables.index', false) ?: '#' }}"
                class="w-full flex items-center gap-2 px-3 py-2.5 rounded-2xl/90 text-amber-50/95 hover:bg-white/10 transition"
            >
                <span class="h-6 w-6 rounded-xl bg-white/15 text-[11px] flex items-center justify-center">
                    🍽
                </span>
                <span class="text-[12px] font-medium">
                    Meja &amp; Antrian
                </span>
            </a>

            {{-- Daftar Menu --}}
            <a
                href="{{ route('pos.menu.index', false) ?: '#' }}"
                class="w-full flex items-center gap-2 px-3 py-2.5 rounded-2xl/90 text-amber-50/95 hover:bg-white/10 transition"
            >
                <span class="h-6 w-6 rounded-xl bg-white/15 text-[11px] flex items-center justify-center">
                    📜
                </span>
                <span class="text-[12px] font-medium">
                    Daftar Menu
                </span>
            </a>

            {{-- Promo & Voucher --}}
            <a
                href="{{ route('pos.promo.index', false) ?: '#' }}"
                class="w-full flex items-center gap-2 px-3 py-2.5 rounded-2xl/90 text-amber-50/95 hover:bg-white/10 transition"
            >
                <span class="h-6 w-6 rounded-xl bg-white/15 text-[11px] flex items-center justify-center">
                    🎟
                </span>
                <span class="text-[12px] font-medium">
                    Promo &amp; Voucher
                </span>
            </a>
        </nav>

        {{-- Footer / Info Kasir --}}
        <div class="mt-auto px-4 pb-4 pt-3 border-t border-amber-200/60 flex items-center justify-between text-[11px] text-amber-50/95">
            <span>
                Kasir: {{ auth()->user()->name ?? 'Kasir' }}
            </span>
            <span class="flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                Online
            </span>
        </div>
    </div>
</aside>
