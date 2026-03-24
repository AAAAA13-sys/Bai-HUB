<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Casino Games - Home</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <main class="container">
        <div class="homepage-container">
            <div class="hero-section">
                <div class="hero-icon">
                    <img src="img/icons/take-my-money.svg" alt="Take My Money" class="hero-svg" />
                </div>
                <h1 class="game-title">CASINO GAMES</h1>
                <p class="game-subtitle">Choose your game and test your luck!</p>
            </div>
            
            <div class="games-grid">
                <!-- Dice Game Card -->
                <a href="dice.php" class="game-card-display">
                    <div class="game-card-icon">
                        <img src="img/icons/rolling-dices.svg" alt="Rolling Dices" class="card-svg" />
                    </div>
                    <h2 class="game-card-title">Dice Betting</h2>
                    <p class="game-card-description">Predict patterns (Odd/Even, High/Low) or exact numbers. Roll the dice and win up to 10x your bet!</p>
                    <div class="game-card-features">
                        <span class="feature-tag">Pattern Bets</span>
                        <span class="feature-tag">Exact Number</span>
                        <span class="feature-tag">2x-10x Multipliers</span>
                    </div>
                    <button class="play-btn">Play Now →</button>
                </a>
                
                <!-- Blackjack Card -->
                <a href="blackjack.php" class="game-card-display">
                    <div class="game-card-icon">
                        <img src="img/icons/card-random.svg" alt="Card Random" class="card-svg" />
                    </div>
                    <h2 class="game-card-title">Blackjack</h2>
                    <p class="game-card-description">Classic 21! Beat the dealer by getting closer to 21 without going over.</p>
                    <div class="game-card-features">
                        <span class="feature-tag">vs Dealer</span>
                        <span class="feature-tag">Hit or Stand</span>
                        <span class="feature-tag">2x Payout</span>
                    </div>
                    <button class="play-btn">Play Now →</button>
                </a>
                
                <!-- Slot Machine Card -->
                <a href="slot.php" class="game-card-display">
                    <div class="game-card-icon">
                        <img src="img/icons/slot-machine.svg" alt="Slot Machine" class="card-svg" />
                    </div>
                    <h2 class="game-card-title">Slot Machine</h2>
                    <p class="game-card-description">Spin the reels and match symbols! Fruits, Lucky 7s, and Diamond Jackpots!</p>
                    <div class="game-card-features">
                        <span class="feature-tag">Fruits (0.8x-1.2x)</span>
                        <span class="feature-tag">Lucky 7 (2x)</span>
                        <span class="feature-tag">Diamond Jackpot (5x)</span>
                    </div>
                    <button class="play-btn">Play Now →</button>
                </a>
            </div>
            
            <div class="info-section">
                <div class="info-card">
                    <h3>🎮 How to Play</h3>
                    <p>Each game has its own rules and multipliers. Start with 100 credits and try to grow your balance!</p>
                </div>
                <div class="info-card">
                    <h3>Betting Rules</h3>
                    <p>All games use the same credit system. Your balance carries over between games when you stay in the same session.</p>
                </div>
                <div class="info-card">
                    <h3>Win Multipliers</h3>
                    <p>Different patterns and combinations offer various multipliers. Check each game's rules for details!</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>