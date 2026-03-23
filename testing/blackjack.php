<?php
session_start();

class BlackjackGame {
    private $deck;
    public $playerHand;
    public $dealerHand;
    private $gameState;
    
    public function __construct() {
        $this->initializeDeck();
        $this->gameState = 'betting';
    }
    
    private function initializeDeck() {
        $suits = ['♥', '♠', '♦', '♣'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $this->deck = [];
        
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->deck[] = ['value' => $value, 'suit' => $suit];
            }
        }
        
        shuffle($this->deck);
    }
    
    public function getCardValue($card) {
        $value = $card['value'];
        if (in_array($value, ['J', 'Q', 'K'])) {
            return 10;
        }
        if ($value == 'A') {
            return 11;
        }
        return (int)$value;
    }
    
    public function calculateHandValue($hand) {
        $total = 0;
        $aces = 0;
        
        foreach ($hand as $card) {
            $value = $this->getCardValue($card);
            if ($value == 11) {
                $aces++;
            }
            $total += $value;
        }
        
        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }
        
        return $total;
    }
    
    public function drawCard() {
        if (empty($this->deck)) {
            $this->initializeDeck();
        }
        return array_pop($this->deck);
    }
    
    public function startGame() {
        $this->playerHand = [$this->drawCard(), $this->drawCard()];
        $this->dealerHand = [$this->drawCard(), $this->drawCard()];
        $this->gameState = 'playing';
        
        return [
            'playerHand' => $this->playerHand,
            'dealerHand' => [$this->dealerHand[0], ['value' => '?', 'suit' => '?']],
            'playerValue' => $this->calculateHandValue($this->playerHand),
            'dealerValue' => $this->getCardValue($this->dealerHand[0])
        ];
    }
    
    public function hit() {
        $this->playerHand[] = $this->drawCard();
        $playerValue = $this->calculateHandValue($this->playerHand);
        
        if ($playerValue > 21) {
            $this->gameState = 'gameover';
        }
        
        return [
            'playerHand' => $this->playerHand,
            'playerValue' => $playerValue,
            'gameActive' => $playerValue <= 21
        ];
    }
    
    public function stand() {
        $playerValue = $this->calculateHandValue($this->playerHand);
        
        if ($playerValue > 21) {
            return [
                'win' => false,
                'message' => 'Bust! You went over 21.',
                'dealerHand' => $this->dealerHand,
                'dealerValue' => $this->calculateHandValue($this->dealerHand)
            ];
        }
        
        $dealerValue = $this->calculateHandValue($this->dealerHand);
        while ($dealerValue < 17) {
            $this->dealerHand[] = $this->drawCard();
            $dealerValue = $this->calculateHandValue($this->dealerHand);
        }
        
        $win = false;
        $message = '';
        
        if ($dealerValue > 21) {
            $win = true;
            $message = 'Dealer busts! You win!';
        } elseif ($playerValue > $dealerValue) {
            $win = true;
            $message = 'You beat the dealer!';
        } elseif ($playerValue == $dealerValue) {
            $message = 'Push! Your bet is returned.';
        } else {
            $message = 'Dealer wins. Better luck next time!';
        }
        
        $this->gameState = 'gameover';
        
        return [
            'win' => $win,
            'message' => $message,
            'dealerHand' => $this->dealerHand,
            'dealerValue' => $dealerValue,
            'playerValue' => $playerValue
        ];
    }
    
    public function getGameState() {
        return $this->gameState;
    }
}

// Initialize session variables
if (!isset($_SESSION['blackjack_score'])) {
    $_SESSION['blackjack_score'] = 100.00;
}
if (!isset($_SESSION['blackjack_history'])) {
    $_SESSION['blackjack_history'] = [];
}
if (!isset($_SESSION['blackjack_game'])) {
    $_SESSION['blackjack_game'] = null;
}
if (!isset($_SESSION['blackjack_current_bet'])) {
    $_SESSION['blackjack_current_bet'] = null;
}
if (!isset($_SESSION['blackjack_game_data'])) {
    $_SESSION['blackjack_game_data'] = null;
}
if (!isset($_SESSION['blackjack_message'])) {
    $_SESSION['blackjack_message'] = null;
}

$score = $_SESSION['blackjack_score'];
$history = $_SESSION['blackjack_history'];
$game = $_SESSION['blackjack_game'];
$currentBet = $_SESSION['blackjack_current_bet'];
$gameData = $_SESSION['blackjack_game_data'];
$error = null;
$message = $_SESSION['blackjack_message'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'reset') {
            $_SESSION['blackjack_score'] = 100.00;
            $_SESSION['blackjack_history'] = [];
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_current_bet'] = null;
            $_SESSION['blackjack_game_data'] = null;
            $_SESSION['blackjack_message'] = null;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'place_bet') {
            $bet = (float)$_POST['bet'];
            
            if ($bet <= 0) {
                $error = 'Invalid bet amount. Please enter a stake amount greater than 0.';
            } elseif ($bet > $score) {
                $error = "Insufficient balance! You have " . number_format($score, 2) . " credits.";
            } else {
                $_SESSION['blackjack_current_bet'] = $bet;
                $game = new BlackjackGame();
                $_SESSION['blackjack_game'] = $game;
                $gameData = $game->startGame();
                $_SESSION['blackjack_game_data'] = $gameData;
                $_SESSION['blackjack_message'] = null;
                $currentBet = $bet;
            }
        }
        
        if ($_POST['action'] === 'hit' && $game && $game->getGameState() === 'playing') {
            $result = $game->hit();
            $gameData = [
                'playerHand' => $result['playerHand'],
                'playerValue' => $result['playerValue'],
                'dealerHand' => [$game->dealerHand[0], ['value' => '?', 'suit' => '?']],
                'dealerValue' => $game->getCardValue($game->dealerHand[0])
            ];
            $_SESSION['blackjack_game_data'] = $gameData;
            
            if (!$result['gameActive']) {
                $standResult = $game->stand();
                $winAmount = 0;
                if ($standResult['win']) {
                    $winAmount = $currentBet * 2;
                    $message = $standResult['message'] . " You won " . number_format($winAmount, 2) . " credits!";
                } elseif (isset($standResult['message']) && strpos($standResult['message'], 'Push') !== false) {
                    $winAmount = $currentBet;
                    $message = $standResult['message'] . " Your bet of " . number_format($currentBet, 2) . " credits is returned.";
                } else {
                    $message = $standResult['message'];
                }
                
                $newScore = $score - $currentBet + $winAmount;
                $_SESSION['blackjack_score'] = $newScore;
                
                $historyEntry = [
                    'bet' => $currentBet,
                    'win' => $standResult['win'],
                    'payout' => $winAmount,
                    'playerValue' => $gameData['playerValue'],
                    'dealerValue' => $standResult['dealerValue'],
                    'message' => $message
                ];
                array_unshift($history, $historyEntry);
                $history = array_slice($history, 0, 10);
                $_SESSION['blackjack_history'] = $history;
                $_SESSION['blackjack_message'] = $message;
                
                $_SESSION['blackjack_game'] = null;
                $_SESSION['blackjack_current_bet'] = null;
                $_SESSION['blackjack_game_data'] = null;
                $gameData = null;
                $score = $newScore;
            }
        }
        
        if ($_POST['action'] === 'stand' && $game && $game->getGameState() === 'playing') {
            $result = $game->stand();
            $winAmount = 0;
            if ($result['win']) {
                $winAmount = $currentBet * 2;
                $message = $result['message'] . " You won " . number_format($winAmount, 2) . " credits!";
            } elseif (isset($result['message']) && strpos($result['message'], 'Push') !== false) {
                $winAmount = $currentBet;
                $message = $result['message'] . " Your bet of " . number_format($currentBet, 2) . " credits is returned.";
            } else {
                $message = $result['message'];
            }
            
            $newScore = $score - $currentBet + $winAmount;
            $_SESSION['blackjack_score'] = $newScore;
            
            $historyEntry = [
                'bet' => $currentBet,
                'win' => $result['win'],
                'payout' => $winAmount,
                'playerValue' => $gameData['playerValue'],
                'dealerValue' => $result['dealerValue'],
                'message' => $message
            ];
            array_unshift($history, $historyEntry);
            $history = array_slice($history, 0, 10);
            $_SESSION['blackjack_history'] = $history;
            $_SESSION['blackjack_message'] = $message;
            
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_current_bet'] = null;
            $_SESSION['blackjack_game_data'] = null;
            $gameData = null;
            $score = $newScore;
        }
    }
}

$score = $_SESSION['blackjack_score'];
$history = $_SESSION['blackjack_history'];
$game = $_SESSION['blackjack_game'];
$currentBet = $_SESSION['blackjack_current_bet'];
$gameData = $_SESSION['blackjack_game_data'];
$message = $_SESSION['blackjack_message'];

if ($message) {
    $_SESSION['blackjack_message'] = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blackjack - Casino Games</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <main class="container">
        <div class="game-header">
            <a href="index.php" class="home-btn">🏠 Home</a>
            <h1 class="game-title">♠️ BLACKJACK ♥️</h1>
            <div class="score">Score: <strong id="scoreValue"><?php echo number_format($score, 2); ?></strong></div>
        </div>

        <div class="main-layout">
            <div class="left-side">
                <!-- Game Display Section -->
                <div class="game-display-section">
                    <div class="section-header">
                        <h2>Blackjack Table</h2>
                    </div>
                    <div class="blackjack-table">
                        <div class="dealer-area">
                            <div class="area-label">Dealer</div>
                            <div class="cards-area" id="dealerCards">
                                <?php if ($gameData): ?>
                                    <?php foreach ($gameData['dealerHand'] as $card): ?>
                                        <div class="game-card">
                                            <?php echo $card['value'] . $card['suit']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card-placeholder">Waiting for bet...</div>
                                <?php endif; ?>
                            </div>
                            <div class="hand-total">Value: <span id="dealerValue"><?php echo $gameData ? $gameData['dealerValue'] : '-'; ?></span></div>
                        </div>
                        
                        <div class="player-area">
                            <div class="area-label">Player</div>
                            <div class="cards-area" id="playerCards">
                                <?php if ($gameData): ?>
                                    <?php foreach ($gameData['playerHand'] as $card): ?>
                                        <div class="game-card">
                                            <?php echo $card['value'] . $card['suit']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card-placeholder">Place a bet to start</div>
                                <?php endif; ?>
                            </div>
                            <div class="hand-total">Value: <span id="playerValue"><?php echo $gameData ? $gameData['playerValue'] : '-'; ?></span></div>
                        </div>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo $gameData ? number_format($currentBet, 2) . ' credits' : '-'; ?></span>
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
                            <h2>Game Rules</h2>
                        </div>
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">How to Play</div>
                                <div class="rules-list">
                                    <div class="rule-item">🎯 Beat the dealer to 21</div>
                                    <div class="rule-item">💎 Blackjack pays 2x your bet</div>
                                    <div class="rule-item">🃏 Dealer must stand on 17</div>
                                    <div class="rule-item">🔄 Push returns your bet</div>
                                    <div class="rule-item">Ace can be 1 or 11</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & actions</h2>
                        </div>
                        <div class="actions-content">
                            <?php if (!$gameData): ?>
                                <div class="stake-group">
                                    <div class="group-title">Stake amount</div>
                                    <div class="quick-stakes">
                                        <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                        <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                        <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                    </div>
                                    <form method="POST" id="betForm">
                                        <input type="number" name="bet" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                        <input type="hidden" name="action" value="place_bet" />
                                        <button type="submit" class="btn-primary" style="margin-top: 16px;">Place Bet</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="action-group">
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="hit" />
                                        <button type="submit" class="btn-primary">Hit</button>
                                    </form>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="stand" />
                                        <button type="submit" class="btn-primary">Stand</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
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
                        <h2>Game history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No rounds yet</span>
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
                                            Player: <?php echo $round['playerValue']; ?> | Dealer: <?php echo $round['dealerValue']; ?>
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