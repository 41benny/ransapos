@extends('layouts.admin')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')
@section('page-subtitle', 'Buat akun dan tentukan outlet')

@section('content')
    <div class="mx-auto w-full max-w-4xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Tambah Pengguna Baru</h1>
            <p class="text-xs font-normal text-slate-700 mt-0.5">Daftarkan akun baru untuk tim Anda dan tentukan hak
                aksesnya</p>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                {{-- Form Header --}}
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <i class="fas fa-user-plus text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-[13px] font-normal text-slate-900 leading-none">Informasi Akun</h3>
                            <p class="text-[10px] font-normal text-slate-500 mt-1 uppercase tracking-widest">Detail data
                                personal & keamanan</p>
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
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('name') border-rose-500 @enderror"
                                placeholder="Contoh: Budi Santoso">
                            @error('name') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">
                                Alamat Email <span id="email-required-mark" class="text-rose-500">*</span>
                            </label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('email') border-rose-500 @enderror"
                                placeholder="user@example.com">
                            <p id="email-role-hint" class="text-[9px] font-normal text-slate-500 ml-1 mt-0.5 italic">Email
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
                                <option value="">Pilih Role...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                                    <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[9px] font-normal text-slate-500 ml-1 mt-0.5 italic text-indigo-600">Penting
                                untuk laporan penjualan per outlet.</p>
                        </div>
                    </div>

                    {{-- Row 3: Password --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="flex flex-col gap-1.5">
                            <label for="password"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">
                                Password Login <span id="password-required-mark" class="text-rose-500">*</span>
                            </label>
                            <input type="password" name="password" id="password"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('password') border-rose-500 @enderror">
                            <p id="password-role-hint" class="text-[9px] font-normal text-slate-500 ml-1 mt-0.5 italic">
                                Gunakan minimal 8 karakter dengan kombinasi angka.</p>
                            @error('password') <p class="mt-1 text-[10px] text-rose-500 italic">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="password_confirmation"
                                class="text-[10px] font-normal text-slate-600 uppercase tracking-wider ml-1">Konfirmasi
                                Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 py-2 px-1">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 transition-all cursor-pointer">
                        </div>
                        <label for="is_active"
                            class="text-[11.5px] font-normal text-slate-700 cursor-pointer select-none">Aktifkan akun ini
                            segera</label>
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
                        Simpan User
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
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            const emailRequiredMark = document.getElementById('email-required-mark');
            const passwordRequiredMark = document.getElementById('password-required-mark');
            const emailHint = document.getElementById('email-role-hint');
            const passwordHint = document.getElementById('password-role-hint');

            if (!roleSelect || !emailInput || !passwordInput || !passwordConfirmationInput) {
                return;
            }

            function selectedRoleName() {
                const selectedOption = roleSelect.options[roleSelect.selectedIndex];
                return selectedOption ? (selectedOption.dataset.roleName || '') : '';
            }

            function applyRoleRules() {
                const isOutletEmployee = selectedRoleName() === 'karyawan_outlet';
                const emailRequired = !isOutletEmployee;
                const passwordRequired = !isOutletEmployee;

                emailInput.required = emailRequired;
                passwordInput.required = passwordRequired;
                passwordConfirmationInput.required = passwordRequired;

                if (emailRequiredMark) {
                    emailRequiredMark.classList.toggle('hidden', !emailRequired);
                }

                if (passwordRequiredMark) {
                    passwordRequiredMark.classList.toggle('hidden', !passwordRequired);
                }

                if (emailHint) {
                    emailHint.textContent = isOutletEmployee
                        ? 'Untuk role Karyawan Outlet, email tidak wajib. Sistem akan membuat email internal otomatis.'
                        : 'Email dipakai untuk login akun.';
                }

                if (passwordHint) {
                    passwordHint.textContent = isOutletEmployee
                        ? 'Untuk role Karyawan Outlet, password tidak wajib. Sistem akan membuat password internal otomatis.'
                        : 'Password wajib untuk akun yang bisa login.';
                }
            }

            roleSelect.addEventListener('change', applyRoleRules);
            applyRoleRules();
        });
    </script>
@endpush