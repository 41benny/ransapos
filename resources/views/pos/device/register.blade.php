@extends('layouts.pos')

@section('title', 'Aktivasi Perangkat')
@section('page-title', 'Aktivasi Perangkat')

@section('content')
    <div class="min-h-full flex items-center justify-center p-6">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">Perangkat POS Belum Terdaftar</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Masukkan kode pairing dari admin untuk mengaktifkan tablet kasir ini.
                </p>
            </div>

            <div class="p-6">
                @if($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('pos.device.register.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="pairing_code" class="block text-sm font-medium text-gray-700 mb-1">Kode Pairing</label>
                        <input id="pairing_code" name="pairing_code" type="text" maxlength="12"
                            class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 tracking-widest uppercase"
                            placeholder="contoh: 123456" value="{{ old('pairing_code') }}" required>
                    </div>

                    <div>
                        <label for="device_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perangkat (opsional)</label>
                        <input id="device_name" name="device_name" type="text" maxlength="100"
                            class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            placeholder="Kasir Tablet 1" value="{{ old('device_name') }}">
                    </div>

                    <button type="submit"
                        class="w-full px-4 py-2 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white rounded-lg transition shadow-md hover:shadow-lg">
                        Aktifkan Perangkat
                    </button>
                </form>

                <div class="mt-4 text-xs text-gray-500">
                    Jika belum punya kode pairing, hubungi admin outlet.
                </div>
            </div>
        </div>
    </div>
@endsection
