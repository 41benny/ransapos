@extends('layouts.pos')

@section('title', 'Kas Masuk Laci')
@section('page-title', 'Kas Masuk Laci')

@section('content')
<div class="h-full overflow-y-auto bg-gray-900">
    <div class="max-w-2xl mx-auto p-6 space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-white">Kas Masuk Laci</h2>
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

        <div class="p-5 bg-emerald-900/20 rounded-2xl border border-emerald-500/30">
            <p class="text-sm text-emerald-300 mb-1">Sisa Uang di Laci</p>
            <p class="text-3xl font-bold text-white">Rp {{ number_format($onHand, 0, ',', '.') }}</p>
            <p class="text-[11px] text-emerald-400/70 mt-1">Akan bertambah sebesar kas masuk yang dicatat di sini</p>
        </div>

        <div class="p-4 rounded-xl bg-gray-800/60 border border-gray-700/50 text-xs text-gray-400 leading-relaxed">
            Pakai layar ini kalau ada uang masuk ke laci <span class="text-gray-200 font-medium">di luar penjualan</span> &mdash;
            misalnya pemilik menambah modal pagi hari, atau kasir mengambil uang dari kas besar
            untuk kembalian. Modal awal shift tidak boleh diubah untuk ini: nilainya dirantai dari
            modal disisakan shift sebelumnya, dan mengubahnya akan memutus rantai itu.
        </div>

        <form action="{{ route('pos.expenses.cash-in.store') }}" method="POST"
              class="bg-gray-800/80 rounded-2xl border border-gray-700/50 p-6 space-y-5"
              id="cashInForm">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Sumber Dana <span class="text-red-400">*</span></label>

                {{--
                    Sumber dana wajib dipilih, tanpa opsi "lain-lain". Uang yang
                    masuk tanpa asal yang jelas membuat buku kas menunjukkan uang
                    yang seolah tercipta sendiri — persis kebocoran yang dulu
                    membuat saldo ayat silang tidak pernah nol.
                --}}
                <label class="flex items-start gap-3 p-4 rounded-xl bg-gray-900 border border-gray-700 cursor-pointer mb-3">
                    <input type="checkbox" name="from_owner" value="1" id="fromOwner"
                           @checked(old('from_owner'))
                           class="mt-1 w-5 h-5 rounded border-gray-600 bg-gray-800 text-emerald-500 focus:ring-emerald-500">
                    <span>
                        <span class="block text-sm text-white font-medium">Uang pribadi pemilik</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            Dibukukan sebagai Modal Disetor Pemilik. Pilih ini kalau uangnya
                            langsung dari kantong owner, bukan diambil dari kas outlet.
                        </span>
                    </span>
                </label>

                <div id="sourceAccountWrap">
                    @if($sourceAccounts->isEmpty())
                        <div class="p-4 rounded-xl bg-amber-900/20 border border-amber-500/40 text-amber-300 text-sm">
                            Belum ada akun kas besar / bank untuk outlet ini. Minta admin membuatnya di
                            menu <span class="font-semibold">Kas &amp; Bank</span>, atau centang
                            &ldquo;uang pribadi pemilik&rdquo; di atas.
                        </div>
                    @else
                        <select name="source_cash_account_id" id="sourceAccountSelect"
                                class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Pilih akun asal uang --</option>
                            @foreach($sourceAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('source_cash_account_id') == $acc->id)>
                                    {{ $acc->name }} ({{ strtoupper($acc->type) }}) &mdash; saldo Rp {{ number_format((float) $acc->current_balance, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Saldo akun ini akan berkurang saat shift ditutup.</p>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Diserahkan Oleh <span class="text-red-400">*</span></label>
                <input type="text" name="recipient_name" maxlength="60" required
                       value="{{ old('recipient_name') }}"
                       placeholder="Contoh: Pak Beny (owner)"
                       class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Keterangan <span class="text-red-400">*</span></label>
                <input type="text" name="description" maxlength="150" required
                       value="{{ old('description') }}"
                       placeholder="Contoh: Tambah modal pagi untuk kembalian"
                       class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Jumlah <span class="text-red-400">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">Rp</span>
                    {{-- Sama seperti form pengeluaran: yang diketik bermasking ribuan,
                         nilai mentahnya disimpan di hidden input `amount`. --}}
                    <input type="text" inputmode="numeric" id="amountDisplay" autocomplete="off"
                           value="{{ old('amount') ? number_format((float) old('amount'), 0, ',', '.') : '' }}"
                           class="w-full pl-12 pr-4 py-4 bg-gray-900 text-white text-2xl font-bold rounded-xl border border-gray-700 focus:border-emerald-500 focus:outline-none">
                    <input type="hidden" name="amount" id="amountInput" value="{{ old('amount') }}">
                </div>
            </div>

            <div class="flex gap-4 pt-2">
                <a href="{{ route('pos.expenses.index') }}"
                   class="flex-1 py-3.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-semibold text-center transition">Batal</a>
                <button type="submit" id="submitBtn"
                        class="flex-[2] py-3.5 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white rounded-xl font-semibold transition shadow-lg shadow-emerald-600/30">
                    Simpan Kas Masuk
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const amountInput = document.getElementById('amountInput');
    const amountDisplay = document.getElementById('amountDisplay');
    const fromOwner = document.getElementById('fromOwner');
    const sourceAccountWrap = document.getElementById('sourceAccountWrap');
    const sourceAccountSelect = document.getElementById('sourceAccountSelect');
    const form = document.getElementById('cashInForm');
    const submitBtn = document.getElementById('submitBtn');

    function formatThousands(digitsOnly) {
        if (digitsOnly === '') return '';
        return Number(digitsOnly).toLocaleString('id-ID');
    }

    amountDisplay.addEventListener('input', function () {
        const digits = amountDisplay.value.replace(/\D/g, '');
        amountInput.value = digits;
        amountDisplay.value = formatThousands(digits);
    });

    // Select-nya dinonaktifkan (bukan sekadar disembunyikan) saat sumbernya
    // pemilik, supaya nilainya tidak ikut terkirim dan memicu pemotongan saldo
    // akun kas yang sebenarnya tidak mengeluarkan uang apa pun.
    function toggleSource() {
        const owner = fromOwner.checked;
        sourceAccountWrap.classList.toggle('hidden', owner);
        if (sourceAccountSelect) {
            sourceAccountSelect.disabled = owner;
            sourceAccountSelect.required = !owner;
        }
    }

    fromOwner.addEventListener('change', toggleSource);
    toggleSource();

    form.addEventListener('submit', function (e) {
        if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
            e.preventDefault();
            alert('Jumlah kas masuk wajib diisi.');
            amountDisplay.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';
    });
})();
</script>
@endsection
