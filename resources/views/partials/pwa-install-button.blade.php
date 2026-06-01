@php
    $pwaArea = $pwaArea ?? 'pos';
    $basePath = $pwaArea === 'admin' ? '/admin' : '/pos';
    $buttonLabel = $pwaArea === 'admin' ? 'Install Admin' : 'Install POS';
@endphp

<button type="button" id="pwaInstallButton" class="pwa-install-button" hidden>
    <span class="pwa-install-button__icon">+</span>
    <span>{{ $buttonLabel }}</span>
</button>

<style>
    .pwa-install-button {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 9998;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 2.75rem;
        padding: 0.7rem 0.95rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 45%, #991b1b 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 0.85rem;
        letter-spacing: 0.01em;
        box-shadow: 0 18px 35px -18px rgba(153, 27, 27, 0.9);
        cursor: pointer;
    }

    .pwa-install-button[hidden] {
        display: none;
    }

    .pwa-install-button__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        font-size: 1.1rem;
        line-height: 1;
    }

    @media (min-width: 768px) {
        .pwa-install-button {
            right: 1.25rem;
            bottom: 1.25rem;
        }
    }
</style>

<script>
    (function () {
        if (window.location.pathname.indexOf(@json($basePath)) !== 0) return;

        var installButton = document.getElementById('pwaInstallButton');
        if (!installButton) return;

        var deferredInstallPrompt = null;
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        if (isStandalone) {
            installButton.hidden = true;
            return;
        }

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
            installButton.hidden = false;
        });

        installButton.addEventListener('click', async function () {
            if (!deferredInstallPrompt) {
                alert('Jika prompt install belum muncul, buka menu Edge (...) lalu pilih Apps > Install this site as an app.');
                return;
            }

            installButton.disabled = true;
            deferredInstallPrompt.prompt();
            var choice = await deferredInstallPrompt.userChoice.catch(function () {
                return { outcome: 'dismissed' };
            });

            deferredInstallPrompt = null;
            installButton.hidden = choice.outcome === 'accepted';
            installButton.disabled = false;
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            installButton.hidden = true;
        });
    })();
</script>
