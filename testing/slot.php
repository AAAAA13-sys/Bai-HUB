<?php
session_start();

class SlotMachine {
    public const SYMBOLS = [
        'grapes' => ['name' => 'grapes.svg', 'multiplier' => 1.5, 'type' => 'fruit'],
        'orange' => ['name' => 'orange.svg', 'multiplier' => 1.5, 'type' => 'fruit'],
        'clover' => ['name' => 'clover.svg', 'multiplier' => 3, 'type' => 'special'],
        'diamond' => ['name' => 'cut-diamond.svg', 'multiplier' => 5, 'type' => 'jackpot'],
        'star' => ['name' => 'star.svg', 'multiplier' => 0, 'type' => 'wild']
    ];
    
    private array $reels = [];
    
    public function __construct() {
        $this->initializeReels();
    }
    
    private function initializeReels(): void {
        $symbolPool = [];
        foreach (self::SYMBOLS as $key => $symbol) {
            $weight = match($symbol['type']) {
                'jackpot' => 1,
                'wild' => 2,
                'special' => 3,
                default => 5
            };
            for ($i = 0; $i < $weight; $i++) {
                $symbolPool[] = $key;
            }
        }
        $this->reels = [$symbolPool, $symbolPool, $symbolPool];
    }
    
    public function spin(): array {
        $result = [];
        foreach ($this->reels as $reel) {
            $randomIndex = array_rand($reel);
            $result[] = $reel[$randomIndex];
        }
        return $result;
    }
    
    public function calculateWin(array $result, float $bet): array {
        $wild = 'star';
        $target = null;
        
        foreach ($result as $sym) {
            if ($sym !== $wild) {
                $target = $sym;
                break;
            }
        }
        
        if ($target === null) {
            return [
                'win' => true,
                'multiplier' => 10,
                'payout' => $bet * 10,
                'winningSymbol' => $wild,
                'result' => $result
            ];
        }
        
        $allMatch = true;
        foreach ($result as $sym) {
            if ($sym !== $target && $sym !== $wild) {
                $allMatch = false;
                break;
            }
        }
        
        if ($allMatch) {
            $multiplier = self::SYMBOLS[$target]['multiplier'];
            return [
                'win' => true,
                'multiplier' => $multiplier,
                'payout' => $bet * $multiplier,
                'winningSymbol' => $target,
                'result' => $result
            ];
        }
        
        return [
            'win' => false,
            'multiplier' => 0,
            'payout' => 0,
            'winningSymbol' => null,
            'result' => $result
        ];
    }
    
    public function getSymbolImage(string $symbolKey): string {
        return 'img/' . self::SYMBOLS[$symbolKey]['name'];
    }
}

// Initialize session variables
if (!isset($_SESSION['slot_score'])) {
    $_SESSION['slot_score'] = 100.00;
}
if (!isset($_SESSION['slot_history'])) {
    $_SESSION['slot_history'] = [];
}
if (!isset($_SESSION['slot_show_welcome'])) {
    $_SESSION['slot_show_welcome'] = true;
}

// Handle POST requests
$error = null;
$message = null;
$currentBet = null;
$animateEnd = null;
$finalResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'reset') {
        $_SESSION['slot_score'] = 100.00;
        $_SESSION['slot_history'] = [];
        $_SESSION['slot_show_welcome'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if ($action === 'clear_board') {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if ($action === 'spin') {
        $bet = (float)($_POST['bet'] ?? 0);
        $currentBet = $bet;
        $score = $_SESSION['slot_score'];
        
        if ($bet <= 0) {
            $error = 'Invalid bet amount. Please enter a stake amount greater than 0.';
        } elseif ($bet > $score) {
            $error = "Insufficient balance! You have " . number_format($score, 2) . " credits.";
        } else {
            $_SESSION['slot_show_welcome'] = false;
            
            $slot = new SlotMachine();
            $result = $slot->spin();
            $winResult = $slot->calculateWin($result, $bet);
            
            $newScore = $score - $bet;
            if ($winResult['win']) {
                $newScore += $winResult['payout'];
                $message = "WIN! You won " . number_format($winResult['payout'], 2) . " credits! (" . $winResult['multiplier'] . "x multiplier)";
            } else {
                $message = "LOSE! Better luck next time!";
            }
            
            $_SESSION['slot_score'] = $newScore;
            
            $historyEntry = [
                'bet' => $bet,
                'win' => $winResult['win'],
                'payout' => $winResult['payout'],
                'multiplier' => $winResult['multiplier'],
                'symbols' => $result,
                'message' => $message
            ];
            array_unshift($_SESSION['slot_history'], $historyEntry);
            $_SESSION['slot_history'] = array_slice($_SESSION['slot_history'], 0, 10);
            
            $_SESSION['slot_animate_end'] = [
                'reels' => array_map(function($symbol) use ($slot) {
                    return [
                        'symbol' => $symbol,
                        'image' => $slot->getSymbolImage($symbol)
                    ];
                }, $result),
                'win' => $winResult['win'],
                'winningSymbol' => $winResult['winningSymbol'],
                'message' => $message,
                'payout' => $winResult['payout']
            ];
            
            // Store final result to display immediately after animation
            $finalResult = [
                'reels' => array_map(function($symbol) use ($slot) {
                    return [
                        'symbol' => $symbol,
                        'image' => $slot->getSymbolImage($symbol)
                    ];
                }, $result),
                'win' => $winResult['win'],
                'winningSymbol' => $winResult['winningSymbol']
            ];
        }
    }
}

// Prepare display data
$score = $_SESSION['slot_score'];
$history = $_SESSION['slot_history'];
$slot = new SlotMachine();

$animateEnd = $_SESSION['slot_animate_end'] ?? null;
$displayScore = $score;

if ($animateEnd) {
    unset($_SESSION['slot_animate_end']);
    $message = null;
    
    if (!empty($history)) {
        $lastRound = $history[0];
        $displayScore = $score + $lastRound['bet'] - ($lastRound['win'] ? $lastRound['payout'] : 0);
    }
}

// Get the current display reels (either final result or default)
$displayReels = $finalResult ?? ($animateEnd ? $animateEnd['reels'] ?? null : null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Slot Machine - Casino Games</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes rollDown {
            0% { transform: translateY(-70%); opacity: 0; }
            50% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(70%); opacity: 0; }
        }
        
        .rolling-img {
            animation: rollDown 0.15s linear infinite;
        }
        
        .slot-reels-container {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 24px !important;
            flex-wrap: nowrap !important;
        }
        
        .slot-reel {
            width: 100px !important;
            height: 100px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(0, 0, 0, 0.35) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 16px !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4) !important;
            overflow: hidden !important;
            transition: all 0.3s ease;
        }
        
        .slot-reel.winning {
            animation: slotWin 0.5s ease-in-out 3;
            border-color: #4ac47d;
            box-shadow: 0 0 20px rgba(74, 196, 125, 0.5);
        }
        
        @keyframes slotWin {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .slot-symbol {
            width: 80px !important;
            height: 80px !important;
            object-fit: contain !important;
            display: block !important;
        }
        
        .result-value.win {
            color: #4ac47d;
        }
        
        .result-value.lose {
            color: #e64545;
        }
        
        @media (max-width: 800px) {
            .slot-reels-container { gap: 16px !important; }
            .slot-reel { width: 70px !important; height: 70px !important; }
            .slot-symbol { width: 55px !important; height: 55px !important; }
        }
        
        @media (max-width: 480px) {
            .slot-reels-container { gap: 12px !important; }
            .slot-reel { width: 55px !important; height: 55px !important; }
            .slot-symbol { width: 45px !important; height: 45px !important; }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/house.svg" alt="Home" />
    </a>
    
    <main class="container">
        <?php if ($_SESSION['slot_show_welcome'] && !$animateEnd && !$error): ?>
            <div class="message message-welcome">
                Welcome to the Slot Machine! Place a bet and spin to win.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <div class="left-side">
                <div class="slot-display-section">
                    <div class="section-header">
                        <h2>Slot Reels</h2>
                        <div class="score">
                            Credits: <strong id="scoreValue" data-final-score="<?php echo number_format($score, 2); ?>"><?php echo number_format($displayScore, 2); ?></strong>
                        </div>
                    </div>
                    
                    <div class="slot-machine-display">
                        <div class="slot-reels-container" id="slotReelsContainer">
                            <?php if ($displayReels): ?>
                                <?php foreach ($displayReels as $index => $reelData): ?>
                                    <div class="slot-reel" data-symbol="<?php echo htmlspecialchars($reelData['symbol']); ?>" data-final-image="<?php echo htmlspecialchars($reelData['image']); ?>">
                                        <img src="<?php echo htmlspecialchars($reelData['image']); ?>" 
                                             class="slot-symbol" 
                                             alt="<?php echo htmlspecialchars($reelData['symbol']); ?>" />
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="slot-reel" data-symbol="grapes" data-final-image="img/grapes.svg">
                                    <img src="img/grapes.svg" class="slot-symbol" alt="grapes" />
                                </div>
                                <div class="slot-reel" data-symbol="orange" data-final-image="img/orange.svg">
                                    <img src="img/orange.svg" class="slot-symbol" alt="orange" />
                                </div>
                                <div class="slot-reel" data-symbol="clover" data-final-image="img/clover.svg">
                                    <img src="img/clover.svg" class="slot-symbol" alt="clover" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet">
                                <?php echo $currentBet ? number_format($currentBet, 2) . ' credits' : '-'; ?>
                            </span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">
                                <?php if ($message): ?>
                                    <?php echo htmlspecialchars($message); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bottom-section">
                    <div class="betting-settings">
                        <div class="section-header">
                            <h2>Payout Rules</h2>
                        </div>
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">Winning Combinations</div>
                                <div class="payout-table">
                                    <div class="payout-row" style="margin-bottom: 8px;">
                                        <em>All wins require exactly 3 matching symbols.<br>The Star symbol is WILD</em>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>Grapes x3</span>
                                        </div>
                                        <span class="payout-multiplier">1.5x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>Orange x3</span>
                                        </div>
                                        <span class="payout-multiplier">1.5x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>Clover x3</span>
                                        </div>
                                        <span class="payout-multiplier">3x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>Diamond x3</span>
                                        </div>
                                        <span class="payout-multiplier">5x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>Star x3</span>
                                        </div>
                                        <span class="payout-multiplier">10x bet</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & Actions</h2>
                        </div>
                        <div class="actions-content">
                            <div class="stake-group">
                                <div class="group-title">Stake Amount</div>
                                <div class="quick-stakes">
                                    <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                    <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                    <button type="button" class="quick-stake" data-multiplier="1">All In</button>
                                </div>
                                <form method="POST" id="spinForm">
                                    <input type="number" name="bet" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                    <input type="hidden" name="action" value="spin" />
                                    <button type="submit" class="btn-primary" style="margin-top: 16px;">SPIN</button>
                                </form>
                            </div>
                            
                            <form method="POST" id="resetForm" style="width: 100%;">
                                <input type="hidden" name="action" value="reset" />
                                <button type="button" id="resetBtn" class="btn-danger">Reset Game</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Spin History</h2>
                        <span class="badge">Last 10 Rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No spins yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($history as $index => $round): ?>
                                    <li class="history-item" <?php echo ($animateEnd && $index === 0) ? 'id="pendingHistoryItem" style="display: none;"' : ''; ?>>
                                        <div class="history-bet">
                                            Bet: <?php echo number_format($round['bet'], 2); ?> credits
                                        </div>
                                        <div class="history-result <?php echo $round['win'] ? 'win' : 'lose'; ?>">
                                            <?php echo $round['win'] ? 'WIN +' . number_format($round['payout'], 2) : 'LOSE -' . number_format($round['bet'], 2); ?>
                                        </div>
                                        <div class="history-dice">
                                            <?php foreach ($round['symbols'] as $sym): ?>
                                                <img src="<?php echo $slot->getSymbolImage($sym); ?>" 
                                                     style="width: 24px; height: 24px; margin: 0 2px; vertical-align: middle;" 
                                                     alt="<?php echo htmlspecialchars($sym); ?>" />
                                            <?php endforeach; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickStakes = document.querySelectorAll('.quick-stake');
            const betAmount = document.getElementById('betAmount');
            const scoreValueEl = document.getElementById('scoreValue');
            const trueFinalScore = parseFloat(scoreValueEl.getAttribute('data-final-score')) || parseFloat(scoreValueEl.textContent);
            
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
                        document.getElementById('resetForm').submit();
                    }
                });
            }
            
            const resetBtn = document.getElementById('resetBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
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
                            document.getElementById('resetForm').submit();
                        }
                    });
                });
            }
            
            const spinForm = document.getElementById('spinForm');
            if (spinForm) {
                spinForm.addEventListener('submit', function(e) {
                    if (trueFinalScore <= 0) {
                        e.preventDefault();
                        showGameOverNotification();
                        return;
                    }
                    
                    const bet = parseFloat(betAmount.value);
                    if (isNaN(bet) || bet <= 0) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Invalid Bet',
                            text: 'Please enter a valid bet amount greater than 0.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (bet > trueFinalScore) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Insufficient Balance',
                            html: `You have <strong>${trueFinalScore.toFixed(2)}</strong> credits, but you bet <strong>${bet.toFixed(2)}</strong> credits.`,
                            icon: 'error',
                            confirmButtonText: 'Adjust Bet',
                            showCancelButton: true,
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                betAmount.value = trueFinalScore.toFixed(2);
                            }
                        });
                        return;
                    }
                    
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.innerHTML = '<span style="opacity:0.7">Spinning...</span>';
                    }
                });
            }
            
            const animateEndData = <?php echo json_encode($animateEnd); ?>;
            if (animateEndData && !<?php echo json_encode($error); ?>) {
                const container = document.getElementById('slotReelsContainer');
                const reelsElements = document.querySelectorAll('.slot-reel');
                const images = document.querySelectorAll('.slot-symbol');
                const resultTextEl = document.getElementById('resultText');
                
                // Store the final images that will be set after animation
                const finalImages = animateEndData.reels.map(reel => reel.image);
                const finalSymbols = animateEndData.reels.map(reel => reel.symbol);
                
                if (resultTextEl) {
                    resultTextEl.innerText = 'Rolling...';
                }
                
                images.forEach(img => img.classList.add('rolling-img'));
                
                const symbolImages = [
                    'img/grapes.svg', 
                    'img/orange.svg', 
                    'img/clover.svg', 
                    'img/cut-diamond.svg', 
                    'img/star.svg'
                ];
                
                let rolls = 0;
                const rollMax = 15;
                
                const rollInterval = setInterval(() => {
                    images.forEach(img => {
                        const randomIndex = Math.floor(Math.random() * symbolImages.length);
                        img.src = symbolImages[randomIndex];
                    });
                    
                    rolls++;
                    if (rolls >= rollMax) {
                        clearInterval(rollInterval);
                        
                        // Set final images without any refresh or flash
                        images.forEach((img, idx) => {
                            img.classList.remove('rolling-img');
                            img.src = finalImages[idx];
                            
                            if (animateEndData.win && reelsElements[idx]) {
                                const isWinningSymbol = animateEndData.winningSymbol === finalSymbols[idx] ||
                                                      finalSymbols[idx] === 'star';
                                if (isWinningSymbol) {
                                    reelsElements[idx].classList.add('winning');
                                }
                            }
                        });
                        
                        if (resultTextEl) {
                            if (animateEndData.win) {
                                resultTextEl.innerHTML = `<span class="win">${animateEndData.message}</span>`;
                            } else {
                                resultTextEl.innerHTML = `<span class="lose">${animateEndData.message}</span>`;
                            }
                        }
                        
                        const pendingItem = document.getElementById('pendingHistoryItem');
                        if (pendingItem) pendingItem.style.display = 'flex';
                        
                        const finalScore = scoreValueEl.getAttribute('data-final-score');
                        if (finalScore) {
                            scoreValueEl.innerText = finalScore;
                        }
                        
                        scoreValueEl.style.color = animateEndData.win ? '#4ac47d' : '#e64545';
                        setTimeout(() => {
                            if (scoreValueEl) scoreValueEl.style.color = '';
                        }, 1000);
                        
                        setTimeout(() => {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            const actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'clear_board';
                            form.appendChild(actionInput);
                            document.body.appendChild(form);
                            form.submit();
                        }, 3000);
                    }
                }, 150);
            }
            
            if (quickStakes.length > 0 && betAmount) {
                quickStakes.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                        let newValue;
                        
                        if (multiplier === 1) {
                            newValue = trueFinalScore;
                        } else {
                            newValue = trueFinalScore * multiplier;
                        }
                        
                        newValue = Math.floor(newValue * 100) / 100;
                        if (newValue < 0.01) newValue = 0.01;
                        betAmount.value = newValue.toFixed(2);
                    });
                });
            }
            
            if (!animateEndData && trueFinalScore <= 0) {
                showGameOverNotification();
            }
        });
    </script>
</body>
</html>