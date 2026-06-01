@php
    $pwaArea = $pwaArea ?? 'pos';
    $isAdminPwa = $pwaArea === 'admin';
    $basePath = $isAdminPwa ? '/admin' : '/pos';
    $swPath = $isAdminPwa ? 'admin/sw.js' : 'pos/sw.js';
@endphp

<script>
    (function () {
        if (!('serviceWorker' in navigator)) return;
        if (!window.isSecureContext) return;
        if (window.location.pathname.indexOf(@json($basePath)) !== 0) return;

        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@json(asset($swPath)))
                .catch(function () { /* no-op */ });
        });
    })();
</script>
