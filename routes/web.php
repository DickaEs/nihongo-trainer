<?php

use App\Models\Chapter;
use App\Models\Kotoba;
use App\Models\QuizResult;
use App\Models\User;
use Database\Seeders\KotobaSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials, $request->boolean('remember'))) {
        return back()
            ->withErrors(['email' => 'Email atau password belum cocok.'])
            ->onlyInput('email');
    }

    $request->session()->regenerate();

    return redirect()->intended(route('trainer'));
})->middleware('guest')->name('login.store');

Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = User::create($validated);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()->route('trainer');
})->middleware('guest')->name('register.store');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('landing');
})->middleware('auth')->name('logout');

Route::get('/trainer', function () {
    $chapter = Chapter::query()
        ->where('slug', 'n5-minna-bab-1')
        ->first();

    if (! $chapter) {
        $chapter = Chapter::create([
            'level' => 'N5',
            'name' => 'Minna no Nihongo Bab 1',
            'title' => 'Minna no Nihongo Bab 1',
            'slug' => 'n5-minna-bab-1',
            'position' => 1,
        ]);
    }

    if ($chapter->kotobas()->doesntExist()) {
        app(KotobaSeeder::class)->run();
    }

    $kotobas = Kotoba::query()
        ->where('chapter_id', $chapter->id)
        ->where(fn ($query) => $query
            ->where('is_default', true)
            ->orWhere('user_id', auth()->id()))
        ->orderBy('romaji')
        ->get();

    $latestResult = QuizResult::query()
        ->where('chapter_id', $chapter->id)
        ->where('user_id', auth()->id())
        ->latest()
        ->first();

    return view('welcome', [
        'chapter' => $chapter,
        'kotobas' => $kotobas,
        'latestResult' => $latestResult,
    ]);
})->middleware('auth')->name('trainer');

Route::get('/practice', function () {
    $chapter = Chapter::query()
        ->where('slug', 'n5-minna-bab-1')
        ->first();

    if (! $chapter) {
        return redirect()->route('trainer');
    }

    $kotobas = Kotoba::query()
        ->where('chapter_id', $chapter->id)
        ->where(fn ($query) => $query
            ->where('is_default', true)
            ->orWhere('user_id', auth()->id()))
        ->orderBy('romaji')
        ->get();

    $latestResult = QuizResult::query()
        ->where('chapter_id', $chapter->id)
        ->where('user_id', auth()->id())
        ->latest()
        ->first();

    return view('practice', [
        'chapter' => $chapter,
        'kotobas' => $kotobas,
        'latestResult' => $latestResult,
    ]);
})->middleware('auth')->name('practice');

Route::get('/kana', function () {
    return view('kana.index');
})->middleware('auth')->name('kana.index');

Route::get('/kana/{script}', function (string $script) {
    abort_unless(in_array($script, ['hiragana', 'katakana'], true), 404);

    return view('kana.quiz', ['script' => $script]);
})->middleware('auth')->name('kana.quiz');

Route::get('/kotoba', function () {
    $chapter = Chapter::query()->where('slug', 'n5-minna-bab-1')->firstOrFail();

    return Kotoba::query()
        ->where('chapter_id', $chapter->id)
        ->where(fn ($query) => $query
            ->where('is_default', true)
            ->orWhere('user_id', auth()->id()))
        ->orderBy('romaji')
        ->get();
})->middleware('auth');

Route::post('/kotoba', function (Request $request) {
    $chapter = Chapter::query()->where('slug', 'n5-minna-bab-1')->firstOrFail();

    $validated = $request->validate([
        'romaji' => ['required', 'string', 'max:120'],
        'kana' => ['required', 'string', 'max:120'],
        'kanji' => ['nullable', 'string', 'max:120'],
        'meaning' => ['required', 'string', 'max:180'],
    ]);

    $kotoba = Kotoba::create([
        ...$validated,
        'chapter_id' => $chapter->id,
        'user_id' => auth()->id(),
        'hiragana' => $validated['kana'],
        'kanji' => $validated['kanji'] ?? null,
        'is_default' => false,
    ]);

    return response()->json($kotoba, 201);
})->middleware('auth');

Route::post('/quiz-results', function (Request $request) {
    $chapter = Chapter::query()->where('slug', 'n5-minna-bab-1')->firstOrFail();

    $validated = $request->validate([
        'mode' => ['required', Rule::in(['jp-id', 'id-jp'])],
        'total_questions' => ['required', 'integer', 'min:1', 'max:200'],
        'correct_answers' => ['required', 'integer', 'min:0', 'lte:total_questions'],
        'answers' => ['nullable', 'array'],
    ]);

    $result = QuizResult::create([
        ...$validated,
        'chapter_id' => $chapter->id,
        'user_id' => auth()->id(),
        'quiz_type' => $validated['mode'],
        'incorrect_answers' => $validated['total_questions'] - $validated['correct_answers'],
        'score' => round(($validated['correct_answers'] / $validated['total_questions']) * 100, 2),
    ]);

    return response()->json($result, 201);
})->middleware('auth');
