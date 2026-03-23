<?php
session_start();

class SlotMachine {
    private const SYMBOLS = [
        'grapes' => ['name' => 'grapes.svg', 'display' => ' ', 'multiplier' => 1.2, 'type' => 'fruit'],
        'orange' => ['name' => 'orange.svg', 'display' => ' ', 'multiplier' => 1.2, 'type' => 'fruit'],
        'clover' => ['name' => 'clover.svg', 'display' => ' ', 'multiplier' => 2, 'type' => 'special'],
        'diamond' => ['name' => 'cut-diamond.svg', 'display' => ' ', 'multiplier' => 5, 'type' => 'jackpot']
    ];
    
    private array $reels = [];
    
    public function __construct() {
        $this->initializeReels();
    }
    
    private function initializeReels(): void {
        $symbolPool = [];
        foreach (self::SYMBOLS as $key => $symbol) {
            $weight = ($symbol['type'] === 'jackpot') ? 1 : (($symbol['type'] === 'special') ? 2 : 5);
            for ($i = 0; $i < $weight; $i++) {
                $symbolPool[] = $key;
            }
        }
        
        $this->reels = [
            $symbolPool,
            $symbolPool,
            $symbolPool
        ];
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
        $symbolCounts = array_count_values($result);
        $win = false;
        $multiplier = 0;
        $winningSymbol = null;
        
        foreach ($symbolCounts as $symbol => $count) {
            if ($count === 3) {
                $symbolData = self::SYMBOLS[$symbol];
                $win = true;
                $multiplier = $symbolData['multiplier'];
                $winningSymbol = $symbol;
                break;
            }
        }
        
        if (!$win) {
            foreach ($symbolCounts as $symbol => $count) {
                if (isset(self::SYMBOLS[$symbol]) && self::SYMBOLS[$symbol]['type'] === 'fruit' && $count === 2) {
                    $win = true;
                    $multiplier = 0.8;
                    $winningSymbol = $symbol;
                    break;
                }
            }
        }
        
        $payout = 0;
        if ($win) {
            $payout = $bet * $multiplier;
        }
        
        return [
            'win' => $win,
            'multiplier' => $multiplier,
            'payout' => $payout,
            'winningSymbol' => $winningSymbol,
            'result' => $result
        ];
    }
    
    public function getSymbolImage(string $symbolKey): string {
        return 'img/' . self::SYMBOLS[$symbolKey]['name'];
    }
    
    public function getSymbolDisplay(string $symbolKey): string {
        return self::SYMBOLS[$symbolKey]['display'];
    }}

// Initialize session variables
if (!isset($_SESSION['slot_score'])) {
    $_SESSION['slot_score'] = 100.00;
}
if (!isset($_SESSION['slot_history'])) {
    $_SESSION['slot_history'] = [];
}

$score = $_SESSION['slot_score'];
$history = $_SESSION['slot_history'];
$result = null;
$winResult = null;
$error = null;
$message = null;
$currentBet = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'reset') {
            $_SESSION['slot_score'] = 100.00;
            $_SESSION['slot_history'] = [];
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'spin') {
            $bet = (float)$_POST['bet'];
            $currentBet = $bet;
            
            if ($bet <= 0) {
                $error = 'Invalid bet amount. Please enter a stake amount greater than 0.';
            } elseif ($bet > $score) {
                $error = "Insufficient balance! You have " . number_format($score, 2) . " credits.";
            } else {
                $slot = new SlotMachine();
                $result = $slot->spin();
                $winResult = $slot->calculateWin($result, $bet);
                
                $newScore = $score - $bet;
                if ($winResult['win']) {
                    $newScore += $winResult['payout'];
                    $message = "🎉 WIN! You won " . number_format($winResult['payout'], 2) . " credits! (" . $winResult['multiplier'] . "x multiplier) 🎉";
                } else {
                    $message = "😢 LOSE! Better luck next time! 😢";
                }
                
                $_SESSION['slot_score'] = $newScore;
                $score = $newScore;
                
                $historyEntry = [
                    'bet' => $bet,
                    'win' => $winResult['win'],
                    'payout' => $winResult['payout'],
                    'multiplier' => $winResult['multiplier'],
                    'result' => $result,
                    'symbols' => $result
                ];
                array_unshift($history, $historyEntry);
                $history = array_slice($history, 0, 10);
                $_SESSION['slot_history'] = $history;
            }
        }
    }
}

$score = $_SESSION['slot_score'];
$history = $_SESSION['slot_history'];
$slot = new SlotMachine();
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
        /* Force horizontal display and proper sizing */
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
        }
        
        .slot-symbol {
            width: 80px !important;
            height: 80px !important;
            object-fit: contain !important;
            display: block !important;
        }
        
        /* Remove any text that might appear */
        .slot-reel span, 
        .slot-reel:before,
        .slot-reel:after {
            display: none !important;
        }
        
        .slot-symbol-text {
            display: none !important;
        }
        
        /* Responsive for smaller screens */
        @media (max-width: 800px) {
            .slot-reels-container {
                gap: 16px !important;
            }
            .slot-reel {
                width: 70px !important;
                height: 70px !important;
            }
            .slot-symbol {
                width: 55px !important;
                height: 55px !important;
            }
        }
        
        @media (max-width: 480px) {
            .slot-reels-container {
                gap: 12px !important;
            }
            .slot-reel {
                width: 55px !important;
                height: 55px !important;
            }
            .slot-symbol {
                width: 45px !important;
                height: 45px !important;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-btn">
        <img src="img/house.svg" alt="Home" /> Home
    </a>
    
    <main class="container">
        <div class="game-header">
            <h1 class="game-title">🎰 SLOT MACHINE 🎰</h1>
            <div class="score">Score: <strong id="scoreValue"><?php echo number_format($score, 2); ?></strong></div>
        </div>

        <div class="main-layout">
            <div class="left-side">
                <!-- Game Display Section -->
                <div class="slot-display-section">
                    <div class="section-header">
                        <h2>Slot Reels</h2>
                    </div>
                    <div class="slot-machine-display">
                        <div class="slot-reels-container">
                            <?php if ($winResult && isset($winResult['result'])): ?>
                                <?php foreach ($winResult['result'] as $index => $symbol): 
                                    $isWinning = $winResult['win'] && $winResult['winningSymbol'] == $symbol;
                                    $imagePath = $slot->getSymbolImage($symbol);
                                ?>
                                    <div class="slot-reel <?php echo $isWinning ? 'winning' : ''; ?>">
                                        <img src="<?php echo $imagePath; ?>" 
                                             class="slot-symbol" 
                                             alt="<?php echo $symbol; ?>" />
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="slot-reel">
                                    <img src="img/grapes.svg" class="slot-symbol" alt="grapes" />
                                </div>
                                <div class="slot-reel">
                                    <img src="img/orange.svg" class="slot-symbol" alt="orange" />
                                </div>
                                <div class="slot-reel">
                                    <img src="img/clover.svg" class="slot-symbol" alt="clover" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo $currentBet ? number_format($currentBet, 2) . ' credits' : (isset($_POST['bet']) ? number_format($_POST['bet'], 2) . ' credits' : '-'); ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">
                                <?php if ($message): ?>
                                    <?php echo $message; ?>
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
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍇 🍇 (2 Grapes)</span>
                                        </div>
                                        <span class="payout-multiplier">0.8x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍊 🍊 (2 Oranges)</span>
                                        </div>
                                        <span class="payout-multiplier">0.8x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍇 🍇 🍇 (3 Grapes)</span>
                                        </div>
                                        <span class="payout-multiplier">1.2x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍊 🍊 🍊 (3 Oranges)</span>
                                        </div>
                                        <span class="payout-multiplier">1.2x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍀 🍀 🍀 (3 Clovers)</span>
                                        </div>
                                        <span class="payout-multiplier">2x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>💎 💎 💎 (3 Diamonds)</span>
                                        </div>
                                        <span class="payout-multiplier">5x bet</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & actions</h2>
                        </div>
                        <div class="actions-content">
                            <div class="stake-group">
                                <div class="group-title">Stake amount</div>
                                <div class="quick-stakes">
                                    <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                    <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                    <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                </div>
                                <form method="POST" id="spinForm">
                                    <input type="number" name="bet" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                    <input type="hidden" name="action" value="spin" />
                                    <button type="submit" class="btn-primary" style="margin-top: 16px;">🎰 SPIN 🎰</button>
                                </form>
                            </div>
                            
                            <form method="POST" style="width: 100%;">
                                <input type="hidden" name="action" value="reset" />
                                <button type="submit" class="btn-danger">Reset Game</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Spin history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No spins yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($history as $round): ?>
                                    <li class="history-item">
                                        <div class="history-bet">
                                            Bet: <?php echo number_format($round['bet'], 2); ?> credits
                                        </div>
                                        <div class="history-result <?php echo $round['win'] ? 'win' : 'lose'; ?>">
                                            <?php echo $round['win'] ? 'WIN' : 'LOSE'; ?> +<?php echo number_format($round['payout'], 2); ?>
                                        </div>
                                        <div class="history-dice">
                                            <?php 
                                            foreach ($round['symbols'] as $sym) {
                                                echo '<img src="' . $slot->getSymbolImage($sym) . '" style="width: 24px; height: 24px; margin: 0 2px; vertical-align: middle;" />';
                                            }
                                            ?>
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
            const scoreValue = parseFloat(document.getElementById('scoreValue').textContent);
            
            if (quickStakes.length > 0 && betAmount) {
                quickStakes.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                        let newValue;
                        
                        if (multiplier === 1) {
                            newValue = scoreValue;
                        } else {
                            newValue = scoreValue * multiplier;
                        }
                        
                        newValue = Math.floor(newValue * 100) / 100;
                        if (newValue < 0.01) newValue = 0.01;
                        betAmount.value = newValue.toFixed(2);
                    });
                });
            }
        });
    </script>
</body>
</html>