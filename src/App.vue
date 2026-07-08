<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { defaultKotoba, kanaSets } from './data';

const scoreKey = 'nihongo-trainer-last-score';
const preferencesKey = 'nihongo-trainer-preferences';
const routes = ['landing', 'materi', 'latihan', 'kana', 'hiragana', 'katakana'];
const savedPreferences = loadJson(preferencesKey, {});

const page = ref('landing');
const selectedChapter = ref(Number(savedPreferences.selectedChapter ?? 1));
const shuffledPreview = ref(false);
const quizMode = ref(savedPreferences.quizMode ?? 'jp-id');
const quizCount = ref(savedPreferences.quizCount ?? 'all');
const customQuizCount = ref(Number(savedPreferences.customQuizCount ?? 5));
const showRomaji = ref(savedPreferences.showRomaji ?? true);
const theme = ref(savedPreferences.theme ?? 'light');
const settingsOpen = ref(false);
const quiz = reactive({ items: [], current: 0, options: [], answers: [], locked: false, finished: false });
const lastScore = ref(loadJson(scoreKey, null));
const kanaState = reactive({
    script: 'hiragana',
    items: [],
    current: 0,
    startedAt: null,
    finishedAt: null,
    elapsed: '00:00',
    showResult: false,
});

let timer = null;

const chapters = Array.from({ length: 50 }, (_, index) => index + 1);
const allWords = computed(() => defaultKotoba.map((word) => ({ ...word, chapter: word.chapter ?? 1 })));
const words = computed(() => allWords.value
    .filter((word) => word.chapter === selectedChapter.value)
    .sort((a, b) => a.romaji.localeCompare(b.romaji)));
const maxQuizCount = computed(() => words.value.length);
const visibleWords = computed(() => (shuffledPreview.value ? shuffle(words.value) : words.value));
const selectedChapterLabel = computed(() => `Bab ${selectedChapter.value}`);
const currentQuestion = computed(() => quiz.items[quiz.current]);
const quizCorrect = computed(() => quiz.answers.filter((answer) => answer.correct).length);
const quizPercent = computed(() => (quiz.items.length ? Math.round((quizCorrect.value / quiz.items.length) * 100) : 0));
const kanaClean = computed(() => kanaState.items.filter((item) => item.done && !item.hadWrong).length);
const kanaDone = computed(() => kanaState.items.filter((item) => item.done).length);
const kanaWrong = computed(() => kanaState.items.filter((item) => item.hadWrong));
const kanaPercent = computed(() => (kanaState.items.length ? Math.round((kanaClean.value / kanaState.items.length) * 100) : 0));

const answerAliases = {
    shi: ['shi', 'si'],
    chi: ['chi', 'ti'],
    tsu: ['tsu', 'tu'],
    fu: ['fu', 'hu'],
    ji: ['ji', 'zi'],
    wo: ['wo', 'o'],
};

watch(page, (value) => {
    window.location.hash = value === 'landing' ? '' : value;

    if (value === 'hiragana' || value === 'katakana') {
        resetKana(value);
    } else {
        stopTimer();
    }
});

watch([selectedChapter, quizMode, quizCount, customQuizCount, showRomaji, theme], savePreferences);
watch(words, () => {
    customQuizCount.value = clampQuizCount(customQuizCount.value);
    quiz.items = [];
    quiz.finished = false;
});

onMounted(() => {
    const hashPage = window.location.hash.replace('#', '');
    if (routes.includes(hashPage)) page.value = hashPage;
    applyTheme();
    window.addEventListener('hashchange', syncRoute);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', syncRoute);
    stopTimer();
});

function syncRoute() {
    const hashPage = window.location.hash.replace('#', '');
    page.value = routes.includes(hashPage) ? hashPage : 'landing';
}

function go(target) {
    page.value = target;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function loadJson(key, fallback) {
    try {
        return JSON.parse(localStorage.getItem(key)) ?? fallback;
    } catch {
        return fallback;
    }
}

function savePreferences() {
    localStorage.setItem(preferencesKey, JSON.stringify({
        quizMode: quizMode.value,
        quizCount: quizCount.value,
        customQuizCount: customQuizCount.value,
        showRomaji: showRomaji.value,
        selectedChapter: selectedChapter.value,
        theme: theme.value,
    }));
    applyTheme();
}

function applyTheme() {
    document.body.classList.toggle('theme-dark', theme.value === 'dark');
}

function toggleTheme() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
}

function setQuizMode(value) {
    quizMode.value = value;
}

function setQuizCount(value) {
    quizCount.value = value;
    customQuizCount.value = clampQuizCount(customQuizCount.value);
}

function clampQuizCount(value) {
    if (maxQuizCount.value === 0) return 0;
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) return 1;
    return Math.min(Math.max(1, Math.floor(parsed)), maxQuizCount.value);
}

function setCustomQuizCount(value) {
    customQuizCount.value = clampQuizCount(value);
    quizCount.value = 'custom';
}

function toggleRomaji() {
    showRomaji.value = !showRomaji.value;
}

function resolvedQuizCount() {
    if (quizCount.value === 'all') return words.value.length;
    if (quizCount.value === 'custom') return clampQuizCount(customQuizCount.value);
    return Number(quizCount.value);
}

function resetLastScore() {
    lastScore.value = null;
    localStorage.removeItem(scoreKey);
}

function shuffle(items) {
    const copy = [...items];
    for (let index = copy.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(Math.random() * (index + 1));
        [copy[index], copy[swapIndex]] = [copy[swapIndex], copy[index]];
    }
    return copy;
}

function answerValue(word, mode = quizMode.value) {
    return mode === 'jp-id' ? word.meaning : word.kana;
}

function promptParts(word) {
    if (quizMode.value === 'jp-id') {
        return {
            label: 'Pilih arti Indonesia',
            main: word.kana,
            sub: [showRomaji.value ? word.romaji : '', word.kanji].filter(Boolean).join(' / '),
        };
    }

    return { label: 'Pilih hiragana / katakana', main: word.meaning, sub: '' };
}

function optionLabel(word) {
    return quizMode.value === 'jp-id' ? word.meaning : [word.kana, showRomaji.value ? word.romaji : ''].filter(Boolean).join(' / ');
}

function similarityScore(source, candidate) {
    const a = String(source ?? '').toLowerCase();
    const b = String(candidate ?? '').toLowerCase();
    let score = 0;
    if (!a || !b) return score;
    if (a[0] === b[0]) score += 4;
    if (a.slice(0, 2) === b.slice(0, 2)) score += 3;
    if (Math.abs(a.length - b.length) <= 2) score += 2;
    for (const char of new Set(a)) if (b.includes(char)) score += 1;
    return score;
}

function makeOptions(word) {
    const correct = answerValue(word);
    const candidates = words.value
        .filter((item) => item.id !== word.id && answerValue(item) !== correct)
        .sort((a, b) => similarityScore(correct, answerValue(b)) - similarityScore(correct, answerValue(a)));

    quiz.options = shuffle([word, ...shuffle(candidates.slice(0, 7)).slice(0, 3)]).map((item) => ({
        id: item.id,
        label: optionLabel(item),
        value: answerValue(item),
        correct: item.id === word.id,
    }));
}

function startQuiz() {
    if (words.value.length < 2) return;

    const requested = resolvedQuizCount();
    quiz.items = shuffle(words.value).slice(0, Math.min(requested, words.value.length));
    quiz.current = 0;
    quiz.answers = [];
    quiz.finished = false;
    quiz.locked = false;
    makeOptions(quiz.items[0]);
}

function chooseAnswer(option) {
    if (quiz.locked || !currentQuestion.value) return;

    const expected = answerValue(currentQuestion.value);
    quiz.locked = true;
    quiz.answers.push({
        word_id: currentQuestion.value.id,
        prompt: promptParts(currentQuestion.value).main,
        selected: option.value,
        expected,
        correct: option.value === expected,
    });
}

function nextQuestion() {
    if (quiz.current + 1 >= quiz.items.length) {
        quiz.finished = true;
        lastScore.value = { correct: quizCorrect.value, total: quiz.items.length };
        localStorage.setItem(scoreKey, JSON.stringify(lastScore.value));
        return;
    }

    quiz.current += 1;
    quiz.locked = false;
    makeOptions(currentQuestion.value);
}

function resetKana(script = kanaState.script) {
    stopTimer();
    kanaState.script = script;
    kanaState.items = shuffle(kanaSets[script]).map(([kana, answer]) => ({
        kana,
        answer,
        value: '',
        done: false,
        hadWrong: false,
    }));
    kanaState.current = 0;
    kanaState.startedAt = null;
    kanaState.finishedAt = null;
    kanaState.elapsed = '00:00';
    kanaState.showResult = false;

    nextTick(() => {
        document.querySelector('.kana-card.is-active input')?.focus();
    });
}

function startTimer() {
    if (kanaState.startedAt) return;

    kanaState.startedAt = Date.now();
    timer = window.setInterval(updateTimer, 250);
    updateTimer();
}

function stopTimer() {
    if (timer) window.clearInterval(timer);
    timer = null;
}

function updateTimer() {
    if (!kanaState.startedAt) {
        kanaState.elapsed = '00:00';
        return;
    }

    const end = kanaState.finishedAt ?? Date.now();
    const seconds = Math.max(0, Math.floor((end - kanaState.startedAt) / 1000));
    const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
    kanaState.elapsed = `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
}

function checkKana(index) {
    const item = kanaState.items[index];
    if (!item || item.done) return;

    startTimer();
    const accepted = answerAliases[item.answer] ?? [item.answer];
    const answer = item.value.trim().toLowerCase();

    if (!accepted.includes(answer)) {
        item.hadWrong = true;
        return;
    }

    item.done = true;
    do {
        kanaState.current += 1;
    } while (kanaState.items[kanaState.current]?.done);

    if (kanaState.items.every((entry) => entry.done)) {
        finishKana();
        return;
    }

    nextTick(() => document.querySelector('.kana-card.is-active input')?.focus());
}

function handleKanaInput(index) {
    const item = kanaState.items[index];
    if (!item || item.done) return;

    startTimer();

    const answer = item.value.trim().toLowerCase();
    const accepted = answerAliases[item.answer] ?? [item.answer];
    const longestAccepted = Math.max(...accepted.map((value) => value.length));

    if (accepted.includes(answer) || answer.length >= longestAccepted) {
        checkKana(index);
    }
}

function finishKana() {
    if (!kanaState.finishedAt) kanaState.finishedAt = Date.now();
    stopTimer();
    updateTimer();
    kanaState.showResult = true;
}
</script>

<template>
    <main v-if="page === 'landing'" class="landing-shell app-home">
        <nav class="home-topbar">
            <button class="home-logo" type="button" @click="go('landing')" aria-label="Nihongo Trainer home">
                <span>日</span>
            </button>
            <div class="home-top-actions">
                <button type="button" aria-label="Pengaturan belajar" @click="settingsOpen = true">⚙</button>
                <button type="button" :aria-label="theme === 'dark' ? 'Pakai tema terang' : 'Pakai tema malam'" @click="toggleTheme">{{ theme === 'dark' ? '☾' : '☀' }}</button>
            </div>
        </nav>

        <div v-if="settingsOpen" class="settings-modal" role="dialog" aria-modal="true" aria-labelledby="settings-title">
            <button class="settings-backdrop" type="button" aria-label="Tutup pengaturan" @click="settingsOpen = false"></button>
            <section class="settings-panel">
                <div class="settings-head">
                    <div>
                        <p class="eyebrow">Pengaturan</p>
                        <h2 id="settings-title">Belajar harian</h2>
                    </div>
                    <button class="settings-close" type="button" aria-label="Tutup pengaturan" @click="settingsOpen = false">x</button>
                </div>

                <div class="settings-group">
                    <strong>Arah quiz default</strong>
                    <div class="segmented">
                        <button type="button" :class="{ 'is-active': quizMode === 'jp-id' }" @click="setQuizMode('jp-id')">Jepang - Indonesia</button>
                        <button type="button" :class="{ 'is-active': quizMode === 'id-jp' }" @click="setQuizMode('id-jp')">Indonesia - Jepang</button>
                    </div>
                </div>

                <div class="settings-group">
                    <strong>Jumlah soal default</strong>
                    <div class="count-grid">
                        <button v-for="count in ['5', '10', 'all', 'custom']" :key="`setting-${count}`" type="button" :class="{ 'is-active': quizCount === count }" @click="setQuizCount(count)">
                            {{ count === 'all' ? 'Semua' : count }}
                        </button>
                    </div>
                    <label class="custom-count-field">
                        <span>Custom soal</span>
                        <input :value="customQuizCount" type="number" min="1" :max="maxQuizCount" inputmode="numeric" @input="setCustomQuizCount($event.target.value)" />
                        <small>Maksimum {{ maxQuizCount }} soal.</small>
                    </label>
                </div>

                <div class="settings-group">
                    <strong>Bantuan bacaan</strong>
                    <label class="toggle-row">
                        <span>
                            <b>Tampilkan romaji</b>
                            <small>Jika mati, quiz hanya menampilkan kana/kanji tanpa latin.</small>
                        </span>
                        <input type="checkbox" :checked="showRomaji" @change="toggleRomaji" />
                    </label>
                </div>

                <div class="settings-actions">
                    <button class="ghost-button" type="button" @click="resetLastScore">Reset skor</button>
                    <button class="ghost-button" type="button" @click="selectedChapter = 1">Pakai Bab 1</button>
                </div>
            </section>
        </div>

        <section class="home-hero-card">
            <div>
                <p>こんにちは!</p>
                <h1>Nihongo Trainer</h1>
                <span>Platform latihan kotoba, hiragana, dan katakana untuk ritme belajar pendek.</span>
            </div>
            <button class="home-session-pill" type="button" @click="go('latihan')">
                🔥 {{ words.length + 128 }} sesi latihan siap - がんばって!
            </button>
        </section>

        <section class="home-stats" aria-label="Ringkasan latihan">
            <button type="button" @click="go('materi')">
                <span>📚</span>
                <strong>{{ words.length }}</strong>
                <small>Kotoba</small>
            </button>
            <button type="button" @click="go('kana')">
                <span>あ</span>
                <strong>46</strong>
                <small>Hiragana</small>
            </button>
            <button type="button" @click="go('kana')">
                <span>ア</span>
                <strong>46</strong>
                <small>Katakana</small>
            </button>
            <button type="button" @click="go('latihan')">
                <span>🎯</span>
                <strong>{{ lastScore ? `${lastScore.correct}/${lastScore.total}` : 'N5' }}</strong>
                <small>Skor</small>
            </button>
        </section>

        <section class="home-section">
            <div class="home-section-title">
                <strong>🎯 Kuis Utama</strong>
                <span></span>
            </div>
            <div class="home-quiz-grid">
                <button class="home-quiz-card purple" type="button" @click="go('kana')">
                    <span>あ</span>
                    <strong>Kuis Kana</strong>
                    <small>Hiragana & Katakana</small>
                </button>
                <button class="home-quiz-card amber" type="button" @click="go('latihan')">
                    <span>📚</span>
                    <strong>Kuis Kotoba</strong>
                    <small>Kosakata Bab 1-50</small>
                </button>
                <button class="home-quiz-card blue" type="button" @click="go('materi')">
                    <span>単</span>
                    <strong>Bank Materi</strong>
                    <small>Tambah catatan pribadi</small>
                </button>
                <button class="home-quiz-card green" type="button" @click="go('latihan')">
                    <span>文</span>
                    <strong>Review Cepat</strong>
                    <small>Ulang yang belum hafal</small>
                </button>
            </div>
        </section>

        <section class="home-section home-mini-panel">
            <div>
                <p class="eyebrow">Belajar hari ini</p>
                <h2>Mulai dari 5 soal, lalu cek hasilnya.</h2>
            </div>
            <button class="primary-button" type="button" @click="go('latihan')">Mulai Latihan</button>
        </section>

        <nav class="home-bottom-nav" aria-label="Navigasi utama">
            <button class="is-active" type="button" @click="go('landing')"><span>🏠</span><small>Home</small></button>
            <button type="button" @click="go('materi')"><span>📚</span><small>Kotoba</small></button>
            <button type="button" @click="go('kana')"><span>あ</span><small>Kana</small></button>
            <button type="button" @click="go('latihan')"><span>🎯</span><small>Kuis</small></button>
        </nav>
    </main>

    <main v-else-if="page === 'materi'" class="app-shell">
        <header class="topbar page-hero page-hero-material">
            <div>
                <p class="eyebrow">N5 daily kotoba</p>
                <h1>Materi Kotoba</h1>
                <p class="page-hero-copy">Baca ulang bank kosakata per bab, lalu lanjutkan ke kuis saat sudah siap.</p>
            </div>
            <div class="page-hero-side">
                <div class="day-stamp"><span>{{ selectedChapterLabel }}</span><strong>{{ words.length }}</strong><small>kata tersedia</small></div>
                <button class="primary-button" type="button" @click="go('latihan')">Mulai kuis</button>
            </div>
        </header>

        <section class="workspace material-workspace">
            <aside class="left-rail">
                <section class="panel filter-panel">
                    <div class="panel-heading"><p class="eyebrow">Filter kotoba</p><h2>Pilih bab</h2></div>
                    <label class="chapter-select">
                        <span>Minna no Nihongo</span>
                        <select v-model.number="selectedChapter">
                            <option v-for="chapter in chapters" :key="chapter" :value="chapter">Bab {{ chapter }}</option>
                        </select>
                    </label>
                    <div class="chapter-summary">
                        <strong>{{ selectedChapterLabel }}</strong>
                        <span>{{ words.length }} kotoba tersedia</span>
                        <small v-if="words.length === 0">Data bab ini belum diisi. Update kotoba dilakukan manual di kode.</small>
                        <small v-else>Saat ini data yang tersedia baru Bab 1.</small>
                    </div>
                </section>
                <section class="panel drill-panel">
                    <div class="panel-heading"><p class="eyebrow">Ruang latihan</p><h2>Terpisah dari materi</h2></div>
                    <p class="panel-copy">Latihan dibuka di halaman berbeda supaya daftar arti tidak terlihat saat menjawab.</p>
                    <button class="primary-link wide panel-link" type="button" @click="go('latihan')">Ke halaman latihan</button>
                    <p v-if="lastScore" class="last-score">Terakhir: {{ lastScore.correct }}/{{ lastScore.total }} benar</p>
                </section>
            </aside>

            <section class="main-board">
                <div class="chapter-strip">
                    <div><p class="eyebrow">Minna no Nihongo {{ selectedChapterLabel }}</p><h2>Bank materi kotoba</h2></div>
                    <button type="button" @click="shuffledPreview = !shuffledPreview">Acak tampilan</button>
                </div>

                <div class="word-list" aria-live="polite">
                    <div v-if="visibleWords.length === 0" class="empty-quiz material-empty">
                        <p class="eyebrow">Belum ada data</p>
                        <h2>{{ selectedChapterLabel }} belum punya kotoba.</h2>
                        <p>Untuk sekarang, bank kotoba baru tersedia di Bab 1.</p>
                    </div>
                    <article v-for="word in visibleWords" :key="word.id" class="word-card" :class="{ custom: !word.is_default }">
                        <p class="kana">{{ word.kana }}</p>
                        <p class="romaji">{{ word.romaji }}</p>
                        <p class="meaning">{{ word.meaning }}</p>
                        <div class="tag-row">
                            <span v-if="word.kanji">{{ word.kanji }}</span>
                            <span>{{ word.is_default ? 'default' : 'tambahan' }}</span>
                        </div>
                    </article>
                </div>
            </section>
        </section>

        <nav class="page-bottom-nav" aria-label="Navigasi utama">
            <button type="button" @click="go('landing')"><span>🏠</span><small>Home</small></button>
            <button class="is-active" type="button" @click="go('materi')"><span>📚</span><small>Materi</small></button>
            <button type="button" @click="go('kana')"><span>あ</span><small>Kana</small></button>
            <button type="button" @click="go('latihan')"><span>🎯</span><small>Kuis</small></button>
        </nav>
    </main>

    <main v-else-if="page === 'latihan'" class="app-shell">
        <header class="topbar page-hero page-hero-quiz">
            <div>
                <p class="eyebrow">N5 quiz room</p>
                <h1>Latihan Kotoba</h1>
                <p class="page-hero-copy">Pilih arah soal dan jumlah pertanyaan. Daftar jawaban disembunyikan agar latihan benar-benar dari ingatan.</p>
            </div>
            <div class="page-hero-side">
                <div class="day-stamp">
                    <span>{{ selectedChapterLabel }}</span>
                    <strong>{{ words.length }}</strong>
                    <small>kotoba aktif</small>
                </div>
                <button class="secondary-link" type="button" @click="go('materi')">Review materi</button>
            </div>
        </header>

        <section class="workspace practice-workspace">
            <aside class="left-rail">
                <section class="panel drill-panel">
                    <div class="panel-heading"><p class="eyebrow">Atur latihan</p><h2>Tanpa contekan</h2></div>
                    <div class="control-group">
                        <span>Arah soal</span>
                        <div class="segmented" role="group" aria-label="Mode latihan">
                            <button type="button" :class="{ 'is-active': quizMode === 'jp-id' }" @click="setQuizMode('jp-id')">Jepang - Indonesia</button>
                            <button type="button" :class="{ 'is-active': quizMode === 'id-jp' }" @click="setQuizMode('id-jp')">Indonesia - Jepang</button>
                        </div>
                    </div>
                    <div class="control-group">
                        <span>Jumlah</span>
                        <div class="count-grid" role="group" aria-label="Jumlah soal">
                            <button v-for="count in ['5', '10', 'all', 'custom']" :key="count" type="button" :class="{ 'is-active': quizCount === count }" @click="setQuizCount(count)">{{ count === 'all' ? 'Semua' : count }}</button>
                        </div>
                        <label class="custom-count-field compact" v-if="quizCount === 'custom'">
                            <span>Jumlah custom</span>
                            <input :value="customQuizCount" type="number" min="1" :max="maxQuizCount" inputmode="numeric" @input="setCustomQuizCount($event.target.value)" />
                            <small>Maksimum {{ maxQuizCount }} kotoba.</small>
                        </label>
                    </div>
                    <div class="control-group">
                        <span>Bantuan</span>
                        <label class="toggle-row compact">
                            <span>
                                <b>Tampilkan romaji</b>
                                <small>{{ showRomaji ? 'Romaji muncul sebagai bantuan.' : 'Hanya kana/kanji yang ditampilkan.' }}</small>
                            </span>
                            <input type="checkbox" :checked="showRomaji" @change="toggleRomaji" />
                        </label>
                    </div>
                    <button class="primary-button wide" type="button" @click="startQuiz">Mulai latihan</button>
                    <p v-if="lastScore" class="last-score">Terakhir: {{ lastScore.correct }}/{{ lastScore.total }} benar</p>
                </section>

                <section class="panel quiet-panel">
                    <p class="eyebrow">Bank aktif</p>
                    <h2>{{ words.length }} kotoba</h2>
                    <p class="panel-copy">{{ words.length ? `${selectedChapterLabel} sedang dipakai untuk kuis.` : `${selectedChapterLabel} belum punya data. Pilih Bab 1 untuk latihan saat ini.` }}</p>
                </section>
            </aside>

            <section class="quiz-board standalone-quiz" aria-live="polite">
                <div v-if="!quiz.items.length" class="empty-quiz">
                    <p class="eyebrow">Siap latihan</p>
                    <h2>{{ words.length ? 'Pilih jumlah soal, lalu mulai.' : `${selectedChapterLabel} belum punya kotoba.` }}</h2>
                    <p>{{ words.length ? 'Jawaban dibuat pilihan ganda dari kotoba lain agar latihan tidak terlalu mudah ditebak.' : 'Untuk saat ini, pilih Bab 1 di halaman Materi karena data bab lain belum diisi.' }}</p>
                </div>

                <article v-else-if="quiz.finished" class="result-card">
                    <p class="eyebrow">Hasil latihan</p>
                    <h2>{{ quizCorrect }} dari {{ quiz.items.length }} benar</h2>
                    <p class="score-number">{{ quizPercent }}%</p>
                    <p>{{ quizPercent >= 80 ? 'Mantap, lanjut pertahankan ritmenya.' : 'Bagus, ulangi lagi beberapa kata yang meleset.' }}</p>
                    <div class="review-list">
                        <div v-for="answer in quiz.answers" :key="`${answer.word_id}-${answer.prompt}`" class="review-item">
                            <strong :class="answer.correct ? 'ok' : 'no'">{{ answer.correct ? 'Benar' : 'Belum tepat' }} - {{ answer.prompt }}</strong>
                            <span>Jawabanmu: {{ answer.selected }}</span><br />
                            <span>Kunci: {{ answer.expected }}</span>
                        </div>
                    </div>
                    <button class="primary-button wide" type="button" @click="startQuiz">Latihan lagi</button>
                </article>

                <article v-else class="quiz-card">
                    <div class="progress-line">
                        <span>Soal {{ quiz.current + 1 }} dari {{ quiz.items.length }}</span>
                        <span>Benar {{ quizCorrect }}</span>
                    </div>
                    <div class="prompt">
                        <small>{{ promptParts(currentQuestion).label }}</small>
                        <strong>{{ promptParts(currentQuestion).main }}</strong>
                        <span v-if="promptParts(currentQuestion).sub">{{ promptParts(currentQuestion).sub }}</span>
                    </div>
                    <div class="options">
                        <button v-for="option in quiz.options" :key="option.id" class="option-button" :class="{ correct: quiz.locked && option.correct, wrong: quiz.locked && quiz.answers.at(-1)?.selected === option.value && !option.correct }" type="button" :disabled="quiz.locked" @click="chooseAnswer(option)">
                            {{ option.label }}
                        </button>
                    </div>
                    <div class="quiz-footer">
                        <button class="ghost-button" type="button" @click="quiz.items = []">Selesai nanti</button>
                        <button class="primary-button" type="button" :disabled="!quiz.locked" @click="nextQuestion">Lanjut</button>
                    </div>
                </article>
            </section>
        </section>

        <nav class="page-bottom-nav" aria-label="Navigasi utama">
            <button type="button" @click="go('landing')"><span>🏠</span><small>Home</small></button>
            <button type="button" @click="go('materi')"><span>📚</span><small>Materi</small></button>
            <button type="button" @click="go('kana')"><span>あ</span><small>Kana</small></button>
            <button class="is-active" type="button" @click="go('latihan')"><span>🎯</span><small>Kuis</small></button>
        </nav>
    </main>

    <main v-else-if="page === 'kana'" class="app-shell">
        <header class="topbar kana-choice-topbar page-hero page-hero-kana">
            <div>
                <p class="eyebrow">Kana room</p>
                <h1>Latihan Kana</h1>
                <p class="page-hero-copy">Mulai dari set yang ingin kamu tajamkan hari ini, lalu jawab romaji secepat dan sebersih mungkin.</p>
            </div>
            <div class="page-hero-side kana-hero-preview">
                <strong>あ ア</strong>
                <span>46 + 46 huruf dasar</span>
            </div>
        </header>

        <section class="kana-choice">
            <button class="kana-choice-card is-hiragana" type="button" @click="go('hiragana')">
                <span>Hiragana</span><strong>あ い う</strong>
                <p>46 huruf dasar untuk kata asli Jepang. Cocok untuk pemanasan membaca.</p>
                <small>Mulai latihan</small>
            </button>
            <button class="kana-choice-card is-katakana" type="button" @click="go('katakana')">
                <span>Katakana</span><strong>ア イ ウ</strong>
                <p>46 huruf dasar untuk serapan dan nama asing. Urutan selalu diacak.</p>
                <small>Mulai latihan</small>
            </button>
        </section>

        <nav class="page-bottom-nav" aria-label="Navigasi utama">
            <button type="button" @click="go('landing')"><span>🏠</span><small>Home</small></button>
            <button type="button" @click="go('materi')"><span>📚</span><small>Materi</small></button>
            <button class="is-active" type="button" @click="go('kana')"><span>あ</span><small>Kana</small></button>
            <button type="button" @click="go('latihan')"><span>🎯</span><small>Kuis</small></button>
        </nav>
    </main>

    <main v-else class="kana-shell kana-quiz-shell">
        <header class="kana-topbar page-hero page-hero-kana">
            <div>
                <p class="eyebrow">46 basic {{ kanaState.script }}</p>
                <h1>{{ kanaState.script }}</h1>
                <p class="page-hero-copy">Isi romaji, tekan Enter, dan lanjutkan sampai semua kartu selesai.</p>
            </div>
            <div class="kana-actions">
                <span id="kana-timer">{{ kanaState.elapsed }}</span>
                <button class="ghost-button" type="button" @click="resetKana(kanaState.script)">Ulang</button>
                <button class="ghost-button" type="button" @click="go('kana')">Pilih set</button>
            </div>
        </header>

        <section v-if="kanaState.showResult" class="kana-result">
            <div>
                <p class="eyebrow">Hasil kana</p>
                <h2>{{ kanaPercent }}% benar bersih</h2>
                <p>{{ kanaClean }}/{{ kanaState.items.length }} huruf dijawab tanpa salah. Waktu: {{ kanaState.elapsed }}.</p>
            </div>
            <div class="kana-wrong-list">
                <strong>Perlu diulang</strong>
                <p>{{ kanaWrong.length ? kanaWrong.map((item) => item.kana).join(' ') : 'Tidak ada. Semua bersih.' }}</p>
            </div>
        </section>

        <section class="kana-grid" :aria-label="`Latihan ${kanaState.script}`">
            <article v-for="(item, index) in kanaState.items" :key="`${item.kana}-${index}`" class="kana-card" :class="{ 'is-active': index === kanaState.current && !item.done, 'is-correct': item.done, 'is-wrong': item.hadWrong && !item.done }">
                <strong>{{ item.kana }}</strong>
                <input v-model="item.value" autocomplete="off" autocapitalize="none" spellcheck="false" :aria-label="`Jawaban untuk ${item.kana}`" :disabled="item.done" @input="handleKanaInput(index)" @keydown.enter.prevent="checkKana(index)" />
            </article>
        </section>

        <div class="kana-bottom-actions">
            <button class="primary-button" type="button" @click="finishKana">Lihat hasil</button>
        </div>

        <div v-if="kanaState.showResult" class="kana-modal">
            <button class="kana-modal-backdrop" type="button" aria-label="Tutup hasil" @click="kanaState.showResult = false"></button>
            <section class="kana-modal-panel" role="dialog" aria-modal="true" aria-labelledby="kana-modal-title">
                <button class="kana-modal-close" type="button" aria-label="Tutup hasil" @click="kanaState.showResult = false">x</button>
                <p class="eyebrow">Selesai latihan</p>
                <h2 id="kana-modal-title">{{ kanaDone === kanaState.items.length ? 'Latihan selesai' : 'Progress tersimpan' }}</h2>
                <p class="kana-modal-summary">{{ kanaClean }}/{{ kanaState.items.length }} huruf dijawab bersih dalam {{ kanaState.elapsed }}.</p>
                <div class="kana-stat-grid">
                    <div><span>Akurasi bersih</span><strong>{{ kanaPercent }}%</strong></div>
                    <div><span>Selesai</span><strong>{{ kanaDone }}/{{ kanaState.items.length }}</strong></div>
                    <div><span>Sempat salah</span><strong>{{ kanaWrong.length }}</strong></div>
                    <div><span>Waktu</span><strong>{{ kanaState.elapsed }}</strong></div>
                </div>
                <div class="kana-modal-review">
                    <strong>Perlu diulang</strong>
                    <p>{{ kanaWrong.length ? kanaWrong.map((item) => item.kana).join(' ') : 'Tidak ada. Semua bersih.' }}</p>
                </div>
                <div class="kana-modal-actions">
                    <button class="ghost-button" type="button" @click="kanaState.showResult = false">Tutup</button>
                    <button class="primary-button" type="button" @click="resetKana(kanaState.script)">Ulang lagi</button>
                </div>
            </section>
        </div>

        <nav class="page-bottom-nav" aria-label="Navigasi utama">
            <button type="button" @click="go('landing')"><span>🏠</span><small>Home</small></button>
            <button type="button" @click="go('materi')"><span>📚</span><small>Materi</small></button>
            <button class="is-active" type="button" @click="go('kana')"><span>あ</span><small>Kana</small></button>
            <button type="button" @click="go('latihan')"><span>🎯</span><small>Kuis</small></button>
        </nav>
    </main>
</template>
