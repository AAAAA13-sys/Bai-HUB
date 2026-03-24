<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

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
            $weight = 5;
            switch ($symbol['type']) {
                case 'jackpot': $weight = 1; break;
                case 'wild': $weight = 2; break;
                case 'special': $weight = 3; break;
                default: $weight = 5; break;
            }
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
        $wild = 'star';
        $target = null;
        
        foreach ($result as $sym) {
            if ($sym !== $wild) {
                $target = $sym;
                break;
            }
        }
        
        if ($target === null) {
            // All wilds
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

$payload = json_decode(file_get_contents('php://input'), true);
$action = $payload['action'] ?? '';

$userData = getUserData('slot');
$score = (float)$userData['balance'];

$response = [
    'success' => true,
    'balance' => $score,
    'history' => $userData['history'],
    'error' => null
];

if ($action === 'init') {
    echo json_encode($response);
    exit;
}

if ($action === 'reset') {
    $userData['balance'] = 100.00;
    $userData['history'] = [];
    saveUserData('slot', $userData);
    
    $response['balance'] = 100.00;
    $response['history'] = [];
    echo json_encode($response);
    exit;
}

if ($action === 'spin') {
    $bet = isset($payload['bet']) ? (float)$payload['bet'] : 0;
    
    if ($bet <= 0) {
        $response['error'] = 'Invalid bet amount.';
        $response['success'] = false;
    } elseif ($bet > $score) {
        $response['error'] = "Insufficient balance! You have " . number_format($score, 2, '.', '') . " credits.";
        $response['success'] = false;
    } else {
        $slot = new SlotMachine();
        $result = $slot->spin();
        $winResult = $slot->calculateWin($result, $bet);
        
        $newScore = $score - $bet;
        $message = "LOSE! Better luck next time!";
        if ($winResult['win']) {
            $newScore += $winResult['payout'];
            $message = "WIN! You won " . number_format($winResult['payout'], 2, '.', '') . " credits! (" . $winResult['multiplier'] . "x multiplier)";
        }
        $userData['balance'] = $newScore;
        
        $symbolsMapped = array_map(function($symbol) use ($slot) {
            return [
                'symbol' => $symbol,
                'image' => $slot->getSymbolImage($symbol)
            ];
        }, $result);
        
        $round = [
            'bet' => $bet,
            'win' => $winResult['win'],
            'payout' => $winResult['payout'],
            'multiplier' => $winResult['multiplier'],
            'symbols' => $result,
            'message' => $message,
            'status' => $winResult['win'] ? 'win' : 'lose'
        ];
        
        array_unshift($userData['history'], $round);
        $userData['history'] = array_slice($userData['history'], 0, 10);
        saveUserData('slot', $userData);
        
        $response['balance'] = $newScore;
        $response['history'] = $userData['history'];
        $response['spin'] = [
            'reels' => $symbolsMapped,
            'win' => $winResult['win'],
            'winningSymbol' => $winResult['winningSymbol'],
            'message' => $message,
            'payout' => $winResult['payout']
        ];
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
