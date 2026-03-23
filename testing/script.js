let rollingInterval = null;
let rollCount = 0;
const maxRolls = 20;
let isRolling = false;
let currentBetType = 'pattern';
let currentBetValue = 'odd';

const diceImages = {
    1: 'img/dice-six-faces-one.svg',
    2: 'img/dice-six-faces-two.svg',
    3: 'img/dice-six-faces-three.svg',
    4: 'img/dice-six-faces-four.svg',
    5: 'img/dice-six-faces-five.svg',
    6: 'img/dice-six-faces-six.svg'
};

function randomDiceFace() {
    return Math.floor(Math.random() * 6) + 1;
}

function addToHistory(pattern, bet, dice1, dice2, total, win, points) {
    const historyList = document.getElementById('historyList');
    const emptyItem = historyList.querySelector('.empty');
    
    if (emptyItem) {
        emptyItem.remove();
    }
    
    const historyItem = document.createElement('li');
    historyItem.className = 'history-item';
    
    const formattedBet = parseFloat(bet).toFixed(2);
    const formattedPoints = parseFloat(points).toFixed(2);
    
    historyItem.innerHTML = `
        <div class="history-bet">
            <strong>${escapeHtml(pattern)}</strong> x${formattedBet}
        </div>
        <div class="history-result ${win ? 'win' : 'lose'}">
            ${win ? 'WIN' : 'LOSE'} +${formattedPoints}
        </div>
        <div class="history-dice">
            ${dice1} + ${dice2} = ${total}
        </div>
    `;
    
    historyList.insertBefore(historyItem, historyList.firstChild);
    
    while (historyList.children.length > 10) {
        historyList.removeChild(historyList.lastChild);
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function confirmRollToServer() {
    const formData = new FormData();
    formData.append('action', 'confirm_roll');
    
    return fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const scoreValue = document.getElementById('scoreValue');
            scoreValue.textContent = parseFloat(data.new_score).toFixed(2);
            
            if (data.new_score <= 0) {
                showGameOverNotification();
            }
        }
        return data;
    })
    .catch(error => {
        console.error('Error confirming roll:', error);
        return { success: false };
    });
}

function showErrorNotification(error) {
    if (!error) return;
    
    if (error.type === 'insufficient') {
        Swal.fire({
            title: 'Insufficient Balance',
            html: `You have <strong>${parseFloat(error.current_balance).toFixed(2)}</strong> credits, but you bet <strong>${parseFloat(error.bet_amount).toFixed(2)}</strong> credits.`,
            icon: 'error',
            confirmButtonText: 'Adjust Bet',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const betStake = document.getElementById('betStake');
                betStake.value = parseFloat(error.current_balance).toFixed(2);
                updateBetDisplay();
            }
        });
    } else if (error.type === 'gameover') {
        Swal.fire({
            title: 'Game Over',
            text: error.message,
            icon: 'error',
            confirmButtonText: 'Reset Game',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                resetGame();
            }
        });
    } else if (error.type === 'invalid') {
        Swal.fire({
            title: 'Invalid Bet Amount',
            text: error.message,
            icon: 'warning',
            confirmButtonText: 'OK'
        }).then(() => {
            const betStake = document.getElementById('betStake');
            betStake.focus();
            betStake.value = '1.00';
            updateBetDisplay();
        });
    }
}

function showGameOverNotification() {
    Swal.fire({
        title: 'Game Over',
        text: 'You have 0 credits left. Reset the game to continue playing.',
        icon: 'error',
        confirmButtonText: 'Reset Game',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            resetGame();
        }
    });
}

function animateDiceRoll(finalDice1, finalDice2, finalTotal, finalWin, finalPoints, pattern, betAmount) {
    const die1Img = document.getElementById('die1');
    const die2Img = document.getElementById('die2');
    const rollTotalEl = document.getElementById('rollTotal');
    const resultTextEl = document.getElementById('resultText');
    const diceContainer = document.getElementById('diceContainer');
    
    if (rollingInterval) {
        clearInterval(rollingInterval);
    }
    
    diceContainer.classList.add('rolling');
    isRolling = true;
    
    rollCount = 0;
    rollingInterval = setInterval(() => {
        const random1 = randomDiceFace();
        const random2 = randomDiceFace();
        const randomTotal = random1 + random2;
        
        die1Img.src = diceImages[random1];
        die2Img.src = diceImages[random2];
        rollTotalEl.textContent = randomTotal;
        resultTextEl.textContent = 'Rolling...';
        resultTextEl.classList.remove('win', 'lose');
        
        rollCount++;
        
        if (rollCount >= maxRolls) {
            clearInterval(rollingInterval);
            rollingInterval = null;
            
            setTimeout(() => {
                diceContainer.classList.remove('rolling');
                
                die1Img.src = diceImages[finalDice1];
                die2Img.src = diceImages[finalDice2];
                rollTotalEl.textContent = finalTotal;
                
                const formattedPoints = parseFloat(finalPoints).toFixed(2);
                
                if (finalWin) {
                    resultTextEl.textContent = `WIN (+${formattedPoints})`;
                    resultTextEl.classList.add('win');
                } else {
                    resultTextEl.textContent = 'LOSE';
                    resultTextEl.classList.add('lose');
                }
                
                addToHistory(pattern, betAmount, finalDice1, finalDice2, finalTotal, finalWin, finalPoints);
                
                confirmRollToServer().then(() => {
                    const placeBtn = document.getElementById('placeBetBtn');
                    if (placeBtn) {
                        placeBtn.disabled = false;
                        placeBtn.textContent = 'Roll Dice';
                    }
                    
                    isRolling = false;
                });
            }, 2500);
        }
    }, 100);
}

function updateBetDisplay() {
    const displayBet = document.getElementById('displayBet');
    const stake = parseFloat(document.getElementById('betStake').value).toFixed(2);
    
    if (currentBetType === 'number') {
        displayBet.textContent = `Number ${currentBetValue} (${stake} credits)`;
    } else {
        const patternNames = {
            'odd': 'Odd',
            'even': 'Even',
            'low': 'Low (2-6)',
            'high': 'High (7-12)'
        };
        displayBet.textContent = `${patternNames[currentBetValue]} (${stake} credits)`;
    }
}

function submitBet() {
    if (isRolling) {
        Swal.fire({
            title: 'Wait',
            text: 'The dice are still rolling. Please wait a moment.',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
        return false;
    }
    
    let betAmount = parseFloat(document.getElementById('betStake').value);
    const currentScore = parseFloat(document.getElementById('scoreValue').textContent);
    
    betAmount = Math.round(betAmount * 100) / 100;
    
    if (isNaN(betAmount) || betAmount <= 0) {
        Swal.fire({
            title: 'Invalid Bet Amount',
            text: 'Please enter a stake amount greater than 0.',
            icon: 'warning',
            confirmButtonText: 'OK'
        }).then(() => {
            const betStake = document.getElementById('betStake');
            betStake.focus();
            betStake.value = '1.00';
            updateBetDisplay();
        });
        return false;
    }
    
    if (currentScore <= 0) {
        showGameOverNotification();
        return false;
    }
    
    if (betAmount > currentScore) {
        Swal.fire({
            title: 'Insufficient Balance',
            html: `You have <strong>${currentScore.toFixed(2)}</strong> credits, but you bet <strong>${betAmount.toFixed(2)}</strong> credits.`,
            icon: 'error',
            confirmButtonText: 'Adjust Bet',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const betStake = document.getElementById('betStake');
                betStake.value = currentScore.toFixed(2);
                updateBetDisplay();
            }
        });
        return false;
    }
    
    document.getElementById('formBetType').value = currentBetType;
    document.getElementById('formBetValue').value = currentBetValue;
    document.getElementById('formBet').value = betAmount.toFixed(2);
    document.getElementById('formAction').value = 'play';
    
    const placeBtn = document.getElementById('placeBetBtn');
    if (placeBtn) {
        placeBtn.disabled = true;
        placeBtn.textContent = 'Rolling...';
    }
    
    document.getElementById('gameForm').submit();
    return true;
}

function resetGame() {
    Swal.fire({
        title: 'Reset Game',
        text: 'Your score will be reset to 100 credits and all history will be cleared.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e64545',
        cancelButtonColor: '#4ac47d',
        confirmButtonText: 'Yes, reset it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formAction').value = 'reset';
            document.getElementById('gameForm').submit();
        }
    });
}

function formatStake(input) {
    let value = input.value;
    
    if (value === '' || value === '-') {
        input.value = '';
        return;
    }
    
    let floatValue = parseFloat(value);
    
    if (isNaN(floatValue)) {
        input.value = '';
        return;
    }
    
    if (floatValue < 0) {
        floatValue = 0;
    }
    
    floatValue = Math.round(floatValue * 100) / 100;
    input.value = floatValue.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    const patternRadios = document.querySelectorAll('.pattern-radio');
    const numberBtns = document.querySelectorAll('.number-btn');
    const quickStakes = document.querySelectorAll('.quick-stake');
    const placeBtn = document.getElementById('placeBetBtn');
    const resetBtn = document.getElementById('resetBtn');
    const betStake = document.getElementById('betStake');
    
    if (window.error) {
        showErrorNotification(window.error);
    }
    
    if (window.currentScore <= 0 && !window.hasPendingRolls) {
        showGameOverNotification();
    }
    
    const savedBetType = document.getElementById('formBetType').value;
    const savedBetValue = document.getElementById('formBetValue').value;
    
    if (savedBetType === 'number') {
        currentBetType = 'number';
        currentBetValue = savedBetValue;
        const activeBtn = document.querySelector(`.number-btn[data-number="${savedBetValue}"]`);
        if (activeBtn) activeBtn.classList.add('active');
    } else {
        currentBetType = 'pattern';
        currentBetValue = savedBetValue;
        const activeRadio = document.querySelector(`.pattern-radio[value="${savedBetValue}"]`);
        if (activeRadio) activeRadio.checked = true;
    }
    
    if (!window.shouldAnimate) {
        updateBetDisplay();
    }
    
    patternRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked && !isRolling) {
                numberBtns.forEach(btn => btn.classList.remove('active'));
                currentBetType = 'pattern';
                currentBetValue = this.value;
                updateBetDisplay();
            }
        });
    });
    
    numberBtns.forEach(btn => {
        btn.addEventListener('click', function() {
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
        btn.addEventListener('click', function() {
            if (!isRolling) {
                const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                const maxBet = parseFloat(document.getElementById('scoreValue').textContent);
                let newValue;
                
                if (multiplier === 1) {
                    newValue = maxBet;
                } else {
                    newValue = maxBet * multiplier;
                }
                
                newValue = Math.floor(newValue * 100) / 100;
                
                if (newValue < 0.01) newValue = 0.01;
                betStake.value = newValue.toFixed(2);
                updateBetDisplay();
            }
        });
    });
    
    betStake.addEventListener('blur', function() {
        formatStake(this);
        updateBetDisplay();
    });
    
    betStake.addEventListener('input', function() {
        updateBetDisplay();
    });
    
    placeBtn.addEventListener('click', submitBet);
    resetBtn.addEventListener('click', resetGame);
    
    if (window.shouldAnimate && window.rollData) {
        const data = window.rollData;
        
        animateDiceRoll(
            data.die1, 
            data.die2, 
            data.total, 
            data.win, 
            data.points,
            data.pattern_display,
            data.bet_amount
        );
    }
});