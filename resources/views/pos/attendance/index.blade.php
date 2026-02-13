@extends('layouts.pos_theme')

@section('content')

    {{-- Warning Block --}}
    <div class="rounded-r-lg border-l-4 border-amber-500 bg-amber-50 p-4 shadow-sm mb-6">
        <div class="flex items-start gap-3">
            <div class="rounded-full bg-amber-100 p-2 text-amber-700">
                <span class="material-icons-round text-xl">warning</span>
            </div>
            <div>
                <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-amber-800">Peringatan Penting</h3>
                <p class="text-xs leading-relaxed text-amber-700">
                    Titip absen adalah pelanggaran. Semua aktivitas absensi tercatat (waktu, IP address, kasir login)
                    dan dimonitor admin. Pastikan setiap karyawan absen sendiri dengan PIN masing-masing.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($employees as $employee)
            @php
                $todayAttendance = $employee->attendances->first();
                $hasClockIn = $todayAttendance && !$todayAttendance->isClockOut();
                $hasClockOut = $todayAttendance && $todayAttendance->isClockOut();
            @endphp

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft transition-all hover:shadow-lg">
                <div class="h-1.5 w-full bg-gradient-to-r from-gray-800 via-primary to-gray-800"></div>
                <div class="p-5">
                    <div class="mb-5 flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-yellow-500 bg-slate-50 text-lg font-bold text-slate-700">
                                {{ strtoupper(substr($employee->name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">{{ $employee->name }}</h3>
                                <p class="text-xs font-medium text-slate-500">{{ $employee->role?->display_name ?? 'Karyawan' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        @if($hasClockOut)
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                Selesai
                            </span>
                            <p class="mt-2 text-xs text-slate-600">
                                {{ $todayAttendance->clock_in->format('H:i') }} - {{ $todayAttendance->clock_out->format('H:i') }}
                                ({{ $todayAttendance->getDurationFormatted() }})
                            </p>
                        @elseif($hasClockIn)
                            <span class="inline-flex items-center rounded-full border {{ $todayAttendance->status === 'late' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide">
                                {{ $todayAttendance->status === 'late' ? 'Terlambat' : 'Hadir' }}
                            </span>
                            <p class="mt-2 text-xs text-slate-600">
                                Masuk: {{ $todayAttendance->clock_in->format('H:i') }}
                                <span class="font-semibold text-slate-700 duration-live"
                                    data-clock-in="{{ $todayAttendance->clock_in->timestamp }}">
                                    ({{ $todayAttendance->getCurrentDurationFormatted() }})
                                </span>
                            </p>
                        @else
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                                Belum Absen
                            </span>
                        @endif
                    </div>

                    @if(!$hasClockOut)
                        <form action="{{ $hasClockIn ? route('pos.attendance.clock-out') : route('pos.attendance.clock-in') }}"
                            method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500" for="pin-{{ $employee->id }}">
                                    Enter Security PIN
                                </label>
                                <div class="relative">
                                    <span class="material-icons-round pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                                    <input id="pin-{{ $employee->id }}" type="password" name="pin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                        placeholder="PIN 6 Digit" required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-center text-sm tracking-[0.25em] text-slate-800 placeholder:text-slate-300 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                </div>
                            </div>

                            <button type="submit"
                                class="flex h-10 w-full items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold text-white shadow transition {{ $hasClockIn ? 'bg-gray-800 hover:bg-gray-700' : 'bg-primary hover:bg-red-800' }}">
                                <span class="material-icons-round text-lg">{{ $hasClockIn ? 'logout' : 'schedule' }}</span>
                                {{ $hasClockIn ? 'Clock Out' : 'Clock In' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
                Tidak ada karyawan di outlet ini.
            </div>
        @endforelse
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function formatDuration(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `(${hours} jam ${mins} menit)`;
        }

        setInterval(function () {
            document.querySelectorAll('.duration-live').forEach(function (el) {
                const clockIn = parseInt(el.dataset.clockIn, 10);
                if (Number.isNaN(clockIn)) {
                    return;
                }

                const now = Math.floor(Date.now() / 1000);
                const diffMinutes = Math.floor((now - clockIn) / 60);
                el.textContent = formatDuration(diffMinutes);
            });
        }, 60000); // Update every minute
    });
</script>
@endsection
