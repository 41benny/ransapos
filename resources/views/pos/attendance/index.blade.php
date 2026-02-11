@extends('layouts.pos')

@section('title', 'Absensi Karyawan')
@section('page-title', 'Absensi Karyawan')

@section('content')
<style>
    :root {
        --moresto-red: #D32F2F;
        --moresto-charcoal: #1e293b;
        --moresto-gold: #c5a065;
        --bg-soft-grey: #f3f4f6;
        --card-white: #ffffff;
    }
</style>

<div class="h-full overflow-auto bg-[var(--bg-soft-grey)] p-6">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Kasir Login</p>
                <p class="text-xl font-bold text-slate-800">{{ $loggedInUser->name }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $loggedInUser->outlet->name }}</p>
            </div>
            <div class="rounded-lg bg-white px-4 py-2 text-right shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current Session</p>
                <p class="text-sm font-medium text-slate-700">{{ now()->format('d M Y | H:i') }}</p>
            </div>
        </div>

        <div class="rounded-r-lg border-l-4 border-amber-500 bg-amber-50 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-amber-100 p-2 text-amber-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
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
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
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

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-[var(--card-white)] shadow-lg shadow-slate-300/30">
                    <div class="h-1.5 w-full bg-gradient-to-r from-[var(--moresto-charcoal)] via-[var(--moresto-red)] to-[var(--moresto-charcoal)]"></div>
                    <div class="p-5">
                        <div class="mb-5 flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-[var(--moresto-gold)] bg-slate-100 text-lg font-bold text-slate-700">
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
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">
                                    Selesai
                                </span>
                                <p class="mt-2 text-xs text-slate-600">
                                    {{ $todayAttendance->clock_in->format('H:i') }} - {{ $todayAttendance->clock_out->format('H:i') }}
                                    ({{ $todayAttendance->getDurationFormatted() }})
                                </p>
                            @elseif($hasClockIn)
                                <span class="inline-flex items-center rounded-full border {{ $todayAttendance->status === 'late' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide">
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
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
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
                                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500" for="pin-{{ $employee->id }}">
                                        Enter Security PIN
                                    </label>
                                    <input id="pin-{{ $employee->id }}" type="password" name="pin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                        placeholder="PIN 6 Digit" required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-center text-sm tracking-[0.25em] text-slate-800 placeholder:text-slate-300 focus:border-[var(--moresto-red)] focus:outline-none focus:ring-2 focus:ring-[var(--moresto-red)]/20">
                                </div>

                                <button type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow transition {{ $hasClockIn ? 'bg-[var(--moresto-charcoal)] hover:bg-slate-800' : 'bg-[var(--moresto-red)] hover:bg-red-800' }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $hasClockIn ? 'M17 16l4-4m0 0l-4-4m4 4H7' : 'M12 4v16m8-8H4' }}" />
                                    </svg>
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
    </div>
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
        }, 60000);
    });
</script>
@endsection
