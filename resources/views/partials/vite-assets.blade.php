@php
    $assets = $assets ?? ['resources/css/app.css', 'resources/js/app.js'];
@endphp

@if(app()->environment('production'))
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : [];

        $assetUrl = function (string $path): string {
            $basePath = trim(request()->getBaseUrl(), '/');
            $prefix = $basePath === '' ? '' : '/' . $basePath;

            return $prefix . '/build/' . ltrim($path, '/');
        };
    @endphp

    @foreach($assets as $asset)
        @php
            $entry = $manifest[$asset] ?? null;
        @endphp

        @if(is_array($entry))
            @foreach($entry['css'] ?? [] as $cssFile)
                <link rel="stylesheet" href="{{ $assetUrl($cssFile) }}">
            @endforeach

            @if(str_ends_with($entry['file'] ?? '', '.css'))
                <link rel="stylesheet" href="{{ $assetUrl($entry['file']) }}">
            @elseif(str_ends_with($entry['file'] ?? '', '.js'))
                <script type="module" src="{{ $assetUrl($entry['file']) }}"></script>
            @endif
        @endif
    @endforeach
@else
    @vite($assets)
@endif
