<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.pwa-head', ['pwaArea' => 'pos'])
    <title>@yield('title', 'POS') - Ransa POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    @include('partials.vite-assets')
    @stack('head')
</head>

<body class="font-sans antialiased h-screen flex flex-col bg-gray-50 text-gray-800 overflow-hidden">
    <div id="posNetworkBar" class="fixed top-0 left-0 right-0 z-[9999] hidden">
        <div class="h-1 w-full bg-transparent">
            <div id="posNetworkBarInner" class="h-1 w-1/3 bg-primary opacity-90"></div>
        </div>
    </div>
    @yield('content')

    @include('partials.pwa-service-worker', ['pwaArea' => 'pos'])
    @include('partials.pwa-install-button', ['pwaArea' => 'pos'])

    <script>
        (function () {
            if (window.location.pathname.indexOf('/pos') !== 0) return;

            var bar = document.getElementById('posNetworkBar');
            var inner = document.getElementById('posNetworkBarInner');
            if (!bar || !inner) return;

            var active = 0;
            var raf = null;
            var startTs = 0;

            function show() {
                if (!bar.classList.contains('hidden')) return;
                bar.classList.remove('hidden');
                startTs = Date.now();
                tick();
            }

            function hide() {
                if (active > 0) return;
                if (raf) cancelAnimationFrame(raf);
                raf = null;
                inner.style.transform = 'translateX(0)';
                inner.style.width = '35%';
                bar.classList.add('hidden');
            }

            function tick() {
                if (active <= 0) return;
                var elapsed = Date.now() - startTs;
                var phase = (elapsed % 1200) / 1200; // 0..1
                var width = 30 + Math.round(40 * Math.abs(Math.sin(phase * Math.PI)));
                var x = Math.round(65 * phase);
                inner.style.width = width + '%';
                inner.style.transform = 'translateX(' + x + '%)';
                raf = requestAnimationFrame(tick);
            }

            function inc() {
                active += 1;
                show();
            }

            function dec() {
                active = Math.max(0, active - 1);
                if (active === 0) {
                    // small delay to avoid flicker
                    setTimeout(hide, 150);
                }
            }

            var originalFetch = window.fetch;
            if (typeof originalFetch === 'function') {
                window.fetch = function () {
                    inc();
                    try {
                        return Promise.resolve(originalFetch.apply(this, arguments))
                            .finally(dec);
                    } catch (e) {
                        dec();
                        throw e;
                    }
                };
            }

            // Cover full page navigations (rare in POS), show bar briefly
            window.addEventListener('beforeunload', function () {
                inc();
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
