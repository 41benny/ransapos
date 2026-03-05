<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Moresto</title>
    <script>
        (function () {
            const storageKey = 'moresto-theme';
            const stored = localStorage.getItem(storageKey);
            const validThemes = ['light', 'dark', 'system'];
            const preference = validThemes.includes(stored) ? stored : 'system';
            const useDark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', useDark);
        })();
    </script>

    {{-- Tailwind & Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Sidebar Transitions */
        .sidebar {
            transition: all 0.3s ease-in-out;
        }

        body.sidebar-collapsed .sidebar {
            width: 4rem;
        }

        body.sidebar-collapsed .sidebar-text {
            display: none;
        }

        body.sidebar-collapsed .logo-text {
            display: none;
        }

        /* Mobile Sidebar */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 50;
            }

            body.sidebar-mobile-open .sidebar {
                left: 0;
            }

            body.sidebar-mobile-open .sidebar-overlay {
                display: block;
            }
        }

        /* Runtime UI safety net:
           Keep admin dark mode readable even when compiled CSS is stale. */
        :root {
            --runtime-primary-500: #2563eb;
            --runtime-primary-600: #1d4ed8;
            --runtime-primary-700: #1e40af;
            --runtime-ui-surface: #ffffff;
            --runtime-ui-surface-muted: #f8fafc;
            --runtime-ui-border: #cbd5e1;
            --runtime-ui-text: #0f172a;
            --runtime-ui-text-muted: #64748b;
        }

        .dark {
            --runtime-ui-surface: #111827;
            --runtime-ui-surface-muted: #1f2937;
            --runtime-ui-border: #334155;
            --runtime-ui-text: #e2e8f0;
            --runtime-ui-text-muted: #94a3b8;
        }

        .ui-btn-primary,
        .btn-primary,
        .imperial-btn:not(.imperial-btn-info):not(.imperial-btn-warning):not(.imperial-btn-danger) {
            background: linear-gradient(135deg, var(--runtime-primary-500) 0%, var(--runtime-primary-600) 50%, var(--runtime-primary-700) 100%) !important;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.25), 0 2px 4px -1px rgba(37, 99, 235, 0.14) !important;
        }

        .ui-btn-primary:hover,
        .btn-primary:hover,
        .imperial-btn:not(.imperial-btn-info):not(.imperial-btn-warning):not(.imperial-btn-danger):hover {
            background: linear-gradient(135deg, var(--runtime-primary-600) 0%, var(--runtime-primary-700) 50%, #172554 100%) !important;
            box-shadow: 0 10px 15px -3px rgba(29, 78, 216, 0.35), 0 4px 6px -2px rgba(29, 78, 216, 0.2) !important;
        }

        .dark .ui-admin-body .bg-white,
        .dark .ui-admin-body .bg-white\/30,
        .dark .ui-admin-body .bg-white\/40,
        .dark .ui-admin-body .bg-white\/50,
        .dark .ui-admin-body .bg-white\/80,
        .dark .ui-admin-body .bg-slate-50,
        .dark .ui-admin-body .bg-gray-50,
        .dark .ui-admin-body .bg-gray-50\/20,
        .dark .ui-admin-body .bg-gray-50\/30,
        .dark .ui-admin-body .bg-gray-50\/40,
        .dark .ui-admin-body .bg-gray-50\/50,
        .dark .ui-admin-body .bg-gray-50\/60,
        .dark .ui-admin-body .bg-gray-50\/80,
        .dark .ui-admin-body .bg-slate-100,
        .dark .ui-admin-body .bg-gray-100,
        .dark .ui-admin-body .bg-slate-100\/20,
        .dark .ui-admin-body .bg-slate-100\/30,
        .dark .ui-admin-body .bg-slate-100\/40,
        .dark .ui-admin-body .bg-slate-100\/50,
        .dark .ui-admin-body .bg-slate-100\/60,
        .dark .ui-admin-body .bg-slate-100\/80,
        .dark .ui-admin-body .bg-gray-100\/20,
        .dark .ui-admin-body .bg-gray-100\/30,
        .dark .ui-admin-body .bg-gray-100\/40,
        .dark .ui-admin-body .bg-gray-100\/50,
        .dark .ui-admin-body .bg-gray-100\/60,
        .dark .ui-admin-body .bg-gray-100\/80,
        .dark .ui-admin-body .bg-slate-50\/30,
        .dark .ui-admin-body .bg-slate-50\/40,
        .dark .ui-admin-body .bg-slate-50\/50,
        .dark .ui-admin-body .bg-slate-50\/80,
        .dark .ui-admin-body .bg-blue-50,
        .dark .ui-admin-body .bg-blue-100,
        .dark .ui-admin-body .bg-blue-50\/20,
        .dark .ui-admin-body .bg-blue-50\/30,
        .dark .ui-admin-body .bg-blue-50\/40,
        .dark .ui-admin-body .bg-blue-50\/50,
        .dark .ui-admin-body .bg-blue-50\/60,
        .dark .ui-admin-body .bg-blue-50\/80,
        .dark .ui-admin-body .bg-indigo-50,
        .dark .ui-admin-body .bg-indigo-100 {
            background-color: var(--runtime-ui-surface) !important;
        }

        .dark .ui-admin-body .border-slate-200,
        .dark .ui-admin-body .border-slate-100,
        .dark .ui-admin-body .border-gray-200,
        .dark .ui-admin-body .border-gray-100,
        .dark .ui-admin-body .border-white,
        .dark .ui-admin-body .border-blue-200,
        .dark .ui-admin-body .border-blue-100 {
            border-color: var(--runtime-ui-border) !important;
        }

        .dark .ui-admin-body .from-gray-50,
        .dark .ui-admin-body .from-gray-100,
        .dark .ui-admin-body .from-slate-50,
        .dark .ui-admin-body .from-slate-100 {
            --tw-gradient-from: var(--runtime-ui-surface-muted) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: color-mix(in srgb, var(--runtime-ui-surface-muted) 0%, transparent) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }

        .dark .ui-admin-body .to-gray-50,
        .dark .ui-admin-body .to-gray-100,
        .dark .ui-admin-body .to-slate-50,
        .dark .ui-admin-body .to-slate-100 {
            --tw-gradient-to: var(--runtime-ui-surface-muted) var(--tw-gradient-to-position) !important;
        }

        .dark .ui-admin-body .hover\:bg-gray-50:hover,
        .dark .ui-admin-body .hover\:bg-slate-50:hover,
        .dark .ui-admin-body .hover\:bg-blue-50:hover {
            background-color: color-mix(in srgb, var(--runtime-ui-surface-muted) 82%, transparent) !important;
        }

        .dark .ui-admin-body .ts-wrapper .ts-control {
            background: var(--runtime-ui-surface) !important;
            border-color: var(--runtime-ui-border) !important;
            color: var(--runtime-ui-text) !important;
            box-shadow: none !important;
        }

        .dark .ui-admin-body .ts-wrapper.single .ts-control::after {
            border-color: var(--runtime-ui-text-muted) transparent transparent transparent !important;
        }

        .dark .ui-admin-body .ts-wrapper .ts-control .item,
        .dark .ui-admin-body .ts-wrapper .ts-control .placeholder,
        .dark .ui-admin-body .ts-wrapper .ts-control > input {
            color: var(--runtime-ui-text) !important;
            -webkit-text-fill-color: var(--runtime-ui-text) !important;
        }

        .dark .ui-admin-body .ts-wrapper .ts-control .placeholder {
            color: color-mix(in srgb, var(--runtime-ui-text-muted) 80%, transparent) !important;
        }

        .dark .ui-admin-body .ts-dropdown {
            background: var(--runtime-ui-surface) !important;
            border-color: var(--runtime-ui-border) !important;
            color: var(--runtime-ui-text) !important;
        }

        .dark .ui-admin-body .ts-dropdown .option,
        .dark .ui-admin-body .ts-dropdown .create {
            color: var(--runtime-ui-text) !important;
        }

        .dark .ui-admin-body .ts-dropdown .option.active,
        .dark .ui-admin-body .ts-dropdown .option:hover {
            background: color-mix(in srgb, var(--runtime-ui-surface-muted) 88%, transparent) !important;
            color: var(--runtime-ui-text) !important;
        }

        .dark .ui-admin-body .text-slate-900,
        .dark .ui-admin-body .text-slate-800,
        .dark .ui-admin-body .text-gray-900,
        .dark .ui-admin-body .text-gray-800 {
            color: var(--runtime-ui-text) !important;
        }

        .dark .ui-admin-body .text-slate-700,
        .dark .ui-admin-body .text-slate-600,
        .dark .ui-admin-body .text-slate-500,
        .dark .ui-admin-body .text-slate-400,
        .dark .ui-admin-body .text-gray-700,
        .dark .ui-admin-body .text-gray-600,
        .dark .ui-admin-body .text-gray-500,
        .dark .ui-admin-body .text-gray-400 {
            color: var(--runtime-ui-text-muted) !important;
        }

        .dark .ui-admin-body table:not(.no-ui-table),
        .dark .ui-admin-body .ui-table tbody,
        .dark .ui-admin-body .table-modern tbody,
        .dark .ui-admin-body .imperial-table tbody {
            background-color: var(--runtime-ui-surface) !important;
            color: var(--runtime-ui-text) !important;
        }

        .dark .ui-admin-body .ui-table thead,
        .dark .ui-admin-body .table-modern thead,
        .dark .ui-admin-body .imperial-table thead {
            background-color: var(--runtime-ui-surface-muted) !important;
        }

        .dark .ui-admin-body .ui-table tr:hover,
        .dark .ui-admin-body .table-modern tr:hover,
        .dark .ui-admin-body .imperial-table tr:hover {
            background-color: rgba(148, 163, 184, 0.08) !important;
        }

        .dark #hourlyBars,
        .dark #outletBars,
        .dark .apexcharts-canvas,
        .dark .apexcharts-svg {
            background: transparent !important;
        }

        .dark .apexcharts-tooltip {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        /* Runtime dark outline mode for action icons and badges */
        .dark .ui-admin-body .ui-action-icon {
            background: transparent !important;
            border-color: var(--runtime-ui-border) !important;
            box-shadow: none !important;
        }

        .dark .ui-admin-body .ui-action-icon:hover {
            background: transparent !important;
        }

        .dark .ui-admin-body .ui-action-view {
            color: #818cf8 !important;
            border-color: rgba(129, 140, 248, 0.45) !important;
        }

        .dark .ui-admin-body .ui-action-list {
            color: #2dd4bf !important;
            border-color: rgba(45, 212, 191, 0.45) !important;
        }

        .dark .ui-admin-body .ui-action-edit {
            color: #fbbf24 !important;
            border-color: rgba(251, 191, 36, 0.45) !important;
        }

        .dark .ui-admin-body .ui-action-delete {
            color: #f87171 !important;
            border-color: rgba(248, 113, 113, 0.45) !important;
        }

        .dark .ui-admin-body .ui-action-print {
            color: #cbd5e1 !important;
            border-color: rgba(203, 213, 225, 0.45) !important;
        }

        .dark .ui-admin-body .badge,
        .dark .ui-admin-body .ui-badge,
        .dark .ui-admin-body .imperial-badge,
        .dark .ui-admin-body .badge-success,
        .dark .ui-admin-body .badge-warning,
        .dark .ui-admin-body .badge-danger,
        .dark .ui-admin-body .badge-gray,
        .dark .ui-admin-body span.rounded-full.bg-green-100,
        .dark .ui-admin-body span.rounded-full.bg-emerald-50,
        .dark .ui-admin-body span.rounded-full.bg-emerald-100,
        .dark .ui-admin-body span.rounded-full.bg-blue-100,
        .dark .ui-admin-body span.rounded-full.bg-indigo-50,
        .dark .ui-admin-body span.rounded-full.bg-amber-50,
        .dark .ui-admin-body span.rounded-full.bg-amber-100,
        .dark .ui-admin-body span.rounded-full.bg-rose-50,
        .dark .ui-admin-body span.rounded-full.bg-rose-100,
        .dark .ui-admin-body span.rounded-full.bg-red-100,
        .dark .ui-admin-body span.rounded-full.bg-gray-100,
        .dark .ui-admin-body span.rounded-full.bg-slate-50,
        .dark .ui-admin-body span.rounded-full.bg-slate-100 {
            background: transparent !important;
            border: 1px solid rgba(203, 213, 225, 0.4) !important;
        }

        .dark .ui-admin-body .badge-success,
        .dark .ui-admin-body span.rounded-full.bg-green-100,
        .dark .ui-admin-body span.rounded-full.bg-emerald-50,
        .dark .ui-admin-body span.rounded-full.bg-emerald-100 {
            color: #4ade80 !important;
            border-color: rgba(74, 222, 128, 0.45) !important;
        }

        .dark .ui-admin-body span.rounded-full.bg-blue-100,
        .dark .ui-admin-body span.rounded-full.bg-indigo-50 {
            color: #93c5fd !important;
            border-color: rgba(147, 197, 253, 0.45) !important;
        }

        .dark .ui-admin-body .badge-warning,
        .dark .ui-admin-body span.rounded-full.bg-amber-50,
        .dark .ui-admin-body span.rounded-full.bg-amber-100 {
            color: #fbbf24 !important;
            border-color: rgba(251, 191, 36, 0.45) !important;
        }

        .dark .ui-admin-body .badge-danger,
        .dark .ui-admin-body span.rounded-full.bg-rose-50,
        .dark .ui-admin-body span.rounded-full.bg-rose-100,
        .dark .ui-admin-body span.rounded-full.bg-red-100 {
            color: #f87171 !important;
            border-color: rgba(248, 113, 113, 0.45) !important;
        }

        .dark .ui-admin-body .badge-gray,
        .dark .ui-admin-body span.rounded-full.bg-gray-100,
        .dark .ui-admin-body span.rounded-full.bg-slate-50,
        .dark .ui-admin-body span.rounded-full.bg-slate-100 {
            color: #cbd5e1 !important;
            border-color: rgba(203, 213, 225, 0.4) !important;
        }

        .theme-switcher {
            position: relative;
        }

        .theme-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            height: 2.5rem;
            padding: 0 0.625rem;
            border-radius: 0.75rem;
            border: 1px solid var(--runtime-ui-border);
            background: color-mix(in srgb, var(--runtime-ui-surface) 88%, transparent);
            color: var(--runtime-ui-text);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            transition: all 0.2s ease;
        }

        .theme-toggle-btn:hover {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 10px 20px -16px rgba(59, 130, 246, 0.45);
        }

        .theme-toggle-glyph {
            width: 1.75rem;
            height: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.625rem;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .dark .theme-toggle-glyph {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.25);
        }

        .theme-toggle-meta {
            display: none;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.1;
        }

        @media (min-width: 768px) {
            .theme-toggle-meta {
                display: flex;
            }
        }

        .theme-toggle-meta-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--runtime-ui-text-muted);
        }

        .theme-toggle-meta-value {
            font-size: 11px;
            font-weight: 700;
            color: var(--runtime-ui-text);
        }

        .theme-toggle-caret {
            font-size: 10px;
            color: var(--runtime-ui-text-muted);
            transition: transform 0.2s ease;
        }

        .theme-toggle-btn[aria-expanded="true"] .theme-toggle-caret {
            transform: rotate(180deg);
        }

        .theme-menu {
            position: absolute;
            top: calc(100% + 0.55rem);
            right: 0;
            width: 11.5rem;
            padding: 0.45rem;
            border-radius: 0.875rem;
            border: 1px solid var(--runtime-ui-border);
            background: var(--runtime-ui-surface);
            box-shadow: 0 24px 40px -24px rgba(2, 6, 23, 0.5);
            z-index: 70;
        }

        .theme-menu.hidden {
            display: none;
        }

        .theme-option {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.5rem 0.625rem;
            border-radius: 0.625rem;
            border: 1px solid transparent;
            background: transparent;
            color: var(--runtime-ui-text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .theme-option:hover {
            background: color-mix(in srgb, var(--runtime-ui-surface-muted) 92%, transparent);
            color: var(--runtime-ui-text);
        }

        .theme-option[data-active="true"] {
            color: var(--runtime-ui-text);
            border-color: var(--runtime-ui-border);
            background: color-mix(in srgb, var(--runtime-ui-surface-muted) 94%, transparent);
        }

        .theme-option-left {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .theme-check {
            opacity: 0;
            transform: scale(0.7);
            transition: all 0.15s ease;
            color: #2563eb;
        }

        .theme-option[data-active="true"] .theme-check {
            opacity: 1;
            transform: scale(1);
        }
    </style>
    @stack('styles')
</head>

<body class="ui-admin-body bg-slate-50 text-slate-900 font-sans antialiased">

    <script>
        const THEME_STORAGE_KEY = 'moresto-theme';
        const THEME_MODES = ['light', 'dark', 'system'];

        function getStoredThemePreference() {
            const stored = localStorage.getItem(THEME_STORAGE_KEY);
            return THEME_MODES.includes(stored) ? stored : 'system';
        }

        function resolveThemeIsDark(preference) {
            if (preference === 'dark') return true;
            if (preference === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function setThemeToggleIcon(preference) {
            const iconEl = document.getElementById('themeToggleIcon');
            if (!iconEl) return;

            const iconClass = preference === 'dark'
                ? 'fa-moon'
                : preference === 'light'
                    ? 'fa-sun'
                    : 'fa-circle-half-stroke';

            iconEl.classList.remove('fa-sun', 'fa-moon', 'fa-circle-half-stroke');
            iconEl.classList.add(iconClass);
        }

        function updateThemeUI(preference, isDark) {
            const labelEl = document.getElementById('themeToggleLabel');
            if (labelEl) {
                if (preference === 'system') {
                    labelEl.textContent = `System (${isDark ? 'Dark' : 'Light'})`;
                } else if (preference === 'dark') {
                    labelEl.textContent = 'Dark';
                } else {
                    labelEl.textContent = 'Light';
                }
            }

            setThemeToggleIcon(preference);

            const optionEls = document.querySelectorAll('[data-theme-option]');
            optionEls.forEach((btn) => {
                const isActive = btn.dataset.themeOption === preference;
                btn.dataset.active = isActive ? 'true' : 'false';
                btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
            });
        }

        function applyThemePreference(preference, { persist = true, dispatch = true } = {}) {
            const safePreference = THEME_MODES.includes(preference) ? preference : 'system';
            const isDark = resolveThemeIsDark(safePreference);
            document.documentElement.classList.toggle('dark', isDark);

            if (persist) {
                localStorage.setItem(THEME_STORAGE_KEY, safePreference);
            }

            updateThemeUI(safePreference, isDark);

            if (dispatch) {
                window.dispatchEvent(new CustomEvent('theme:changed', {
                    detail: { dark: isDark, preference: safePreference }
                }));
            }
        }

        function toggleTheme() {
            const current = getStoredThemePreference();
            const next = current === 'light' ? 'dark' : current === 'dark' ? 'system' : 'light';
            applyThemePreference(next);
        }

        function closeThemeMenu() {
            const menu = document.getElementById('themeMenu');
            const button = document.getElementById('themeToggleBtn');
            if (menu) menu.classList.add('hidden');
            if (button) button.setAttribute('aria-expanded', 'false');
        }

        function initThemeSwitcher() {
            const switcher = document.getElementById('themeSwitcher');
            const button = document.getElementById('themeToggleBtn');
            const menu = document.getElementById('themeMenu');
            if (!switcher || !button || !menu) return;

            button.addEventListener('click', function (event) {
                event.stopPropagation();
                menu.classList.toggle('hidden');
                button.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
            });

            const optionButtons = menu.querySelectorAll('[data-theme-option]');
            optionButtons.forEach((btn) => {
                btn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const selected = btn.dataset.themeOption || 'system';
                    applyThemePreference(selected);
                    closeThemeMenu();
                });
            });

            document.addEventListener('click', function (event) {
                if (!switcher.contains(event.target)) {
                    closeThemeMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeThemeMenu();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentPreference = getStoredThemePreference();
            applyThemePreference(currentPreference, { persist: false, dispatch: false });
            initThemeSwitcher();

            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const handleSystemThemeChange = function () {
                if (getStoredThemePreference() === 'system') {
                    applyThemePreference('system', { persist: false, dispatch: true });
                }
            };

            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleSystemThemeChange);
            } else if (mediaQuery.addListener) {
                mediaQuery.addListener(handleSystemThemeChange);
            }
        });

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }
        function toggleMobileSidebar() {
            document.body.classList.toggle('sidebar-mobile-open');
        }
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            const arrow = document.getElementById('arrow-' + id);
            if (submenu) {
                if (submenu.style.maxHeight && submenu.style.maxHeight !== '0px') {
                    submenu.style.maxHeight = '0px';
                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                } else {
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                    if (arrow) arrow.style.transform = 'rotate(180deg)';
                }
            }
        }
    </script>

    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Overlay -->
        <div class="sidebar-overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden"
            onclick="toggleMobileSidebar()"></div>

        <!-- Sidebar -->
        <aside
            class="sidebar w-68 bg-[#0B0F1A] text-white flex flex-col shrink-0 h-full border-r border-white/5 relative z-50 shadow-2xl">
            <!-- Logo area -->
            <div class="h-20 flex items-center px-7 mb-4">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/20 text-indigo-400 ring-1 ring-indigo-400/30">
                    <i class="fas fa-bowl-food text-xl"></i>
                </div>
                <div class="ml-4 logo-text">
                    <span class="block text-sm font-black uppercase tracking-[0.2em] text-white/90">Moresto</span>
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">Business
                        Suite</span>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-4 py-2 scrollbar-hide space-y-8">
                {{-- Group: Main Navigation --}}
                <div class="space-y-1">
                    <p class="px-4 text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-3 sidebar-text">
                        General</p>
                    @php
                        $currentUser = auth()->user();
                        $currentUser?->loadMissing('role.permissions', 'customPermissions');

                        $canAccess = function (?string $permission) use ($currentUser): bool {
                            if (!$permission) {
                                return true;
                            }

                            return (bool) ($currentUser && $currentUser->hasPermission($permission));
                        };

                        $mainNav = [
                            [
                                'label' => 'Dashboard',
                                'icon' => 'fas fa-grid-2',
                                'alt_icon' => 'fas fa-chart-pie',
                                'route' => 'admin.dashboard',
                                'match' => 'admin.dashboard',
                                'permission' => 'dashboard.view',
                            ],
                            [
                                'label' => 'Master Data',
                                'icon' => 'fas fa-layer-group',
                                'route' => null,
                                'match' => 'admin.products.*|admin.outlets.*|admin.suppliers.*|admin.payment-methods.*|admin.sales-types.*|admin.customers.*|admin.coa-accounts.*|admin.cash-accounts.*|admin.expense-categories.*|admin.users.*',
                                'children' => [
                                    ['label' => 'Produk', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'permission' => 'products.view'],
                                    ['label' => 'Outlet', 'route' => 'admin.outlets.index', 'match' => 'admin.outlets.*', 'permission' => 'outlets.view'],
                                    ['label' => 'Users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'permission' => 'users.view'],
                                    ['label' => 'Supplier', 'route' => 'admin.suppliers.index', 'match' => 'admin.suppliers.*', 'permission' => 'suppliers.view'],
                                    ['label' => 'Metode Bayar', 'route' => 'admin.payment-methods.index', 'match' => 'admin.payment-methods.*', 'permission' => 'payment-methods.view'],
                                    ['label' => 'Metode Penjualan', 'route' => 'admin.sales-types.index', 'match' => 'admin.sales-types.*', 'permission' => 'sales-types.view'],
                                    ['label' => 'Customer', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'permission' => 'customers.view'],
                                    ['label' => 'Kas & Bank', 'route' => 'admin.cash-accounts.index', 'match' => 'admin.cash-accounts.*', 'permission' => 'cash-accounts.view'],
                                    ['label' => 'Akunting & Biaya', 'route' => 'admin.coa-accounts.index', 'match' => 'admin.coa-accounts.*|admin.expense-categories.*', 'permission' => 'coa-accounts.view'],
                                ]
                            ],
                            [
                                'label' => 'Inventory',
                                'icon' => 'fas fa-box-archive',
                                'alt_icon' => 'fas fa-boxes',
                                'route' => 'admin.stocks.index',
                                'match' => 'admin.stocks.*|admin.stock-transfers.*',
                                'permission' => 'stocks.view',
                            ],
                            [
                                'label' => 'Produksi',
                                'icon' => 'fas fa-flask',
                                'route' => 'admin.boms.index',
                                'match' => 'admin.boms.*',
                                'permission' => 'boms.view',
                            ],
                            [
                                'label' => 'Purchasing',
                                'icon' => 'fas fa-cart-shopping',
                                'route' => null,
                                'match' => 'admin.purchases.*|admin.reports.debts.*',
                                'children' => [
                                    ['label' => 'Pembelian (PO)', 'route' => 'admin.purchases.index', 'match' => 'admin.purchases.*', 'permission' => 'purchases.view'],
                                    ['label' => 'Buku Hutang', 'route' => 'admin.reports.debts.index', 'match' => 'admin.reports.debts.*', 'permission' => 'reports.debts.view'],
                                ]
                            ],
                            [
                                'label' => 'Finance',
                                'icon' => 'fas fa-wallet',
                                'route' => 'admin.cash-transactions.index',
                                'match' => 'admin.cash-transactions.*|admin.expenses.*',
                                'permission' => 'cash-transactions.view',
                            ],
                            [
                                'label' => 'Marketing',
                                'icon' => 'fas fa-bullhorn',
                                'alt_icon' => 'fas fa-tags',
                                'route' => 'admin.promo-vouchers.index',
                                'match' => 'admin.promo-vouchers.*',
                                'permission' => 'promo-vouchers.view',
                            ],
                            [
                                'label' => 'Reports',
                                'icon' => 'fas fa-chart-column',
                                'alt_icon' => 'fas fa-file-invoice-dollar',
                                'route' => 'admin.reports.index',
                                'match' => 'admin.reports.*',
                                'permission' => 'reports.view',
                            ],
                        ];

                        $mainNav = collect($mainNav)
                            ->map(function (array $item) use ($canAccess) {
                                if (isset($item['children'])) {
                                    $item['children'] = array_values(array_filter($item['children'], function (array $child) use ($canAccess): bool {
                                        return $canAccess($child['permission'] ?? null);
                                    }));

                                    if (count($item['children']) === 0) {
                                        return null;
                                    }
                                } elseif (!$canAccess($item['permission'] ?? null)) {
                                    return null;
                                }

                                return $item;
                            })
                            ->filter()
                            ->values()
                            ->all();
                    @endphp

                    @foreach ($mainNav as $index => $item)
                        @php
                            $isActive = $item['match'] ? request()->routeIs(explode('|', $item['match'])) : false;
                            $hasChildren = isset($item['children']) && count($item['children']) > 0;
                            $menuId = 'submenu-' . $index;

                            $baseClass = "flex items-center px-4 py-3 text-xs font-bold rounded-xl transition-all duration-200 group cursor-pointer relative overflow-hidden";
                            $activeClass = "bg-indigo-600/10 text-indigo-400 ring-1 ring-indigo-500/20";
                            $inactiveClass = "text-slate-500 hover:text-white hover:bg-white/[0.03]";

                            $linkClass = $isActive ? "$baseClass $activeClass" : "$baseClass $inactiveClass";
                        @endphp

                        @if($hasChildren)
                            <div class="space-y-1">
                                <div class="{{ $linkClass }}" onclick="toggleSubmenu('{{ $menuId }}')">
                                    <div class="relative z-10 flex items-center w-full">
                                        <i
                                            class="{{ $item['icon'] ?? $item['alt_icon'] }} w-5 text-center mr-3 scale-110 {{ $isActive ? 'text-indigo-400' : 'text-slate-600 group-hover:text-slate-400' }}"></i>
                                        <span class="sidebar-text flex-1 uppercase tracking-wider">{{ $item['label'] }}</span>
                                        <i id="arrow-{{ $menuId }}"
                                            class="fas fa-chevron-down text-[10px] transition-transform duration-200 {{ $isActive ? 'rotate-180' : '' }} sidebar-text opacity-50"></i>
                                    </div>
                                    @if($isActive)
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                                    @endif
                                </div>
                                <div id="{{ $menuId }}"
                                    class="overflow-hidden transition-all duration-300 ease-in-out pl-4 space-y-1"
                                    style="{{ $isActive ? 'max-height: 1000px;' : 'max-height: 0px;' }}">
                                    @foreach($item['children'] as $child)
                                        @php
                                            $isChildActive = $child['match'] ? request()->routeIs(explode('|', $child['match'])) : false;
                                            $childClass = $isChildActive
                                                ? 'flex items-center pl-8 pr-3 py-2 text-[11px] font-bold text-indigo-400 border-l border-indigo-500/50 bg-indigo-500/5'
                                                : 'flex items-center pl-8 pr-3 py-2 text-[11px] font-bold text-slate-500 hover:text-white border-l border-white/5 hover:border-white/20 transition-all';
                                        @endphp
                                        <a href="{{ route($child['route']) }}"
                                            class="{{ $childClass }} sidebar-text uppercase tracking-widest">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}" class="{{ $linkClass }}">
                                <div class="relative z-10 flex items-center">
                                    <i
                                        class="{{ $item['icon'] ?? $item['alt_icon'] }} w-5 text-center mr-3 scale-110 {{ $isActive ? 'text-indigo-400' : 'text-slate-600 group-hover:text-slate-400' }}"></i>
                                    <span class="sidebar-text uppercase tracking-wider">{{ $item['label'] }}</span>
                                </div>
                                @if($isActive)
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Group: System Utilities --}}
                @php
                    $extras = [
                        ['label' => 'Perangkat POS', 'icon' => 'fas fa-tablet-screen-button', 'route' => 'admin.pos-devices.index', 'match' => 'admin.pos-devices.*', 'permission' => 'pos-devices.view'],
                        ['label' => 'Token Void', 'icon' => 'fas fa-key', 'route' => 'admin.void-tokens.index', 'match' => 'admin.void-tokens.*', 'permission' => 'void-tokens.view'],
                    ];

                    if ($currentUser?->hasRole('superadmin')) {
                        $extras[] = [
                            'label' => 'Hak Akses',
                            'icon' => 'fas fa-user-shield',
                            'route' => 'admin.permissions.index',
                            'match' => 'admin.permissions.*',
                            'permission' => 'permissions.manage',
                        ];
                    }

                    $extras = array_values(array_filter($extras, function (array $extra) use ($canAccess): bool {
                        return $canAccess($extra['permission'] ?? null);
                    }));

                    $canAccessPos = $canAccess('pos.dashboard');
                @endphp

                @if($canAccessPos || count($extras) > 0)
                <div class="space-y-1">
                    <p class="px-4 text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-3 sidebar-text">
                        POS & Service</p>

                    @if($canAccessPos)
                    <a href="{{ route('pos.dashboard') }}"
                        class="flex items-center px-4 py-3 text-xs font-bold text-slate-500 hover:text-white hover:bg-white/[0.03] rounded-xl transition-all group uppercase tracking-wider">
                        <i
                            class="fas fa-cash-register w-5 text-center mr-3 scale-110 text-slate-600 group-hover:text-slate-400"></i>
                        <span class="sidebar-text">POS Kasir</span>
                    </a>
                    @endif
                    
                    @foreach($extras as $extra)
                        @php
                            $isExtraActive = request()->routeIs(explode('|', $extra['match']));
                            $extraClass = $isExtraActive
                                ? 'flex items-center px-4 py-3 text-xs font-bold text-indigo-400 bg-indigo-600/10 ring-1 ring-indigo-500/20 rounded-xl transition-all group uppercase tracking-wider relative'
                                : 'flex items-center px-4 py-3 text-xs font-bold text-slate-500 hover:text-white hover:bg-white/[0.03] rounded-xl transition-all group uppercase tracking-wider';
                        @endphp
                        <a href="{{ route($extra['route']) }}" class="{{ $extraClass }}">
                            <i
                                class="{{ $extra['icon'] }} w-5 text-center mr-3 scale-110 {{ $isExtraActive ? 'text-indigo-400' : 'text-slate-600 group-hover:text-slate-400' }}"></i>
                            <span class="sidebar-text">{{ $extra['label'] }}</span>
                            @if($isExtraActive)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                            @endif
                        </a>
                    @endforeach
                </div>
                @endif
            </nav>

            <!-- User Profile (Bottom) -->
            <div class="p-6">
                <div class="rounded-2xl bg-white/[0.03] p-4 ring-1 ring-white/5">
                    <div class="flex items-center">
                        <div class="relative">
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-sm shadow-lg shadow-indigo-500/20 ring-2 ring-white/10">
                                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                            </div>
                            <div
                                class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-[#0B0F1A]">
                            </div>
                        </div>
                        <div class="ml-3 sidebar-text overflow-hidden">
                            <p class="text-xs font-black text-white truncate uppercase tracking-wider">
                                {{ auth()->user()->name ?? 'User' }}
                            </p>
                            <p class="text-[9px] font-bold text-slate-500 truncate uppercase tracking-[0.1em] mt-0.5">
                                {{ auth()->user()->role?->display_name ?? 'Administrator' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="ui-page-shell flex-1 flex flex-col h-screen overflow-hidden bg-slate-50/50 relative">

            <!-- Header -->
            <header
                class="ui-header-shell h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-8 z-40 shrink-0 sticky top-0">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()"
                        class="hidden lg:flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all focus:outline-none mr-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-300 dark:ring-slate-700">
                        <i class="fas fa-bars-staggered text-sm"></i>
                    </button>
                    <button onclick="toggleMobileSidebar()"
                        class="lg:hidden h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all focus:outline-none mr-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-300 dark:ring-slate-700">
                        <i class="fas fa-bars text-sm"></i>
                    </button>

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-black text-slate-800 tracking-tight dark:text-slate-100">
                                @yield('page-title', 'Dashboard')
                            </h1>
                            @hasSection('page-badge')
                                @yield('page-badge')
                            @endif
                        </div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5 dark:text-slate-400">
                            @yield('page-subtitle', 'Overview & Business Insights')
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div id="themeSwitcher" class="theme-switcher">
                        <button type="button" id="themeToggleBtn" class="theme-toggle-btn" aria-haspopup="menu"
                            aria-expanded="false" title="Theme">
                            <span class="theme-toggle-glyph">
                                <i id="themeToggleIcon" class="fas fa-circle-half-stroke text-sm"></i>
                            </span>
                            <span class="theme-toggle-meta">
                                <span class="theme-toggle-meta-label">Theme</span>
                                <span id="themeToggleLabel" class="theme-toggle-meta-value">System</span>
                            </span>
                            <i class="fas fa-chevron-down theme-toggle-caret"></i>
                        </button>

                        <div id="themeMenu" class="theme-menu hidden" role="menu">
                            <button type="button" class="theme-option" role="menuitemradio" aria-checked="false"
                                data-theme-option="light" data-active="false">
                                <span class="theme-option-left">
                                    <i class="fas fa-sun"></i>
                                    <span>Light</span>
                                </span>
                                <i class="fas fa-check theme-check"></i>
                            </button>
                            <button type="button" class="theme-option" role="menuitemradio" aria-checked="false"
                                data-theme-option="dark" data-active="false">
                                <span class="theme-option-left">
                                    <i class="fas fa-moon"></i>
                                    <span>Dark</span>
                                </span>
                                <i class="fas fa-check theme-check"></i>
                            </button>
                            <button type="button" class="theme-option" role="menuitemradio" aria-checked="false"
                                data-theme-option="system" data-active="false">
                                <span class="theme-option-left">
                                    <i class="fas fa-circle-half-stroke"></i>
                                    <span>System</span>
                                </span>
                                <i class="fas fa-check theme-check"></i>
                            </button>
                        </div>
                    </div>
                    <div class="hidden md:flex flex-col items-end">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest dark:text-slate-400">Server Time</p>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ now()->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="ui-divider h-8 w-px bg-slate-200 dark:bg-slate-700"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="group flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all shadow-sm ring-1 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:ring-rose-800 dark:hover:bg-rose-500 dark:hover:text-white"
                            title="Sign Out">
                            <i class="fas fa-power-off text-sm"></i>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 pb-20 scrollbar-hide">
                <div class="animate-in fade-in duration-500">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
