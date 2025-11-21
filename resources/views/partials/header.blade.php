<!-- Header POS Latte -->
<header class="bg-gradient-to-r from-amber-50 via-white to-orange-50 border-b border-amber-100 shadow-sm">
    <div class="px-5 py-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-[11px] uppercase tracking-[0.24em] text-amber-700/80">
                Kasir · POS Latte
            </p>
            <h2 class="text-base md:text-lg font-semibold text-slate-900">
                @yield('page-title', 'Meja &amp; Transaksi')
            </h2>
            <p class="text-[11px] text-amber-700 mt-0.5">
                {{ now()->translatedFormat('l, d F Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            {{-- Kalau halaman punya section "header-actions", pakai itu --}}
            @hasSection('header-actions')
                @yield('header-actions')
            @else
                {{-- Default: filter tanggal + Tahan Bill + Bayar (QRIS) --}}
                <div class="flex items-center gap-2">
                    <label class="text-[11px] font-medium text-amber-900">
                        Tanggal:
                    </label>
                    <input
                        type="date"
                        value="{{ now()->format('Y-m-d') }}"
                        class="px-3 py-1.5 rounded-xl border border-amber-200 bg-white text-[11px] text-slate-800
                               focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                    >
                </div>

                <button
                    type="button"
                    class="px-3 py-1.5 rounded-2xl border border-amber-200 bg-white text-[11px] font-medium text-amber-800 shadow-sm"
                >
                    Tahan Bill
                </button>

                <button
                    type="button"
                    class="px-4 py-1.5 rounded-2xl text-[11px] font-semibold text-white shadow-md
                           bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500"
                >
                    Bayar (QRIS)
                </button>
            @endif
        </div>
    </div>
</header>
