@php
    $pwaArea = $pwaArea ?? 'pos';
    $isAdminPwa = $pwaArea === 'admin';
    $manifestPath = $isAdminPwa ? 'admin/manifest.webmanifest' : 'pos/manifest.webmanifest';
    $themeColor = $isAdminPwa ? '#4f46e5' : '#ef4444';
    $appTitle = $isAdminPwa ? 'Ransa Admin' : 'Ransa POS';
@endphp

<meta name="theme-color" content="{{ $themeColor }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">
<link rel="manifest" href="{{ asset($manifestPath) }}">
<link rel="apple-touch-icon" href="{{ asset('pos/icons/apple-touch-icon.v2.png') }}">
