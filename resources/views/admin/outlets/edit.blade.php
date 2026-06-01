@extends('layouts.admin')

@section('title', 'Edit Outlet')
@section('page-title', 'Edit Outlet')
@section('page-subtitle', 'Perbarui informasi outlet')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.outlets.update', $outlet) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Informasi Outlet</h3>
                <p class="text-sm text-gray-500 mt-1">Perbarui data cabang/toko</p>
            </div>

            <!-- Form Content -->
            <div class="p-6 space-y-6">
                <!-- Kode & Nama -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            Kode Outlet <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="code"
                            id="code"
                            value="{{ old('code', $outlet->code) }}"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('code') border-red-500 @enderror"
                            placeholder="Contoh: OUT003"
                            required
                        >
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Outlet <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $outlet->name) }}"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            placeholder="Contoh: Cabang BSD"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Alamat -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Alamat
                    </label>
                    <textarea
                        name="address"
                        id="address"
                        rows="3"
                        class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('address') border-red-500 @enderror"
                        placeholder="Alamat lengkap outlet (opsional)"
                    >{{ old('address', $outlet->address) }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kontak -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            No. Telepon
                        </label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone', $outlet->phone) }}"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('phone') border-red-500 @enderror"
                            placeholder="Contoh: 021-12345678"
                        >
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $outlet->email) }}"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-500 @enderror"
                            placeholder="Contoh: outlet@morest.com"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Header & Footer Struk -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="receipt_header" class="block text-sm font-medium text-gray-700 mb-2">
                            Header Struk
                        </label>
                        <textarea
                            name="receipt_header"
                            id="receipt_header"
                            rows="3"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('receipt_header') border-red-500 @enderror"
                            placeholder="Teks tambahan di bagian atas struk (opsional)"
                        >{{ old('receipt_header', $outlet->receipt_header) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk hanya menampilkan info perusahaan.</p>
                        @error('receipt_header')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="receipt_footer" class="block text-sm font-medium text-gray-700 mb-2">
                            Footer Struk
                        </label>
                        <textarea
                            name="receipt_footer"
                            id="receipt_footer"
                            rows="3"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('receipt_footer') border-red-500 @enderror"
                            placeholder="Teks di bagian bawah struk (opsional)"
                        >{{ old('receipt_footer', $outlet->receipt_footer) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Kosongkan untuk memakai default "Terima Kasih atas Kunjungan Anda".</p>
                        @error('receipt_footer')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Pajak & Service Charge -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Pajak (PB1) % <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="tax_rate"
                            id="tax_rate"
                            value="{{ old('tax_rate', $outlet->tax_rate ?? 10) }}"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('tax_rate') border-red-500 @enderror"
                            placeholder="10"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        >
                        @error('tax_rate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="service_charge_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Service Charge % <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="service_charge_rate"
                            id="service_charge_rate"
                            value="{{ old('service_charge_rate', $outlet->service_charge_rate ?? 0) }}"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('service_charge_rate') border-red-500 @enderror"
                            placeholder="0"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        >
                        @error('service_charge_rate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $outlet->is_active) ? 'checked' : '' }}
                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        >
                        <span class="ml-3 text-sm text-gray-700">Outlet Aktif</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Outlet nonaktif tidak akan muncul di POS</p>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end space-x-3">
                <a
                    href="{{ route('admin.outlets.index') }}"
                    class="ui-btn ui-btn-ghost px-5 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="ui-btn ui-btn-primary px-5 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
