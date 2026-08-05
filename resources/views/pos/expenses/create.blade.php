@extends('layouts.pos')

@section('title', 'Input Pengeluaran Kios')
@section('page-title', 'Input Pengeluaran Kios')

@section('content')
<div class="h-full overflow-y-auto bg-gray-900">
    <div class="max-w-2xl mx-auto p-6 space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-white">Input Pengeluaran Kios</h2>
            <p class="text-gray-400 text-sm mt-1">Shift {{ $session->session_number }}</p>
        </div>

        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-900/30 border border-red-500/40 text-red-300">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-900/30 border border-red-500/40 text-red-300">
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-5 bg-indigo-900/20 rounded-2xl border border-indigo-500/30">
            <p class="text-sm text-indigo-300 mb-1">Sisa Uang di Laci</p>
            <p class="text-3xl font-bold text-white" id="onHandLabel">Rp {{ number_format($onHand, 0, ',', '.') }}</p>
        </div>

        <form action="{{ route('pos.expenses.store') }}" method="POST"
              class="bg-gray-800/80 rounded-2xl border border-gray-700/50 p-6 space-y-5"
              id="expenseForm">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Kategori Biaya <span class="text-red-400">*</span></label>
                <select name="coa_account_id" required
                        class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-indigo-500 focus:outline-none">
                    <option value="">-- Pilih kategori --</option>
                    @foreach($coaAccounts as $coa)
                        <option value="{{ $coa->id }}" @selected(old('coa_account_id') == $coa->id)>
                            {{ $coa->code }} &mdash; {{ $coa->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-gray-500">
                    Pembelian bahan baku / barang dagangan tidak boleh lewat sini &mdash; harus lewat
                    Pembelian agar stok dan HPP ikut tercatat.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Sumber Pembayaran <span class="text-red-400">*</span></label>
                <select name="payment_source" id="paymentSource" required
                        class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-indigo-500 focus:outline-none">
                    @foreach($paymentSources as $key => $label)
                        <option value="{{ $key }}" @selected(old('payment_source', 'drawer') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-gray-500" id="sourceHint">
                    Hanya pengeluaran tunai dari laci yang mengurangi saldo laci saat closing.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Nama Penerima <span class="text-red-400">*</span></label>
                <input type="text" name="recipient_name" maxlength="60" required
                       value="{{ old('recipient_name') }}"
                       placeholder="Contoh: Pak Budi (tukang galon)"
                       class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Keperluan <span class="text-red-400">*</span></label>
                <input type="text" name="description" maxlength="150" required
                       value="{{ old('description') }}"
                       placeholder="Contoh: Beli galon 2 buah"
                       class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Jumlah <span class="text-red-400">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">Rp</span>
                    {{-- Input teks bermasking ribuan, bukan type="number" — supaya nominal besar
                         mudah dibaca saat diketik. Nilai mentah yang dikirim ke server disimpan
                         di hidden input `amount`, disinkronkan oleh JS di bawah. --}}
                    <input type="text" inputmode="numeric" id="amountDisplay" autocomplete="off"
                           value="{{ old('amount') ? number_format((float) old('amount'), 0, ',', '.') : '' }}"
                           class="w-full pl-12 pr-4 py-4 bg-gray-900 text-white text-2xl font-bold rounded-xl border border-gray-700 focus:border-indigo-500 focus:outline-none">
                    <input type="hidden" name="amount" id="amountInput" value="{{ old('amount') }}">
                </div>
                <p class="mt-2 text-xs text-amber-400 hidden" id="overLimitWarn"></p>
            </div>

            <div class="flex gap-4 pt-2">
                <a href="{{ route('pos.expenses.index') }}"
                   class="flex-1 py-3.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-semibold text-center transition">Batal</a>
                <button type="submit" id="submitBtn"
                        class="flex-[2] py-3.5 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white rounded-xl font-semibold transition shadow-lg shadow-amber-600/30">
                    Simpan Pengeluaran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const onHand = {{ (float) $onHand }};
    const maxPerTransaction = {{ (float) $maxPerTransaction }};
    const amountInput = document.getElementById('amountInput');
    const amountDisplay = document.getElementById('amountDisplay');
    const sourceSelect = document.getElementById('paymentSource');
    const warn = document.getElementById('overLimitWarn');
    const form = document.getElementById('expenseForm');
    const submitBtn = document.getElementById('submitBtn');

    function fmt(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    function formatThousands(digitsOnly) {
        if (digitsOnly === '') return '';
        return Number(digitsOnly).toLocaleString('id-ID');
    }

    // Peringatan di sini hanya bantuan visual. Aturan sebenarnya ditegakkan di
    // server, karena validasi klien bisa dilewati.
    function check() {
        const amount = parseFloat(amountInput.value) || 0;
        const source = sourceSelect.value;
        const messages = [];

        if (source === 'drawer' && amount > onHand) {
            messages.push('Melebihi sisa uang di laci (' + fmt(onHand) + ').');
        }
        if (maxPerTransaction > 0 && amount > maxPerTransaction) {
            messages.push('Melebihi batas ' + fmt(maxPerTransaction) + ' per transaksi, butuh otorisasi.');
        }

        if (messages.length) {
            warn.textContent = messages.join(' ');
            warn.classList.remove('hidden');
        } else {
            warn.classList.add('hidden');
        }
    }

    // amountDisplay adalah yang diketik kasir (bermasking ribuan); amountInput
    // (hidden) menyimpan angka mentah yang benar-benar dikirim ke server.
    amountDisplay.addEventListener('input', function () {
        const digits = amountDisplay.value.replace(/\D/g, '');
        amountInput.value = digits;
        amountDisplay.value = formatThousands(digits);
        check();
    });
    sourceSelect.addEventListener('change', check);

    // Cegah klik ganda. Kunci idempotensi di form tetap jadi pengaman utama
    // kalau tombolnya sempat tertekan dua kali sebelum handler ini jalan.
    form.addEventListener('submit', function (e) {
        // amount sekarang hidden input tanpa validasi `required` bawaan
        // browser (hidden field tidak divalidasi native secara andal).
        if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
            e.preventDefault();
            alert('Jumlah pengeluaran wajib diisi.');
            amountDisplay.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';
    });
})();
</script>
@endsection
