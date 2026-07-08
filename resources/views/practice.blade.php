<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Latihan Kotoba - Nihongo Trainer</title>
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
                <p class="eyebrow">{{ $chapter->level }} quiz room</p>
                <h1>Latihan Kotoba</h1>
            </div>
            <div class="trainer-actions">
                <a class="ghost-button" href="{{ route('landing') }}">Landing</a>
                <a class="ghost-button" href="{{ route('trainer') }}">Materi</a>
                <a class="ghost-button" href="{{ route('kana.index') }}">Kana</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="ghost-button" type="submit">Keluar</button>
                </form>
            </div>
        </header>

        <section class="workspace practice-workspace">
            <aside class="left-rail">
                <section class="panel drill-panel">
                    <div class="panel-heading">
                        <p class="eyebrow">Atur latihan</p>
                        <h2>Tanpa contekan</h2>
                    </div>

                    <div class="control-group">
                        <span>Arah soal</span>
                        <div class="segmented" role="group" aria-label="Mode latihan">
                            <button type="button" class="is-active" data-mode="jp-id">Jepang -> Indonesia</button>
                            <button type="button" data-mode="id-jp">Indonesia -> Jepang</button>
                        </div>
                    </div>

                    <div class="control-group">
                        <span>Jumlah</span>
                        <div class="count-grid" role="group" aria-label="Jumlah soal">
                            <button type="button" data-count="5">5</button>
                            <button type="button" data-count="10">10</button>
                            <button type="button" class="is-active" data-count="all">Semua</button>
                        </div>
                    </div>

                    <button id="start-quiz" class="primary-button wide" type="button">Mulai latihan</button>

                    @if ($latestResult)
                        <p class="last-score">
                            Terakhir: {{ $latestResult->correct_answers }}/{{ $latestResult->total_questions }}
                            benar
                        </p>
                    @endif
                </section>

                <section class="panel quiet-panel">
                    <p class="eyebrow">Bank aktif</p>
                    <h2>{{ $kotobas->count() }} kotoba</h2>
                    <p class="panel-copy">Daftar kata disembunyikan di halaman ini. Kalau ingin melihat materi lagi, kembali ke halaman Materi.</p>
                </section>
            </aside>

            <section class="quiz-board standalone-quiz" id="quiz-board" aria-live="polite">
                <div class="empty-quiz">
                    <p class="eyebrow">Siap latihan</p>
                    <h2>Pilih jumlah soal, lalu mulai.</h2>
                    <p>Jawaban dibuat pilihan ganda dari kotoba lain agar latihan tidak terlalu mudah ditebak.</p>
                </div>
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
