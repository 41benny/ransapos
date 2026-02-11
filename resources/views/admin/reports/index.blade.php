@extends('layouts.admin')

@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-subtitle', 'Pusat laporan bisnis dan operasional')

@section('content')
<div class="mx-auto w-full max-w-7xl">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 pt-6">
            <div class="flex flex-wrap items-center gap-2">
                @foreach($categories as $key => $category)
                    <button type="button"
                        class="report-tab-btn rounded-lg px-4 py-2 text-sm font-medium transition-colors {{ $loop->first ? 'bg-indigo-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                        data-target="{{ $key }}">
                        {{ $category['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            @foreach($categories as $key => $category)
                <div class="report-tab-panel {{ $loop->first ? '' : 'hidden' }}" data-panel="{{ $key }}">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">{{ $category['label'] }}</h3>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                            {{ count($category['items']) }} laporan
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-x-12 gap-y-3 lg:grid-cols-2">
                        @foreach($category['items'] as $slug)
                            @php
                                $report = $reports[$slug] ?? null;
                            @endphp

                            @continue(!$report)

                            <a href="{{ route('admin.reports.catalog.show', $slug) }}"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                                <span>{{ $report['title'] }}</span>
                                @if($report['implemented'] ?? false)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                        Draft
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.report-tab-btn');
        const panels = document.querySelectorAll('.report-tab-panel');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.dataset.target;

                buttons.forEach(function (btn) {
                    btn.classList.remove('bg-indigo-700', 'text-white');
                    btn.classList.add('text-slate-600', 'hover:bg-slate-100');
                });

                panels.forEach(function (panel) {
                    panel.classList.add('hidden');
                });

                button.classList.add('bg-indigo-700', 'text-white');
                button.classList.remove('text-slate-600', 'hover:bg-slate-100');

                const activePanel = document.querySelector(`[data-panel="${target}"]`);
                if (activePanel) {
                    activePanel.classList.remove('hidden');
                }
            });
        });
    });
</script>
@endpush
