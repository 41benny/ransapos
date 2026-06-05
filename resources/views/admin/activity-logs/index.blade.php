@extends('layouts.admin')

@section('title', 'Log Aktivitas')
@section('page-title', 'Log Aktivitas')
@section('page-subtitle', 'Audit Trail Aktivitas Pengguna')

@php
    $colorMap = [
        'green' => 'bg-green-100 text-green-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'rose'  => 'bg-rose-100 text-rose-700',
        'blue'  => 'bg-blue-100 text-blue-700',
        'slate' => 'bg-slate-100 text-slate-700',
        'red'   => 'bg-red-100 text-red-700',
        'gray'  => 'bg-gray-100 text-gray-700',
    ];
@endphp

@section('content')
    <div class="container mx-auto px-1 py-2">

        {{-- Filter --}}
        <form method="GET" action="{{ route('admin.activity-logs.index') }}"
            class="bg-white rounded-2xl shadow ring-1 ring-slate-200 p-4 mb-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Cari</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}"
                        placeholder="Keterangan, nama user, IP..."
                        class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Aksi</label>
                    <select name="event"
                        class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Aksi</option>
                        @foreach($events as $key => $label)
                            <option value="{{ $key }}" @selected($filters['event'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">User</label>
                    <select name="user_id"
                        class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2 lg:col-span-1">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Dari</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                            class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Sampai</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                            class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button type="submit"
                    class="ui-btn-primary inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded-lg shadow">
                    <i class="fas fa-filter text-xs"></i> Terapkan
                </button>
                <a href="{{ route('admin.activity-logs.index') }}"
                    class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold py-2 px-4 rounded-lg">
                    <i class="fas fa-rotate-left text-xs"></i> Reset
                </a>
            </div>
        </form>

        {{-- Tabel --}}
        <div class="bg-white rounded-2xl shadow ring-1 ring-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ui-table min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">IP</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50/60 align-top">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                    <div class="font-semibold">{{ $log->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700 font-semibold">
                                    {{ $log->actorName() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-[11px] leading-4 font-bold rounded-full {{ $colorMap[$log->eventColor()] ?? $colorMap['gray'] }}">
                                        {{ $log->eventLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 max-w-md">
                                    {{ $log->description }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-400 font-mono">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if(!empty($log->properties))
                                        <details class="group">
                                            <summary class="cursor-pointer text-indigo-600 hover:text-indigo-800 text-xs font-bold list-none">
                                                <i class="fas fa-chevron-right text-[10px] transition-transform group-open:rotate-90"></i> Lihat
                                            </summary>
                                            <div class="mt-2 max-w-sm">
                                                @php
                                                    $old = $log->properties['old'] ?? [];
                                                    $attrs = $log->properties['attributes'] ?? [];
                                                @endphp
                                                @if($log->event === 'updated' && !empty($old))
                                                    <table class="w-full text-[11px] border border-slate-200 rounded">
                                                        <thead class="bg-slate-50">
                                                            <tr>
                                                                <th class="px-2 py-1 text-left font-bold text-slate-500">Field</th>
                                                                <th class="px-2 py-1 text-left font-bold text-slate-500">Lama</th>
                                                                <th class="px-2 py-1 text-left font-bold text-slate-500">Baru</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($attrs as $field => $newVal)
                                                                <tr class="border-t border-slate-100">
                                                                    <td class="px-2 py-1 font-mono text-slate-600">{{ $field }}</td>
                                                                    <td class="px-2 py-1 text-rose-600 break-all">{{ \Illuminate\Support\Str::limit(is_scalar($old[$field] ?? null) ? ($old[$field] ?? '-') : json_encode($old[$field] ?? null), 60) }}</td>
                                                                    <td class="px-2 py-1 text-green-600 break-all">{{ \Illuminate\Support\Str::limit(is_scalar($newVal) ? $newVal : json_encode($newVal), 60) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <pre class="text-[11px] bg-slate-50 border border-slate-200 rounded p-2 overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($attrs ?: $log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                                    <i class="fas fa-clock-rotate-left text-2xl mb-2 block"></i>
                                    Belum ada aktivitas tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
