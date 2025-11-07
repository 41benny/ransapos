@extends('layouts.pos')

@section('title', 'Buka Shift Kasir')
@section('page-title', 'Buka Shift Kasir')

@section('content')
<div class="h-full flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        
        <!-- Card -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-8">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Buka Shift Kasir</h2>
                <p class="text-gray-400">Isi informasi untuk memulai shift</p>
            </div>

            <!-- Form -->
            <form action="{{ route('pos.sessions.store') }}" method="POST">
                @csrf
                
                <!-- Outlet (Fixed - dari user yang login) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Outlet
                    </label>
                    <input type="hidden" name="outlet_id" value="{{ $userOutlet->id }}">
                    <div class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="font-medium">{{ $userOutlet->name }}</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $userOutlet->code }}</p>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Outlet sesuai dengan akun Anda</p>
                </div>

                <!-- Opening Balance -->
                <div class="mb-6">
                    <label for="opening_balance" class="block text-sm font-medium text-gray-300 mb-2">
                        Saldo Awal Kas <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-400">Rp</span>
                        <input type="number" 
                               name="opening_balance" 
                               id="opening_balance" 
                               value="{{ old('opening_balance', 500000) }}"
                               step="1000"
                               min="0"
                               required
                               class="w-full pl-12 pr-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:outline-none @error('opening_balance') border-red-500 @enderror">
                    </div>
                    @error('opening_balance')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-400">Jumlah uang di laci kasir saat memulai shift</p>
                </div>

                <!-- Notes -->
                <div class="mb-8">
                    <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                        Catatan (Opsional)
                    </label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="3"
                              placeholder="Misal: Shift pagi, Shift malam, dll"
                              class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:outline-none @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex space-x-3">
                    <a href="{{ route('pos.dashboard') }}" 
                       class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-semibold text-center transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition">
                        Buka Shift
                    </button>
                </div>
            </form>
        </div>

        <!-- Info -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-400">
                Pastikan saldo awal sesuai dengan uang fisik di laci kasir
            </p>
        </div>
    </div>
</div>
@endsection

