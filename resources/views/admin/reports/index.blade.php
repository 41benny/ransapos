@extends('layouts.admin')

@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-subtitle', 'Pusat laporan bisnis dan operasional')

@section('content')
<div class="w-full space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-normal uppercase tracking-widest text-slate-400">Pusat Informasi</div>
            <h2 class="text-3xl font-normal text-slate-800">Katalog Laporan</h2>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-normal text-slate-400 uppercase tracking-wider">Total:</span>
            <span class="rounded-full bg-indigo-600 px-3 py-1 text-xs font-normal text-white shadow-lg shadow-indigo-100">
                {{ collect($categories)->sum(fn($c) => count($c['items'])) }} Laporan
            </span>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2 bg-slate-100/50 p-1.5 rounded-2xl w-fit border border-slate-200/60 shadow-sm">
        @php
            $catIcons = [
                'ikhtisar' => 'fa-chart-pie',
                'penjualan' => 'fa-shopping-cart',
                'pembelian' => 'fa-truck',
                'produk' => 'fa-box-open',
                'lain' => 'fa-ellipsis-h',
                'sdm' => 'fa-users'
            ];
        @endphp
        @foreach($categories as $key => $category)
            <button type="button"
                class="ui-btn report-tab-btn ui-tab-btn {{ $loop->first ? 'is-active' : '' }} flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-normal uppercase tracking-wider transition-all"
                data-target="{{ $key }}">
                <i class="fas {{ $catIcons[$key] ?? 'fa-file-alt' }} text-[10px] {{ $loop->first ? 'text-indigo-500' : 'text-slate-400' }}"></i>
                {{ $category['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Panels --}}
    <div>
        @foreach($categories as $key => $category)
            <div class="report-tab-panel {{ $loop->first ? 'grid' : 'hidden' }} grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 animate-in fade-in slide-in-from-bottom-2 duration-300" data-panel="{{ $key }}">
                @foreach($category['items'] as $slug)
                    @php
                        $report = $reports[$slug] ?? null;
                    @endphp

                    @continue(!$report)

                    <a href="{{ route('admin.reports.catalog.show', ['slug' => $slug, 'tab' => $key]) }}"
                        class="group relative flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50 hover:border-indigo-100">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-colors group-hover:bg-indigo-50 group-hover:text-indigo-500">
                                    <i class="fas {{ $catIcons[$key] ?? 'fa-file-invoice' }} text-sm"></i>
                                </div>
                                @if($report['implemented'] ?? false)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-normal uppercase tracking-wider text-emerald-600 border border-emerald-100">
                                        Active
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-normal uppercase tracking-wider text-amber-600 border border-amber-100">
                                        Draft
                                    </span>
                                @endif
                            </div>
                            <div>
                                <h4 class="text-base font-normal text-slate-800 transition-colors group-hover:text-indigo-600">{{ $report['title'] }}</h4>
                                <p class="mt-1 text-xs text-slate-400">Analisis data lengkap untuk {{ strtolower($report['title']) }}.</p>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-between pt-4 border-t border-slate-50 group-hover:border-indigo-50/50">
                            <span class="text-[10px] font-normal uppercase tracking-widest text-slate-300 group-hover:text-indigo-300">Open Report</span>
                            <i class="fas fa-arrow-right text-[10px] text-slate-300 transition-transform group-hover:translate-x-1 group-hover:text-indigo-500"></i>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.report-tab-btn');
        const panels = document.querySelectorAll('.report-tab-panel');
        const initialTab = new URLSearchParams(window.location.search).get('tab');

        const activateTab = function (target) {
            if (!target) return;

            buttons.forEach(function (btn) {
                btn.classList.remove('is-active');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.remove('text-indigo-500');
                    icon.classList.add('text-slate-400');
                }
            });

            panels.forEach(function (panel) {
                panel.classList.add('hidden');
                panel.classList.remove('grid');
            });

            const activeButton = document.querySelector(`.report-tab-btn[data-target="${target}"]`);
            const activePanel = document.querySelector(`.report-tab-panel[data-panel="${target}"]`);
            if (!activeButton || !activePanel) return;

            activeButton.classList.add('is-active');
            
            const activeIcon = activeButton.querySelector('i');
            if (activeIcon) {
                activeIcon.classList.remove('text-slate-400');
                activeIcon.classList.add('text-indigo-500');
            }

            activePanel.classList.remove('hidden');
            activePanel.classList.add('grid');
        };

        if (initialTab) {
            activateTab(initialTab);
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.dataset.target;
                activateTab(target);
                const url = new URL(window.location.href);
                url.searchParams.set('tab', target);
                window.history.replaceState({}, '', url);
            });
        });
    });
</script>
@endpush
