@extends('layouts.admin')

@section('title', 'Role & Permission')
@section('page-title', 'Role & Permission')
@section('page-subtitle', 'Checklist permission untuk role back office')

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Hak Akses & Role</h1>
            <p class="text-xs font-normal text-slate-700 mt-0.5">Konfigurasi batasan akses untuk setiap jabatan dalam sistem</p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600">
                <i class="fas fa-check-circle"></i>
                <p class="text-xs font-normal">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <p class="text-[10px] font-normal text-slate-500 uppercase tracking-widest">Jabatan Terdaftar</p>
                <div class="flex items-baseline gap-2 mt-2">
                    <p class="text-2xl font-normal text-slate-900">{{ $roles->count() }}</p>
                    <p class="text-[10px] font-normal text-slate-400">Role</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <p class="text-[10px] font-normal text-slate-500 uppercase tracking-widest">Matriks Akses</p>
                <div class="flex items-baseline gap-2 mt-2">
                    <p class="text-2xl font-normal text-slate-900">{{ $totalPermissions }}</p>
                    <p class="text-[10px] font-normal text-slate-400">Titik Kontrol</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm bg-indigo-50/30">
                <p class="text-[10px] font-normal text-indigo-600 uppercase tracking-widest">Cakupan Sistem</p>
                <p class="text-[11px] font-normal text-slate-700 mt-3 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-[10px] text-indigo-400"></i>
                    Back Office (`/admin/*`)
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            {{-- Table Header --}}
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-[13px] font-normal text-slate-900 leading-none">Matriks Role</h3>
                        <p class="text-[10px] font-normal text-slate-500 mt-1 uppercase tracking-widest">Khusus role `manager` memiliki akses penuh otomatis</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-600">Nama Jabatan</th>
                            <th class="px-6 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-600">Deskripsi</th>
                            <th class="px-6 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-600">Aktivitas Akses</th>
                            <th class="px-6 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-600">Konfigurasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($roles as $role)
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-[11.5px] font-normal text-slate-900 leading-tight">{{ $role->display_name ?? ucfirst($role->name) }}</span>
                                        <span class="text-[10px] font-normal text-slate-500 mt-0.5">{{ $role->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[11px] font-normal text-slate-600 italic">{{ $role->description ?: 'Tidak ada deskripsi' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($role->name === 'manager')
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[9px] font-normal text-indigo-600 ring-1 ring-inset ring-indigo-200 uppercase tracking-widest italic">Full Access (Superadmin)</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-[9px] font-normal text-slate-600 ring-1 ring-inset ring-slate-200 uppercase tracking-widest bold">
                                            {{ $role->permissions_count }} Poin Akses
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($role->name === 'manager')
                                        <span class="text-[10px] text-slate-400 italic">Otomatis Terbuka</span>
                                    @else
                                        <a href="{{ route('admin.permissions.edit', $role) }}"
                                            class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-[10px] font-normal text-slate-700 shadow-sm transition-all hover:text-indigo-600 hover:border-indigo-100 active:scale-95">
                                            <i class="fas fa-sliders-h text-[9px]"></i>
                                            <span>Kelola Akses</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-30">
                                        <i class="fas fa-shield-virus text-5xl text-slate-300 mb-4"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Belum ada role terdaftar</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
