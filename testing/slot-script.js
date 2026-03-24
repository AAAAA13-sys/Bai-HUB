const symbolImagesBase = [
    'img/grapes.svg',
    'img/orange.svg',
    'img/clover.svg',
    'img/cut-diamond.svg',
    'img/star.svg'
];

let isSpinning = false;

async function apiRequest(action, data = {}) {
    const response = await fetch('api/slot_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...data })
    });
    return response.json();
}

function renderState(state) {
    document.getElementById('scoreValue').textContent = parseFloat(state.balance).toFixed(2);

    const historyList = document.getElementById('historyList');
    historyList.innerHTML = '';

    if (!state.history || state.history.length === 0) {
        historyList.innerHTML = '<li class="history-item empty"><span>No spins yet</span></li>';
    } else {
        state.history.forEach(round => {
            const li = document.createElement('li');
            li.className = 'history-item';

            const isWin = round.status === 'win';
            const sign = isWin ? '+' : '-';
            const amount = isWin ? round.payout : round.bet;

            let symbolsHtml = round.symbols.map(s => {
                let imgPath = '';
                if (s === 'grapes') imgPath = 'img/grapes.svg';
                else if (s === 'orange') imgPath = 'img/orange.svg';
                else if (s === 'clover') imgPath = 'img/clover.svg';
                else if (s === 'diamond') imgPath = 'img/cut-diamond.svg';
                else if (s === 'star') imgPath = 'img/star.svg';
                return `<img src="${imgPath}" style="width: 24px; height: 24px; margin: 0 2px; vertical-align: middle;" />`;
            }).join('');

            li.innerHTML = `
                <div class="history-bet">Bet: ${parseFloat(round.bet).toFixed(2)} credits</div>
                <div class="history-result ${round.status}">
                    ${round.status.toUpperCase()} ${sign}${parseFloat(amount).toFixed(2)}
                </div>
                <div class="history-dice">
                    ${symbolsHtml}
                </div>
            `;
            historyList.appendChild(li);
        });
    }
}

function animateSlotSpin(spinData, stateAfter) {
    const reelsElements = document.querySelectorAll('.slot-reel');
    const images = document.querySelectorAll('.slot-symbol');
    const resultTextEl = document.getElementById('resultText');
    const placeBtn = document.getElementById('spinBtn');

    placeBtn.disabled = true;
    placeBtn.innerHTML = '<span>🎰 Spinning...</span>';
    resultTextEl.textContent = 'Rolling...';
    resultTextEl.className = 'result-value';

    images.forEach(img => img.classList.add('rolling-img'));
    reelsElements.forEach(reel => reel.classList.remove('winning'));

    isSpinning = true;
    let rolls = 0;
    const rollMax = 20;

    const rollInterval = setInterval(() => {
        images.forEach(img => {
            img.src = symbolImagesBase[Math.floor(Math.random() * symbolImagesBase.length)];
        });

        rolls++;
        if (rolls >= rollMax) {
            clearInterval(rollInterval);

            images.forEach((img, idx) => {
                img.classList.remove('rolling-img');
                img.src = spinData.reels[idx].image;

                if (spinData.win && (spinData.winningSymbol === spinData.reels[idx].symbol || spinData.reels[idx].symbol === 'star')) {
                    reelsElements[idx].classList.add('winning');
                }
            });

            if (spinData.win) {
                resultTextEl.textContent = `WIN (+${parseFloat(spinData.payout).toFixed(2)})`;
                resultTextEl.classList.add('win');
            } else {
                resultTextEl.textContent = 'LOSE';
                resultTextEl.classList.add('lose');
            }

            renderState(stateAfter);
            placeBtn.disabled = false;
            placeBtn.innerHTML = '🎰 SPIN 🎰';
            isSpinning = false;
        }
    }, 100);
}

document.addEventListener('DOMContentLoaded', () => {
    apiRequest('init').then(renderState);

    const betStake = document.getElementById('betAmount');
    const displayBet = document.getElementById('displayBet');

    betStake.addEventListener('input', () => {
        displayBet.textContent = parseFloat(betStake.value || 0).toFixed(2) + ' credits';
    });

    const quickStakes = document.querySelectorAll('.quick-stake');
    quickStakes.forEach(btn => {
        btn.addEventListener('click', function () {
            if (!isSpinning) {
                const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                const balance = parseFloat(document.getElementById('scoreValue').textContent);
                let val = balance * multiplier;
                if (val < 0.01) val = 0.01;
                betStake.value = val.toFixed(2);
                displayBet.textContent = val.toFixed(2) + ' credits';
            }
        });
    });

    document.getElementById('spinForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isSpinning) return;

        const bet = parseFloat(betStake.value || 0);
        if (bet <= 0) {
            Swal.fire('Invalid Bet', 'Please enter a valid bet amount.', 'warning');
            return;
        }

        const res = await apiRequest('spin', { bet });
        if (res.error) {
            Swal.fire('Error', res.error, 'error');
        } else {
            animateSlotSpin(res.spin, res);
        }
    });

    document.getElementById('resetBtn').addEventListener('click', async () => {
        if (isSpinning) return;
        const res = await apiRequest('reset');
        renderState(res);
        Swal.fire('Reset', 'Game has been reset to 100 credits.', 'success');
    });
});
