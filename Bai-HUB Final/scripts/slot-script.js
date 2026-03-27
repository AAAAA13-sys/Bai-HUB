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

    // Show/Hide reset button: Only show if balance != 100 OR history has entries
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        const isDefault = parseFloat(state.balance) === 100 && (!state.history || state.history.length === 0);
        resetBtn.style.display = isDefault ? 'none' : 'inline-block';
    }

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
            const amount = isWin ? (parseFloat(round.payout) - parseFloat(round.bet)) : parseFloat(round.bet);

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
    placeBtn.innerHTML = '<span>Spinning...</span>';
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

            // Show winning/losing notification
            setTimeout(() => {
                let icon = 'info';
                let title = 'Result';
                if (spinData.win) {
                    icon = 'success';
                    title = 'WINNER!';
                } else {
                    icon = 'error';
                    title = 'No luck';
                }

                Swal.fire({
                    title: title,
                    text: spinData.win
                        ? `Congratulations! You won ${parseFloat(spinData.payout).toFixed(2)} credits!`
                        : 'Better luck next time!',
                    icon: icon,
                    confirmButtonText: spinData.win ? 'Great!' : 'Try Again',
                    confirmButtonColor: spinData.win ? '#4ac47d' : '#e64545',
                    backdrop: `rgba(0,0,0,0.4)`
                });
            }, 300);

            placeBtn.disabled = false;
            placeBtn.innerHTML = 'SPIN';
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
        
        const { isConfirmed: firstConfirm } = await Swal.fire({
            title: 'Reset Game?',
            text: 'This will restore your balance to 100 and clear your game history.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, I want a fresh start',
            confirmButtonColor: '#e64545',
            cancelButtonText: 'Cancel'
        });

        if (firstConfirm) {
            const { isConfirmed: finalConfirm } = await Swal.fire({
                title: 'ARE YOU ABSOLUTELY SURE?',
                text: 'This action cannot be undone. All your progress will be lost!',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'YES, PERMANENT RESET',
                confirmButtonColor: '#ff0000',
                cancelButtonText: 'Wait, go back'
            });

            if (finalConfirm) {
                const res = await apiRequest('reset');
                renderState(res);
                Swal.fire({
                    title: 'System Reset',
                    text: 'Your balance has been restored to 100.00.',
                    icon: 'success',
                    confirmButtonColor: '#4ac47d'
                }).then(() => {
                    window.location.href = 'index.php';
                });
            }
        }
    });

    document.getElementById('rulesIcon').addEventListener('click', () => {
        Swal.fire({
            title: 'Slot Machine Payouts',
            html: `
                <div style="text-align: left; line-height: 1.6;">
                    <p><strong>How to Win:</strong> Get 3 matching symbols in a row!</p>
                    <p><strong>Wild Symbol:</strong> The Star symbol is WILD and substitutes for any other symbol.</p>
                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 0;">
                    <p><strong>3 Grapes:</strong> 1.5x bet (~1 in 10 spins)</p>
                    <p><strong>3 Oranges:</strong> 1.5x bet (~1 in 10 spins)</p>
                    <p><strong>3 Clovers:</strong> 3x bet (~1 in 25 spins)</p>
                    <p><strong>3 Diamonds:</strong> 5x bet (~1 in 150 spins)</p>
                    <p><strong>3 Stars:</strong> 10x bet (~1 in 1000 spins)</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Got it!',
            confirmButtonColor: '#4ac47d'
        });
    });
});
