<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

$payload = json_decode(file_get_contents('php://input'), true);
$gameName = $payload['game'] ?? '';

if (!$gameName) {
    echo json_encode(['success' => false, 'error' => 'Missing game parameter']);
    exit;
}

$userData = getUserData($gameName);
$creditsToAdd = rand(100, 500);
$userData['balance'] += $creditsToAdd;

saveUserData($gameName, $userData);

echo json_encode([
    'success' => true,
    'creditsAdded' => $creditsToAdd,
    'newBalance' => $userData['balance']
]);
