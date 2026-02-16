@extends('layouts.admin')

@section('title', 'Promo & Voucher')
@section('page-title', 'Promo & Voucher')

@section('content')
    <div class="page-fullwidth px-0">
        <div class="px-6 py-6 page-card-fill space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Promo & Voucher</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola promo kategori dan voucher agar langsung bisa dipakai di POS.</p>
            </div>

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
                    <p class="font-semibold mb-2">Periksa kembali input berikut:</p>
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('admin.promo-vouchers.index') }}"
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    <div>
                        <label class="form-label" for="date_from">Tanggal Dari</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="date_to">Tanggal Sampai</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="outlet_id">Outlet</label>
                        <select id="outlet_id" name="outlet_id" class="form-input">
                            <option value="">Semua Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected((string) $selectedOutletId === (string) $outlet->id)>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary w-full justify-center">
                            <i class="fas fa-filter"></i>
                            Tampilkan
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="{{ route('admin.promo-vouchers.index') }}" class="btn btn-secondary w-full justify-center">
                            <i class="fas fa-rotate-right"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Transaksi</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($overview['total_transactions']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Diskon</p>
                    <p class="mt-2 text-2xl font-bold text-primary">Rp {{ number_format($overview['total_discount_amount'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Item: Rp {{ number_format($overview['item_discount_amount'], 0, ',', '.') }} | Invoice: Rp {{ number_format($overview['invoice_discount_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rasio Diskon</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($overview['discount_rate'], 2, ',', '.') }}%</p>
                    <p class="mt-1 text-xs text-gray-500">Dari subtotal Rp {{ number_format($overview['subtotal_amount'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penjualan Bersih</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">Rp {{ number_format($overview['net_sales_amount'], 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Tambah Promo Kategori</h3>
                        <p class="text-sm text-gray-500">Satu promo bisa punya banyak diskon kategori.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.promo-vouchers.promotions.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Nama Promo</label>
                                <input type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Anniversary 2026" required>
                            </div>
                            <div>
                                <label class="form-label">Kode Promo (opsional)</label>
                                <input type="text" name="code" class="form-input" value="{{ old('code') }}" placeholder="ANNIV26">
                            </div>
                            <div>
                                <label class="form-label">Outlet</label>
                                <select name="outlet_id" class="form-input">
                                    <option value="">Semua Outlet</option>
                                    @foreach($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected((string) old('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-input" value="{{ old('start_date', now()->toDateString()) }}" required>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-input" value="{{ old('end_date', now()->toDateString()) }}" required>
                            </div>
                            <div>
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-input" value="{{ old('notes') }}" placeholder="Promo anniversary all day">
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/60">
                            <p class="text-sm font-semibold text-gray-800 mb-3">Diskon per Kategori (%)</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                                @foreach($categories as $category)
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2">
                                        <span class="text-sm font-medium text-gray-700">{{ $category->name }}</span>
                                        <input type="number"
                                            name="category_discounts[{{ $category->id }}]"
                                            value="{{ old('category_discounts.' . $category->id) }}"
                                            min="0" max="100" step="0.01"
                                            class="form-input max-w-[130px] text-right"
                                            placeholder="0">
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Isi > 0 untuk kategori yang dapat diskon.</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Simpan Promo
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Tambah Voucher</h3>
                        <p class="text-sm text-gray-500">Voucher dipakai sebagai diskon invoice di POS.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.promo-vouchers.vouchers.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Nama Voucher</label>
                                <input type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Voucher Member" required>
                            </div>
                            <div>
                                <label class="form-label">Kode Voucher</label>
                                <input type="text" name="code" class="form-input" value="{{ old('code') }}" placeholder="MEMBER10" required>
                            </div>
                            <div>
                                <label class="form-label">Outlet</label>
                                <select name="outlet_id" class="form-input">
                                    <option value="">Semua Outlet</option>
                                    @foreach($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected((string) old('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tipe Diskon</label>
                                <select name="discount_type" class="form-input">
                                    <option value="percentage" @selected(old('discount_type') === 'percentage')>Persentase (%)</option>
                                    <option value="fixed" @selected(old('discount_type', 'fixed') === 'fixed')>Nominal (Rp)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Nilai Diskon</label>
                                <input type="number" name="discount_value" class="form-input" value="{{ old('discount_value') }}" min="0.01" step="0.01" required>
                            </div>
                            <div>
                                <label class="form-label">Minimum Belanja (Rp)</label>
                                <input type="number" name="min_purchase" class="form-input" value="{{ old('min_purchase', 0) }}" min="0" step="0.01">
                            </div>
                            <div>
                                <label class="form-label">Maksimum Diskon (opsional)</label>
                                <input type="number" name="max_discount_amount" class="form-input" value="{{ old('max_discount_amount') }}" min="0" step="0.01">
                            </div>
                            <div>
                                <label class="form-label">Batas Penggunaan (opsional)</label>
                                <input type="number" name="usage_limit" class="form-input" value="{{ old('usage_limit') }}" min="1" step="1" placeholder="Kosong = tanpa batas">
                            </div>
                            <div>
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-input" value="{{ old('start_date', now()->toDateString()) }}" required>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-input" value="{{ old('end_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-input" value="{{ old('notes') }}" placeholder="Voucher khusus member baru">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-ticket-alt"></i>
                                Simpan Voucher
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Daftar Promo Kategori</h3>
                        <span class="text-xs text-gray-500">{{ number_format($promotions->total()) }} data</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-5 py-3 text-left">Promo</th>
                                    <th class="px-5 py-3 text-left">Rule</th>
                                    <th class="px-5 py-3 text-center">Status</th>
                                    <th class="px-5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($promotions as $promotion)
                                    <tr>
                                        <td class="px-5 py-3 align-top">
                                            <div class="font-semibold text-gray-800">{{ $promotion->name }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ optional($promotion->start_date)->format('d M Y') }} - {{ optional($promotion->end_date)->format('d M Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Outlet: {{ $promotion->outlet?->name ?? 'Semua Outlet' }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 align-top">
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($promotion->categoryRules as $rule)
                                                    <span class="px-2 py-1 rounded-md text-xs bg-orange-50 text-orange-700 border border-orange-200">
                                                        {{ $rule->category?->name ?? 'Kategori' }} {{ rtrim(rtrim(number_format((float) $rule->discount_percent, 2, '.', ''), '0'), '.') }}%
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-gray-400">Tanpa rule</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-center align-top">
                                            @if($promotion->is_active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge badge-gray">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 align-top">
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" action="{{ route('admin.promo-vouchers.promotions.toggle', $promotion) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary btn-sm">
                                                        {{ $promotion->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.promo-vouchers.promotions.destroy', $promotion) }}" onsubmit="return confirm('Hapus promo ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-gray-500">Belum ada promo kategori.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-gray-100">
                        {{ $promotions->links() }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Daftar Voucher</h3>
                        <span class="text-xs text-gray-500">{{ number_format($vouchers->total()) }} data</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-5 py-3 text-left">Voucher</th>
                                    <th class="px-5 py-3 text-left">Benefit</th>
                                    <th class="px-5 py-3 text-center">Status</th>
                                    <th class="px-5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($vouchers as $voucher)
                                    <tr>
                                        <td class="px-5 py-3 align-top">
                                            <div class="font-semibold text-gray-800">{{ $voucher->name }}</div>
                                            <div class="text-xs font-mono text-gray-500">{{ $voucher->code }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ optional($voucher->start_date)->format('d M Y') }} - {{ optional($voucher->end_date)->format('d M Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">Outlet: {{ $voucher->outlet?->name ?? 'Semua Outlet' }}</div>
                                        </td>
                                        <td class="px-5 py-3 align-top">
                                            <div class="text-gray-700">
                                                @if($voucher->discount_type === 'percentage')
                                                    {{ rtrim(rtrim(number_format((float) $voucher->discount_value, 2, '.', ''), '0'), '.') }}%
                                                @else
                                                    Rp {{ number_format($voucher->discount_value, 0, ',', '.') }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">Min belanja: Rp {{ number_format($voucher->min_purchase, 0, ',', '.') }}</div>
                                            <div class="text-xs text-gray-500">Dipakai: {{ number_format($voucher->used_count) }}
                                                @if($voucher->usage_limit)
                                                    / {{ number_format($voucher->usage_limit) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-center align-top">
                                            @if($voucher->is_active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge badge-gray">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 align-top">
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" action="{{ route('admin.promo-vouchers.vouchers.toggle', $voucher) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary btn-sm">
                                                        {{ $voucher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.promo-vouchers.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Hapus voucher ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-gray-500">Belum ada voucher.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-gray-100">
                        {{ $vouchers->links() }}
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Ringkasan Diskon per Kategori (Transaksi)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Kategori</th>
                                <th class="px-5 py-3 text-right">Diskon Item</th>
                                <th class="px-5 py-3 text-right">Penjualan Neto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($categoryDiscountRows as $row)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-800">{{ $row->category_name }}</td>
                                    <td class="px-5 py-3 text-right text-rose-700">Rp {{ number_format($row->item_discount_total, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">Rp {{ number_format($row->net_sales_total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-8 text-center text-gray-500">Belum ada data diskon pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
