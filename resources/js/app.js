const state = {
    words: Array.isArray(window.nihongoInitialKotoba) ? [...window.nihongoInitialKotoba] : [],
    mode: 'jp-id',
    count: 'all',
    quiz: [],
    currentOptions: [],
    current: 0,
    answers: [],
    locked: false,
};

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const wordList = document.querySelector('#word-list');
const quizBoard = document.querySelector('#quiz-board');
const form = document.querySelector('#kotoba-form');
const formNote = document.querySelector('#form-note');

const normalize = (value) => String(value ?? '').trim();

const escapeHtml = (value) => normalize(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const shuffle = (items) => {
    const copy = [...items];

    for (let index = copy.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(Math.random() * (index + 1));
        [copy[index], copy[swapIndex]] = [copy[swapIndex], copy[index]];
    }

    return copy;
};

const similarityScore = (source, candidate) => {
    const a = normalize(source).toLowerCase();
    const b = normalize(candidate).toLowerCase();
    let score = 0;

    if (!a || !b) {
        return score;
    }

    if (a[0] === b[0]) score += 4;
    if (a.slice(0, 2) === b.slice(0, 2)) score += 3;
    if (Math.abs(a.length - b.length) <= 2) score += 2;

    for (const char of new Set(a)) {
        if (b.includes(char)) score += 1;
    }

    return score;
};

const answerValue = (word, mode = state.mode) => {
    if (mode === 'jp-id') {
        return word.meaning;
    }

    return word.kana;
};

const promptParts = (word, mode = state.mode) => {
    if (mode === 'jp-id') {
        return {
            label: 'Pilih arti Indonesia',
            main: word.kana,
            sub: [word.romaji, word.kanji].filter(Boolean).join(' / '),
        };
    }

    return {
        label: 'Pilih hiragana / katakana',
        main: word.meaning,
        sub: '',
    };
};

const optionLabel = (word, mode = state.mode) => {
    if (mode === 'jp-id') {
        return word.meaning;
    }

    return [word.kana, word.romaji].filter(Boolean).join(' / ');
};

const makeOptions = (word) => {
    const correct = answerValue(word);
    const source = state.mode === 'jp-id' ? word.meaning : word.kana;
    const candidates = state.words
        .filter((item) => item.id !== word.id && answerValue(item) !== correct)
        .sort((a, b) => similarityScore(source, answerValue(b)) - similarityScore(source, answerValue(a)));

    const chosen = candidates.slice(0, 7);
    const options = shuffle([word, ...shuffle(chosen).slice(0, 3)]);

    return options.map((item) => ({
        id: item.id,
        label: optionLabel(item),
        value: answerValue(item),
        correct: item.id === word.id,
    }));
};

const renderWords = (words = state.words) => {
    if (!wordList) return;

    if (words.length === 0) {
        wordList.innerHTML = '<div class="empty-quiz"><h2>Belum ada kotoba.</h2><p>Tambahkan kata pertama dari panel kiri.</p></div>';
        return;
    }

    wordList.innerHTML = words.map((word) => `
        <article class="word-card ${word.is_default ? '' : 'custom'}">
            <p class="kana">${escapeHtml(word.kana)}</p>
            <p class="romaji">${escapeHtml(word.romaji)}</p>
            <p class="meaning">${escapeHtml(word.meaning)}</p>
            <div class="tag-row">
                ${word.kanji ? `<span>${escapeHtml(word.kanji)}</span>` : ''}
                <span>${word.is_default ? 'default' : 'tambahan'}</span>
            </div>
        </article>
    `).join('');
};

const renderEmptyQuiz = (message = 'Pilih jumlah soal, lalu mulai.') => {
    quizBoard.innerHTML = `
        <div class="empty-quiz">
            <p class="eyebrow">Siap latihan</p>
            <h2>${message}</h2>
            <p>Jawaban dibuat pilihan ganda dari kotoba yang mirip agar latihan tidak terlalu mudah ditebak.</p>
        </div>
    `;
};

const renderQuestion = () => {
    const word = state.quiz[state.current];
    const parts = promptParts(word);
    const options = makeOptions(word);
    state.currentOptions = options;

    state.locked = false;
    quizBoard.innerHTML = `
        <article class="quiz-card">
            <div class="progress-line">
                <span>Soal ${state.current + 1} dari ${state.quiz.length}</span>
                <span>Benar ${state.answers.filter((item) => item.correct).length}</span>
            </div>
            <div class="prompt">
                <small>${escapeHtml(parts.label)}</small>
                <strong>${escapeHtml(parts.main)}</strong>
                ${parts.sub ? `<span>${escapeHtml(parts.sub)}</span>` : ''}
            </div>
            <div class="options">
                ${options.map((option, index) => `
                    <button class="option-button" type="button" data-option-index="${index}" data-correct="${option.correct ? '1' : '0'}">
                        ${escapeHtml(option.label)}
                    </button>
                `).join('')}
            </div>
            <div class="quiz-footer">
                <button class="ghost-button" type="button" id="stop-quiz">Selesai nanti</button>
                <button class="primary-button" type="button" id="next-question" disabled>Lanjut</button>
            </div>
        </article>
    `;
};

const saveResult = async () => {
    const correct = state.answers.filter((answer) => answer.correct).length;

    await fetch('/quiz-results', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            Accept: 'application/json',
        },
        body: JSON.stringify({
            mode: state.mode,
            total_questions: state.quiz.length,
            correct_answers: correct,
            answers: state.answers,
        }),
    });
};

const renderResult = async () => {
    const correct = state.answers.filter((answer) => answer.correct).length;
    const percent = Math.round((correct / state.quiz.length) * 100);

    try {
        await saveResult();
    } catch {
        // The score is still useful locally if the backend request fails.
    }

    quizBoard.innerHTML = `
        <article class="result-card">
            <p class="eyebrow">Hasil latihan</p>
            <h2>${correct} dari ${state.quiz.length} benar</h2>
            <p class="score-number">${percent}%</p>
            <p>${percent >= 80 ? 'Mantap, lanjut pertahankan ritmenya.' : 'Bagus, ulangi lagi beberapa kata yang meleset.'}</p>
            <div class="review-list">
                ${state.answers.map((answer) => `
                    <div class="review-item">
                        <strong class="${answer.correct ? 'ok' : 'no'}">${answer.correct ? 'Benar' : 'Belum tepat'} - ${escapeHtml(answer.prompt)}</strong>
                        <span>Jawabanmu: ${escapeHtml(answer.selected)}</span><br>
                        <span>Kunci: ${escapeHtml(answer.expected)}</span>
                    </div>
                `).join('')}
            </div>
            <button class="primary-button wide" type="button" id="retry-quiz">Latihan lagi</button>
        </article>
    `;
};

const startQuiz = () => {
    if (state.words.length < 2) {
        renderEmptyQuiz('Minimal butuh 2 kotoba.');
        return;
    }

    const requestedCount = state.count === 'all' ? state.words.length : Number(state.count);
    const total = Math.min(requestedCount, state.words.length);

    state.quiz = shuffle(state.words).slice(0, total);
    state.current = 0;
    state.answers = [];
    renderQuestion();
};

document.addEventListener('click', async (event) => {
    const modeButton = event.target.closest('[data-mode]');
    const countButton = event.target.closest('[data-count]');
    const optionButton = event.target.closest('.option-button');

    if (modeButton) {
        state.mode = modeButton.dataset.mode;
        document.querySelectorAll('[data-mode]').forEach((button) => button.classList.toggle('is-active', button === modeButton));
    }

    if (countButton) {
        state.count = countButton.dataset.count;
        document.querySelectorAll('[data-count]').forEach((button) => button.classList.toggle('is-active', button === countButton));
    }

    if (event.target.closest('#shuffle-preview')) {
        renderWords(shuffle(state.words));
    }

    if (event.target.closest('#start-quiz') || event.target.closest('#retry-quiz')) {
        startQuiz();
    }

    if (event.target.closest('#stop-quiz')) {
        renderEmptyQuiz('Sesi dihentikan.');
    }

    if (optionButton && !state.locked) {
        state.locked = true;
        const currentWord = state.quiz[state.current];
        const selectedOption = state.currentOptions[Number(optionButton.dataset.optionIndex)];
        const selected = selectedOption?.value ?? '';
        const expected = answerValue(currentWord);
        const correct = selected === expected;

        document.querySelectorAll('.option-button').forEach((button) => {
            button.disabled = true;
            if (button.dataset.correct === '1') button.classList.add('correct');
        });

        if (!correct) {
            optionButton.classList.add('wrong');
        }

        state.answers.push({
            word_id: currentWord.id,
            prompt: promptParts(currentWord).main,
            selected,
            expected,
            correct,
        });

        document.querySelector('#next-question').disabled = false;
    }

    if (event.target.closest('#next-question')) {
        state.current += 1;

        if (state.current >= state.quiz.length) {
            await renderResult();
            return;
        }

        renderQuestion();
    }
});

form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    formNote.textContent = 'Menyimpan...';

    const payload = Object.fromEntries(new FormData(form).entries());

    try {
        const response = await fetch('/kotoba', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error('Gagal menyimpan kotoba.');
        }

        const kotoba = await response.json();
        state.words = [...state.words, kotoba].sort((a, b) => a.romaji.localeCompare(b.romaji));
        form.reset();
        formNote.textContent = 'Tersimpan. Kotoba sudah masuk daftar latihan.';
        renderWords();
    } catch (error) {
        formNote.textContent = error.message;
    }
});

renderWords();
