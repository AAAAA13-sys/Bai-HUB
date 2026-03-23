<?php
session_start();

class Game {
    private $patterns;
    
    public function __construct() {
        $this->patterns = [
            'odd' => ['label' => 'Odd', 'points' => 10, 'multiplier' => 2],
            'even' => ['label' => 'Even', 'points' => 10, 'multiplier' => 2],
            'low' => ['label' => 'Low (2-6)', 'points' => 10, 'multiplier' => 2],
            'high' => ['label' => 'High (7-12)', 'points' => 10, 'multiplier' => 2],
        ];
    }
    
    public function getPatterns() {
        return $this->patterns;
    }
    
    public function matchesPattern($pattern, $value) {
        switch ($pattern) {
            case 'odd':
                return $value % 2 !== 0;
            case 'even':
                return $value % 2 === 0;
            case 'low':
                return $value >= 2 && $value <= 6;
            case 'high':
                return $value >= 7 && $value <= 12;
            default:
                return false;
        }
    }
    
    public function rollDice() {
        $die1 = random_int(1, 6);
        $die2 = random_int(1, 6);
        return [
            'die1' => $die1,
            'die2' => $die2,
            'total' => $die1 + $die2
        ];
    }
    
    public function calculateWin($type, $value, $bet, $rollTotal) {
        $win = false;
        $multiplier = 1;
        
        if ($type === 'number') {
            $win = $rollTotal === (int)$value;
            $multiplier = 10;
        } else {
            $win = $this->matchesPattern($value, $rollTotal);
            $multiplier = $this->patterns[$value]['multiplier'];
        }
        
        $points = 0;
        if ($win) {
            // Keep decimal precision instead of rounding
            $points = $bet * $multiplier;
            // Format to 2 decimal places but keep as float
            $points = round($points, 2);
        }
        
        return [
            'win' => $win,
            'points' => $points,
            'multiplier' => $multiplier
        ];
    }
    
    public function normalizeHistory($history) {
        return array_values(array_slice($history, 0, 10));
    }
    
    public function createRound($type, $value, $bet, $dice, $generated, $win, $points) {
        $label = ($type === 'number') ? "Number $value" : $this->patterns[$value]['label'];
        return [
            'timestamp' => date('c'),
            'pattern' => $label,
            'bet' => number_format($bet, 2), // Format bet with 2 decimals
            'dice' => [$dice['die1'], $dice['die2']],
            'generated' => $generated,
            'win' => $win,
            'points' => number_format($points, 2), // Format points with 2 decimals
        ];
    }
}

$game = new Game();

if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 100.00; // Use float instead of int
    $_SESSION['history'] = [];
    $_SESSION['showWelcome'] = true;
    $_SESSION['pending_rolls'] = [];
    $_SESSION['error'] = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'reset') {
            $_SESSION['score'] = 100.00;
            $_SESSION['history'] = [];
            $_SESSION['showWelcome'] = true;
            $_SESSION['pending_rolls'] = [];
            $_SESSION['error'] = null;
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'score' => 100.00]);
                exit;
            }
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'play') {
            $_SESSION['showWelcome'] = false;
            
            $betType = isset($_POST['bet_type']) ? $_POST['bet_type'] : 'pattern';
            $betValue = isset($_POST['bet_value']) ? $_POST['bet_value'] : 'odd';
            $bet = isset($_POST['bet']) ? (float)$_POST['bet'] : 1;
            
            $currentScore = (float)$_SESSION['score']; // Use float instead of int
            $error = null;
            
            if ($currentScore <= 0) {
                $error = [
                    'message' => 'Game Over! You have 0 credits. Please reset the game to continue playing.',
                    'type' => 'gameover'
                ];
            } elseif ($bet <= 0) {
                $error = [
                    'message' => 'Invalid bet amount. Please enter a stake amount greater than 0.',
                    'type' => 'invalid'
                ];
            } elseif ($bet > $currentScore) {
                $error = [
                    'message' => "Insufficient balance! You have " . number_format($currentScore, 2) . " credits, but you bet " . number_format($bet, 2) . " credits.",
                    'type' => 'insufficient',
                    'current_balance' => $currentScore,
                    'bet_amount' => $bet
                ];
            }
            
            if ($error) {
                $_SESSION['error'] = $error;
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => $error]);
                    exit;
                }
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            $diceRoll = $game->rollDice();
            $result = $game->calculateWin($betType, $betValue, $bet, $diceRoll['total']);
            
            // Proper decimal calculation
            $newScore = $currentScore - $bet;
            if ($result['win']) {
                $newScore += $result['points'];
            }
            $newScore = round($newScore, 2); // Keep 2 decimal places
            
            $round = $game->createRound(
                $betType, 
                $betValue, 
                $bet, 
                $diceRoll, 
                $diceRoll['total'], 
                $result['win'], 
                $result['points']
            );
            
            $_SESSION['pending_rolls'][] = [
                'round' => $round,
                'new_score' => $newScore,
                'display_data' => [
                    'die1' => $diceRoll['die1'],
                    'die2' => $diceRoll['die2'],
                    'total' => $diceRoll['total'],
                    'win' => $result['win'],
                    'points' => $result['points'],
                    'bet_type' => $betType,
                    'bet_value' => $betValue,
                    'bet_amount' => $bet,
                    'pattern_display' => ($betType === 'number') ? "Number $betValue" : $game->getPatterns()[$betValue]['label']
                ]
            ];
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'confirm_roll') {
            $response = ['success' => false, 'new_score' => null];
            
            if (!empty($_SESSION['pending_rolls'])) {
                $pending = array_shift($_SESSION['pending_rolls']);
                $round = $pending['round'];
                $newScore = $pending['new_score'];
                
                $history = $_SESSION['history'];
                array_unshift($history, $round);
                $history = $game->normalizeHistory($history);
                $_SESSION['history'] = $history;
                $_SESSION['score'] = $newScore;
                
                $response = [
                    'success' => true,
                    'new_score' => $newScore
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}

$score = number_format($_SESSION['score'], 2);
$history = $_SESSION['history'];
$showWelcome = $_SESSION['showWelcome'];
$pendingRolls = $_SESSION['pending_rolls'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;

if ($error) {
    unset($_SESSION['error']);
}

$displayRoll = !empty($pendingRolls) ? end($pendingRolls)['display_data'] : null;

$diceImages = [
    1 => 'img/dice-six-faces-one.svg',
    2 => 'img/dice-six-faces-two.svg',
    3 => 'img/dice-six-faces-three.svg',
    4 => 'img/dice-six-faces-four.svg',
    5 => 'img/dice-six-faces-five.svg',
    6 => 'img/dice-six-faces-six.svg',
];

$die1 = $displayRoll ? $displayRoll['die1'] : 1;
$die2 = $displayRoll ? $displayRoll['die2'] : 1;
$total = $displayRoll ? $displayRoll['total'] : null;
$win = $displayRoll ? $displayRoll['win'] : null;
$points = $displayRoll ? $displayRoll['points'] : null;
$betAmount = $displayRoll ? $displayRoll['bet_amount'] : null;
$betType = $displayRoll ? $displayRoll['bet_type'] : 'pattern';
$betValue = $displayRoll ? $displayRoll['bet_value'] : 'odd';
$patternDisplay = $displayRoll ? $displayRoll['pattern_display'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dice Betting Game</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="game-header">
    <a href="index.php" class="home-btn">🏠 Home</a>
    <h1 class="game-title">🎲 DICE BETTING GAME 🎲</h1>
    <div class="score">Score: <strong id="scoreValue"><?php echo $score; ?></strong></div>
</div>

    <main class="container">
        <?php if ($showWelcome && !$displayRoll && !$error): ?>
            <div class="message message-welcome">
                Welcome to Dice Betting Game! Place your bet and roll the dice.
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <div class="left-side">
                <div class="dice-section">
                    <div class="section-header">
                        <h2>Dice wheel</h2>
                        <div class="score">Score: <strong id="scoreValue"><?php echo $score; ?></strong></div>
                    </div>
                    <div class="dice-container" id="diceContainer">
                        <img id="die1" class="die" src="<?php echo $diceImages[$die1]; ?>" alt="Die" data-final="<?php echo $die1; ?>" />
                        <img id="die2" class="die" src="<?php echo $diceImages[$die2]; ?>" alt="Die" data-final="<?php echo $die2; ?>" />
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo $displayRoll ? htmlspecialchars($patternDisplay) . ' (' . number_format($betAmount, 2) . ' credits)' : '-'; ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Roll total:</span>
                            <span class="result-value" id="rollTotal" data-final="<?php echo $total !== null ? $total : '0'; ?>"><?php echo $total !== null ? $total : '-'; ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText" data-win="<?php echo $win === true ? 'win' : ($win === false ? 'lose' : ''); ?>" data-points="<?php echo $points !== null ? $points : 0; ?>">
                                <?php if ($win === true): ?>
                                    WIN (+<?php echo number_format($points, 2); ?>)
                                <?php elseif ($win === false): ?>
                                    LOSE
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
                            <h2>Betting settings</h2>
                        </div>
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">Pattern bets <span class="multiplier">2x</span></div>
                                <div class="pattern-grid">
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="odd" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'odd') ? 'checked' : ''; ?> />
                                        <span>Odd</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="even" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'even') ? 'checked' : ''; ?> />
                                        <span>Even</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="low" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'low') ? 'checked' : ''; ?> />
                                        <span>Low (2-6)</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="high" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'high') ? 'checked' : ''; ?> />
                                        <span>High (7-12)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="bet-group">
                                <div class="group-title">Exact number <span class="multiplier">10x</span></div>
                                <div class="number-grid">
                                    <?php for ($i = 2; $i <= 12; $i++): ?>
                                        <button type="button" class="number-btn <?php echo ($displayRoll && $betType === 'number' && $betValue == $i) ? 'active' : ''; ?>" data-number="<?php echo $i; ?>"><?php echo $i; ?></button>
                                    <?php endfor; ?>
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
                                <input id="betStake" type="number" class="stake-input" step="0.01"/>
                            </div>

                            <div class="action-group">
                                <button id="placeBetBtn" class="btn-primary">Roll Dice</button>
                                <button id="resetBtn" class="btn-danger">Reset Game</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Roll history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list" id="historyList">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No rounds yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($history as $round): ?>
                                    <li class="history-item">
                                        <div class="history-bet">
                                            <strong><?php echo htmlspecialchars($round['pattern']); ?></strong> x<?php echo $round['bet']; ?>
                                        </div>
                                        <div class="history-result <?php echo $round['win'] ? 'win' : 'lose'; ?>">
                                            <?php echo $round['win'] ? 'WIN' : 'LOSE'; ?> +<?php echo $round['points']; ?>
                                        </div>
                                        <div class="history-dice">
                                            <?php echo $round['dice'][0]; ?> + <?php echo $round['dice'][1]; ?> = <?php echo $round['generated']; ?>
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

    <form id="gameForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="formAction" value="play" />
        <input type="hidden" name="bet_type" id="formBetType" value="pattern" />
        <input type="hidden" name="bet_value" id="formBetValue" value="odd" />
        <input type="hidden" name="bet" id="formBet" value="20.00" />
    </form>
    
    <script>
        window.shouldAnimate = <?php echo $displayRoll ? 'true' : 'false'; ?>;
        window.rollData = <?php echo json_encode($displayRoll); ?>;
        window.currentScore = <?php echo (float)$_SESSION['score']; ?>;
        window.hasPendingRolls = <?php echo !empty($pendingRolls) ? 'true' : 'false'; ?>;
        window.error = <?php echo json_encode($error); ?>;
    </script>

    <script src="script.js"></script>
</body>
</html>