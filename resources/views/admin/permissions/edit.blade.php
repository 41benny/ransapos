@extends('layouts.admin')

@section('title', 'Checklist Permission')
@section('page-title', 'Checklist Permission')
@section('page-subtitle', 'Atur hak akses untuk role: ' . ($role->display_name ?? $role->name))

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header & Info --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <a href="{{ route('admin.permissions.index') }}"
                        class="ui-btn ui-btn-ghost h-8 w-8 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                        <i class="fas fa-arrow-left text-[10px]"></i>
                    </a>
                    <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Kelola Hak Akses</h1>
                </div>
                <p class="text-xs font-normal text-slate-700 ml-11">Atur batasan fitur untuk jabatan: <span
                        class="font-bold text-indigo-600">{{ $role->display_name ?? $role->name }}</span></p>
            </div>
            <div class="no-print">
                <span
                    class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-normal text-indigo-600 ring-1 ring-inset ring-indigo-200 uppercase tracking-widest italic">
                    Internal Identifier: {{ $role->name }}
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600">
                <i class="fas fa-check-circle"></i>
                <p class="text-xs font-normal">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4">
                <div class="flex items-center gap-3 text-rose-600 mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <p class="text-xs font-bold uppercase tracking-widest">Validasi Gagal</p>
                </div>
                <ul class="list-disc ml-8 space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="text-[11px] text-rose-500">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Duplication Card --}}
        <div class="ui-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-6">
            <div class="flex items-center gap-3 mb-5">
                <div
                    class="h-8 w-8 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center border border-slate-100">
                    <i class="fas fa-copy text-[10px]"></i>
                </div>
                <h3 class="text-[12px] font-normal text-slate-900 uppercase tracking-widest">Duplikasi Konfigurasi</h3>
            </div>
            <form method="POST" action="{{ route('admin.permissions.duplicate', $role) }}"
                class="flex items-end gap-3 flex-wrap">
                @csrf
                <div class="flex-1 min-w-[240px]">
                    <label for="source_role_id"
                        class="text-[9px] font-normal text-slate-500 uppercase tracking-wider ml-1 mb-1.5 block">Salin Hak
                        Akses Dari Role:</label>
                    <select name="source_role_id" id="source_role_id"
                        class="ui-input w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
                        @foreach($sourceRoles as $sourceRole)
                            <option value="{{ $sourceRole->id }}">{{ $sourceRole->display_name ?? $sourceRole->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="ui-btn ui-btn-primary h-[38px] px-6 rounded-lg bg-slate-800 text-white text-[11px] font-normal hover:bg-slate-900 transition-all active:scale-95 shadow-sm">
                    Terapkan Duplikasi
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.permissions.update', $role) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Bulk Actions Toolbar --}}
            <div
                class="ui-card sticky top-6 z-40 bg-white/80 backdrop-blur-md rounded-2xl border border-slate-200 p-5 shadow-lg flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-indigo-200 shadow-md">
                        <i class="fas fa-list-check text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[12px] font-normal text-slate-900">Total Akses Tersedia: <span
                                class="font-bold">{{ $permissionsByModule->flatten(1)->count() }}</span></p>
                        <p class="text-[9px] font-normal text-slate-500 uppercase tracking-widest mt-0.5 italic">Pilih fitur
                            yang diperbolehkan untuk jabatan ini</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="checkAll"
                        class="ui-btn ui-btn-ghost px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 text-[10px] font-normal hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95 shadow-sm">
                        Ceklis Semua
                    </button>
                    <button type="button" id="uncheckAll"
                        class="ui-btn ui-btn-ghost px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 text-[10px] font-normal hover:text-rose-600 hover:border-rose-100 transition-all active:scale-95 shadow-sm">
                        Hapus Semua
                    </button>
                    <div class="ml-2 pl-4 border-l border-slate-200">
                        <button type="submit"
                            class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2 text-[11px] font-normal text-white shadow-indigo-600/20 shadow-lg transition-all hover:bg-indigo-700 active:scale-95">
                            <i class="fas fa-save text-[10px]"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($permissionsByModule as $module => $permissions)
                    @php
                        $moduleLabel = $moduleLabels[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
                    @endphp
                    <div
                        class="ui-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[12px] font-normal text-slate-900 tracking-wide">{{ $moduleLabel }}</p>
                                <p class="text-[9px] font-normal text-slate-400 uppercase tracking-widest mt-0.5">Namespace:
                                    {{ $module }}</p>
                            </div>
                            <button type="button"
                                class="module-toggle ui-btn ui-btn-ghost h-7 px-3 rounded-md bg-white border border-slate-200 text-slate-500 text-[9px] font-normal hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95 shadow-sm"
                                data-module="{{ $module }}">
                                Toggle Modul
                            </button>
                        </div>
                        <div class="p-5 grid grid-cols-1 gap-3">
                            @foreach($permissions as $permission)
                                <label
                                    class="group relative flex items-start gap-3 p-3.5 rounded-xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all cursor-pointer">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox"
                                            class="permission-checkbox module-{{ $module }} h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer"
                                            name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assignedPermissionIds, true))>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[11.5px] font-normal text-slate-800 group-hover:text-indigo-700 transition-colors">{{ $permission->label }}</span>
                                        <span
                                            class="text-[9px] font-normal text-slate-400 mt-0.5 uppercase tracking-wider">{{ $permission->key }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-center pt-8 pb-20 no-print">
                <button type="submit"
                    class="ui-btn ui-btn-primary inline-flex items-center gap-3 rounded-2xl bg-indigo-600 px-12 py-4 text-sm font-normal text-white shadow-indigo-600/30 shadow-xl transition-all hover:bg-indigo-700 active:scale-95">
                    <i class="fas fa-save text-xs"></i>
                    Simpan Seluruh Checklist
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const checkAllButton = document.getElementById('checkAll');
        const uncheckAllButton = document.getElementById('uncheckAll');
        const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        const moduleToggleButtons = document.querySelectorAll('.module-toggle');

        if (checkAllButton) {
            checkAllButton.addEventListener('click', () => {
                permissionCheckboxes.forEach((checkbox) => {
                    checkbox.checked = true;
                });
            });
        }

        if (uncheckAllButton) {
            uncheckAllButton.addEventListener('click', () => {
                permissionCheckboxes.forEach((checkbox) => {
                    checkbox.checked = false;
                });
            });
        }

        moduleToggleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const module = button.dataset.module;
                const moduleCheckboxes = document.querySelectorAll(`.module-${module}`);
                const hasUnchecked = Array.from(moduleCheckboxes).some((checkbox) => !checkbox.checked);

                moduleCheckboxes.forEach((checkbox) => {
                    checkbox.checked = hasUnchecked;
                });
            });
        });
    </script>
@endpush
