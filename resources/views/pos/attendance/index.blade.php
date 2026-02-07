@extends('layouts.pos')

@section('title', 'Absensi Karyawan')
@section('page-title', 'Absensi Karyawan')

@section('content')
    <div class="h-full p-6 overflow-auto">

        {{-- Header Info --}}
        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-300">Logged in sebagai kasir:</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $loggedInUser->name }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $loggedInUser->outlet->name }}</p>
                <p class="text-xs text-gray-500">{{ now()->format('d M Y, H:i') }}</p>
            </div>
        </div>

        {{-- Warning Banner --}}
        <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-amber-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="font-bold text-amber-800 mb-1">⚠️ PERINGATAN PENTING</h3>
                    <p class="text-sm text-amber-700">
                        Titip absen adalah <strong>pelanggaran</strong>. Semua aktivitas absensi tercatat lengkap (waktu, IP
                        address, kasir yang login) dan <strong>dimonitor oleh admin</strong>. Pastikan setiap karyawan absen
                        sendiri dengan PIN masing-masing.
                    </p>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r-lg">
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg">
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Employee Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($employees as $employee)
                @php
                    $todayAttendance = $employee->attendances->first();
                    $hasClockIn = $todayAttendance && !$todayAttendance->isClockOut();
                    $hasClockOut = $todayAttendance && $todayAttendance->isClockOut();
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 border border-gray-200 dark:border-gray-700">
                    {{-- Avatar & Name --}}
                    <div class="flex items-center mb-3">
                        <div
                            class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-lg mr-3">
                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $employee->role->display_name ?? 'Karyawan' }}</p>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="mb-3">
                        @if($hasClockOut)
                            <span class="inline-block px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                ✓ Selesai
                            </span>
                            <p class="text-xs text-gray-600 mt-1">
                                {{ $todayAttendance->clock_in->format('H:i') }} - {{ $todayAttendance->clock_out->format('H:i') }}
                                ({{ $todayAttendance->getDurationFormatted() }})
                            </p>
                        @elseif($hasClockIn)
                            <span class="inline-block px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                ● Hadir{{ $todayAttendance->status === 'late' ? ' (Terlambat)' : '' }}
                            </span>
                            <p class="text-xs text-gray-600 mt-1">
                                Masuk: {{ $todayAttendance->clock_in->format('H:i') }}
                                <span class="font-medium duration-{{ $employee->id }}"
                                    data-clock-in="{{ $todayAttendance->clock_in->timestamp }}">
                                    ({{ $todayAttendance->getCurrentDurationFormatted() }})
                                </span>
                            </p>
                        @else
                            <span class="inline-block px-3 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                ○ Belum Absen
                            </span>
                        @endif
                    </div>

                    {{-- PIN Input & Action Button --}}
                    @if(!$hasClockOut)
                        <form action="{{ $hasClockIn ? route('pos.attendance.clock-out') : route('pos.attendance.clock-in') }}"
                            method="POST" class="space-y-2">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

                            <div>
                                <input type="password" name="pin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                    placeholder="PIN 6 Digit" required
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>

                            <button type="submit"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-md transition-colors {{ $hasClockIn ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                                {{ $hasClockIn ? 'Clock Out' : 'Clock In' }}
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500">Tidak ada karyawan di outlet ini</p>
                </div>
            @endforelse
        </div>

    </div>

    {{-- Live Duration Timer (JavaScript) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Update durasi setiap 1 menit untuk yang sudah clock-in
            setInterval(function () {
                document.querySelectorAll('[class*="duration-"]').forEach(function (el) {
                    const clockIn = parseInt(el.dataset.clockIn);
                    const now = Math.floor(Date.now() / 1000);
                    const diffMinutes = Math.floor((now - clockIn) / 60);
                    const hours = Math.floor(diffMinutes / 60);
                    const minutes = diffMinutes % 60;
                    el.textContent = `(${hours} jam ${minutes} menit)`;
                });
            }, 60000); // Update setiap 1 menit
        });
    </script>
@endsection