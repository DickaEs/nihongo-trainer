<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Latihan Kana - Nihongo Trainer</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
</head>
<body>
    <main class="app-shell">
        <header class="topbar kana-choice-topbar">
            <div>
                <p class="eyebrow">Kana room</p>
                <h1>Latihan Kana</h1>
                <p>Mulai dari set yang ingin kamu tajamkan hari ini, lalu jawab romaji secepat dan sebersih mungkin.</p>
            </div>
            <div class="trainer-actions">
                <a class="ghost-button" href="{{ route('landing') }}">Landing</a>
                <a class="ghost-button" href="{{ route('trainer') }}">Materi</a>
                <a class="ghost-button" href="{{ route('practice') }}">Kotoba</a>
            </div>
        </header>

        <section class="kana-choice">
            <a class="kana-choice-card is-hiragana" href="{{ route('kana.quiz', 'hiragana') }}">
                <span>Hiragana</span>
                <strong>&#12354; &#12356; &#12358;</strong>
                <p>46 huruf dasar untuk kata asli Jepang. Cocok untuk pemanasan membaca.</p>
                <small>Mulai latihan</small>
            </a>
            <a class="kana-choice-card is-katakana" href="{{ route('kana.quiz', 'katakana') }}">
                <span>Katakana</span>
                <strong>&#12450; &#12452; &#12454;</strong>
                <p>46 huruf dasar untuk serapan dan nama asing. Urutan selalu diacak.</p>
                <small>Mulai latihan</small>
            </a>
        </section>
    </main>
</body>
</html>
