<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

class SlotMachine {
    public const SYMBOLS = [
        'grapes' => ['name' => 'grapes.svg', 'multiplier' => 2, 'type' => 'fruit'],
        'orange' => ['name' => 'orange.svg', 'multiplier' => 2, 'type' => 'fruit'],
        'clover' => ['name' => 'clover.svg', 'multiplier' => 3, 'type' => 'special'],
        'diamond' => ['name' => 'cut-diamond.svg', 'multiplier' => 5, 'type' => 'jackpot'],
        'star' => ['name' => 'star.svg', 'multiplier' => 0, 'type' => 'wild']
    ];
    
    private array $reels = [];
    private bool $isLucky = false;
    
    public function __construct(bool $lucky = false) {
        $this->isLucky = $lucky;
        $this->initializeReels();
    }
    
    private function initializeReels(): void {
        $symbolPool = [];
        foreach (self::SYMBOLS as $key => $symbol) {
            $weight = 5;
            switch ($symbol['type']) {
                case 'jackpot': $weight = $this->isLucky ? 5 : 1; break;
                case 'wild': $weight = $this->isLucky ? 5 : 2; break;
                case 'special': $weight = $this->isLucky ? 8 : 3; break;
                default: $weight = $this->isLucky ? 2 : 5; break;
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

$sharedData = getSharedBalance();
$score = (float)$sharedData['balance'];
$userData = getUserData('slot');

$response = [
    'success' => true,
    'balance' => $score,
    'history' => $userData['history'],
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
        $sharedData = recordBet($bet);
        $isLucky = ($sharedData['total_bets'] % 10 === 0 && random_int(1, 100) <= 50);
        
        $slot = new SlotMachine($isLucky);
        $result = $slot->spin();
        $winResult = $slot->calculateWin($result, $bet);
        
        $winAmount = $winResult['payout'];
        $newScore = updateSharedBalance(-$bet + $winAmount);
        
        $message = $winResult['win'] 
            ? "WIN! You won " . number_format($winAmount, 2, '.', '') . " credits! (" . $winResult['multiplier'] . "x multiplier)"
            : "LOSE! Better luck next time!";
        
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
            'status' => $winResult['win'] ? 'win' : 'lose',
            'lucky' => $isLucky
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
            'payout' => $winResult['payout'],
            'is_lucky' => $isLucky
        ];
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
