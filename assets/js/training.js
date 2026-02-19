/* training.js – card flip, TTS, answer handling */
document.addEventListener('DOMContentLoaded', () => {
    const scene        = document.getElementById('card-scene');
    if (!scene) return;

    const cardFlip     = document.getElementById('card-flip');
    const frontWord    = document.getElementById('front-word');
    const backWord     = document.getElementById('back-word');
    const btnTtsFront  = document.getElementById('btn-tts-front');
    const btnTtsBack   = document.getElementById('btn-tts-back');
    const btnCorrect   = document.getElementById('btn-correct');
    const btnWrong     = document.getElementById('btn-wrong');
    const btnPause     = document.getElementById('btn-pause');
    const btnEndSet    = document.getElementById('btn-end-set');
    const actionBar    = document.getElementById('action-bar');
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    const counterEl    = document.getElementById('card-counter');
    const resultScreen = document.getElementById('result-screen');
    const trainingWrap = document.getElementById('training-wrap');

    // Data injected by PHP as JSON in the page
    const SET    = window.TRAINING_SET    || [];
    const USER_ID = window.TRAINING_USER_ID || '';
    const LANG_FRONT = window.TRAINING_LANG_FRONT || 'de';

    let index   = 0;
    let correct = 0;
    let wrong   = 0;
    let flipped = false;
    let busy    = false;

    function currentCard() { return SET[index] || null; }

    function updateProgress() {
        const total = SET.length;
        const done  = index;
        const pct   = total ? Math.round((done / total) * 100) : 0;
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressText) progressText.textContent = done + ' / ' + total;
        if (counterEl)    counterEl.textContent     = (done + 1) + ' / ' + total;
    }

    function showCard(card) {
        if (!card) return;
        const frontLang = LANG_FRONT;
        const backLang  = frontLang === 'de' ? 'en' : 'de';
        if (frontWord) frontWord.textContent = card[frontLang] || '';
        if (backWord)  backWord.textContent  = card[backLang]  || '';
        if (cardFlip)  cardFlip.classList.remove('flipped');
        if (actionBar) actionBar.classList.add('hidden');
        flipped = false;
        updateProgress();
    }

    function flipCard() {
        if (busy || !cardFlip || flipped) return;
        cardFlip.classList.add('flipped');
        flipped = true;
        if (actionBar) actionBar.classList.remove('hidden');
        // Auto-TTS back
        const card = currentCard();
        if (card) {
            const lang = LANG_FRONT === 'de' ? 'en-US' : 'de-DE';
            TTS.speak(card[LANG_FRONT === 'de' ? 'en' : 'de'], lang);
        }
    }

    function showMotivation(isCorrect) {
        const messages = isCorrect
            ? ['Super! 🎉', 'Richtig! ✅', 'Toll gemacht! 🌟', 'Weiter so! 💪']
            : ['Nicht schlimm! 💪', 'Versuch\'s nochmal!', 'Fast! 🤔', 'Übung macht den Meister!'];
        const msg = messages[Math.floor(Math.random() * messages.length)];
        const el  = document.createElement('div');
        el.className = 'motivation-toast';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2100);
    }

    async function submitAnswer(isCorrect) {
        if (busy) return;
        busy = true;

        const card = currentCard();
        if (!card) return;

        // Animate card out
        if (cardFlip) {
            cardFlip.classList.add(isCorrect ? 'card-dismiss-correct' : 'card-dismiss-wrong');
        }

        showMotivation(isCorrect);
        if (isCorrect) correct++; else wrong++;

        // POST to server
        try {
            await API.post('/training.php?action=answer', {
                vocab_id: card.id,
                correct:  isCorrect,
                user_id:  USER_ID,
                session:  { correct, wrong, total: index + 1, set_size: SET.length }
            });
        } catch (_) { /* ignore, we continue locally */ }

        await sleep(420);

        index++;
        busy = false;

        if (index >= SET.length) {
            showResults();
        } else {
            if (cardFlip) {
                cardFlip.classList.remove('card-dismiss-correct', 'card-dismiss-wrong', 'flipped');
            }
            showCard(currentCard());
        }
    }

    function showResults() {
        if (trainingWrap) trainingWrap.classList.add('hidden');
        if (resultScreen) {
            resultScreen.classList.remove('hidden');
            const rCorrect = document.getElementById('result-correct');
            const rWrong   = document.getElementById('result-wrong');
            const rTotal   = document.getElementById('result-total');
            if (rCorrect) rCorrect.textContent = correct;
            if (rWrong)   rWrong.textContent   = wrong;
            if (rTotal)   rTotal.textContent   = SET.length;
        }
        API.post('/training.php?action=end_set', { user_id: USER_ID, correct, wrong, total: SET.length })
           .catch(() => {});
    }

    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    // ── Event listeners ──
    if (cardFlip) cardFlip.addEventListener('click', flipCard);

    if (btnTtsFront) btnTtsFront.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = currentCard();
        if (card) TTS.speak(card[LANG_FRONT], LANG_FRONT === 'de' ? 'de-DE' : 'en-US');
    });

    if (btnTtsBack) btnTtsBack.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = currentCard();
        if (card) {
            const lang = LANG_FRONT === 'de' ? 'en' : 'de';
            TTS.speak(card[lang], lang === 'de' ? 'de-DE' : 'en-US');
        }
    });

    if (btnCorrect) btnCorrect.addEventListener('click', () => submitAnswer(true));
    if (btnWrong)   btnWrong.addEventListener('click',   () => submitAnswer(false));

    if (btnPause) btnPause.addEventListener('click', () => {
        API.post('/training.php?action=pause', {
            user_id: USER_ID,
            session: { correct, wrong, index, set_size: SET.length }
        }).catch(() => {});
        window.location.href = '/index.php';
    });

    if (btnEndSet) btnEndSet.addEventListener('click', () => {
        showResults();
    });

    // Init first card
    if (SET.length > 0) {
        showCard(SET[0]);
    } else {
        if (trainingWrap) trainingWrap.innerHTML = '<p class="text-center text-muted py-8">Keine Vokabeln gefunden.</p>';
    }
});
