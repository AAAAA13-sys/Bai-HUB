<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

$userId = getUserId();
$sharedData = getSharedBalance();
$action = $_GET['action'] ?? '';

if ($action === 'check') {
    $now = time();
    $lastBlessing = $sharedData['last_blessing'] ?? 0;
    $cooldown = 15 * 60; // 15 minutes
    
    $eligible = false;
    $reason = '';
    
    // Condition 4: Cooldown
    if (($now - $lastBlessing) < $cooldown) {
        echo json_encode(['eligible' => false, 'reason' => 'cooldown', 'remaining' => $cooldown - ($now - $lastBlessing)]);
        exit;
    }
    
    // Condition 1: Fresh Start (no bets yet)
    if ($sharedData['total_bets'] == 0 && $sharedData['balance'] <= 100) {
        $eligible = true;
        $reason = 'fresh';
    } 
    // Condition 2: Broke
    elseif ($sharedData['balance'] < 10) {
        $eligible = true;
        $reason = 'broke';
    }
    // Condition 3: High-Roller returning
    elseif ($sharedData['total_spent'] >= 1000) {
        $eligible = true;
        $reason = 'high_roller';
    }
    
    echo json_encode(['eligible' => $eligible, 'reason' => $reason]);
    exit;
}

if ($action === 'claim') {
    $now = time();
    $lastBlessing = $sharedData['last_blessing'] ?? 0;
    if (($now - $lastBlessing) < (15 * 60)) {
        echo json_encode(['success' => false, 'error' => 'Cooldown active.']);
        exit;
    }
    
    $amount = rand(100, 300);
    $sharedData['balance'] += $amount;
    $sharedData['last_blessing'] = $now;
    
    // Reset spent counter if it was a high-roller blessing to require another 1k for next
    if ($sharedData['total_spent'] >= 1000) {
        $sharedData['total_spent'] = 0;
    }
    
    saveSharedBalance($sharedData);
    
    echo json_encode([
        'success' => true, 
        'amount' => $amount, 
        'new_balance' => $sharedData['balance']
    ]);
    exit;
}
