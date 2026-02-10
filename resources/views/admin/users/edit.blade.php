@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('page-subtitle', 'Perbarui data akun')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Informasi User</h3>
                <p class="text-sm text-gray-500 mt-1">Perbarui data akun dan outlet.</p>
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
                            value="{{ old('name', $user->name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $user->email) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-500 @enderror"
                            required
                        >
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
                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
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
                                <option value="{{ $outlet->id }}" {{ old('outlet_id', $user->outlet_id) == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Wajib untuk kasir/kitchen.</p>
                        @error('outlet_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password Baru
                        </label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-500 @enderror"
                        >
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin diubah.</p>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Konfirmasi Password Baru
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
                        {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                    >
                    <label for="is_active" class="text-sm text-gray-700">Aktifkan user</label>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">Batal</a>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>

    @if(session('success'))
        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.users.set-pin', $user) }}" method="POST" class="mt-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">PIN Absensi</h3>
                        <p class="text-sm text-gray-500 mt-1">Set PIN 6 digit unik untuk absensi karyawan.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $user->attendance_pin ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $user->attendance_pin ? 'PIN sudah diset' : 'PIN belum diset' }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label for="attendance_pin" class="block text-sm font-medium text-gray-700 mb-2">
                        PIN 6 Digit <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input
                            type="text"
                            name="pin"
                            id="attendance_pin"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            autocomplete="off"
                            value="{{ old('pin') }}"
                            class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('pin', 'setPin') border-red-500 @enderror"
                            placeholder="Contoh: 482913"
                            required
                        >
                        <button
                            type="button"
                            id="generateAttendancePin"
                            class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition font-medium"
                        >
                            Generate PIN
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        PIN tidak ditampilkan lagi setelah disimpan. Salin manual dari kolom ini sebelum klik Simpan PIN.
                    </p>
                    @error('pin', 'setPin')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 flex items-center justify-end">
                <button
                    type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    Simpan PIN
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
