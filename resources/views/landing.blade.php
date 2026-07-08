<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nihongo Trainer</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
</head>
<body class="landing-body">
    <main class="landing-shell">
        <nav class="landing-nav">
            <a class="brand-mark" href="{{ route('landing') }}">
                <span>&#26085;</span>
                <strong>Nihongo Trainer</strong>
            </a>
            <div class="landing-links">
                <a href="#fitur">Fitur</a>
                <a href="#alur">Alur</a>
                <a href="#kana">Kana</a>
                @auth
                    <a href="{{ route('trainer') }}">Materi</a>
                    <a class="nav-cta" href="{{ route('practice') }}">Latihan</a>
                @else
                    <a href="{{ route('login') }}">Masuk</a>
                    <a class="nav-cta" href="{{ route('register') }}">Daftar</a>
                @endauth
            </div>
        </nav>

        <section class="landing-hero">
            <div class="hero-copy reveal-item">
                <p class="eyebrow">N5 kotoba practice</p>
                <h1>Nihongo Trainer</h1>
                <p class="hero-lead">
                    Mulai dari kosakata Minna no Nihongo bab 1, tambah kata versi kamu, lalu latihan di ruang terpisah supaya jawaban tidak kelihatan saat mengerjakan.
                </p>
                <div class="hero-actions">
                    <a class="primary-link" href="{{ auth()->check() ? route('practice') : route('register') }}">Mulai latihan</a>
                    <a class="secondary-link" href="{{ auth()->check() ? route('trainer') : route('login') }}">Buka materi</a>
                    @auth
                        <a class="secondary-link" href="{{ route('kana.index') }}">Latihan kana</a>
                    @endauth
                </div>
            </div>

            <div class="study-sheet reveal-item" aria-label="Preview latihan kotoba">
                <div class="sheet-header">
                    <span>Bab 1</span>
                    <strong>&#20170;&#26085;</strong>
                </div>
                <div class="sheet-row">
                    <span>&#12431;&#12383;&#12375;</span>
                    <strong>Saya</strong>
                </div>
                <div class="sheet-row">
                    <span>&#12379;&#12435;&#12379;&#12356;</span>
                    <strong>Guru, dosen</strong>
                </div>
                <div class="sheet-row muted-row">
                    <span>&#12452;&#12531;&#12489;&#12493;&#12471;&#12450;</span>
                    <strong>Indonesia</strong>
                </div>
                <div class="mini-quiz">
                    <small>Pilih arti</small>
                    <b>&#12364;&#12367;&#12379;&#12356;</b>
                    <button>Siswa, murid</button>
                    <button>Pegawai bank</button>
                </div>
            </div>
        </section>

        <section class="landing-note reveal-item" id="alur">
            <div>
                <p class="eyebrow">Kenapa dipisah?</p>
                <h2>Materi untuk membaca, latihan untuk mengingat.</h2>
            </div>
            <p>Di halaman materi kamu boleh melihat daftar kotoba, menambah kata, dan merapikan catatan. Saat masuk latihan, daftar itu disembunyikan agar jawabannya benar-benar dari ingatan.</p>
        </section>

        <section class="landing-band" id="fitur">
            <article class="reveal-item">
                <span>01</span>
                <h2>Simpan kotoba</h2>
                <p>Catat romaji, hiragana atau katakana, kanji opsional, dan arti Indonesia.</p>
            </article>
            <article class="reveal-item">
                <span>02</span>
                <h2>Latihan terpisah</h2>
                <p>Pilih 5, 10, atau semua kata tanpa melihat bank materi di sisi layar.</p>
            </article>
            <article class="reveal-item">
                <span>03</span>
                <h2>Review hasil</h2>
                <p>Setelah selesai, lihat skor dan jawaban yang masih perlu diulang.</p>
            </article>
        </section>

        <section class="landing-split reveal-item" id="kana">
            <div class="method-copy">
                <p class="eyebrow">Ritme belajar</p>
                <h2>Belajar pendek, tapi sering.</h2>
                <p>Website ini dibuat untuk sesi kecil: buka materi, tambah satu-dua kotoba baru, lalu ambil latihan singkat. Tidak perlu dashboard ramai kalau tujuan hari ini cuma mengingat dengan lebih rapi.</p>
            </div>
            <div class="routine-table">
                <div><span>2 menit</span><strong>Baca ulang materi</strong></div>
                <div><span>1 menit</span><strong>Tambah kotoba baru</strong></div>
                <div><span>5 menit</span><strong>Latihan pilihan ganda</strong></div>
                <div><span>1 menit</span><strong>Cek yang salah</strong></div>
            </div>
        </section>

        <section class="landing-showcase">
            <div class="showcase-card dark-card reveal-item">
                <p class="eyebrow">Mode latihan</p>
                <h2>Kotoba dan kana tidak dicampur.</h2>
                <p>Kosakata punya alur pilihan ganda sendiri, sedangkan hiragana dan katakana punya grid keyboard dengan timer.</p>
            </div>
            <div class="showcase-card metric-card reveal-item">
                <span>46</span>
                <strong>huruf dasar</strong>
                <p>Hiragana dan katakana diacak setiap mulai ulang.</p>
            </div>
            <div class="showcase-card metric-card green-card reveal-item">
                <span>N5</span>
                <strong>mulai dari dasar</strong>
                <p>Bank awal memakai kotoba Minna no Nihongo bab 1.</p>
            </div>
        </section>

        <section class="feature-ledger reveal-item">
            <div class="ledger-row">
                <strong>Default N5</strong>
                <span>Kosakata awal Minna no Nihongo bab 1 sudah tersedia.</span>
            </div>
            <div class="ledger-row">
                <strong>Kotoba pribadi</strong>
                <span>Kata tambahan tersimpan di akunmu sendiri.</span>
            </div>
            <div class="ledger-row">
                <strong>Mode bolak-balik</strong>
                <span>Jepang ke Indonesia atau Indonesia ke hiragana/katakana.</span>
            </div>
            <div class="ledger-row">
                <strong>Distraktor mirip</strong>
                <span>Jawaban pilihan ganda diambil dari kata lain agar tidak terlalu gampang.</span>
            </div>
            <div class="ledger-row">
                <strong>Latihan kana</strong>
                <span>Hiragana dan katakana dasar dilatih di halaman khusus dengan timer.</span>
            </div>
        </section>

        <footer class="landing-footer reveal-item">
            <strong>Nihongo Trainer</strong>
            <span>Materi, kotoba, kana, dan hasil latihan dalam satu ruang belajar kecil.</span>
            <a href="{{ auth()->check() ? route('practice') : route('register') }}">Mulai sekarang</a>
        </footer>
    </main>
    <script>{!! file_get_contents(resource_path('js/motion.js')) !!}</script>
</body>
</html>
