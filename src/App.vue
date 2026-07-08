<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { defaultKotoba, kanaSets } from './data';

const storageKey = 'nihongo-trainer-custom-kotoba';
const scoreKey = 'nihongo-trainer-last-score';
const routes = ['landing', 'materi', 'latihan', 'kana', 'hiragana', 'katakana'];

const page = ref('landing');
const customWords = ref(loadCustomWords());
const form = reactive({ romaji: '', kana: '', kanji: '', meaning: '' });
const formNote = ref('');
const shuffledPreview = ref(false);
const quizMode = ref('jp-id');
const quizCount = ref('all');
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

const words = computed(() => [...defaultKotoba, ...customWords.value].sort((a, b) => a.romaji.localeCompare(b.romaji)));
const visibleWords = computed(() => (shuffledPreview.value ? shuffle(words.value) : words.value));
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

onMounted(() => {
    const hashPage = window.location.hash.replace('#', '');
    if (routes.includes(hashPage)) page.value = hashPage;
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

function loadCustomWords() {
    return loadJson(storageKey, []);
}

function saveCustomWords() {
    localStorage.setItem(storageKey, JSON.stringify(customWords.value));
}

function addKotoba() {
    const payload = Object.fromEntries(Object.entries(form).map(([key, value]) => [key, String(value).trim()]));

    if (!payload.romaji || !payload.kana || !payload.meaning) {
        formNote.value = 'Romaji, kana, dan arti wajib diisi.';
        return;
    }

    customWords.value = [
        ...customWords.value,
        {
            id: Date.now(),
            ...payload,
            kanji: payload.kanji || '',
            is_default: false,
        },
    ];
    saveCustomWords();
    Object.assign(form, { romaji: '', kana: '', kanji: '', meaning: '' });
    formNote.value = 'Tersimpan di browser ini. Kotoba sudah masuk daftar latihan.';
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
            sub: [word.romaji, word.kanji].filter(Boolean).join(' / '),
        };
    }

    return { label: 'Pilih hiragana / katakana', main: word.meaning, sub: '' };
}

function optionLabel(word) {
    return quizMode.value === 'jp-id' ? word.meaning : [word.kana, word.romaji].filter(Boolean).join(' / ');
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

    const requested = quizCount.value === 'all' ? words.value.length : Number(quizCount.value);
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

function finishKana() {
    if (!kanaState.finishedAt) kanaState.finishedAt = Date.now();
    stopTimer();
    updateTimer();
    kanaState.showResult = true;
}
</script>

<template>
    <main v-if="page === 'landing'" class="landing-shell">
        <nav class="landing-nav">
            <button class="brand-mark as-link" type="button" @click="go('landing')">
                <span>日</span>
                <strong>Nihongo Trainerrrr</strong>
            </button>
            <div class="landing-links">
                <a href="#fitur">Fitur</a>
                <a href="#alur">Alur</a>
                <a href="#kana">Kana</a>
                <button type="button" @click="go('materi')">Materi</button>
                <button class="nav-cta" type="button" @click="go('latihan')">Latihan</button>
            </div>
        </nav>

        <section class="landing-hero">
            <div class="hero-copy reveal-item is-visible">
                <p class="eyebrow">N5 kotoba practice</p>
                <h1>Nihongo Trainer</h1>
                <p class="hero-lead">Mulai dari kosakata Minna no Nihongo bab 1, tambah kata versi kamu, lalu latihan di ruang terpisah supaya jawaban tidak kelihatan saat mengerjakan.</p>
                <div class="hero-actions">
                    <button class="primary-link" type="button" @click="go('latihan')">Mulai latihan</button>
                    <button class="secondary-link" type="button" @click="go('materi')">Buka materi</button>
                    <button class="secondary-link" type="button" @click="go('kana')">Latihan kana</button>
                </div>
            </div>

            <div class="study-sheet reveal-item is-visible" aria-label="Preview latihan kotoba">
                <div class="sheet-header"><span>Bab 1</span><strong>今日</strong></div>
                <div class="sheet-row"><span>わたし</span><strong>Saya</strong></div>
                <div class="sheet-row"><span>せんせい</span><strong>Guru, dosen</strong></div>
                <div class="sheet-row muted-row"><span>インドネシア</span><strong>Indonesia</strong></div>
                <div class="mini-quiz">
                    <small>Pilih arti</small>
                    <b>がくせい</b>
                    <button type="button">Siswa, murid</button>
                    <button type="button">Pegawai bank</button>
                </div>
            </div>
        </section>

        <section class="landing-note reveal-item is-visible" id="alur">
            <div>
                <p class="eyebrow">Kenapa dipisah?</p>
                <h2>Materi untuk membaca, latihan untuk mengingat.</h2>
            </div>
            <p>Di halaman materi kamu boleh melihat daftar kotoba, menambah kata, dan merapikan catatan. Saat masuk latihan, daftar itu disembunyikan agar jawabannya benar-benar dari ingatan.</p>
        </section>

        <section class="landing-band" id="fitur">
            <article class="reveal-item is-visible"><span>01</span><h2>Simpan kotoba</h2><p>Catat romaji, hiragana atau katakana, kanji opsional, dan arti Indonesia.</p></article>
            <article class="reveal-item is-visible"><span>02</span><h2>Latihan terpisah</h2><p>Pilih 5, 10, atau semua kata tanpa melihat bank materi di sisi layar.</p></article>
            <article class="reveal-item is-visible"><span>03</span><h2>Review hasil</h2><p>Setelah selesai, lihat skor dan jawaban yang masih perlu diulang.</p></article>
        </section>

        <section class="landing-split reveal-item is-visible" id="kana">
            <div class="method-copy">
                <p class="eyebrow">Ritme belajar</p>
                <h2>Belajar pendek, tapi sering.</h2>
                <p>Website ini dibuat untuk sesi kecil: buka materi, tambah satu-dua kotoba baru, lalu ambil latihan singkat.</p>
            </div>
            <div class="routine-table">
                <div><span>2 menit</span><strong>Baca ulang materi</strong></div>
                <div><span>1 menit</span><strong>Tambah kotoba baru</strong></div>
                <div><span>5 menit</span><strong>Latihan pilihan ganda</strong></div>
                <div><span>1 menit</span><strong>Cek yang salah</strong></div>
            </div>
        </section>

        <footer class="landing-footer reveal-item is-visible">
            <strong>Nihongo Trainer</strong>
            <span>Materi, kotoba, kana, dan hasil latihan dalam satu ruang belajar kecil.</span>
            <button type="button" @click="go('latihan')">Mulai sekarang</button>
        </footer>
    </main>

    <main v-else-if="page === 'materi'" class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">N5 daily kotoba</p>
                <h1>Materi Kotoba</h1>
            </div>
            <div class="trainer-actions">
                <button class="ghost-button" type="button" @click="go('landing')">Landing</button>
                <button class="ghost-button" type="button" @click="go('kana')">Kana</button>
                <button class="primary-link compact-link" type="button" @click="go('latihan')">Mulai latihan</button>
                <div class="day-stamp"><span>Local</span><strong>{{ words.length }}</strong><small>kata tersimpan</small></div>
            </div>
        </header>

        <section class="workspace material-workspace">
            <aside class="left-rail">
                <section class="panel add-panel">
                    <div class="panel-heading"><p class="eyebrow">Tambah kotoba</p><h2>Catatan baru</h2></div>
                    <form class="kotoba-form" @submit.prevent="addKotoba">
                        <label><span>Romaji</span><input v-model="form.romaji" autocomplete="off" placeholder="watashi" required /></label>
                        <label><span>Hiragana / katakana</span><input v-model="form.kana" autocomplete="off" placeholder="わたし" required /></label>
                        <label><span>Kanji</span><input v-model="form.kanji" autocomplete="off" placeholder="私" /></label>
                        <label><span>Arti Indonesia</span><input v-model="form.meaning" autocomplete="off" placeholder="Saya" required /></label>
                        <button class="primary-button" type="submit">Simpan kotoba</button>
                        <p class="form-note" aria-live="polite">{{ formNote }}</p>
                    </form>
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
                    <div><p class="eyebrow">Minna no Nihongo Bab 1</p><h2>Bank materi kotoba</h2></div>
                    <button type="button" @click="shuffledPreview = !shuffledPreview">Acak tampilan</button>
                </div>

                <div class="word-list" aria-live="polite">
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
    </main>

    <main v-else-if="page === 'latihan'" class="app-shell">
        <header class="topbar">
            <div><p class="eyebrow">N5 quiz room</p><h1>Latihan Kotoba</h1></div>
            <div class="trainer-actions">
                <button class="ghost-button" type="button" @click="go('landing')">Landing</button>
                <button class="ghost-button" type="button" @click="go('materi')">Materi</button>
                <button class="ghost-button" type="button" @click="go('kana')">Kana</button>
            </div>
        </header>

        <section class="workspace practice-workspace">
            <aside class="left-rail">
                <section class="panel drill-panel">
                    <div class="panel-heading"><p class="eyebrow">Atur latihan</p><h2>Tanpa contekan</h2></div>
                    <div class="control-group">
                        <span>Arah soal</span>
                        <div class="segmented" role="group" aria-label="Mode latihan">
                            <button type="button" :class="{ 'is-active': quizMode === 'jp-id' }" @click="quizMode = 'jp-id'">Jepang - Indonesia</button>
                            <button type="button" :class="{ 'is-active': quizMode === 'id-jp' }" @click="quizMode = 'id-jp'">Indonesia - Jepang</button>
                        </div>
                    </div>
                    <div class="control-group">
                        <span>Jumlah</span>
                        <div class="count-grid" role="group" aria-label="Jumlah soal">
                            <button v-for="count in ['5', '10', 'all']" :key="count" type="button" :class="{ 'is-active': quizCount === count }" @click="quizCount = count">{{ count === 'all' ? 'Semua' : count }}</button>
                        </div>
                    </div>
                    <button class="primary-button wide" type="button" @click="startQuiz">Mulai latihan</button>
                    <p v-if="lastScore" class="last-score">Terakhir: {{ lastScore.correct }}/{{ lastScore.total }} benar</p>
                </section>

                <section class="panel quiet-panel">
                    <p class="eyebrow">Bank aktif</p>
                    <h2>{{ words.length }} kotoba</h2>
                    <p class="panel-copy">Daftar kata disembunyikan di halaman ini. Kalau ingin melihat materi lagi, kembali ke halaman Materi.</p>
                </section>
            </aside>

            <section class="quiz-board standalone-quiz" aria-live="polite">
                <div v-if="!quiz.items.length" class="empty-quiz">
                    <p class="eyebrow">Siap latihan</p>
                    <h2>Pilih jumlah soal, lalu mulai.</h2>
                    <p>Jawaban dibuat pilihan ganda dari kotoba lain agar latihan tidak terlalu mudah ditebak.</p>
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
    </main>

    <main v-else-if="page === 'kana'" class="app-shell">
        <header class="topbar kana-choice-topbar">
            <div>
                <p class="eyebrow">Kana room</p>
                <h1>Latihan Kana</h1>
                <p>Mulai dari set yang ingin kamu tajamkan hari ini, lalu jawab romaji secepat dan sebersih mungkin.</p>
            </div>
            <div class="trainer-actions">
                <button class="ghost-button" type="button" @click="go('landing')">Landing</button>
                <button class="ghost-button" type="button" @click="go('materi')">Materi</button>
                <button class="ghost-button" type="button" @click="go('latihan')">Kotoba</button>
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
    </main>

    <main v-else class="kana-shell kana-quiz-shell">
        <header class="kana-topbar">
            <div>
                <p class="eyebrow">46 basic {{ kanaState.script }}</p>
                <h1>{{ kanaState.script }}</h1>
                <p>Isi romaji, tekan Enter, dan lanjutkan sampai semua kartu selesai.</p>
            </div>
            <div class="kana-actions">
                <span id="kana-timer">{{ kanaState.elapsed }}</span>
                <button class="ghost-button" type="button" @click="resetKana(kanaState.script)">Ulang</button>
                <button class="ghost-button" type="button" @click="go('kana')">Pilih kana</button>
                <button class="ghost-button" type="button" @click="go('landing')">Landing</button>
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
                <input v-model="item.value" autocomplete="off" autocapitalize="none" spellcheck="false" :aria-label="`Jawaban untuk ${item.kana}`" :disabled="item.done" @input="startTimer" @keydown.enter.prevent="checkKana(index)" />
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
    </main>
</template>
