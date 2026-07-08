const kanaSets = {
    hiragana: [
        ['あ', 'a'], ['い', 'i'], ['う', 'u'], ['え', 'e'], ['お', 'o'],
        ['か', 'ka'], ['き', 'ki'], ['く', 'ku'], ['け', 'ke'], ['こ', 'ko'],
        ['さ', 'sa'], ['し', 'shi'], ['す', 'su'], ['せ', 'se'], ['そ', 'so'],
        ['た', 'ta'], ['ち', 'chi'], ['つ', 'tsu'], ['て', 'te'], ['と', 'to'],
        ['な', 'na'], ['に', 'ni'], ['ぬ', 'nu'], ['ね', 'ne'], ['の', 'no'],
        ['は', 'ha'], ['ひ', 'hi'], ['ふ', 'fu'], ['へ', 'he'], ['ほ', 'ho'],
        ['ま', 'ma'], ['み', 'mi'], ['む', 'mu'], ['め', 'me'], ['も', 'mo'],
        ['や', 'ya'], ['ゆ', 'yu'], ['よ', 'yo'],
        ['ら', 'ra'], ['り', 'ri'], ['る', 'ru'], ['れ', 're'], ['ろ', 'ro'],
        ['わ', 'wa'], ['を', 'wo'], ['ん', 'n'],
    ],
    katakana: [
        ['ア', 'a'], ['イ', 'i'], ['ウ', 'u'], ['エ', 'e'], ['オ', 'o'],
        ['カ', 'ka'], ['キ', 'ki'], ['ク', 'ku'], ['ケ', 'ke'], ['コ', 'ko'],
        ['サ', 'sa'], ['シ', 'shi'], ['ス', 'su'], ['セ', 'se'], ['ソ', 'so'],
        ['タ', 'ta'], ['チ', 'chi'], ['ツ', 'tsu'], ['テ', 'te'], ['ト', 'to'],
        ['ナ', 'na'], ['ニ', 'ni'], ['ヌ', 'nu'], ['ネ', 'ne'], ['ノ', 'no'],
        ['ハ', 'ha'], ['ヒ', 'hi'], ['フ', 'fu'], ['ヘ', 'he'], ['ホ', 'ho'],
        ['マ', 'ma'], ['ミ', 'mi'], ['ム', 'mu'], ['メ', 'me'], ['モ', 'mo'],
        ['ヤ', 'ya'], ['ユ', 'yu'], ['ヨ', 'yo'],
        ['ラ', 'ra'], ['リ', 'ri'], ['ル', 'ru'], ['レ', 're'], ['ロ', 'ro'],
        ['ワ', 'wa'], ['ヲ', 'wo'], ['ン', 'n'],
    ],
};

const root = document.querySelector('[data-kana-script]');
const grid = document.querySelector('#kana-grid');
const result = document.querySelector('#kana-result');
const timer = document.querySelector('#kana-timer');
const finishButton = document.querySelector('#kana-finish');
const retryButton = document.querySelector('#kana-retry');
const resultModal = document.querySelector('#kana-result-modal');
const resultModalContent = document.querySelector('#kana-modal-content');

const state = {
    items: [],
    current: 0,
    startedAt: null,
    finishedAt: null,
    interval: null,
};

const answerAliases = {
    shi: ['shi', 'si'],
    chi: ['chi', 'ti'],
    tsu: ['tsu', 'tu'],
    fu: ['fu', 'hu'],
    ji: ['ji', 'zi'],
    wo: ['wo', 'o'],
};

const shuffleKana = (items) => {
    const copy = [...items];

    for (let index = copy.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(Math.random() * (index + 1));
        [copy[index], copy[swapIndex]] = [copy[swapIndex], copy[index]];
    }

    return copy;
};

const formatTime = (milliseconds) => {
    const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
    const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
    const seconds = String(totalSeconds % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
};

const updateTimer = () => {
    if (!state.startedAt) {
        timer.textContent = '00:00';
        return;
    }

    const end = state.finishedAt ?? Date.now();
    timer.textContent = formatTime(end - state.startedAt);
};

const startTimer = () => {
    if (state.startedAt) return;

    state.startedAt = Date.now();
    state.interval = window.setInterval(updateTimer, 250);
    updateTimer();
};

const stopTimer = () => {
    if (!state.startedAt || state.finishedAt) return;

    state.finishedAt = Date.now();
    window.clearInterval(state.interval);
    updateTimer();
};

const activeInput = () => grid.querySelector(`[data-index="${state.current}"] input`);

const closeResultModal = () => {
    if (!resultModal) return;

    resultModal.hidden = true;
    document.body.classList.remove('kana-modal-open');
};

const moveToCurrent = () => {
    const input = activeInput();

    if (input) {
        input.focus();
        input.scrollIntoView({ block: 'center', inline: 'center' });
    }
};

const renderGrid = () => {
    grid.innerHTML = state.items.map((item, index) => `
        <article class="kana-card ${index === 0 ? 'is-active' : ''}" data-index="${index}">
            <strong>${item.kana}</strong>
            <input autocomplete="off" autocapitalize="none" spellcheck="false" aria-label="Jawaban untuk ${item.kana}">
        </article>
    `).join('');

    window.setTimeout(moveToCurrent, 50);
};

const renderResult = () => {
    stopTimer();

    const clean = state.items.filter((item) => item.done && !item.hadWrong).length;
    const wrong = state.items.filter((item) => item.hadWrong);
    const done = state.items.filter((item) => item.done).length;
    const remaining = state.items.length - done;
    const percent = Math.round((clean / state.items.length) * 100);
    const totalMistakes = wrong.length;

    result.hidden = false;
    result.innerHTML = `
        <div>
            <p class="eyebrow">Hasil kana</p>
            <h2>${percent}% benar bersih</h2>
            <p>${clean}/${state.items.length} huruf dijawab tanpa salah. Waktu: ${timer.textContent}.</p>
        </div>
        <div class="kana-wrong-list">
            <strong>Perlu diulang</strong>
            <p>${wrong.length ? wrong.map((item) => item.kana).join(' ') : 'Tidak ada. Semua bersih.'}</p>
        </div>
    `;

    if (!resultModal || !resultModalContent) return;

    resultModalContent.innerHTML = `
        <p class="eyebrow">Selesai latihan</p>
        <h2 id="kana-modal-title">${remaining ? 'Progress tersimpan' : 'Latihan selesai'}</h2>
        <p class="kana-modal-summary">${clean}/${state.items.length} huruf dijawab bersih dalam ${timer.textContent}.</p>
        <div class="kana-stat-grid">
            <div>
                <span>Akurasi bersih</span>
                <strong>${percent}%</strong>
            </div>
            <div>
                <span>Selesai</span>
                <strong>${done}/${state.items.length}</strong>
            </div>
            <div>
                <span>Sempat salah</span>
                <strong>${totalMistakes}</strong>
            </div>
            <div>
                <span>Waktu</span>
                <strong>${timer.textContent}</strong>
            </div>
        </div>
        <div class="kana-modal-review">
            <strong>Perlu diulang</strong>
            <p>${wrong.length ? wrong.map((item) => item.kana).join(' ') : 'Tidak ada. Semua bersih.'}</p>
        </div>
        <div class="kana-modal-actions">
            <button class="ghost-button" type="button" data-kana-close>Tutup</button>
            <button class="primary-button" type="button" data-kana-retry-modal>Ulang lagi</button>
        </div>
    `;
    resultModal.hidden = false;
    document.body.classList.add('kana-modal-open');
};

const finishIfComplete = () => {
    if (state.items.every((item) => item.done)) {
        renderResult();
        return true;
    }

    return false;
};

const checkAnswer = (card) => {
    const index = Number(card.dataset.index);
    const item = state.items[index];
    const input = card.querySelector('input');
    const answer = input.value.trim().toLowerCase();

    if (item.done) return;

    startTimer();

    const accepted = answerAliases[item.answer] ?? [item.answer];

    if (accepted.includes(answer)) {
        item.done = true;
        card.classList.remove('is-active', 'is-wrong');
        card.classList.add('is-correct');
        input.disabled = true;

        if (finishIfComplete()) return;

        do {
            state.current += 1;
        } while (state.items[state.current]?.done);

        const nextCard = grid.querySelector(`[data-index="${state.current}"]`);
        nextCard?.classList.add('is-active');
        moveToCurrent();
        return;
    }

    item.hadWrong = true;
    card.classList.add('is-wrong');
    card.classList.add('is-active');
    input.select();
};

const resetQuiz = () => {
    const script = root.dataset.kanaScript;
    const selected = kanaSets[script] ?? kanaSets.hiragana;

    window.clearInterval(state.interval);
    state.items = shuffleKana(selected).map(([kana, answer]) => ({
        kana,
        answer,
        done: false,
        hadWrong: false,
    }));
    state.current = 0;
    state.startedAt = null;
    state.finishedAt = null;
    state.interval = null;
    result.hidden = true;
    result.innerHTML = '';
    closeResultModal();
    updateTimer();
    renderGrid();
};

grid.addEventListener('input', (event) => {
    if (event.target.matches('input')) {
        startTimer();
    }
});

grid.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || !event.target.matches('input')) {
        return;
    }

    event.preventDefault();
    checkAnswer(event.target.closest('.kana-card'));
});

finishButton.addEventListener('click', renderResult);
retryButton.addEventListener('click', resetQuiz);

resultModal?.addEventListener('click', (event) => {
    if (event.target.matches('[data-kana-close]')) {
        closeResultModal();
    }

    if (event.target.matches('[data-kana-retry-modal]')) {
        resetQuiz();
    }
});

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && resultModal && !resultModal.hidden) {
        closeResultModal();
    }
});

resetQuiz();
