@extends('layouts.admin')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')
@section('page-subtitle', 'Buat akun dan tentukan outlet')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Informasi User</h3>
                <p class="text-sm text-gray-500 mt-1">Isi data akun untuk admin/manager/kasir/kitchen/karyawan outlet.</p>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span id="email-required-mark" class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-500 @enderror"
                        >
                        <p id="email-role-hint" class="mt-1 text-xs text-gray-500">Email dipakai untuk login akun.</p>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="role_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="role_id"
                            id="role_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('role_id') border-red-500 @enderror"
                            required
                        >
                            <option value="">Pilih Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name ?? $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="outlet_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Outlet
                        </label>
                        <select
                            name="outlet_id"
                            id="outlet_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('outlet_id') border-red-500 @enderror"
                        >
                            <option value="">(Opsional) Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Wajib untuk kasir/kitchen/karyawan outlet.</p>
                        @error('outlet_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span id="password-required-mark" class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-500 @enderror"
                        >
                        <p id="password-role-hint" class="mt-1 text-xs text-gray-500">Password wajib untuk akun yang bisa login.</p>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Konfirmasi Password
                        </label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        {{ old('is_active', true) ? 'checked' : '' }}
                    >
                    <label for="is_active" class="text-sm text-gray-700">Aktifkan user</label>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">Batal</a>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Simpan
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
