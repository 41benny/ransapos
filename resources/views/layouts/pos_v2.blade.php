<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ef4444">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MorestoPOS">
    <link rel="manifest" href="{{ asset('pos/manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('pos/icons/apple-touch-icon.png') }}">
    <title>@yield('title', 'POS') - Morest POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="font-sans antialiased h-screen flex flex-col bg-gray-50 text-gray-800 overflow-hidden">
    @yield('content')

    <script>
        (function () {
            if (!('serviceWorker' in navigator)) return;
            if (window.location.pathname.indexOf('/pos') !== 0) return;

            navigator.serviceWorker.register('{{ asset('pos/sw.js') }}')
                .catch(function () { /* no-op */ });
        })();
    </script>
    @stack('scripts')
</body>

</html>
