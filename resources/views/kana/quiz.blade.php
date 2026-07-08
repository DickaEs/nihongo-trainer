<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ucfirst($script) }} Quiz - Nihongo Trainer</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/kana.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
</head>
<body>
    <main class="kana-shell kana-quiz-shell" data-kana-script="{{ $script }}">
        <header class="kana-topbar">
            <div>
                <p class="eyebrow">46 basic {{ $script }}</p>
                <h1>{{ ucfirst($script) }}</h1>
                <p>Isi romaji, tekan Enter, dan lanjutkan sampai semua kartu selesai.</p>
            </div>
            <div class="kana-actions">
                <span id="kana-timer">00:00</span>
                <button id="kana-retry" class="ghost-button" type="button">Ulang</button>
                <a class="ghost-button" href="{{ route('kana.index') }}">Pilih kana</a>
                <a class="ghost-button" href="{{ route('landing') }}">Landing</a>
            </div>
        </header>

        <section id="kana-result" class="kana-result" hidden></section>
        <section id="kana-grid" class="kana-grid" aria-label="Latihan {{ $script }}"></section>

        <div class="kana-bottom-actions">
            <button id="kana-finish" class="primary-button" type="button">Lihat hasil</button>
        </div>

        <div id="kana-result-modal" class="kana-modal" hidden>
            <div class="kana-modal-backdrop" data-kana-close></div>
            <section class="kana-modal-panel" role="dialog" aria-modal="true" aria-labelledby="kana-modal-title">
                <button class="kana-modal-close" type="button" data-kana-close aria-label="Tutup hasil">x</button>
                <div id="kana-modal-content"></div>
            </section>
        </div>
    </main>

    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/kana.js')) !!}</script>
    @endunless
</body>
</html>
