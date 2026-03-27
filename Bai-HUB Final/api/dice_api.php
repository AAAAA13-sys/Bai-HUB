<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

class DiceGame {
    private $patterns = [
        'odd' => ['label' => 'Odd', 'multiplier' => 2],
        'even' => ['label' => 'Even', 'multiplier' => 2],
        'low' => ['label' => 'Low (3-10)', 'multiplier' => 2],
        'high' => ['label' => 'High (11-18)', 'multiplier' => 2],
    ];
    
    public function getPatterns() {
        return $this->patterns;
    }
    
    public function matchesPattern($pattern, $value) {
        switch ($pattern) {
            case 'odd': return $value % 2 !== 0;
            case 'even': return $value % 2 === 0;
            case 'low': return $value >= 3 && $value <= 10;
            case 'high': return $value >= 11 && $value <= 18;
            default: return false;
        }
    }
    
    public function rollDice() {
        return [
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6)
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
        
        $points = $win ? round($bet * $multiplier, 2) : 0;
        
        return [
            'win' => $win,
            'points' => $points,
            'multiplier' => $multiplier
        ];
    }
}

$payload = json_decode(file_get_contents('php://input'), true);
$action = $payload['action'] ?? '';

$sharedData = getSharedBalance();
$score = (float)$sharedData['balance'];
$userData = getUserData('dice'); // Still used for history
$history = $userData['history'];

$response = [
    'success' => true,
    'balance' => $score,
    'history' => $history,
    'total_bets' => $sharedData['total_bets'],
    'error' => null
];

if ($action === 'init') {
    echo json_encode($response);
    exit;
}

if ($action === 'reset') {
    $sharedData['balance'] = 100.00;
    $sharedData['total_bets'] = 0;
    $sharedData['total_spent'] = 0;
    $sharedData['last_blessing'] = 0;
    saveSharedBalance($sharedData);
    
    $userData['history'] = [];
    saveUserData('dice', $userData);
    
    $response['balance'] = 100.00;
    $response['history'] = [];
    echo json_encode($response);
    exit;
}

if ($action === 'play') {
    $betType = $payload['bet_type'] ?? 'pattern';
    $betValue = $payload['bet_value'] ?? 'odd';
    $bet = isset($payload['bet']) ? (float)$payload['bet'] : 0;
    
    if ($bet <= 0) {
        $response['error'] = 'Invalid bet amount.';
        $response['success'] = false;
    } elseif ($bet > $score) {
        $response['error'] = "Insufficient balance! You have " . number_format($score, 2, '.', '') . " credits.";
        $response['success'] = false;
    } else {
        $sharedData = recordBet($bet); // Increment total_bets
        $isLucky = ($sharedData['total_bets'] % 10 === 0 && random_int(1, 100) <= 50);
        
        $game = new DiceGame();
        $dice = $game->rollDice();
        $total = array_sum($dice);
        
        $result = $game->calculateWin($betType, $betValue, $bet, $total);
        
        // Lucky mechanic: if lost on a lucky roll, try one more time
        if (!$result['win'] && $isLucky) {
            $dice = $game->rollDice();
            $total = array_sum($dice);
            $result = $game->calculateWin($betType, $betValue, $bet, $total);
        }
        
        $winAmount = $result['points'];
        $newScore = updateSharedBalance(-$bet + $winAmount);
        
        $label = ($betType === 'number') ? "Number $betValue" : $game->getPatterns()[$betValue]['label'];
        
        $round = [
            'pattern' => $label,
            'bet' => $bet,
            'dice' => $dice,
            'total' => $total,
            'win' => $result['win'],
            'payout' => $winAmount,
            'status' => $result['win'] ? 'win' : 'lose',
            'lucky' => $isLucky
        ];
        
        array_unshift($userData['history'], $round);
        $userData['history'] = array_slice($userData['history'], 0, 10);
        saveUserData('dice', $userData); // Keep history per game
        
        $response['balance'] = $newScore;
        $response['history'] = $userData['history'];
        $response['roll'] = $round;
        $response['is_lucky'] = $isLucky;
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
