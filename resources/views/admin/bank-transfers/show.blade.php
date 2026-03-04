@extends('layouts.admin')

@section('title', 'Detail Transfer')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <a href="{{ route('admin.bank-transfers.index') }}" class="ui-btn ui-btn-ghost text-indigo-600 hover:text-indigo-900 text-sm">
                ← Kembali ke Daftar Transfer
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Detail Transfer</h1>
        </div>

        <div class="ui-card bg-white rounded-lg shadow p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500">Nomor Transfer</div>
                    <div class="font-medium">{{ $bankTransfer->transfer_number }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Tanggal</div>
                    <div class="font-medium">{{ $bankTransfer->transfer_date->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="text-sm font-medium text-gray-700 mb-3">Dari Rekening</div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="font-medium">{{ $bankTransfer->fromAccount->name }}</div>
                    <div class="text-sm text-gray-600">{{ $bankTransfer->fromAccount->outlet->name ?? '-' }}</div>
                    <div class="text-sm text-gray-600 mt-1">
                        {{ $bankTransfer->fromAccount->bank_name ?? 'Kas' }}
                        @if($bankTransfer->fromAccount->account_number)
                            - {{ $bankTransfer->fromAccount->account_number }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="text-sm font-medium text-gray-700 mb-3">Ke Rekening</div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="font-medium">{{ $bankTransfer->toAccount->name }}</div>
                    <div class="text-sm text-gray-600">{{ $bankTransfer->toAccount->outlet->name ?? '-' }}</div>
                    <div class="text-sm text-gray-600 mt-1">
                        {{ $bankTransfer->toAccount->bank_name ?? 'Kas' }}
                        @if($bankTransfer->toAccount->account_number)
                            - {{ $bankTransfer->toAccount->account_number }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Jumlah Transfer</div>
                        <div class="text-xl font-bold text-indigo-600">Rp
                            {{ number_format($bankTransfer->amount, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Dibuat Oleh</div>
                        <div class="font-medium">{{ $bankTransfer->creator->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="text-sm text-gray-500">Deskripsi</div>
                <div>{{ $bankTransfer->description }}</div>
                @if($bankTransfer->notes)
                    <div class="text-sm text-gray-500 mt-2">Catatan</div>
                    <div class="text-sm">{{ $bankTransfer->notes }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection