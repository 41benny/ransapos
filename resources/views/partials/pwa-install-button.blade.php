@php
    $pwaArea = $pwaArea ?? 'pos';
    $basePath = $pwaArea === 'admin' ? '/admin' : '/pos';
    $buttonLabel = $pwaArea === 'admin' ? 'Install Admin' : 'Install POS';
@endphp

<div id="pwaInstallPrompt" class="pwa-install-prompt" hidden>
    <button type="button" id="pwaInstallButton" class="pwa-install-button">
        <span class="pwa-install-button__icon">+</span>
        <span>{{ $buttonLabel }}</span>
    </button>
    <button type="button" id="pwaInstallDismissButton" class="pwa-install-dismiss" aria-label="Tutup install app">
        x
    </button>
</div>

<style>
    .pwa-install-prompt {
        position: fixed;
        right: 1rem;
        top: 1rem;
        z-index: 9998;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        filter: drop-shadow(0 14px 22px rgba(153, 27, 27, 0.18));
    }

    .pwa-install-prompt[hidden] {
        display: none;
    }

    .pwa-install-button {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2.75rem;
        padding: 0.65rem 0.85rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 45%, #991b1b 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 0.82rem;
        letter-spacing: 0.01em;
        cursor: pointer;
    }

    .pwa-install-button:disabled {
        cursor: wait;
        opacity: 0.72;
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

    .pwa-install-dismiss {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 0;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.78);
        color: #ffffff;
        font-size: 0.85rem;
        font-weight: 800;
        line-height: 1;
        cursor: pointer;
    }

    @media (min-width: 768px) {
        .pwa-install-prompt {
            right: 1.25rem;
            top: 1.25rem;
        }
    }

    @media (max-width: 767px) {
        .pwa-install-prompt {
            right: auto;
            left: 0.75rem;
            top: 0.75rem;
        }

        .pwa-install-button {
            min-height: 2.45rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.76rem;
        }

        .pwa-install-button__icon {
            width: 1.15rem;
            height: 1.15rem;
            font-size: 0.95rem;
        }

        .pwa-install-dismiss {
            width: 1.8rem;
            height: 1.8rem;
        }
    }
</style>

<script>
    (function () {
        if (window.location.pathname.indexOf(@json($basePath)) !== 0) return;

        var promptEl = document.getElementById('pwaInstallPrompt');
        var installButton = document.getElementById('pwaInstallButton');
        var dismissButton = document.getElementById('pwaInstallDismissButton');
        if (!promptEl || !installButton || !dismissButton) return;

        var deferredInstallPrompt = null;
        var dismissedStorageKey = 'RansaPOS:pwa-install-dismissed-until';
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        if (isStandalone) {
            promptEl.hidden = true;
            return;
        }

        function isDismissed() {
            var dismissedUntil = Number(localStorage.getItem(dismissedStorageKey) || 0);
            return Number.isFinite(dismissedUntil) && dismissedUntil > Date.now();
        }

        function dismissForSevenDays() {
            localStorage.setItem(dismissedStorageKey, String(Date.now() + (7 * 24 * 60 * 60 * 1000)));
            promptEl.hidden = true;
        }

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
            promptEl.hidden = isDismissed();
        });

        dismissButton.addEventListener('click', function () {
            dismissForSevenDays();
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
            promptEl.hidden = choice.outcome === 'accepted';
            installButton.disabled = false;

            if (choice.outcome !== 'accepted') {
                dismissForSevenDays();
            }
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            promptEl.hidden = true;
        });
    })();
</script>
