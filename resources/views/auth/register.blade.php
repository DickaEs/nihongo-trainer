<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - Nihongo Trainer</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
</head>
<body class="auth-body">
    <main class="auth-shell">
        <a class="brand-mark" href="{{ route('landing') }}">日本語 Trainer</a>
        <section class="auth-card">
            <p class="eyebrow">Akun baru</p>
            <h1>Buat ruang latihanmu.</h1>

            <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                @csrf
                <label>
                    <span>Nama</span>
                    <input name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
                </label>
                <label>
                    <span>Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                </label>
                <label>
                    <span>Password</span>
                    <input name="password" type="password" autocomplete="new-password" required>
                </label>
                <label>
                    <span>Ulangi password</span>
                    <input name="password_confirmation" type="password" autocomplete="new-password" required>
                </label>
                @if ($errors->any())
                    <p class="auth-error">{{ $errors->first() }}</p>
                @endif
                <button class="primary-button wide" type="submit">Daftar dan mulai</button>
            </form>

            <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </section>
    </main>
</body>
</html>
