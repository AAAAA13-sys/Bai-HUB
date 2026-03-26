<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blackjack - Casino Games</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="blackjack-script.js" defer></script>
    <script src="ads.js" defer></script>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/icons/Home-button.svg" alt="Home" />
    </a>
    <main class="container">
        <div class="main-layout">
            <div class="left-side">
                <div class="game-display-section">
                    <div class="section-header">
                        <div style="display: flex; align-items: center;">
                            <h2>Blackjack Table</h2>
                            <span class="rules-icon" id="rulesIcon" title="How to Play">?</span>
                        </div>
                        <div class="score">Credits: <strong id="scoreValue">0.00</strong></div>
                    </div>
                    <div class="blackjack-table">
                        <div class="dealer-area">
                            <div class="area-label">Dealer</div>
                            <div class="cards-area" id="dealerCards">
                                <div class="card-placeholder">Waiting for bet...</div>
                            </div>
                            <div class="hand-total">Value: <span id="dealerValue">-</span></div>
                        </div>
                        
                        <div class="player-area">
                            <div class="area-label">Player</div>
                            <div class="cards-area" id="playerCards">
                                <div class="card-placeholder">Place a bet to start</div>
                            </div>
                            <div class="hand-total">Value: <span id="playerValue">-</span></div>
                        </div>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet">-</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">-</span>
                        </div>
                    </div>
                </div>

                <div class="bottom-section">
                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & actions</h2>
                        </div>
                        <div class="actions-content">
                            <div id="bettingContainer" class="stake-group">
                                <div class="group-title">Stake amount</div>
                                <div class="quick-stakes">
                                    <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                    <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                    <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                </div>
                                <form id="betForm">
                                    <input type="number" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                    <button type="submit" class="btn-primary" style="margin-top: 16px;">Place Bet</button>
                                </form>
                            </div>

                            <div id="playingActions" class="action-group" style="display: none;">
                                <button type="button" id="hitBtn" class="btn-primary" style="width: 100%; margin-bottom: 8px;">Hit</button>
                                <button type="button" id="standBtn" class="btn-primary" style="width: 100%; margin-bottom: 8px;">Stand</button>
                                <button type="button" id="doubleBtn" class="btn-primary" style="width: 100%; background: linear-gradient(135deg, #ffd700, #ff8c00);">Double Down</button>
                            </div>
                            
                            <button type="button" id="resetBtn" class="btn-danger" style="width: 100%; margin-top: 16px;">Reset Game</button>
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
                            <li class="history-item empty">
                                <span>Loading...</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </main>
</body>
</html>