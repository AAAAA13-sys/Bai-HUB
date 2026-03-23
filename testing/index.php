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
                <a href="dice.php" class="game-card">
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
                <a href="blackjack.php" class="game-card">
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
                <a href="slot.php" class="game-card">
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
                    <h3>💰 Betting Rules</h3>
                    <p>All games use the same credit system. Your balance carries over between games when you stay in the same session.</p>
                </div>
                <div class="info-card">
                    <h3>🏆 Win Multipliers</h3>
                    <p>Different patterns and combinations offer various multipliers. Check each game's rules for details!</p>
                </div>
            </div>
        </div>
    </main>
    
    <style>
        .homepage-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .hero-section {
            text-align: center;
            margin-bottom: 48px;
            padding: 32px;
            background: rgba(15, 22, 38, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            backdrop-filter: blur(10px);
        }
        
        .hero-icon {
            margin-bottom: 20px;
        }
        
        .hero-svg {
            width: 80px;
            height: 80px;
            filter: drop-shadow(0 0 10px rgba(74, 196, 125, 0.5));
        }
        
        .game-title {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4ac47d, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }
        
        .game-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
            margin-bottom: 48px;
        }
        
        .game-card {
            background: rgba(15, 22, 38, 0.9);
            border: 1px solid rgba(74, 196, 125, 0.3);
            border-radius: 24px;
            padding: 32px;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
        }
        
        .game-card:hover {
            transform: translateY(-8px);
            border-color: #4ac47d;
            box-shadow: 0 20px 40px rgba(74, 196, 125, 0.2);
        }
        
        .game-card-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .card-svg {
            width: 80px;
            height: 80px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
            transition: transform 0.3s ease;
        }
        
        .game-card:hover .card-svg {
            transform: scale(1.05);
        }
        
        .game-card-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            text-align: center;
            margin-bottom: 12px;
        }
        
        .game-card-description {
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        
        .game-card-features {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-bottom: 24px;
        }
        
        .feature-tag {
            background: rgba(74, 196, 125, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #4ac47d;
        }
        
        .play-btn {
            background: linear-gradient(135deg, #4ac47d, #3a9d62);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            color: #000;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: auto;
        }
        
        .play-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 196, 125, 0.4);
        }
        
        .info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .info-card {
            background: rgba(15, 22, 38, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 24px;
        }
        
        .info-card h3 {
            color: #4ac47d;
            margin-bottom: 12px;
            font-size: 1.2rem;
        }
        
        .info-card p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .game-title {
                font-size: 2rem;
            }
            
            .games-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-svg,
            .card-svg {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</body>
</html>