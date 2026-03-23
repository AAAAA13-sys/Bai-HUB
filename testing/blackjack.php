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
        
        for ($i = 0; $i < 6; $i++) {
            foreach ($suits as $suit) {
                foreach ($values as $value) {
                    $this->deck[] = ['value' => $value, 'suit' => $suit];
                }
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
        
        $playerValue = $this->calculateHandValue($this->playerHand);
        $dealerValue = $this->calculateHandValue($this->dealerHand);

        return [
            'playerHand' => $this->playerHand,
            'dealerHand' => [$this->dealerHand[0], ['value' => '?', 'suit' => '?']],
            'playerValue' => $playerValue,
            'dealerValue' => $this->getCardValue($this->dealerHand[0]),
            'naturalBlackjack' => ($playerValue === 21)
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
        while ($dealerValue < 17 || ($dealerValue == 17 && $this->isSoft17($this->dealerHand))) {
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
    
    private function isSoft17($hand) {
        $total = 0;
        $aces = 0;
        foreach ($hand as $card) {
            $val = $this->getCardValue($card);
            if ($val == 11) $aces++;
            $total += $val;
        }
        return ($total == 17 && $aces > 0);
    }
    
    public function surrender() {
        $this->gameState = 'gameover';
        return [
            'win' => false,
            'surrender' => true,
            'message' => 'You surrendered. Half your bet is returned.',
            'dealerHand' => $this->dealerHand,
            'dealerValue' => $this->calculateHandValue($this->dealerHand)
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
if (!isset($_SESSION['blackjack_showWelcome'])) {
    $_SESSION['blackjack_showWelcome'] = true;
}
if (!isset($_SESSION['blackjack_pending_results'])) {
    $_SESSION['blackjack_pending_results'] = [];
}

$score = $_SESSION['blackjack_score'];
$history = $_SESSION['blackjack_history'];
$game = $_SESSION['blackjack_game'];
$currentBet = $_SESSION['blackjack_current_bet'];
$gameData = $_SESSION['blackjack_game_data'];
$error = isset($_SESSION['blackjack_error']) ? $_SESSION['blackjack_error'] : null;
if ($error) unset($_SESSION['blackjack_error']);
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
            $_SESSION['blackjack_showWelcome'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($_POST['action'] === 'clear_board') {
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_current_bet'] = null;
            $_SESSION['blackjack_game_data'] = null;
            $_SESSION['blackjack_message'] = null;
            $_SESSION['blackjack_animate_end'] = null;
            $_SESSION['blackjack_pending_results'] = [];
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'place_bet') {
            $bet = (float)$_POST['bet'];
            
            if ($bet <= 0) {
                $_SESSION['blackjack_error'] = ['type' => 'invalid', 'message' => 'Invalid bet amount. Please enter a stake amount greater than 0.'];
            } elseif ($bet > $score) {
                $_SESSION['blackjack_error'] = [
                    'type' => 'insufficient', 
                    'current_balance' => $score, 
                    'bet_amount' => $bet
                ];
            } else {
                $_SESSION['blackjack_showWelcome'] = false;
                $_SESSION['blackjack_current_bet'] = $bet;
                $game = new BlackjackGame();
                $_SESSION['blackjack_game'] = $game;
                $gameData = $game->startGame();
                $currentBet = $bet;
                
                if ($gameData['naturalBlackjack']) {
                    $dealerTotal = $game->calculateHandValue($game->dealerHand);
                    $winAmount = 0;
                    if ($dealerTotal === 21) {
                        $winAmount = $currentBet;
                        $message = "Push! Both have Blackjack. Your bet is returned.";
                    } else {
                        $winAmount = $currentBet * 2.5; // Pays 3:2
                        $message = "BLACKJACK! You won " . number_format($winAmount, 2) . " credits!";
                    }
                    
                    $newScore = $score + $winAmount;
                    $_SESSION['blackjack_score'] = $newScore;
                    
                    $historyEntry = [
                        'bet' => $currentBet,
                        'win' => ($dealerTotal === 21) ? false : true,
                        'payout' => $winAmount,
                        'playerValue' => 21,
                        'dealerValue' => $dealerTotal,
                        'message' => $message
                    ];
                    array_unshift($history, $historyEntry);
                    $history = array_slice($history, 0, 10);
                    $_SESSION['blackjack_history'] = $history;
                    $_SESSION['blackjack_message'] = $message;
                    
                    $_SESSION['blackjack_animate_end'] = [
                        'dealerHand' => $game->dealerHand,
                        'dealerValue' => $dealerTotal,
                        'playerHand' => $game->playerHand,
                        'playerValue' => 21,
                        'status' => ($dealerTotal === 21) ? 'push' : 'win',
                        'betAmount' => $currentBet,
                        'message' => $message,
                        'newScore' => $newScore,
                        'oldScore' => $score
                    ];
                    
                    $_SESSION['blackjack_game'] = null;
                    $_SESSION['blackjack_current_bet'] = null;
                    $_SESSION['blackjack_game_data'] = null;
                    $gameData = null;
                    $score = $newScore;
                } else {
                    $_SESSION['blackjack_game_data'] = $gameData;
                    $_SESSION['blackjack_message'] = null;
                }
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
                
                $newScore = $score + $winAmount;
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
                
                $_SESSION['blackjack_animate_end'] = [
                    'dealerHand' => $standResult['dealerHand'],
                    'dealerValue' => $standResult['dealerValue'],
                    'playerHand' => $gameData['playerHand'],
                    'playerValue' => $gameData['playerValue'],
                    'status' => $standResult['win'] ? 'win' : ((isset($standResult['message']) && strpos($standResult['message'], 'Push') !== false) ? 'push' : 'lose'),
                    'betAmount' => $currentBet,
                    'message' => $message,
                    'newScore' => $newScore,
                    'oldScore' => $score
                ];
                
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
            $newScore = $score + $winAmount;
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
            
            $_SESSION['blackjack_animate_end'] = [
                'dealerHand' => $result['dealerHand'],
                'dealerValue' => $result['dealerValue'],
                'playerHand' => $gameData['playerHand'],
                'playerValue' => $gameData['playerValue'],
                'status' => $result['win'] ? 'win' : ((isset($result['message']) && strpos($result['message'], 'Push') !== false) ? 'push' : 'lose'),
                'betAmount' => $currentBet,
                'message' => $message,
                'newScore' => $newScore,
                'oldScore' => $score
            ];
            
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_current_bet'] = null;
            $_SESSION['blackjack_game_data'] = null;
            $gameData = null;
            $score = $newScore;
        }

        if ($_POST['action'] === 'double_down' && $game && $game->getGameState() === 'playing') {
            if ($score >= $currentBet) {
                $_SESSION['blackjack_score'] -= $currentBet;
                $score = $_SESSION['blackjack_score'];
                $currentBet *= 2;
                $_SESSION['blackjack_current_bet'] = $currentBet;
                
                $result = $game->hit();
                
                if ($result['gameActive']) {
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
                    
                    $newScore = $score + $winAmount;
                    $_SESSION['blackjack_score'] = $newScore;
                    
                    $historyEntry = [
                        'bet' => $currentBet,
                        'win' => $standResult['win'],
                        'payout' => $winAmount,
                        'playerValue' => $result['playerValue'],
                        'dealerValue' => $standResult['dealerValue'],
                        'message' => $message
                    ];
                    array_unshift($history, $historyEntry);
                    $history = array_slice($history, 0, 10);
                    $_SESSION['blackjack_history'] = $history;
                    $_SESSION['blackjack_message'] = $message;
                    
                    $_SESSION['blackjack_animate_end'] = [
                        'dealerHand' => $standResult['dealerHand'],
                        'dealerValue' => $standResult['dealerValue'],
                        'playerHand' => $result['playerHand'],
                        'playerValue' => $result['playerValue'],
                        'status' => $standResult['win'] ? 'win' : ((isset($standResult['message']) && strpos($standResult['message'], 'Push') !== false) ? 'push' : 'lose'),
                        'betAmount' => $currentBet,
                        'message' => $message,
                        'newScore' => $newScore,
                        'oldScore' => $score + ($currentBet / 2)
                    ];
                } else {
                    $newScore = $score;
                    $_SESSION['blackjack_score'] = $newScore;
                    $message = "Bust! You went over 21 on double down.";
                    $historyEntry = [
                        'bet' => $currentBet,
                        'win' => false,
                        'payout' => 0,
                        'playerValue' => $result['playerValue'],
                        'dealerValue' => $game->getCardValue($game->dealerHand[0]),
                        'message' => $message
                    ];
                    array_unshift($history, $historyEntry);
                    $history = array_slice($history, 0, 10);
                    $_SESSION['blackjack_history'] = $history;
                    $_SESSION['blackjack_message'] = $message;
                    
                    $_SESSION['blackjack_animate_end'] = [
                        'dealerHand' => [$game->dealerHand[0], ['value' => '?', 'suit' => '?']],
                        'dealerValue' => $game->getCardValue($game->dealerHand[0]),
                        'playerHand' => $result['playerHand'],
                        'playerValue' => $result['playerValue'],
                        'status' => 'lose',
                        'betAmount' => $currentBet,
                        'message' => $message,
                        'newScore' => $newScore,
                        'oldScore' => $score + ($currentBet / 2)
                    ];
                }
                
                $_SESSION['blackjack_game'] = null;
                $_SESSION['blackjack_current_bet'] = null;
                $_SESSION['blackjack_game_data'] = null;
                $gameData = null;
                $score = $newScore;
            } else {
                $_SESSION['blackjack_error'] = ['type' => 'invalid', 'message' => 'Not enough credits to double down!'];
            }
        }
        
        if ($_POST['action'] === 'surrender' && $game && $game->getGameState() === 'playing') {
            if (count($gameData['playerHand']) === 2) {
                $winAmount = $currentBet * 0.5;
                $message = "You surrendered. Half your bet is returned.";
                $newScore = $score + $winAmount;
                $_SESSION['blackjack_score'] = $newScore;
                
                $historyEntry = [
                    'bet' => $currentBet,
                    'win' => false,
                    'payout' => $winAmount,
                    'playerValue' => $gameData['playerValue'],
                    'dealerValue' => $game->getCardValue($game->dealerHand[0]),
                    'message' => $message,
                    'status' => 'surrender'
                ];
                array_unshift($history, $historyEntry);
                $history = array_slice($history, 0, 10);
                $_SESSION['blackjack_history'] = $history;
                $_SESSION['blackjack_message'] = $message;
                
                $_SESSION['blackjack_animate_end'] = [
                    'dealerHand' => [$game->dealerHand[0], ['value' => '?', 'suit' => '?']],
                    'dealerValue' => $game->getCardValue($game->dealerHand[0]),
                    'playerHand' => $gameData['playerHand'],
                    'playerValue' => $gameData['playerValue'],
                    'status' => 'surrender',
                    'betAmount' => $currentBet,
                    'message' => $message,
                    'newScore' => $newScore,
                    'oldScore' => $score
                ];
                
                $_SESSION['blackjack_game'] = null;
                $_SESSION['blackjack_current_bet'] = null;
                $_SESSION['blackjack_game_data'] = null;
                $gameData = null;
                $score = $newScore;
            }
        }
    }
}

$score = $_SESSION['blackjack_score'];
$history = $_SESSION['blackjack_history'];
$game = $_SESSION['blackjack_game'];
$currentBet = $_SESSION['blackjack_current_bet'];
$gameData = $_SESSION['blackjack_game_data'];

$animateEnd = isset($_SESSION['blackjack_animate_end']) ? $_SESSION['blackjack_animate_end'] : null;
$message = $_SESSION['blackjack_message'];
$displayScore = $score;

if ($animateEnd) {
    unset($_SESSION['blackjack_animate_end']);
    // Before animation concludes visually, show the old score to the user
    $displayScore = $animateEnd['oldScore'];
}

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
    <script>
        window.blackjackError = <?php echo json_encode($error); ?>;
        window.blackjackAnimationData = <?php echo $animateEnd ? json_encode($animateEnd) : 'null'; ?>;
        window.blackjackResultMessage = <?php echo json_encode($message); ?>;
    </script>
    <script src="script.js"></script>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/house.svg" alt="Home" />
    </a>
    <main class="container">
        <?php if (isset($_SESSION['blackjack_showWelcome']) && $_SESSION['blackjack_showWelcome'] && !$gameData): ?>
            <div class="message message-welcome">
                Welcome to Blackjack! Place a bet to start the game.
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <div class="left-side">
                <!-- Game Display Section -->
                <div class="game-display-section">
                    <div class="section-header">
                        <h2>Blackjack Table</h2>
                        <div class="score">Credits: <strong id="scoreValue" data-final-score="<?php echo number_format($score, 2); ?>"><?php echo number_format($displayScore, 2); ?></strong></div>
                    </div>
                    <div class="blackjack-table">
                        <div class="dealer-area">
                            <div class="area-label">Dealer</div>
                            <div class="cards-area" id="dealerCards">
                                <?php if ($gameData || $animateEnd): ?>
                                    <?php $dHand = $gameData ? $gameData['dealerHand'] : $animateEnd['dealerHand']; ?>
                                    <?php foreach ($dHand as $card): ?>
                                        <?php 
                                            $isRed = ($card['suit'] === '♥' || $card['suit'] === '♦');
                                            $suitClass = $isRed ? 'suit-red' : 'suit-black';
                                        ?>
                                        <div class="game-card <?php echo $suitClass; ?>">
                                            <div class="card-val"><?php echo $card['value']; ?></div>
                                            <div class="card-suit"><?php echo $card['suit']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card-placeholder">Waiting for bet...</div>
                                <?php endif; ?>
                            </div>
                            <div class="hand-total">Value: <span id="dealerValue"><?php echo $gameData ? $gameData['dealerValue'] : ($animateEnd ? $animateEnd['dealerValue'] : '-'); ?></span></div>
                        </div>
                        
                        <div class="player-area">
                            <div class="area-label">Player</div>
                            <div class="cards-area" id="playerCards">
                                <?php if ($gameData || $animateEnd): ?>
                                    <?php $pHand = $gameData ? $gameData['playerHand'] : $animateEnd['playerHand']; ?>
                                    <?php foreach ($pHand as $card): ?>
                                        <?php 
                                            $isRed = ($card['suit'] === '♥' || $card['suit'] === '♦');
                                            $suitClass = $isRed ? 'suit-red' : 'suit-black';
                                        ?>
                                        <div class="game-card <?php echo $suitClass; ?>">
                                            <div class="card-val"><?php echo $card['value']; ?></div>
                                            <div class="card-suit"><?php echo $card['suit']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card-placeholder">Place a bet to start</div>
                                <?php endif; ?>
                            </div>
                            <div class="hand-total">Value: <span id="playerValue"><?php echo $gameData ? $gameData['playerValue'] : ($animateEnd ? $animateEnd['playerValue'] : '-'); ?></span></div>
                        </div>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo $gameData ? number_format($currentBet, 2) . ' credits' : ($animateEnd ? number_format($animateEnd['betAmount'], 2) . ' credits' : '-'); ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">
                                <?php if ($message && !$animateEnd): ?>
                                    <?php 
                                        $statusClass = '';
                                        if (!empty($history)) {
                                            $latest = $history[0];
                                            $isPush = (!$latest['win'] && $latest['payout'] > 0);
                                            $statusClass = $latest['win'] ? 'win' : ($isPush ? 'push' : 'lose');
                                        }
                                    ?>
                                    <span class="<?php echo $statusClass; ?>"><?php echo $message; ?></span>
                                <?php elseif ($animateEnd): ?>
                                    <span class="suspense-text" style="opacity: 0.6;">Calculating...</span>
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
                            <?php if (!$gameData || $animateEnd): ?>
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
                            <?php elseif (!$animateEnd): ?>
                                <div class="action-group">
                                    <?php 
                                        $canDouble = (count($gameData['playerHand']) === 2 && $score >= $currentBet);
                                        $canSurrender = (count($gameData['playerHand']) === 2);
                                    ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="hit" />
                                        <button type="submit" class="btn-primary">Hit</button>
                                    </form>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="stand" />
                                        <button type="submit" class="btn-primary">Stand</button>
                                    </form>
                                    <?php if ($canDouble): ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="double_down" />
                                        <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #f1c40f, #f39c12);">Double Down</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($canSurrender): ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="surrender" />
                                        <button type="submit" class="btn-danger">Surrender</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
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
                                <?php foreach ($history as $index => $round): ?>
                                    <?php $isNewest = ($index === 0 && $animateEnd); ?>
                                    <li class="history-item <?php echo $isNewest ? 'pending-animation' : ''; ?>" <?php echo $isNewest ? 'style="display:none;"' : ''; ?>>
                                        <div class="history-bet">
                                            Bet: <?php echo number_format($round['bet'], 2); ?> credits
                                        </div>
                                        <?php 
                                            // Handle old records gracefully
                                            $status = 'lose';
                                            if (isset($round['status'])) {
                                                $status = $round['status'];
                                            } else {
                                                if (isset($round['win']) && is_string($round['win'])) {
                                                    $status = $round['win'];
                                                } elseif (isset($round['win'])) {
                                                    $status = $round['win'] ? 'win' : (($round['payout'] > 0) ? 'push' : 'lose');
                                                }
                                            }
                                        ?>
                                        <div class="history-result <?php echo $status; ?>">
                                            <?php echo strtoupper($status); ?> 
                                            <?php if ($status === 'win' || $status === 'push'): ?>+<?php echo number_format($round['payout'], 2); ?><?php else: ?>-<?php echo number_format($round['bet'], 2); ?><?php endif; ?>
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
            const scoreValueEl = document.getElementById('scoreValue');
            const trueFinalScore = parseFloat(scoreValueEl.getAttribute('data-final-score')) || parseFloat(scoreValueEl.textContent);
            const forms = document.querySelectorAll('form');
            
            if (window.blackjackError) {
                showErrorNotification(window.blackjackError);
            }

            if (!window.blackjackAnimationData && !<?php echo json_encode($gameData); ?> && trueFinalScore <= 0) {
                showGameOverNotification();
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
            
            const betForm = document.getElementById('betForm');
            if (betForm) {
                betForm.addEventListener('submit', function(e) {
                    if (trueFinalScore <= 0) {
                        e.preventDefault();
                        showGameOverNotification();
                        return;
                    }
                    if (betAmount) {
                        const bet = parseFloat(betAmount.value);
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
                    }
                });
            }
            
            // Smooth out the conclusion interactions
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const actionInput = this.querySelector('input[name="action"]');
                    if (actionInput) {
                        const action = actionInput.value;
                        if (action === 'stand' || action === 'double_down' || action === 'surrender') {
                            e.preventDefault();
                            const btn = this.querySelector('button[type="submit"]');
                            if (btn && !btn.disabled) {
                                btn.disabled = true;
                                btn.innerHTML = '<span style="opacity:0.7">Dealer playing...</span>';
                                document.getElementById('dealerCards').style.opacity = '0.5';
                                document.getElementById('dealerCards').style.transition = 'opacity 0.3s ease';
                                this.submit();
                            }
                        } else if (action === 'hit') {
                            e.preventDefault();
                            const btn = this.querySelector('button[type="submit"]');
                            if (btn && !btn.disabled) {
                                btn.disabled = true;
                                btn.innerHTML = '<span style="opacity:0.7">Hitting...</span>';
                                this.submit();
                            }
                        }
                    }
                });
            });
            
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
        });
    </script>
    <script>
        <?php if ($animateEnd): ?>
        window.blackjackAnimationData = <?php echo json_encode($animateEnd); ?>;
        window.blackjackResultMessage = <?php echo json_encode($message); ?>;
        <?php endif; ?>
        window.blackjackError = <?php echo json_encode($error); ?>;
    </script>
</body>
</html>