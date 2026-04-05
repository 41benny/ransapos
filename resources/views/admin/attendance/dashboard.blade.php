@extends('layouts.admin')

@section('title', 'Rekap Absensi Karyawan')
@section('page-title', 'Laporan Rekap Absensi')
@section('page-subtitle', 'Ringkasan kehadiran, kedisiplinan, dan anomali absensi karyawan')

@section('content')
    <div class="w-full space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="bg-gradient-to-r from-orange-50 via-amber-50 to-white dark:from-slate-800 dark:via-slate-800 dark:to-slate-800 rounded-xl shadow-sm border border-orange-100 dark:border-slate-700 p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Kode laporan: attendance-recap</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-gray-100">Rekap Absensi Karyawan</div>
                </div>
                <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'sdm')]) }}"
                    class="inline-flex h-10 items-center rounded-lg border border-slate-300 dark:border-slate-600 px-4 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                    Kembali ke Katalog
                </a>
            </div>

            <form method="GET" action="{{ route('admin.reports.attendance.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <input type="hidden" name="tab" value="{{ request('tab', 'sdm') }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Periode</label>
                    <select name="period" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Tanggal Mulai</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Tanggal Akhir</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Outlet</label>
                    <select name="outlet_id" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ $selectedOutletId === $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Karyawan</label>
                    <select name="user_id" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option value="">Semua Karyawan</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $selectedUserId === $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option value="">Semua Status</option>
                        <option value="present" {{ $selectedStatus === 'present' ? 'selected' : '' }}>Tepat Waktu</option>
                        <option value="late" {{ $selectedStatus === 'late' ? 'selected' : '' }}>Terlambat</option>
                    </select>
                </div>
                <div class="md:col-span-6 flex flex-wrap gap-2">
                    <button type="submit"
                        class="ui-btn ui-btn-primary inline-flex h-10 items-center rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 px-4 text-sm font-semibold text-white hover:from-orange-600 hover:to-orange-700 shadow-sm">
                        <i class="fas fa-filter mr-2"></i>
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.reports.attendance.index', ['tab' => request('tab', 'sdm')]) }}"
                        class="inline-flex h-10 items-center rounded-lg bg-gray-100 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                        <i class="fas fa-rotate-right mr-2"></i>
                        Reset
                    </a>
                    <a href="{{ route('admin.reports.attendance.export', array_merge(request()->query(), ['tab' => request('tab', 'sdm'), 'format' => 'xlsx'])) }}"
                        class="inline-flex h-10 items-center rounded-lg border border-orange-200 bg-orange-100 px-4 text-sm font-semibold text-orange-700 hover:bg-orange-200 dark:border-slate-600 dark:bg-slate-800 dark:text-emerald-400 dark:hover:bg-slate-700">
                        <i class="fas fa-file-excel mr-2"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('admin.reports.attendance.export', array_merge(request()->query(), ['tab' => request('tab', 'sdm'), 'format' => 'pdf'])) }}"
                        class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700 p-5">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total Hadir</p>
                <p class="mt-2 text-3xl font-bold text-green-600 dark:text-emerald-400">{{ $totalPresent }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700 p-5">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total Terlambat</p>
                <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-500">{{ $totalLate }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700 p-5">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Belum Absen</p>
                <p class="mt-2 text-3xl font-bold text-gray-700 dark:text-gray-300">{{ $totalNotPresent }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700 p-5">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Anomali</p>
                <p class="mt-2 text-3xl font-bold {{ $anomalyCount > 0 ? 'text-red-600 dark:text-red-500' : 'text-green-600 dark:text-emerald-400' }}">{{ $anomalyCount }}</p>
            </div>
        </div>

        @if($anomalyCount > 0)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/30 rounded-xl p-5">
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-400 mb-3">Monitoring Anomali</h3>
                <div class="space-y-2">
                    @foreach($anomalies as $anomaly)
                        <div class="bg-white dark:bg-slate-800 border border-red-100 dark:border-slate-700 rounded-lg p-3">
                            <div class="text-sm font-semibold text-red-800 dark:text-red-400">{{ strtoupper(str_replace('_', ' ', $anomaly['type'])) }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ $anomaly['message'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-slate-400 mt-1">{{ $anomaly['time']->format('d M Y H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700">
            <div class="p-5 border-b border-orange-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tabel Rekap Detail Absensi</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Periode {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
                </div>
                <div class="text-xs text-gray-500 dark:text-slate-400">
                    @if($attendances->total() > 0)
                        Menampilkan {{ $attendances->firstItem() }}-{{ $attendances->lastItem() }} dari {{ $attendances->total() }} baris
                    @else
                        Tidak ada data
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-orange-50 text-orange-800 dark:bg-slate-700/50 dark:text-orange-400 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Karyawan</th>
                            <th class="px-4 py-3 text-left">Outlet</th>
                            <th class="px-4 py-3 text-left">Jam Masuk</th>
                            <th class="px-4 py-3 text-left">Jam Keluar</th>
                            <th class="px-4 py-3 text-left">Durasi</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Kasir Login</th>
                            <th class="px-4 py-3 text-left">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                        @forelse($attendances as $attendance)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 text-gray-700 dark:text-slate-300">
                                <td class="px-4 py-3">{{ $attendance->clock_in->format('d-m-Y') }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $attendance->user->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $attendance->outlet->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $attendance->clock_in->format('H:i:s') }}</td>
                                <td class="px-4 py-3">{{ $attendance->clock_out?->format('H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $attendance->isClockOut() ? $attendance->getDurationFormatted() : 'Masih aktif' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full {{ $attendance->status === 'late' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                                        {{ $attendance->status === 'late' ? 'Terlambat' : 'Tepat Waktu' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $attendance->loggedInUser->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $attendance->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-slate-400">Tidak ada data absensi untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attendances->hasPages())
                <div class="px-5 py-4 border-t border-orange-100 dark:border-slate-700">
                    {{ $attendances->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700">
                <div class="p-5 border-b border-orange-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rekap Per Outlet</h3>
                    <div class="text-xs text-gray-500 dark:text-slate-400">
                        @if($outletStats->total() > 0)
                            Menampilkan {{ $outletStats->firstItem() }}-{{ $outletStats->lastItem() }} dari {{ $outletStats->total() }} outlet
                        @else
                            Tidak ada data
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-orange-50 text-orange-800 dark:bg-slate-700/50 dark:text-orange-400 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Outlet</th>
                                <th class="px-4 py-3 text-left">Record</th>
                                <th class="px-4 py-3 text-left">Karyawan</th>
                                <th class="px-4 py-3 text-left">Terlambat</th>
                                <th class="px-4 py-3 text-left">On-Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                            @forelse($outletStats as $stat)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 text-gray-700 dark:text-slate-300">
                                    <td class="px-4 py-3 font-medium dark:text-slate-200">{{ $stat['outlet_name'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['total_records'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['unique_employees'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['late_count'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['on_time_rate'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-slate-400">Belum ada data outlet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($outletStats->hasPages())
                    <div class="px-5 py-4 border-t border-orange-100 dark:border-slate-700">
                        {{ $outletStats->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-slate-700">
                <div class="p-5 border-b border-orange-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rekap Per Karyawan</h3>
                    <div class="text-xs text-gray-500 dark:text-slate-400">
                        @if($employeeStats->total() > 0)
                            Menampilkan {{ $employeeStats->firstItem() }}-{{ $employeeStats->lastItem() }} dari {{ $employeeStats->total() }} karyawan
                        @else
                            Tidak ada data
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-orange-50 text-orange-800 dark:bg-slate-700/50 dark:text-orange-400 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Karyawan</th>
                                <th class="px-4 py-3 text-left">Outlet</th>
                                <th class="px-4 py-3 text-left">Record</th>
                                <th class="px-4 py-3 text-left">Terlambat</th>
                                <th class="px-4 py-3 text-left">On-Time</th>
                                <th class="px-4 py-3 text-left">Rata Durasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                            @forelse($employeeStats as $stat)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 text-gray-700 dark:text-slate-300">
                                    <td class="px-4 py-3 font-medium dark:text-slate-200">{{ $stat['employee_name'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['outlet_name'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['total_records'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['late_count'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['on_time_rate'] }}%</td>
                                    <td class="px-4 py-3">{{ is_null($stat['avg_duration_minutes']) ? '-' : $stat['avg_duration_minutes'] . ' menit' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-slate-400">Belum ada data karyawan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($employeeStats->hasPages())
                    <div class="px-5 py-4 border-t border-orange-100 dark:border-slate-700">
                        {{ $employeeStats->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
