async function apiRequest(action, data = {}) {
    const response = await fetch('api/blackjack_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...data })
    });
    return response.json();
}

function renderState(state) {
    const { balance, gameData, history, currentBet } = state;
    document.getElementById('scoreValue').textContent = parseFloat(balance).toFixed(2);

    const displayBetEl = document.getElementById('displayBet');
    if (gameData && state.currentBet) {
        displayBetEl.textContent = parseFloat(state.currentBet).toFixed(2) + ' credits';
    } else {
        displayBetEl.textContent = '-';
    }

    const dealerArea = document.getElementById('dealerCards');
    const playerArea = document.getElementById('playerCards');
    const dealerTotal = document.getElementById('dealerValue');
    const playerTotal = document.getElementById('playerValue');
    const resultText = document.getElementById('resultText');

    dealerArea.innerHTML = '';
    playerArea.innerHTML = '';

    if (gameData) {
        gameData.dealerHand.forEach(card => {
            const el = document.createElement('div');
            el.className = 'game-card';
            el.textContent = card.value + card.suit;
            dealerArea.appendChild(el);
        });
        dealerTotal.textContent = gameData.dealerValue;

        gameData.playerHand.forEach(card => {
            const el = document.createElement('div');
            el.className = 'game-card';
            el.textContent = card.value + card.suit;
            playerArea.appendChild(el);
        });
        playerTotal.textContent = gameData.playerValue;
        resultText.textContent = gameData.message || '-';
    } else {
        dealerArea.innerHTML = '<div class="card-placeholder">Waiting for bet...</div>';
        playerArea.innerHTML = '<div class="card-placeholder">Place a bet to start</div>';
        dealerTotal.textContent = '-';
        playerTotal.textContent = '-';
        resultText.textContent = '-';
    }

    const historyList = document.querySelector('.history-list');
    historyList.innerHTML = '';
    if (!history || history.length === 0) {
        historyList.innerHTML = '<li class="history-item empty"><span>No rounds yet</span></li>';
    } else {
        history.forEach(round => {
            const li = document.createElement('li');
            li.className = 'history-item';

            const isWin = round.status === 'win';
            const isPush = round.status === 'push';
            const sign = isWin ? '+' : (isPush ? '±' : '-');
            let amount = 0;
            
            if (isWin) amount = parseFloat(round.payout) - parseFloat(round.bet);
            else if (isPush) amount = 0;
            else amount = parseFloat(round.bet);

            li.innerHTML = `
                <div class="history-bet">Bet: ${parseFloat(round.bet).toFixed(2)} credits</div>
                <div class="history-result ${round.status}">
                    ${round.status.toUpperCase()} ${sign}${parseFloat(amount).toFixed(2)}
                </div>
                <div class="history-dice">
                    Player: ${round.playerValue} | Dealer: ${round.dealerValue}
                </div>
            `;
            historyList.appendChild(li);
        });
    }

    const bettingForm = document.getElementById('bettingContainer');
    const playingActions = document.getElementById('playingActions');

    if (gameData && !gameData.status) {
        bettingForm.style.display = 'none';
        playingActions.style.display = 'block';

        const doubleBtn = document.getElementById('doubleBtn');
        if (gameData.playerHand.length === 2 && balance >= state.currentBet) {
            doubleBtn.style.display = 'inline-block';
        } else {
            doubleBtn.style.display = 'none';
        }
    } else {
        bettingForm.style.display = 'block';
        playingActions.style.display = 'none';
    }
}

function showResultPopup(gameData) {
    if (gameData && gameData.status) {
        setTimeout(() => {
            let icon = 'info';
            if (gameData.status === 'win') icon = 'success';
            if (gameData.status === 'lose') icon = 'error';
            Swal.fire({
                title: gameData.status.toUpperCase(),
                text: gameData.message,
                icon: icon,
                confirmButtonText: 'Great!',
                confirmButtonColor: '#4ac47d',
                backdrop: `rgba(0,0,0,0.4)`
            });
        }, 500);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    apiRequest('init').then(renderState);

    document.getElementById('betForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const bet = document.getElementById('betAmount').value;
        const res = await apiRequest('place_bet', { bet });
        if (res.error) {
            Swal.fire('Error', res.error, 'error');
        } else {
            renderState(res);
            if (!res.gameActive) {
                showResultPopup(res.gameData);
            }
        }
    });

    document.getElementById('hitBtn').addEventListener('click', async () => {
        const res = await apiRequest('hit');
        renderState(res);
        if (res.gameActive === false) {
            showResultPopup(res.gameData);
        }
    });

    document.getElementById('standBtn').addEventListener('click', async () => {
        const res = await apiRequest('stand');
        renderState(res);
        showResultPopup(res.gameData);
    });

    document.getElementById('doubleBtn').addEventListener('click', async () => {
        const res = await apiRequest('double_down');
        if (res.error) {
            Swal.fire('Error', res.error, 'error');
        } else {
            renderState(res);
            showResultPopup(res.gameData);
        }
    });

    document.getElementById('resetBtn').addEventListener('click', async () => {
        const res = await apiRequest('reset');
        renderState(res);
    });

    const quickStakes = document.querySelectorAll('.quick-stake');
    quickStakes.forEach(btn => {
        btn.addEventListener('click', function () {
            const multiplier = parseFloat(this.getAttribute('data-multiplier'));
            const balance = parseFloat(document.getElementById('scoreValue').textContent);
            let val = balance * multiplier;
            if (val < 0.01) val = 0.01;
            document.getElementById('betAmount').value = val.toFixed(2);
        });
    });

    document.getElementById('rulesIcon').addEventListener('click', () => {
        Swal.fire({
            title: 'Blackjack Rules',
            html: `
                <div style="text-align: left; line-height: 1.6;">
                    <p>🎯 <strong>Objective:</strong> Beat the dealer's hand without going over 21.</p>
                    <p>💎 <strong>Payout:</strong> Blackjack (21 with first 2 cards) pays <strong>2.5x</strong> your bet.</p>
                    <p>🃏 <strong>Dealer Rule:</strong> Dealer must stand on <strong>17</strong> or higher.</p>
                    <p>🔄 <strong>Push:</strong> If you and the dealer have the same total, it's a push and your bet is returned.</p>
                    <p>⚖️ <strong>Odds:</strong> Win Probability is ~42.22% (House Edge ~0.5%).</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Got it!',
            confirmButtonColor: '#4ac47d'
        });
    });
});
