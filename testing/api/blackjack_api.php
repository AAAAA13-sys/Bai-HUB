<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

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

$payload = json_decode(file_get_contents('php://input'), true);
$action = $payload['action'] ?? '';

$userData = getUserData('blackjack');
$score = (float)$userData['balance'];
$history = $userData['history'];

$game = isset($_SESSION['blackjack_game']) ? $_SESSION['blackjack_game'] : null;
$currentBet = isset($_SESSION['blackjack_current_bet']) ? $_SESSION['blackjack_current_bet'] : null;
$gameData = isset($_SESSION['blackjack_game_data']) ? $_SESSION['blackjack_game_data'] : null;

$response = [
    'success' => true,
    'action' => $action,
    'balance' => $score,
    'gameData' => $gameData,
    'history' => $history,
    'error' => null
];

if ($action === 'init') {
    // Just return state
    echo json_encode($response);
    exit;
}

if ($action === 'reset') {
    $userData['balance'] = 100.00;
    $userData['history'] = [];
    saveUserData('blackjack', $userData);
    
    $_SESSION['blackjack_game'] = null;
    $_SESSION['blackjack_current_bet'] = null;
    $_SESSION['blackjack_game_data'] = null;
    
    $response['balance'] = 100.00;
    $response['history'] = [];
    $response['gameData'] = null;
    echo json_encode($response);
    exit;
}

if ($action === 'clear_board') {
    $_SESSION['blackjack_game'] = null;
    $_SESSION['blackjack_current_bet'] = null;
    $_SESSION['blackjack_game_data'] = null;
    $response['gameData'] = null;
    echo json_encode($response);
    exit;
}

if ($action === 'place_bet') {
    $bet = isset($payload['bet']) ? (float)$payload['bet'] : 0;
    
    if ($bet <= 0) {
        $response['error'] = 'Invalid bet amount. Please enter a stake amount greater than 0.';
        $response['success'] = false;
    } elseif ($bet > $score) {
        $response['error'] = "Insufficient balance! You have " . number_format($score, 2, '.', '') . " credits.";
        $response['success'] = false;
    } else {
        $_SESSION['blackjack_current_bet'] = $bet;
        $game = new BlackjackGame();
        $_SESSION['blackjack_game'] = $game;
        $gameData = $game->startGame();
        $currentBet = $bet;
        
        $response['gameActive'] = true;
        
        if ($gameData['naturalBlackjack']) {
            $dealerTotal = $game->calculateHandValue($game->dealerHand);
            $winAmount = 0;
            $outcomeStatus = 'lose';
            if ($dealerTotal === 21) {
                $winAmount = $currentBet;
                $message = "Push! Both have Blackjack. Your bet is returned.";
                $outcomeStatus = 'push';
            } else {
                $winAmount = $currentBet * 2.5; // Pays 3:2
                $message = "BLACKJACK! You won " . number_format($winAmount, 2, '.', '') . " credits!";
                $outcomeStatus = 'win';
            }
            
            $newScore = $score - $currentBet + $winAmount;
            $userData['balance'] = $newScore;
            
            $historyEntry = [
                'bet' => $currentBet,
                'status' => $outcomeStatus,
                'payout' => $winAmount,
                'playerValue' => 21,
                'dealerValue' => $dealerTotal,
                'message' => $message
            ];
            array_unshift($userData['history'], $historyEntry);
            $userData['history'] = array_slice($userData['history'], 0, 10);
            saveUserData('blackjack', $userData);
            
            $gameData['dealerHand'] = $game->dealerHand;
            $gameData['dealerValue'] = $dealerTotal;
            $gameData['status'] = $outcomeStatus;
            $gameData['message'] = $message;
            $gameData['payout'] = $winAmount;
            
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_game_data'] = $gameData;
            
            $response['gameActive'] = false;
            $response['balance'] = $newScore;
            $response['history'] = $userData['history'];
        } else {
            $_SESSION['blackjack_game_data'] = $gameData;
        }
        $response['gameData'] = $gameData;
        $response['currentBet'] = $currentBet;
    }
    echo json_encode($response);
    exit;
}

if ($action === 'hit' && $game && $game->getGameState() === 'playing') {
    $result = $game->hit();
    $gameData = [
        'playerHand' => $result['playerHand'],
        'playerValue' => $result['playerValue'],
        'dealerHand' => [$game->dealerHand[0], ['value' => '?', 'suit' => '?']],
        'dealerValue' => $game->getCardValue($game->dealerHand[0])
    ];
    $_SESSION['blackjack_game_data'] = $gameData;
    $response['gameActive'] = $result['gameActive'];
    
    if (!$result['gameActive']) {
        // Bust!
        $standResult = $game->stand();
        $winAmount = 0;
        $outcomeStatus = 'lose';
        $message = $standResult['message'];
        
        $newScore = $score - $currentBet;
        $userData['balance'] = $newScore;
        
        $historyEntry = [
            'bet' => $currentBet,
            'status' => $outcomeStatus,
            'payout' => $winAmount,
            'playerValue' => $result['playerValue'],
            'dealerValue' => $standResult['dealerValue'],
            'message' => $message
        ];
        array_unshift($userData['history'], $historyEntry);
        $userData['history'] = array_slice($userData['history'], 0, 10);
        saveUserData('blackjack', $userData);
        
        $gameData['dealerHand'] = $standResult['dealerHand'];
        $gameData['dealerValue'] = $standResult['dealerValue'];
        $gameData['status'] = $outcomeStatus;
        $gameData['message'] = $message;
        
        $_SESSION['blackjack_game'] = null;
        $_SESSION['blackjack_game_data'] = $gameData;
        
        $response['balance'] = $newScore;
        $response['history'] = $userData['history'];
    }
    
    $response['gameData'] = $gameData;
    echo json_encode($response);
    exit;
}

if ($action === 'stand' && $game && $game->getGameState() === 'playing') {
    $result = $game->stand();
    $winAmount = 0;
    $outcomeStatus = 'lose';
    if ($result['win']) {
        $winAmount = $currentBet * 2;
        $message = $result['message'] . " You won " . number_format($winAmount, 2, '.', '') . " credits!";
        $outcomeStatus = 'win';
    } elseif (isset($result['message']) && strpos($result['message'], 'Push') !== false) {
        $winAmount = $currentBet;
        $message = $result['message'] . " Your bet is returned.";
        $outcomeStatus = 'push';
    } else {
        $message = $result['message'];
    }
    
    $newScore = $score - $currentBet + $winAmount;
    $userData['balance'] = $newScore;
    
    $historyEntry = [
        'bet' => $currentBet,
        'status' => $outcomeStatus,
        'payout' => $winAmount,
        'playerValue' => $result['playerValue'],
        'dealerValue' => $result['dealerValue'],
        'message' => $message
    ];
    array_unshift($userData['history'], $historyEntry);
    $userData['history'] = array_slice($userData['history'], 0, 10);
    saveUserData('blackjack', $userData);
    
    $gameData = $_SESSION['blackjack_game_data'];
    $gameData['dealerHand'] = $result['dealerHand'];
    $gameData['dealerValue'] = $result['dealerValue'];
    $gameData['status'] = $outcomeStatus;
    $gameData['message'] = $message;
    $gameData['payout'] = $winAmount;
    
    $_SESSION['blackjack_game'] = null;
    $_SESSION['blackjack_game_data'] = $gameData;
    
    $response['gameActive'] = false;
    $response['gameData'] = $gameData;
    $response['balance'] = $newScore;
    $response['history'] = $userData['history'];
    
    echo json_encode($response);
    exit;
}

if ($action === 'double_down' && $game && $game->getGameState() === 'playing') {
    if ($score >= $currentBet * 2) {
        $currentBet *= 2;
        $_SESSION['blackjack_current_bet'] = $currentBet;
        
        $game->hit();
        $result = $game->stand();
        
        $winAmount = 0;
        $outcomeStatus = 'lose';
        if ($result['win']) {
            $winAmount = $currentBet * 2;
            $message = $result['message'] . " You won " . number_format($winAmount, 2, '.', '') . " credits!";
            $outcomeStatus = 'win';
        } elseif (isset($result['message']) && strpos($result['message'], 'Push') !== false) {
            $winAmount = $currentBet;
            $message = $result['message'] . " Your bet is returned.";
            $outcomeStatus = 'push';
        } else {
            $message = $result['message'];
        }
        
        $newScore = $score - $currentBet + $winAmount;
        $userData['balance'] = $newScore;
        
        $historyEntry = [
            'bet' => $currentBet,
            'status' => $outcomeStatus,
            'payout' => $winAmount,
            'playerValue' => $result['playerValue'],
            'dealerValue' => $result['dealerValue'],
            'message' => 'Double Down: ' . $message
        ];
        array_unshift($userData['history'], $historyEntry);
        $userData['history'] = array_slice($userData['history'], 0, 10);
        saveUserData('blackjack', $userData);
        
        $gameData = $_SESSION['blackjack_game_data'];
        $gameData['dealerHand'] = $result['dealerHand'];
        $gameData['dealerValue'] = $result['dealerValue'];
        $gameData['status'] = $outcomeStatus;
        $gameData['message'] = 'Double Down: ' . $message;
        $gameData['payout'] = $winAmount;
        
        $_SESSION['blackjack_game'] = null;
        $_SESSION['blackjack_game_data'] = $gameData;
        
        $response['gameActive'] = false;
        $response['gameData'] = $gameData;
        $response['balance'] = $newScore;
        $response['history'] = $userData['history'];
    } else {
        $response['success'] = false;
        $response['error'] = "Insufficient balance to Double Down.";
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
