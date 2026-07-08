<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Nihongo Trainer</title>
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
            <p class="eyebrow">Masuk akun</p>
            <h1>Lanjutkan latihan hari ini.</h1>

            <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                @csrf
                <label>
                    <span>Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </label>
                <label>
                    <span>Password</span>
                    <input name="password" type="password" autocomplete="current-password" required>
                </label>
                <label class="check-row">
                    <input name="remember" type="checkbox" value="1">
                    <span>Ingat saya</span>
                </label>
                @if ($errors->any())
                    <p class="auth-error">{{ $errors->first() }}</p>
                @endif
                <button class="primary-button wide" type="submit">Masuk</button>
            </form>

            <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar dulu</a></p>
        </section>
    </main>
</body>
</html>
