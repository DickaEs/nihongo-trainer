<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nihongo Trainer N5</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
</head>
<body>
    <main class="app-shell" data-chapter-id="{{ $chapter->id }}">
        <header class="topbar">
            <div>
                <p class="eyebrow">{{ $chapter->level }} daily kotoba</p>
                <h1>Materi Kotoba</h1>
            </div>
            <div class="trainer-actions">
                <a class="ghost-button" href="{{ route('landing') }}">Landing</a>
                <a class="ghost-button" href="{{ route('kana.index') }}">Kana</a>
                <a class="primary-link compact-link" href="{{ route('practice') }}">Mulai latihan</a>
                <div class="day-stamp">
                    <span>{{ auth()->user()->name }}</span>
                    <strong>{{ $kotobas->count() }}</strong>
                    <small>kata tersimpan</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="ghost-button" type="submit">Keluar</button>
                </form>
            </div>
        </header>

        <section class="workspace material-workspace">
            <aside class="left-rail">
                <section class="panel add-panel">
                    <div class="panel-heading">
                        <p class="eyebrow">Tambah kotoba</p>
                        <h2>Catatan baru</h2>
                    </div>

                    <form id="kotoba-form" class="kotoba-form">
                        <label>
                            <span>Romaji</span>
                            <input name="romaji" autocomplete="off" placeholder="watashi" required>
                        </label>
                        <label>
                            <span>Hiragana / katakana</span>
                            <input name="kana" autocomplete="off" placeholder="わたし" required>
                        </label>
                        <label>
                            <span>Kanji</span>
                            <input name="kanji" autocomplete="off" placeholder="私">
                        </label>
                        <label>
                            <span>Arti Indonesia</span>
                            <input name="meaning" autocomplete="off" placeholder="Saya" required>
                        </label>
                        <button class="primary-button" type="submit">Simpan kotoba</button>
                        <p id="form-note" class="form-note" aria-live="polite"></p>
                    </form>
                </section>

                <section class="panel drill-panel">
                    <div class="panel-heading">
                        <p class="eyebrow">Ruang latihan</p>
                        <h2>Terpisah dari materi</h2>
                    </div>
                    <p class="panel-copy">Latihan dibuka di halaman berbeda supaya daftar arti tidak terlihat saat menjawab.</p>
                    <a class="primary-link wide panel-link" href="{{ route('practice') }}">Ke halaman latihan</a>

                    @if ($latestResult)
                        <p class="last-score">
                            Terakhir: {{ $latestResult->correct_answers }}/{{ $latestResult->total_questions }}
                            benar
                        </p>
                    @endif
                </section>
            </aside>

            <section class="main-board">
                <div class="chapter-strip">
                    <div>
                        <p class="eyebrow">{{ $chapter->title }}</p>
                        <h2>Bank materi kotoba</h2>
                    </div>
                    <button id="shuffle-preview" type="button">Acak tampilan</button>
                </div>

                <div id="word-list" class="word-list" aria-live="polite"></div>
            </section>
        </section>
    </main>

    <script>
        window.nihongoInitialKotoba = @json($kotobas);
    </script>
    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/app.js')) !!}</script>
    @endunless
</body>
</html>
