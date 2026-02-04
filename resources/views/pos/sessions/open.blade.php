@extends('layouts.pos')

@section('title', 'Buka Shift Kasir')
@section('page-title', 'Buka Shift Kasir')

@section('content')
<div class="h-full flex items-center justify-center p-4 bg-gray-900 relative overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none opacity-20">
        <div class="absolute -top-20 -left-20 w-96 h-96 bg-indigo-600 rounded-full blur-3xl filter mix-blend-multiply"></div>
        <div class="absolute top-40 right-20 w-80 h-80 bg-orange-500 rounded-full blur-3xl filter mix-blend-multiply"></div>
    </div>

    <div class="w-full max-w-lg relative z-10">
        <!-- Card -->
        <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-700/50 p-8">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl shadow-lg mb-4 transform rotate-3 hover:rotate-6 transition">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">Buka Shift</h2>
                <p class="text-gray-400">Siapkan kasir untuk memulai transaksi</p>
            </div>

            <!-- Form -->
            <form action="{{ route('pos.sessions.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Outlet (Fixed) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Outlet</label>
                    <input type="hidden" name="outlet_id" value="{{ $userOutlet->id }}">
                    <div class="flex items-center p-3 bg-gray-700/50 rounded-xl border border-gray-600">
                        <div class="w-10 h-10 rounded-full bg-indigo-900/50 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-medium">{{ $userOutlet->name }}</h3>
                            <p class="text-xs text-gray-400">{{ $userOutlet->code }}</p>
                        </div>
                    </div>
                </div>

                <!-- Opening Balance -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                        Saldo Awal Kas <span class="text-red-400">*</span>
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-400 group-focus-within:text-indigo-400 transition font-bold">Rp</span>
                        </div>
                        <input type="number" 
                               name="opening_balance" 
                               value="{{ old('opening_balance', 500000) }}"
                               step="1000"
                               min="0"
                               required
                               class="block w-full pl-12 pr-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition text-lg font-mono">
                    </div>
                    @error('opening_balance')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Catatan Shift</label>
                    <textarea name="notes" 
                              rows="2"
                              placeholder="Kondisi awal, Shift pagi/siang..."
                              class="block w-full px-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition text-sm">{{ old('notes') }}</textarea>
                </div>

                <!-- Actions -->
                <div class="pt-4 flex gap-4">
                    <a href="{{ route('pos.dashboard') }}" 
                       class="flex-1 px-6 py-3.5 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-xl font-semibold text-center transition focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                        Batal
                    </a>
                    <button type="submit" 
                            class="flex-1 px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-500/30 transition transform hover:-translate-y-0.5 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                        Buka Shift
                    </button>
                </div>
            </form>
        </div>
        
        <p class="mt-6 text-center text-sm text-gray-500">
            Morest POS System v1.0 • &copy; {{ date('Y') }}
        </p>
    </div>
</div>
@endsection

