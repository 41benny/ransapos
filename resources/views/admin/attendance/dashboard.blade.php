@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Monitoring Absensi</h1>
            <p class="text-gray-600">Monitoring absensi karyawan seluruh outlet dalam satu halaman</p>
        </div>

        {{-- Overview Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Hadir Hari Ini</p>
                        <p class="text-3xl font-bold text-green-600">{{ $totalPresent }}</p>
                    </div>
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Terlambat</p>
                        <p class="text-3xl font-bold text-amber-600">{{ $totalLate }}</p>
                    </div>
                    <svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Belum Absen</p>
                        <p class="text-3xl font-bold text-gray-600">{{ $totalNotPresent }}</p>
                    </div>
                    <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Anomali Terdeteksi</p>
                        <p class="text-3xl font-bold {{ $anomalyCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $anomalyCount }}</p>
                    </div>
                    <svg class="w-12 h-12 {{ $anomalyCount > 0 ? 'text-red-500' : 'text-green-500' }}" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Anomaly Detection Section --}}
        @if($anomalyCount > 0)
            <div class="bg-red-50 border-l-4 border-red-500 p-6 mb-8 rounded-r-lg">
                <h3 class="text-lg font-bold text-red-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Pola Mencurigakan Terdeteksi
                </h3>
                <div class="space-y-3">
                    @foreach($anomalies as $anomaly)
                        <div class="bg-white p-4 rounded-lg border border-red-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded mb-2">
                                        {{ strtoupper($anomaly['type']) }}
                                    </span>
                                    <p class="text-gray-800">{{ $anomaly['message'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $anomaly['time']->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Real-time Attendance Table --}}
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6 border-b">
                <h3 class="text-xl font-bold text-gray-900">Absensi Real-time (Hari Ini)</h3>
                <p class="text-sm text-gray-600">Semua outlet</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kasir Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($attendances as $attendance)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $attendance->clock_in->format('H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $attendance->outlet->name }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $attendance->user->name }}</td>
                                <td class="px-6 py-4">
                                    @if($attendance->isClockOut())
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">Clock Out
                                            ({{ $attendance->getDurationFormatted() }})</span>
                                    @else
                                        <span
                                            class="px-2 py-1 text-xs font-medium {{ $attendance->status === 'late' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }} rounded">
                                            {{ $attendance->status === 'late' ? 'Terlambat' : 'Clock In' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $attendance->loggedInUser->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $attendance->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada absensi hari ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Per Outlet Stats --}}
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-xl font-bold text-gray-900">Statistik Per Outlet</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($outlets as $outlet)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-bold text-gray-900 mb-2">{{ $outlet->name }}</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Hadir</span>
                                <span class="text-lg font-bold text-green-600">{{ $outlet->present_count }} /
                                    {{ $outlet->active_employees }}</span>
                            </div>
                            <div class="mt-2 bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-green-500 h-2"
                                    style="width: {{ $outlet->active_employees > 0 ? round(($outlet->present_count / $outlet->active_employees) * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection