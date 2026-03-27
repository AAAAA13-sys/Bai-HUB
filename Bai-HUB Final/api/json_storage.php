<?php
// testing/api/json_storage.php

function getUserId()
{
    if (!isset($_COOKIE['user_id'])) {
        $userId = 'user_' . uniqid() . '_' . rand(1000, 9999);
        setcookie('user_id', $userId, time() + (86400 * 30), "/"); // 30 days
        $_COOKIE['user_id'] = $userId;
    }
    return $_COOKIE['user_id'];
}

function getGameData($gameName)
{
    $file = __DIR__ . "/../data/{$gameName}.json";
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

function saveGameData($gameName, $data)
{
    $dir = __DIR__ . "/../data";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = "{$dir}/{$gameName}.json";
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function getUserData($gameName)
{
    $userId = getUserId();
    $allData = getGameData($gameName);
    if (!isset($allData[$userId])) {
        $allData[$userId] = [
            'balance' => 100.00, // Default starting balance
            'history' => []
        ];
        saveGameData($gameName, $allData);
    }
    return $allData[$userId];
}

function saveUserData($gameName, $userData)
{
    $userId = getUserId();
    $allData = getGameData($gameName);
    $allData[$userId] = $userData;
    saveGameData($gameName, $allData);
}

function getSharedBalance()
{
    $userId = getUserId();
    $allData = getGameData('global_balance');
    if (!isset($allData[$userId])) {
        $allData[$userId] = [
            'balance' => 100.00,
            'total_bets' => 0,
            'total_spent' => 0,
            'last_blessing' => 0
        ];
        saveGameData('global_balance', $allData);
    }
    return $allData[$userId];
}

function saveSharedBalance($data)
{
    $userId = getUserId();
    $allData = getGameData('global_balance');
    $allData[$userId] = $data;
    saveGameData('global_balance', $allData);
}

function updateSharedBalance($delta)
{
    $data = getSharedBalance();
    $data['balance'] += (float)$delta;
    saveSharedBalance($data);
    return $data['balance'];
}

function recordBet($amount)
{
    $data = getSharedBalance();
    $data['total_bets']++;
    $data['total_spent'] += (float)$amount;
    saveSharedBalance($data);
    return $data;
}
