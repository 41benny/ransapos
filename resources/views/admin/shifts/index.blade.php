@extends('layouts.admin')

@section('title', 'Master Shift')
@section('page-title', 'Master Shift Absensi')
@section('page-subtitle', 'Kelola sesi shift (jam masuk, jam pulang, toleransi keterlambatan)')

@section('content')
    <div class="w-full">
        <div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">

            @if(session('success'))
                <div class="p-6 pb-0">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle text-lg"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-6 pb-0">
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-6 pb-0">
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <div class="p-6 border-b border-gray-100">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Daftar Shift</h3>
                        <p class="text-sm text-gray-500 mt-1">Total: {{ $shifts->total() }} shift</p>
                    </div>

                    <button type="button" onclick="openShiftModal('create')"
                        class="ui-btn ui-btn-primary btn btn-primary inline-flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Tambah Shift
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="ui-table imperial-table w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Urutan</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Shift</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jam Kerja</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Toleransi</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($shifts as $shift)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500">{{ $shift->sort_order }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $shift->name }}</div>
                                    @if($shift->is_overnight)
                                        <span class="mt-1 inline-flex items-center px-2 py-0.5 text-[10px] font-medium bg-indigo-50 text-indigo-700 rounded-full">Lintas tengah malam</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono text-gray-900">{{ $shift->timeRangeLabel() }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-700">{{ $shift->late_tolerance_minutes }} menit</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($shift->is_active)
                                        <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Aktif</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @php($shiftRowData = [
                                            'id' => $shift->id,
                                            'name' => $shift->name,
                                            'start_time' => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                                            'end_time' => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
                                            'late_tolerance_minutes' => (int) $shift->late_tolerance_minutes,
                                            'sort_order' => (int) $shift->sort_order,
                                            'is_active' => (bool) $shift->is_active,
                                        ])
                                        <button type="button"
                                            onclick="openShiftModal('edit', {{ \Illuminate\Support\Js::from($shiftRowData) }})"
                                            class="ui-btn ui-btn-ghost ui-btn-sm px-3 py-1.5 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.shifts.destroy', $shift) }}" method="POST"
                                            onsubmit="return confirm('Hapus shift {{ $shift->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="ui-btn ui-btn-ghost ui-btn-sm px-3 py-1.5 text-xs font-medium bg-rose-50 text-rose-700 rounded-lg hover:bg-rose-100 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-business-time text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Belum ada shift. Klik "Tambah Shift" untuk membuat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($shifts->hasPages())
                <div class="p-6 border-t border-gray-100">
                    {{ $shifts->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Create/Edit Shift --}}
    <div id="shiftModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 id="shiftModalTitle" class="text-lg font-semibold text-gray-900">Tambah Shift</h3>
                <button type="button" onclick="closeShiftModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form id="shiftForm" method="POST" action="{{ route('admin.shifts.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="shiftFormMethod" value="POST">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="shiftName">Nama Shift</label>
                    <input type="text" name="name" id="shiftName" required maxlength="100"
                        placeholder="mis. Pagi, Sore, Malam"
                        class="ui-input w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="shiftStart">Jam Masuk</label>
                        <input type="time" name="start_time" id="shiftStart" required
                            class="ui-input w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="shiftEnd">Jam Pulang</label>
                        <input type="time" name="end_time" id="shiftEnd" required
                            class="ui-input w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                <p class="text-xs text-gray-400 -mt-2">Bila jam pulang ≤ jam masuk, shift otomatis dianggap melewati tengah malam.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="shiftTolerance">Toleransi Telat (menit)</label>
                        <input type="number" name="late_tolerance_minutes" id="shiftTolerance" min="0" max="240" value="0"
                            class="ui-input w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="shiftOrder">Urutan</label>
                        <input type="number" name="sort_order" id="shiftOrder" min="0" value="0"
                            class="ui-input w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" id="shiftActive" value="1" checked
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Shift aktif (bisa dipilih saat absen)
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeShiftModal()"
                        class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="ui-btn ui-btn-primary px-4 py-2 text-sm font-medium text-white rounded-lg">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const shiftModal = document.getElementById('shiftModal');
        const shiftForm = document.getElementById('shiftForm');
        const shiftStoreUrl = "{{ route('admin.shifts.store') }}";
        const shiftUpdateUrlTpl = "{{ route('admin.shifts.update', ['shift' => '__ID__']) }}";

        function openShiftModal(mode, data = null) {
            document.getElementById('shiftModalTitle').textContent = mode === 'edit' ? 'Edit Shift' : 'Tambah Shift';

            if (mode === 'edit' && data) {
                shiftForm.action = shiftUpdateUrlTpl.replace('__ID__', data.id);
                document.getElementById('shiftFormMethod').value = 'PUT';
                document.getElementById('shiftName').value = data.name ?? '';
                document.getElementById('shiftStart').value = data.start_time ?? '';
                document.getElementById('shiftEnd').value = data.end_time ?? '';
                document.getElementById('shiftTolerance').value = data.late_tolerance_minutes ?? 0;
                document.getElementById('shiftOrder').value = data.sort_order ?? 0;
                document.getElementById('shiftActive').checked = !!data.is_active;
            } else {
                shiftForm.action = shiftStoreUrl;
                document.getElementById('shiftFormMethod').value = 'POST';
                shiftForm.reset();
                document.getElementById('shiftTolerance').value = 0;
                document.getElementById('shiftOrder').value = 0;
                document.getElementById('shiftActive').checked = true;
            }

            shiftModal.classList.remove('hidden');
            shiftModal.classList.add('flex');
        }

        function closeShiftModal() {
            shiftModal.classList.add('hidden');
            shiftModal.classList.remove('flex');
        }

        shiftModal.addEventListener('click', function (e) {
            if (e.target === shiftModal) closeShiftModal();
        });

        // Buka kembali modal (mode tambah) bila ada error validasi
        @if($errors->any())
            document.addEventListener('DOMContentLoaded', function () {
                openShiftModal('create');
                @if(old('name'))
                    document.getElementById('shiftName').value = @json(old('name'));
                    document.getElementById('shiftStart').value = @json(old('start_time'));
                    document.getElementById('shiftEnd').value = @json(old('end_time'));
                    document.getElementById('shiftTolerance').value = @json(old('late_tolerance_minutes', 0));
                    document.getElementById('shiftOrder').value = @json(old('sort_order', 0));
                @endif
            });
        @endif
    </script>
@endsection
