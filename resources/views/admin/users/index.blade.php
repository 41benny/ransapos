@extends('layouts.admin')

@section('title', 'User')
@section('page-title', 'User')
@section('page-subtitle', 'Kelola akun pengguna dan outlet')

@section('content')
    @php
        $totalUsers = $posUsers->total() + $officeUsers->total();
    @endphp
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Daftar Pengguna</h1>
                <p class="text-xs font-normal text-slate-700 mt-0.5">Kelola akun pengguna, role, dan penempatan outlet</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.attendance.index') }}"
                    class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs font-normal text-slate-600 shadow-sm transition-all hover:text-indigo-600 hover:border-indigo-100 active:scale-95">
                    <i class="fas fa-calendar-check text-[10px]"></i>
                    <span>Rekap Absensi</span>
                </a>
                <a href="{{ route('admin.users.create') }}"
                    class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>Tambah User</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600 animate-in slide-in-from-top-2">
                <i class="fas fa-check-circle"></i>
                <p class="text-xs font-normal">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center gap-3 text-rose-600 animate-in slide-in-from-top-2">
                <i class="fas fa-exclamation-circle"></i>
                <p class="text-xs font-normal">{{ session('error') }}</p>
            </div>
        @endif

        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-4 md:p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-[11px] font-normal uppercase tracking-widest text-slate-500">Filter Daftar Pengguna</h3>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-normal uppercase tracking-widest text-indigo-600">
                    Total {{ $totalUsers }} User
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-5">
                    <input type="text" data-name="name" class="ui-input filter-input w-full px-3 py-2 text-xs font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400" placeholder="Cari nama pengguna...">
                </div>
                <div class="md:col-span-4">
                    <input type="text" data-name="role" class="ui-input filter-input w-full px-3 py-2 text-xs font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400" placeholder="Filter role...">
                </div>
                <div class="md:col-span-2">
                    <select data-name="status" class="ui-input filter-input w-full px-3 py-2 text-xs font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <button type="button" id="clearFilters" class="ui-btn ui-btn-ghost h-[34px] w-full inline-flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-rose-500 hover:border-rose-100 rounded-lg transition-all active:scale-95" title="Reset Filter">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            @include('admin.users.partials.user-card-table', [
                'title' => 'User POS',
                'subtitle' => 'Kasir, Kitchen, dan Karyawan Outlet',
                'users' => $posUsers,
                'emptyText' => 'Belum ada user POS'
            ])

            @include('admin.users.partials.user-card-table', [
                'title' => 'User Office',
                'subtitle' => 'Admin, Manager, Superadmin, Pajak, dan role office lain',
                'users' => $officeUsers,
                'emptyText' => 'Belum ada user Office'
            ])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.action-dropdown-btn');
            const container = e.target.closest('.product-actions-dropdown');

            if (btn || !container) {
                document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
                    if (!container || menu !== container.querySelector('.action-dropdown-menu')) {
                        menu.classList.add('hidden');
                    }
                });
            }

            if (btn && container) {
                const menu = container.querySelector('.action-dropdown-menu');
                menu.classList.toggle('hidden');
            }
        });

        const filterInputs = document.querySelectorAll('.filter-input');
        const clearFiltersBtn = document.getElementById('clearFilters');

        function populateFilters() {
            const params = new URLSearchParams(window.location.search);

            filterInputs.forEach(input => {
                const name = input.dataset.name;
                if (params.has(name)) {
                    input.value = params.get(name);
                }
            });
        }

        let debounceTimer;
        function updateFilter(name, value) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const url = new URL(window.location.href);

                if (value.trim() !== '') {
                    url.searchParams.set(name, value.trim());
                } else {
                    url.searchParams.delete(name);
                }

                url.searchParams.delete('page');
                url.searchParams.delete('pos_page');
                url.searchParams.delete('office_page');
                window.location.href = url.toString();
            }, 500);
        }

        filterInputs.forEach(input => {
            const name = input.dataset.name;

            if (input.tagName === 'SELECT') {
                input.addEventListener('change', (e) => {
                    updateFilter(name, e.target.value);
                });
            } else {
                input.addEventListener('input', (e) => {
                    updateFilter(name, e.target.value);
                });
            }
        });

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function () {
                const url = new URL(window.location.href);

                filterInputs.forEach(input => {
                    url.searchParams.delete(input.dataset.name);
                });

                url.searchParams.delete('page');
                url.searchParams.delete('pos_page');
                url.searchParams.delete('office_page');
                window.location.href = url.toString();
            });
        }

        populateFilters();
    </script>
@endpush
