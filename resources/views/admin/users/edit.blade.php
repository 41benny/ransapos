@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('page-subtitle', 'Perbarui data akun')

@section('content')
    <div class="mx-auto w-full max-w-4xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Edit Pengguna</h1>
            <p class="text-xs font-normal text-slate-700 mt-0.5">Perbarui informasi akun, hak akses, dan pengaturan keamanan
            </p>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                {{-- Form Header --}}
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <i class="fas fa-user-edit text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-[13px] font-normal text-slate-900 leading-none">Profil Pengguna</h3>
                            <p class="text-[10px] font-normal text-slate-500 mt-1 uppercase tracking-widest">Detail data
                                personal & penempatan</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 space-y-8">
                    {{-- Row 1: Name & Email --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="flex flex-col gap-1.5">
                            <label for="name"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">Nama Lengkap
                                <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('name') border-rose-500 @enderror">
                            @error('name') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">
                                Alamat Email <span id="email-required-mark" class="text-rose-500">*</span>
                            </label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('email') border-rose-500 @enderror">
                            <p id="email-role-hint"
                                class="text-[9px] font-normal text-slate-500 ml-1 mt-0.5 italic text-slate-500">Email
                                digunakan sebagai username untuk login.</p>
                            @error('email') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Row 2: Role & Outlet --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="flex flex-col gap-1.5">
                            <label for="role_id"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">Role / Hak
                                Akses <span class="text-rose-500">*</span></label>
                            <select name="role_id" id="role_id" required
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all ring-0 outline-none @error('role_id') border-rose-500 @enderror">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                        {{ $role->display_name ?? $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="outlet_id"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">Lokasi Outlet
                                Kerja</label>
                            <select name="outlet_id" id="outlet_id"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all ring-0 outline-none">
                                <option value="">Semua Outlet (Admin Only)</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ old('outlet_id', $user->outlet_id) == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 3: Password --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="flex flex-col gap-1.5">
                            <label for="password"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">Password
                                Baru</label>
                            <input type="password" name="password" id="password"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('password') border-rose-500 @enderror"
                                placeholder="Kosongkan jika tidak diubah">
                            @error('password') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="password_confirmation"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">Konfirmasi
                                Password Baru</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400">
                        </div>
                    </div>

                    @php
                        $selectedPermissionIds = collect(old('permissions', $assignedPermissionIds ?? []))
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    @endphp
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div>
                                <h4 class="text-[12px] font-normal text-slate-900 uppercase tracking-widest">Checklist Akses User</h4>
                                <p class="text-[10px] font-normal text-slate-500 mt-1">
                                    Role adalah akses dasar. Di sini Anda bisa ubah akses khusus user ini.
                                </p>
                            </div>
                            <div id="managerPermissionHint"
                                class="hidden px-3 py-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 text-[10px]">
                                Role manager selalu full akses, checklist tidak bisa diubah.
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" id="checkAllUserPermissions"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-[10px] text-slate-600 hover:border-indigo-100 hover:text-indigo-600 transition-all">
                                Ceklis Semua
                            </button>
                            <button type="button" id="uncheckAllUserPermissions"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-[10px] text-slate-600 hover:border-rose-100 hover:text-rose-600 transition-all">
                                Hapus Semua
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($permissionsByModule as $module => $permissions)
                                <div class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                        <div>
                                            <p class="text-[11px] text-slate-900">{{ ucfirst(str_replace(['-', '_'], ' ', $module)) }}</p>
                                            <p class="text-[9px] uppercase tracking-widest text-slate-400 mt-0.5">{{ $module }}</p>
                                        </div>
                                        <button type="button" class="module-toggle text-[10px] text-indigo-600" data-module="{{ $module }}">
                                            Toggle
                                        </button>
                                    </div>
                                    <div class="p-4 space-y-2">
                                        @foreach($permissions as $permission)
                                            <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">
                                                <input type="checkbox"
                                                    class="user-permission-checkbox module-{{ $module }} mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                                                    name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissionIds, true))>
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] text-slate-700 leading-tight">{{ $permission->label }}</span>
                                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">{{ $permission->key }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('permissions') <p class="text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        @error('permissions.*') <p class="text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 py-2 px-1">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 transition-all cursor-pointer">
                        </div>
                        <label for="is_active"
                            class="text-[11.5px] font-normal text-slate-700 cursor-pointer select-none">Akun Aktif</label>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3 no-print">
                    <a href="{{ route('admin.users.index') }}"
                        class="px-4 py-2.5 text-xs font-normal text-slate-500 hover:text-slate-700 transition-all active:scale-95">
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                        <i class="fas fa-save text-[10px]"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>

        {{-- PIN Section --}}
        <form action="{{ route('admin.users.set-pin', $user) }}" method="POST"
            class="mt-8 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-150">
            @csrf
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                {{-- PIN Header --}}
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                                <i class="fas fa-key text-sm"></i>
                            </div>
                            <div>
                                <h3 class="text-[13px] font-normal text-slate-900 leading-none">PIN Absensi Karyawan</h3>
                                <p class="text-[10px] font-normal text-slate-500 mt-1 uppercase tracking-widest">Keamanan
                                    akses point of sale & absensi</p>
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-normal {{ $user->attendance_pin ? 'bg-emerald-50 text-emerald-600 ring-emerald-200' : 'bg-amber-50 text-amber-600 ring-amber-200' }} ring-1 ring-inset uppercase tracking-widest">
                            {{ $user->attendance_pin ? 'PIN Aktif' : 'PIN Belum Diset' }}
                        </span>
                    </div>
                </div>

                <div class="p-8 space-y-6">
                    <div class="max-w-md">
                        <label for="attendance_pin"
                            class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">PIN 6 Digit
                            Unik</label>
                        <div class="flex flex-col sm:flex-row gap-3 mt-1.5">
                            <input type="text" name="pin" id="attendance_pin" inputmode="numeric" pattern="[0-9]{6}"
                                maxlength="6" autocomplete="off" required
                                class="flex-1 px-4 py-2.5 text-[14px] font-mono tracking-[0.5em] text-center bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:tracking-normal placeholder:text-[11px] @error('pin', 'setPin') border-rose-500 @enderror"
                                placeholder="------">
                            <button type="button" id="generateAttendancePin"
                                class="px-4 py-2.5 text-xs font-normal text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                                Generate PIN
                            </button>
                        </div>
                        <p class="text-[9px] font-normal text-slate-500 ml-1 mt-2 italic">PIN akan dienkripsi dan tidak
                            dapat dilihat kembali setelah disimpan.</p>
                        @error('pin', 'setPin') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end no-print">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-xs font-normal text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95">
                        <i class="fas fa-lock text-[10px]"></i>
                        Simpan PIN Baru
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleSelect = document.getElementById('role_id');
            const emailInput = document.getElementById('email');
            const emailRequiredMark = document.getElementById('email-required-mark');
            const emailHint = document.getElementById('email-role-hint');

            function selectedRoleName() {
                if (!roleSelect) {
                    return '';
                }

                const selectedOption = roleSelect.options[roleSelect.selectedIndex];
                return selectedOption ? (selectedOption.dataset.roleName || '') : '';
            }

            function applyRoleRules() {
                if (!roleSelect || !emailInput) {
                    return;
                }

                const isOutletEmployee = selectedRoleName() === 'karyawan_outlet';
                const emailRequired = !isOutletEmployee;

                emailInput.required = emailRequired;

                if (emailRequiredMark) {
                    emailRequiredMark.classList.toggle('hidden', !emailRequired);
                }

                if (emailHint) {
                    emailHint.textContent = isOutletEmployee
                        ? 'Untuk role Karyawan Outlet, email tidak wajib. Jika dikosongkan, email internal akan tetap dipertahankan/dibuat otomatis.'
                        : 'Email dipakai untuk login akun.';
                }
            }

            if (roleSelect && emailInput) {
                roleSelect.addEventListener('change', applyRoleRules);
                applyRoleRules();
            }

            const permissionCheckboxes = document.querySelectorAll('.user-permission-checkbox');
            const checkAllPermissionsBtn = document.getElementById('checkAllUserPermissions');
            const uncheckAllPermissionsBtn = document.getElementById('uncheckAllUserPermissions');
            const moduleToggleButtons = document.querySelectorAll('.module-toggle');
            const managerPermissionHint = document.getElementById('managerPermissionHint');

            function applyPermissionRulesByRole() {
                const isManager = selectedRoleName() === 'manager';

                permissionCheckboxes.forEach((checkbox) => {
                    checkbox.disabled = isManager;
                });

                if (checkAllPermissionsBtn) {
                    checkAllPermissionsBtn.disabled = isManager;
                    checkAllPermissionsBtn.classList.toggle('opacity-50', isManager);
                    checkAllPermissionsBtn.classList.toggle('cursor-not-allowed', isManager);
                }

                if (uncheckAllPermissionsBtn) {
                    uncheckAllPermissionsBtn.disabled = isManager;
                    uncheckAllPermissionsBtn.classList.toggle('opacity-50', isManager);
                    uncheckAllPermissionsBtn.classList.toggle('cursor-not-allowed', isManager);
                }

                moduleToggleButtons.forEach((button) => {
                    button.disabled = isManager;
                    button.classList.toggle('opacity-50', isManager);
                    button.classList.toggle('cursor-not-allowed', isManager);
                });

                if (managerPermissionHint) {
                    managerPermissionHint.classList.toggle('hidden', !isManager);
                }
            }

            if (checkAllPermissionsBtn) {
                checkAllPermissionsBtn.addEventListener('click', function () {
                    permissionCheckboxes.forEach((checkbox) => {
                        checkbox.checked = true;
                    });
                });
            }

            if (uncheckAllPermissionsBtn) {
                uncheckAllPermissionsBtn.addEventListener('click', function () {
                    permissionCheckboxes.forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                });
            }

            moduleToggleButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const module = button.dataset.module;
                    const moduleCheckboxes = document.querySelectorAll(`.module-${module}`);
                    const hasUnchecked = Array.from(moduleCheckboxes).some((checkbox) => !checkbox.checked);

                    moduleCheckboxes.forEach((checkbox) => {
                        checkbox.checked = hasUnchecked;
                    });
                });
            });

            if (roleSelect) {
                roleSelect.addEventListener('change', applyPermissionRulesByRole);
                applyPermissionRulesByRole();
            }

            const pinInput = document.getElementById('attendance_pin');
            const generateButton = document.getElementById('generateAttendancePin');

            if (!pinInput || !generateButton) {
                return;
            }

            const blockedPins = new Set([
                '000000', '111111', '222222', '333333', '444444',
                '555555', '666666', '777777', '888888', '999999',
                '123456', '654321'
            ]);

            function isSequential(pin) {
                let asc = true;
                let desc = true;

                for (let i = 1; i < pin.length; i++) {
                    const prev = Number(pin[i - 1]);
                    const current = Number(pin[i]);
                    if (current !== prev + 1) {
                        asc = false;
                    }
                    if (current !== prev - 1) {
                        desc = false;
                    }
                }

                return asc || desc;
            }

            function generatePin() {
                for (let i = 0; i < 200; i++) {
                    const candidate = String(Math.floor(100000 + Math.random() * 900000));
                    if (!blockedPins.has(candidate) && !isSequential(candidate)) {
                        return candidate;
                    }
                }

                return String(Math.floor(100000 + Math.random() * 900000));
            }

            generateButton.addEventListener('click', function () {
                pinInput.value = generatePin();
                pinInput.focus();
                pinInput.select();
            });
        });
    </script>
@endpush
