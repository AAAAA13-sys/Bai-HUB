<?php
session_start();

class SlotMachine {
    private const SYMBOLS = [
        'grapes' => ['name' => 'grapes.svg', 'multiplier' => 1.2, 'type' => 'fruit'],
        'orange' => ['name' => 'orange.svg', 'multiplier' => 1.2, 'type' => 'fruit'],
        'clover' => ['name' => 'clover.svg', 'multiplier' => 2, 'type' => 'special'],
        'diamond' => ['name' => 'cut-diamond.svg', 'multiplier' => 5, 'type' => 'jackpot']
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
        
        $this->reels = [$symbolPool, $symbolPool, $symbolPool];
    }
    
    public function spin(): array {
        $result = [];
        foreach ($this->reels as $reel) {
            $result[] = $reel[array_rand($reel)];
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
        
        $payout = $win ? $bet * $multiplier : 0;
        
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
}

// Initialize session
if (!isset($_SESSION['slot_score'])) {
    $_SESSION['slot_score'] = 100.00;
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
</head>
<body>
    <a href="index.php" class="home-btn">
        <img src="img/icons/home-button.svg" alt="Home" />
    </a>
    
    <main class="container">
        <div class="game-header">
            <h1 class="game-title">🎰 SLOT MACHINE 🎰</h1>
            <div class="score">Score: <strong id="scoreValue"><?php echo number_format($score, 2); ?></strong></div>
        </div>

        <div class="main-layout">
            <div class="left-side">
                <div class="slot-section">
                    <div class="section-header">
                        <h2>Slot Reels</h2>
                    </div>
                    <div class="slot-container">
                        <?php if ($winResult && isset($winResult['result'])): ?>
                            <?php foreach ($winResult['result'] as $symbol): 
                                $isWinning = $winResult['win'] && $winResult['winningSymbol'] == $symbol;
                            ?>
                                <div class="slot-symbol <?php echo $isWinning ? 'winning' : ''; ?>">
                                    <img src="<?php echo $slot->getSymbolImage($symbol); ?>" alt="<?php echo $symbol; ?>" />
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="slot-symbol">
                                <img src="img/grapes.svg" alt="grapes" />
                            </div>
                            <div class="slot-symbol">
                                <img src="img/orange.svg" alt="orange" />
                            </div>
                            <div class="slot-symbol">
                                <img src="img/clover.svg" alt="clover" />
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo $currentBet ? number_format($currentBet, 2) . ' credits' : '-'; ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">
                                <?php echo $message ?: '-'; ?>
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
                                    <div class="payout-row"><span>🍇 🍇 (2 Grapes)</span><span class="payout-multiplier">0.8x bet</span></div>
                                    <div class="payout-row"><span>🍊 🍊 (2 Oranges)</span><span class="payout-multiplier">0.8x bet</span></div>
                                    <div class="payout-row"><span>🍇 🍇 🍇 (3 Grapes)</span><span class="payout-multiplier">1.2x bet</span></div>
                                    <div class="payout-row"><span>🍊 🍊 🍊 (3 Oranges)</span><span class="payout-multiplier">1.2x bet</span></div>
                                    <div class="payout-row"><span>🍀 🍀 🍀 (3 Clovers)</span><span class="payout-multiplier">2x bet</span></div>
                                    <div class="payout-row"><span>💎 💎 💎 (3 Diamonds)</span><span class="payout-multiplier">5x bet</span></div>
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
                                <form method="POST">
                                    <input type="number" name="bet" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                    <input type="hidden" name="action" value="spin" />
                                    <button type="submit" class="btn-primary" style="margin-top: 16px;">🎰 SPIN 🎰</button>
                                </form>
                            </div>
                            <form method="POST">
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
                                <li class="history-item empty"><span>No spins yet</span></li>
                            <?php else: ?>
                                <?php foreach ($history as $round): ?>
                                    <li class="history-item">
                                        <div class="history-bet">Bet: <?php echo number_format($round['bet'], 2); ?> credits</div>
                                        <div class="history-result <?php echo $round['win'] ? 'win' : 'lose'; ?>">
                                            <?php echo $round['win'] ? 'WIN' : 'LOSE'; ?> +<?php echo number_format($round['payout'], 2); ?>
                                        </div>
                                        <div class="history-dice">
                                            <?php foreach ($round['symbols'] as $sym) {
                                                echo '<img src="' . $slot->getSymbolImage($sym) . '" style="width: 24px; height: 24px; margin: 0 2px; vertical-align: middle;" />';
                                            } ?>
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
            
            quickStakes.forEach(btn => {
                btn.addEventListener('click', function() {
                    const multiplier = parseFloat(this.dataset.multiplier);
                    let newValue = multiplier === 1 ? scoreValue : scoreValue * multiplier;
                    newValue = Math.floor(newValue * 100) / 100;
                    if (newValue < 0.01) newValue = 0.01;
                    if (betAmount) betAmount.value = newValue.toFixed(2);
                });
            });
        });
    </script>
</body>
</html>