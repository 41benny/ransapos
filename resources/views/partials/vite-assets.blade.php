@php
    $assets = $assets ?? ['resources/css/app.css', 'resources/js/app.js'];
@endphp

@if(app()->environment('production'))
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true)
            : [];

        $assetContents = function (string $path): ?string {
            $fullPath = public_path('build/' . ltrim($path, '/'));

            return is_file($fullPath) ? file_get_contents($fullPath) : null;
        };
    @endphp

    @foreach($assets as $asset)
        @php
            $entry = $manifest[$asset] ?? null;
        @endphp

        @if(is_array($entry))
            @foreach($entry['css'] ?? [] as $cssFile)
                @php
                    $cssContents = $assetContents($cssFile);
                @endphp

                @if($cssContents !== null)
                    <style>{!! $cssContents !!}</style>
                @endif
            @endforeach

            @if(str_ends_with($entry['file'] ?? '', '.css'))
                @php
                    $cssContents = $assetContents($entry['file']);
                @endphp

                @if($cssContents !== null)
                    <style>{!! $cssContents !!}</style>
                @endif
            @elseif(str_ends_with($entry['file'] ?? '', '.js'))
                @php
                    $jsContents = $assetContents($entry['file']);
                @endphp

                @if($jsContents !== null)
                    <script type="module">{!! $jsContents !!}</script>
                @endif
            @endif
        @endif
    @endforeach
@else
    @vite($assets)
@endif
