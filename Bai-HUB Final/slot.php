<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Slot Machine - Casino Games</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="scripts/slot-script.js" defer></script>
    <style>
        @keyframes rollDown {
            0% { transform: translateY(-70%); opacity: 0; }
            50% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(70%); opacity: 0; }
        }
        .rolling-img {
            animation: rollDown 0.15s linear infinite;
        }
        .slot-reels-container {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 24px !important;
            flex-wrap: nowrap !important;
        }
        .slot-reel {
            width: 100px !important;
            height: 100px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(0, 0, 0, 0.35) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 16px !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4) !important;
            overflow: hidden !important;
        }
        .slot-symbol {
            width: 80px !important;
            height: 80px !important;
            object-fit: contain !important;
            display: block !important;
        }
        @media (max-width: 800px) {
            .slot-reels-container { gap: 16px !important; }
            .slot-reel { width: 70px !important; height: 70px !important; }
            .slot-symbol { width: 55px !important; height: 55px !important; }
        }
        @media (max-width: 480px) {
            .slot-reels-container { gap: 12px !important; }
            .slot-reel { width: 55px !important; height: 55px !important; }
            .slot-symbol { width: 45px !important; height: 45px !important; }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/icons/Home-button.svg" alt="Home" />
    </a>
    
    <main class="container">
        <div class="main-layout">
            <div class="left-side">
                <div class="slot-display-section">
                    <div class="section-header">
                        <div style="display: flex; align-items: center;">
                            <h2>Slot Reels</h2>
                            <span class="rules-icon" id="rulesIcon" title="Payout Rules">?</span>
                        </div>
                        <div class="score">Credits: <strong id="scoreValue">0.00</strong></div>
                    </div>
                    <div class="slot-machine-display">
                        <div class="slot-reels-container">
                            <div class="slot-reel">
                                <img src="img/grapes.svg" class="slot-symbol" alt="grapes" id="reel-0" />
                            </div>
                            <div class="slot-reel">
                                <img src="img/orange.svg" class="slot-symbol" alt="orange" id="reel-1" />
                            </div>
                            <div class="slot-reel">
                                <img src="img/clover.svg" class="slot-symbol" alt="clover" id="reel-2" />
                            </div>
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
                            <div class="stake-group">
                                <div class="group-title">Stake amount</div>
                                <div class="quick-stakes">
                                    <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                    <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                    <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                </div>
                                <form id="spinForm">
                                    <input type="number" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" value="10.00" required />
                                    <button type="submit" id="spinBtn" class="btn-primary" style="margin-top: 16px; width: 100%;">SPIN</button>
                                </form>
                            </div>
                            
                            <button type="button" id="resetBtn" class="btn-danger" style="width: 100%; margin-top: 16px;">Reset Game</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Spin history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list" id="historyList">
                            <li class="history-item empty">
                                <span>Loading...</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>