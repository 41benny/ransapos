@extends('layouts.admin')

@section('title', 'Rekap Absensi Karyawan')
@section('page-title', $isReportPage ? 'Laporan Rekap Absensi' : 'Dashboard Monitoring Absensi')
@section('page-subtitle', 'Ringkasan kehadiran, kedisiplinan, dan anomali absensi karyawan')

@section('content')
    <div class="w-full max-w-7xl mx-auto space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="GET"
                action="{{ $isReportPage ? route('admin.reports.attendance.index') : route('admin.attendance.dashboard') }}"
                class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Periode</label>
                    <select name="period" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Mulai</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Akhir</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Outlet</label>
                    <select name="outlet_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ $selectedOutletId === $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Karyawan</label>
                    <select name="user_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Semua Karyawan</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $selectedUserId === $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Semua Status</option>
                        <option value="present" {{ $selectedStatus === 'present' ? 'selected' : '' }}>Tepat Waktu</option>
                        <option value="late" {{ $selectedStatus === 'late' ? 'selected' : '' }}>Terlambat</option>
                    </select>
                </div>
                <div class="md:col-span-6 flex flex-wrap gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm inline-flex items-center">
                        <i class="fas fa-filter mr-2"></i>
                        Terapkan Filter
                    </button>
                    <a href="{{ $isReportPage ? route('admin.reports.attendance.index') : route('admin.attendance.dashboard') }}"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm inline-flex items-center">
                        <i class="fas fa-rotate-right mr-2"></i>
                        Reset
                    </a>
                    <a href="{{ route('admin.attendance.export', request()->query()) }}"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm inline-flex items-center">
                        <i class="fas fa-file-csv mr-2"></i>
                        Export CSV
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <p class="text-xs text-gray-500 uppercase">Total Hadir</p>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ $totalPresent }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <p class="text-xs text-gray-500 uppercase">Total Terlambat</p>
                <p class="mt-2 text-3xl font-bold text-amber-600">{{ $totalLate }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <p class="text-xs text-gray-500 uppercase">Belum Absen</p>
                <p class="mt-2 text-3xl font-bold text-gray-700">{{ $totalNotPresent }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <p class="text-xs text-gray-500 uppercase">Anomali</p>
                <p class="mt-2 text-3xl font-bold {{ $anomalyCount > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $anomalyCount }}</p>
            </div>
        </div>

        @if($anomalyCount > 0)
            <div class="bg-red-50 border border-red-200 rounded-xl p-5">
                <h3 class="text-lg font-semibold text-red-900 mb-3">Monitoring Anomali</h3>
                <div class="space-y-2">
                    @foreach($anomalies as $anomaly)
                        <div class="bg-white border border-red-100 rounded-lg p-3">
                            <div class="text-sm font-semibold text-red-800">{{ strtoupper(str_replace('_', ' ', $anomaly['type'])) }}</div>
                            <div class="text-sm text-gray-700 mt-1">{{ $anomaly['message'] }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $anomaly['time']->format('d M Y H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Tabel Rekap Detail Absensi</h3>
                <p class="text-sm text-gray-500">Periode {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
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
                    <tbody class="divide-y divide-gray-100">
                        @forelse($attendances as $attendance)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $attendance->clock_in->format('d-m-Y') }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $attendance->user->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $attendance->outlet->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $attendance->clock_in->format('H:i:s') }}</td>
                                <td class="px-4 py-3">{{ $attendance->clock_out?->format('H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $attendance->isClockOut() ? $attendance->getDurationFormatted() : 'Masih aktif' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full {{ $attendance->status === 'late' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $attendance->status === 'late' ? 'Terlambat' : 'Tepat Waktu' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $attendance->loggedInUser->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $attendance->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">Tidak ada data absensi untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-100">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Rekap Per Outlet</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Outlet</th>
                                <th class="px-4 py-3 text-left">Record</th>
                                <th class="px-4 py-3 text-left">Karyawan</th>
                                <th class="px-4 py-3 text-left">Terlambat</th>
                                <th class="px-4 py-3 text-left">On-Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($outletStats as $stat)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $stat['outlet_name'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['total_records'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['unique_employees'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['late_count'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['on_time_rate'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada data outlet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Rekap Per Karyawan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Karyawan</th>
                                <th class="px-4 py-3 text-left">Outlet</th>
                                <th class="px-4 py-3 text-left">Record</th>
                                <th class="px-4 py-3 text-left">Terlambat</th>
                                <th class="px-4 py-3 text-left">On-Time</th>
                                <th class="px-4 py-3 text-left">Rata Durasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($employeeStats as $stat)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $stat['employee_name'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['outlet_name'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['total_records'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['late_count'] }}</td>
                                    <td class="px-4 py-3">{{ $stat['on_time_rate'] }}%</td>
                                    <td class="px-4 py-3">{{ is_null($stat['avg_duration_minutes']) ? '-' : $stat['avg_duration_minutes'] . ' menit' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada data karyawan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
