const diceImages = {
    1: 'img/dice-six-faces-one.svg',
    2: 'img/dice-six-faces-two.svg',
    3: 'img/dice-six-faces-three.svg',
    4: 'img/dice-six-faces-four.svg',
    5: 'img/dice-six-faces-five.svg',
    6: 'img/dice-six-faces-six.svg'
};

let currentBetType = 'pattern';
let currentBetValue = 'odd';
let isRolling = false;

async function apiRequest(action, data = {}) {
    const response = await fetch('api/dice_api.php', {
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
        historyList.innerHTML = '<li class="history-item empty"><span>No rounds yet</span></li>';
    } else {
        state.history.forEach(round => {
            const li = document.createElement('li');
            li.className = 'history-item';

            const isWin = round.status === 'win';
            const sign = isWin ? '+' : '-';
            const amount = isWin ? (parseFloat(round.payout) - parseFloat(round.bet)) : parseFloat(round.bet);

            li.innerHTML = `
                <div class="history-bet">
                    <strong>${escapeHtml(round.pattern)}</strong> x${parseFloat(round.bet).toFixed(2)}
                </div>
                <div class="history-result ${round.status}">
                    ${round.status.toUpperCase()} ${sign}${parseFloat(amount).toFixed(2)}
                </div>
                <div class="history-dice">
                    ${round.dice[0]} + ${round.dice[1]} + ${round.dice[2]} = ${round.total}
                </div>
            `;
            historyList.appendChild(li);
        });
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function updateBetDisplay() {
    const displayBet = document.getElementById('displayBet');
    const betStakeEl = document.getElementById('betStake');
    if (!displayBet || !betStakeEl) return;

    const stake = parseFloat(betStakeEl.value || 0).toFixed(2);
    if (currentBetType === 'number') {
        displayBet.textContent = `Number ${currentBetValue} (${stake} credits)`;
    } else {
        const patternNames = {
            'odd': 'Odd',
            'even': 'Even',
            'low': 'Low (3-10)',
            'high': 'High (11-18)'
        };
        displayBet.textContent = `${patternNames[currentBetValue]} (${stake} credits)`;
    }
}

function randomDiceFace() {
    return Math.floor(Math.random() * 6) + 1;
}

function animateDiceRoll(rollData, stateAfter) {
    const die1Img = document.getElementById('die1');
    const die2Img = document.getElementById('die2');
    const die3Img = document.getElementById('die3');
    const rollTotalEl = document.getElementById('rollTotal');
    const resultTextEl = document.getElementById('resultText');
    const diceContainer = document.getElementById('diceContainer');
    const placeBtn = document.getElementById('placeBetBtn');

    placeBtn.disabled = true;
    placeBtn.textContent = '...Rolling...';

    diceContainer.classList.add('rolling');
    isRolling = true;

    let rollCount = 0;
    const maxRolls = 25;

    const rollingInterval = setInterval(() => {
        die1Img.src = diceImages[randomDiceFace()];
        die2Img.src = diceImages[randomDiceFace()];
        die3Img.src = diceImages[randomDiceFace()];
        rollTotalEl.textContent = '...';
        resultTextEl.textContent = 'Rolling...';
        resultTextEl.className = 'result-value';

        rollCount++;
        if (rollCount >= maxRolls) {
            clearInterval(rollingInterval);

            setTimeout(() => {
                diceContainer.classList.remove('rolling');

                die1Img.src = diceImages[rollData.dice[0]];
                die2Img.src = diceImages[rollData.dice[1]];
                die3Img.src = diceImages[rollData.dice[2]];
                rollTotalEl.textContent = rollData.total;

                if (rollData.status === 'win') {
                    resultTextEl.textContent = `WIN (+${parseFloat(rollData.payout).toFixed(2)})`;
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
                    if (rollData.status === 'win') {
                        icon = 'success';
                        title = 'WINNER!';
                    } else {
                        icon = 'error';
                        title = 'Try Again';
                    }

                    Swal.fire({
                        title: title,
                        text: rollData.status === 'win' 
                            ? `Congratulations! You won ${parseFloat(rollData.payout).toFixed(2)} credits!` 
                            : 'Better luck next time!',
                        icon: icon,
                        confirmButtonText: 'Great!',
                        confirmButtonColor: '#4ac47d',
                        backdrop: `rgba(0,0,0,0.4)`
                    });
                }, 300);

                placeBtn.disabled = false;
                placeBtn.textContent = 'Roll Dice';
                isRolling = false;

            }, 500);
        }
    }, 80);
}

document.addEventListener('DOMContentLoaded', () => {
    apiRequest('init').then(renderState);

    const patternRadios = document.querySelectorAll('.pattern-radio');
    const numberBtns = document.querySelectorAll('.number-btn');
    const quickStakes = document.querySelectorAll('.quick-stake');
    const betStake = document.getElementById('betStake');

    patternRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.checked && !isRolling) {
                numberBtns.forEach(b => b.classList.remove('active'));
                currentBetType = 'pattern';
                currentBetValue = this.value;
                updateBetDisplay();
            }
        });
    });

    numberBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            if (!isRolling) {
                numberBtns.forEach(b => b.classList.remove('active'));
                patternRadios.forEach(r => r.checked = false);
                this.classList.add('active');
                currentBetType = 'number';
                currentBetValue = this.getAttribute('data-number');
                updateBetDisplay();
            }
        });
    });

    quickStakes.forEach(btn => {
        btn.addEventListener('click', function () {
            if (!isRolling) {
                const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                const balance = parseFloat(document.getElementById('scoreValue').textContent);
                let val = balance * multiplier;
                if (val < 0.01) val = 0.01;
                betStake.value = val.toFixed(2);
                updateBetDisplay();
            }
        });
    });

    betStake.addEventListener('input', updateBetDisplay);

    document.getElementById('placeBetBtn').addEventListener('click', async () => {
        if (isRolling) return;

        const bet = parseFloat(betStake.value || 0);
        if (bet <= 0) {
            Swal.fire('Invalid Bet', 'Please enter a valid bet amount.', 'warning');
            return;
        }

        const res = await apiRequest('play', {
            bet_type: currentBetType,
            bet_value: currentBetValue,
            bet: bet
        });

        if (res.error) {
            Swal.fire('Error', res.error, 'error');
        } else {
            animateDiceRoll(res.roll, res);
        }
    });

    document.getElementById('resetBtn').addEventListener('click', async () => {
        if (isRolling) return;
        const res = await apiRequest('reset');
        renderState(res);
        Swal.fire('Reset', 'Game has been reset to 100 credits.', 'success');
    });

    document.getElementById('rulesIcon').addEventListener('click', () => {
        Swal.fire({
            title: 'Dice Game Rules',
            html: `
                <div style="text-align: left; line-height: 1.6;">
                    <p>🎲 <strong>How to Play:</strong> Select a bet type and amount, then roll the dice!</p>
                    <p>📈 <strong>Pattern Bets (2x Payout):</strong></p>
                    <ul>
                        <li><strong>Odd/Even:</strong> Bet on the total sum being odd or even.</li>
                        <li><strong>Low (3-10):</strong> Bet on the total sum being between 3 and 10.</li>
                        <li><strong>High (11-18):</strong> Bet on the total sum being between 11 and 18.</li>
                    </ul>
                    <p>🎯 <strong>Exact Number (10x Payout):</strong> Bet on the exact sum of the three dice (3-18).</p>
                    <p>⚖️ <strong>Probabilities:</strong> Pattern bets have a 50% win probability. Exact numbers vary.</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Got it!',
            confirmButtonColor: '#4ac47d'
        });
    });

    updateBetDisplay();
});
