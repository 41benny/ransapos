@extends('layouts.admin')

@section('title', 'User')
@section('page-title', 'User')
@section('page-subtitle', 'Kelola akun pengguna dan outlet')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Daftar Pengguna</h1>
                <p class="text-xs font-normal text-slate-700 mt-0.5">Kelola akun pengguna, role, dan penempatan outlet</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.attendance.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs font-normal text-slate-600 shadow-sm transition-all hover:text-indigo-600 hover:border-indigo-100 active:scale-95">
                    <i class="fas fa-calendar-check text-[10px]"></i>
                    <span>Rekap Absensi</span>
                </a>
                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>Tambah User</span>
                </a>
            </div>
        </div>

        {{-- Alert Success/Error --}}
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

        {{-- Table Section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                        <tr>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-600">Nama & Email</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-600">Role & Outlet</th>
                            <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-600">Status</th>
                            <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-600">Aksi</th>
                        </tr>
                        {{-- Filter Row --}}
                        <tr class="bg-slate-50/30">
                            <td class="px-4 py-2">
                                <input type="text" data-name="name" class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400" placeholder="Cari nama...">
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" data-name="role" class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400" placeholder="Filter role...">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <select data-name="status" class="filter-input w-32 px-3 py-1.5 text-xs font-normal bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                    <option value="">Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button type="button" id="clearFilters" class="h-8 w-8 inline-flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-rose-500 hover:border-rose-100 rounded-lg transition-all active:scale-95" title="Reset Filter">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($users as $user)
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-normal text-slate-900 leading-tight">{{ $user->name }}</span>
                                        <span class="text-xs font-normal text-slate-500 mt-0.5">{{ $user->email }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[11px] font-normal text-indigo-600 uppercase tracking-wider">{{ $user->role?->display_name ?? $user->role?->name ?? '-' }}</span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <i class="fas fa-store text-[8px] text-slate-400"></i>
                                            <span class="text-[10px] font-normal text-slate-600">{{ $user->outlet?->name ?? 'Semua Outlet' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[9px] font-normal text-emerald-600 ring-1 ring-inset ring-emerald-200 uppercase tracking-widest">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-[9px] font-normal text-slate-400 ring-1 ring-inset ring-slate-200 uppercase tracking-widest">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="relative inline-block text-left product-actions-dropdown">
                                        <button type="button" class="action-dropdown-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-normal text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                            <span>Aksi</span>
                                            <i class="fas fa-chevron-down text-[8px] text-slate-400"></i>
                                        </button>
                                        <div class="action-dropdown-menu hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-xl bg-white shadow-xl border border-slate-100 ring-1 ring-black ring-opacity-5 animate-in fade-in slide-in-from-top-2 duration-200">
                                            <div class="py-1">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-amber-600 transition-colors">
                                                    <i class="fas fa-edit w-4 text-center opacity-70"></i>
                                                    Edit User
                                                </a>
                                                
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="block border-t border-slate-50" onsubmit="return confirm('Nonaktifkan user ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-[11px] text-rose-500 hover:bg-rose-50 transition-colors">
                                                        <i class="fas fa-user-slash w-4 text-center opacity-70"></i>
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-30">
                                        <i class="fas fa-users-slash text-5xl text-slate-300 mb-4"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Belum ada user terdaftar</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .filter-row th {
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
    </style>

    <script>
        // Action Dropdown Handler
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.action-dropdown-btn');
        const container = e.target.closest('.product-actions-dropdown');
        
        // Close all other menus first
        if (btn || !container) {
            document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
                if (!container || menu !== container.querySelector('.action-dropdown-menu')) {
                    menu.classList.add('hidden');
                }
            });
        }

        // Toggle current menu
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
                window.location.href = url.toString();
            });
        }

        populateFilters();
    </script>
@endpush
