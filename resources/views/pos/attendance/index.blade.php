@extends('layouts.pos_theme')

@section('content')

    {{-- Warning Block --}}
    <div class="rounded-r-lg border-l-4 border-amber-500 bg-amber-50 p-4 shadow-sm mb-6">
        <div class="flex items-start gap-3">
            <div class="rounded-full bg-amber-100 p-2 text-amber-700">
                <span class="material-icons-round text-xl">warning</span>
            </div>
            <div>
                <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-amber-800">Peringatan Penting</h3>
                <p class="text-xs leading-relaxed text-amber-700">
                    Titip absen adalah pelanggaran. Semua aktivitas absensi tercatat (waktu, IP address, kasir login)
                    dan dimonitor admin. Pastikan setiap karyawan absen sendiri dengan PIN masing-masing.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 mb-6">
            {{ session('error') }}
        </div>
    @endif

    {{-- Pencarian karyawan outlet lain (kasir rolling) --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
        <label class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500" for="cross-outlet-search">
            <span class="material-icons-round text-base text-indigo-600">person_search</span>
            Cari Karyawan dari Outlet Lain
        </label>
        <div class="relative">
            <span class="material-icons-round pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input id="cross-outlet-search" type="search" autocomplete="off"
                placeholder="Ketik minimal 3 huruf nama karyawan..."
                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
        </div>
        <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
            Gunakan hanya untuk kasir/karyawan yang benar-benar bertugas di outlet ini hari ini.
            Absensi akan tercatat di outlet ini, namun rekapnya tetap menempel pada satu nama karyawan.
        </p>
    </div>

    <div id="cross-outlet-results" class="mb-6 hidden">
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-600">Hasil Pencarian</h2>
        <div id="cross-outlet-results-grid" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"></div>
    </div>

    @if($crossOutletEmployees->isNotEmpty())
        <div class="mb-6">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-indigo-700">Karyawan Titipan Hari Ini</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($crossOutletEmployees as $employee)
                    @include('pos.attendance._employee-card', [
                        'employee' => $employee,
                        'shifts' => $shifts,
                        'currentOutletId' => $currentOutletId,
                    ])
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($employees as $employee)
            @include('pos.attendance._employee-card', [
                'employee' => $employee,
                'shifts' => $shifts,
                'currentOutletId' => $currentOutletId,
            ])
        @empty
            <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
                Tidak ada karyawan di outlet ini.
            </div>
        @endforelse
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function formatDuration(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `(${hours} jam ${mins} menit)`;
        }

        setInterval(function () {
            document.querySelectorAll('.duration-live').forEach(function (el) {
                const clockIn = parseInt(el.dataset.clockIn, 10);
                if (Number.isNaN(clockIn)) {
                    return;
                }

                const now = Math.floor(Date.now() / 1000);
                const diffMinutes = Math.floor((now - clockIn) / 60);
                el.textContent = formatDuration(diffMinutes);
            });
        }, 60000); // Update every minute

        // Pencarian karyawan outlet lain (kasir rolling)
        const searchInput = document.getElementById('cross-outlet-search');
        const resultsWrapper = document.getElementById('cross-outlet-results');
        const resultsGrid = document.getElementById('cross-outlet-results-grid');

        if (searchInput && resultsWrapper && resultsGrid) {
            let debounceTimer = null;
            let activeController = null;

            searchInput.addEventListener('input', function () {
                const keyword = searchInput.value.trim();
                clearTimeout(debounceTimer);

                if (keyword.length < 3) {
                    resultsWrapper.classList.add('hidden');
                    resultsGrid.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(function () {
                    if (activeController) {
                        activeController.abort();
                    }
                    activeController = new AbortController();

                    const url = '{{ route('pos.attendance.search') }}?q=' + encodeURIComponent(keyword);

                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: activeController.signal,
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Gagal memuat hasil pencarian');
                            }
                            return response.text();
                        })
                        .then(function (html) {
                            resultsGrid.innerHTML = html;
                            resultsWrapper.classList.remove('hidden');
                        })
                        .catch(function (error) {
                            if (error.name === 'AbortError') {
                                return;
                            }
                            resultsGrid.innerHTML = '<div class="col-span-full rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">Gagal memuat hasil pencarian. Coba lagi.</div>';
                            resultsWrapper.classList.remove('hidden');
                        });
                }, 350);
            });
        }
    });
</script>
@endsection
