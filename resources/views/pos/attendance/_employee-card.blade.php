@php
    $todayAttendance = $employee->attendances->first();
    $hasClockIn = $todayAttendance && !$todayAttendance->isClockOut();
    $hasClockOut = $todayAttendance && $todayAttendance->isClockOut();
    // Karyawan titipan: outlet induknya bukan outlet perangkat POS ini
    $isGuestEmployee = $currentOutletId && (int) $employee->outlet_id !== (int) $currentOutletId;
    // Absensi hari ini tercatat di outlet lain (mis. clock-in di outlet sebelah)
    $attendanceElsewhere = $todayAttendance
        && $currentOutletId
        && (int) $todayAttendance->outlet_id !== (int) $currentOutletId;
@endphp

<div class="overflow-hidden rounded-2xl border {{ $isGuestEmployee ? 'border-indigo-300' : 'border-slate-200' }} bg-white shadow-soft transition-all hover:shadow-lg">
    <div class="h-1.5 w-full {{ $isGuestEmployee ? 'bg-gradient-to-r from-indigo-700 via-indigo-400 to-indigo-700' : 'bg-gradient-to-r from-gray-800 via-primary to-gray-800' }}"></div>
    <div class="p-5">
        <div class="mb-5 flex items-start justify-between gap-2">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 {{ $isGuestEmployee ? 'border-indigo-500' : 'border-yellow-500' }} bg-slate-50 text-lg font-bold text-slate-700">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">{{ $employee->name }}</h3>
                    <p class="text-xs font-medium text-slate-500">{{ $employee->role?->display_name ?? 'Karyawan' }}</p>
                </div>
            </div>
        </div>

        @if($isGuestEmployee)
            <div class="mb-3 flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5">
                <span class="material-icons-round text-sm text-indigo-600">swap_horiz</span>
                <span class="text-[11px] font-semibold text-indigo-700">
                    Titipan dari {{ $employee->outlet?->name ?? 'outlet lain' }}
                </span>
            </div>
        @endif

        @if($attendanceElsewhere)
            <div class="mb-3 flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5">
                <span class="material-icons-round text-sm text-amber-600">place</span>
                <span class="text-[11px] font-semibold text-amber-700">
                    Absen hari ini tercatat di {{ $todayAttendance->outlet?->name ?? 'outlet lain' }}
                </span>
            </div>
        @endif

        <div class="mb-4">
            @if($hasClockOut)
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                        Selesai
                    </span>
                    @if($todayAttendance->shift)
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                            {{ $todayAttendance->shift->name }}
                        </span>
                    @endif
                    @if($todayAttendance->isEarlyLeave())
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700">
                            Pulang Cepat {{ $todayAttendance->early_leave_minutes }}m
                        </span>
                    @elseif($todayAttendance->isOvertime())
                        <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-indigo-700">
                            Lembur {{ $todayAttendance->overtime_minutes }}m
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    {{ $todayAttendance->clock_in->format('H:i') }} - {{ $todayAttendance->clock_out->format('H:i') }}
                    ({{ $todayAttendance->getDurationFormatted() }})
                </p>
            @elseif($hasClockIn)
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center rounded-full border {{ $todayAttendance->status === 'late' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide">
                        {{ $todayAttendance->status === 'late' ? 'Terlambat' : 'Hadir' }}
                    </span>
                    @if($todayAttendance->shift)
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                            {{ $todayAttendance->shift->name }}
                        </span>
                    @endif
                    @if($todayAttendance->status === 'late' && $todayAttendance->late_minutes > 0)
                        <span class="text-[10px] font-bold text-amber-600">+{{ $todayAttendance->late_minutes }}m</span>
                    @endif
                </div>
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
            @if(!$hasClockIn && $shifts->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] font-medium text-amber-700">
                    Belum ada shift aktif. Minta admin menambahkan shift terlebih dahulu sebelum bisa clock-in.
                </div>
            @else
            <form action="{{ $hasClockIn ? route('pos.attendance.clock-out') : route('pos.attendance.clock-in') }}"
                method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">

                @if(!$hasClockIn)
                    @php
                        // Shift dengan jam mulai paling dekat ke waktu sekarang disorot
                        // sebagai saran, supaya karyawan tinggal konfirmasi ketimbang
                        // membaca dan membandingkan semua pilihan sendiri. Bukan
                        // dipilihkan paksa -- radio tetap bisa diganti sebelum submit.
                        $recommendedShiftId = $shifts->sortBy(function ($s) {
                            return abs($s->startFor(today())->diffInMinutes(now()));
                        })->first()?->id;
                    @endphp
                    <div>
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Pilih Shift
                        </span>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($shifts as $shift)
                                <label class="shift-option flex cursor-pointer items-center justify-between gap-2 rounded-lg border-2 border-slate-200 bg-slate-50 px-3 py-2 transition has-[:checked]:border-primary has-[:checked]:bg-red-50 has-[:checked]:ring-2 has-[:checked]:ring-primary/20 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary">
                                    <input type="radio" name="shift_id" value="{{ $shift->id }}" class="sr-only" required @checked($shift->id === $recommendedShiftId)>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-800">{{ $shift->name }}</span>
                                        <span class="block font-mono text-xs text-slate-500">{{ $shift->timeRangeLabel() }}</span>
                                    </span>
                                    @if($shift->id === $recommendedShiftId)
                                        <span class="shrink-0 rounded-full bg-primary px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">disarankan</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

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
        @endif
    </div>
</div>
