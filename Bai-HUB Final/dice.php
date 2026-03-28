<?php
session_start();
if (!isset($_SESSION['authorized_entry'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dice Betting Game</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="scripts/confirm-utils.js" defer></script>
    <script src="scripts/dice-script.js" defer></script>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/icons/Home-button.svg" alt="Home" />
    </a>
    <main class="container">
        <div class="main-layout">
            <div class="left-side">
                <div class="dice-section">
                    <div class="section-header">
                        <div style="display: flex; align-items: center;">
                            <h2>Dice Table</h2>
                            <span class="rules-icon" id="rulesIcon" title="How to Play">?</span>
                        </div>
                        <div class="score">Credits: <strong id="scoreValue">0.00</strong></div>
                    </div>
                    <div class="dice-container" id="diceContainer">
                        <img id="die1" class="die" src="img/dice-six-faces-one.svg" alt="Die" />
                        <img id="die2" class="die" src="img/dice-six-faces-two.svg" alt="Die" />
                        <img id="die3" class="die" src="img/dice-six-faces-three.svg" alt="Die" />
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet">-</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Roll total:</span>
                            <span class="result-value" id="rollTotal">-</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">-</span>
                        </div>
                    </div>
                </div>

                <div class="bottom-section">
                    <div class="betting-settings">
                        <div class="section-header">
                            <h2>Betting Options</h2>
                        </div>
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">Pattern bets</div>
                                <div class="pattern-grid">
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="odd" class="pattern-radio" checked />
                                        <span>Odd</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="even" class="pattern-radio" />
                                        <span>Even</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="low" class="pattern-radio" />
                                        <span>Low (3-10)</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="high" class="pattern-radio" />
                                        <span>High (11-18)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="bet-group">
                                <div class="group-title">Exact number</div>
                                <div class="number-grid">
                                    <button type="button" class="number-btn" data-number="3">3</button>
                                    <button type="button" class="number-btn" data-number="4">4</button>
                                    <button type="button" class="number-btn" data-number="5">5</button>
                                    <button type="button" class="number-btn" data-number="6">6</button>
                                    <button type="button" class="number-btn" data-number="7">7</button>
                                    <button type="button" class="number-btn" data-number="8">8</button>
                                    <button type="button" class="number-btn" data-number="9">9</button>
                                    <button type="button" class="number-btn" data-number="10">10</button>
                                    <button type="button" class="number-btn" data-number="11">11</button>
                                    <button type="button" class="number-btn" data-number="12">12</button>
                                    <button type="button" class="number-btn" data-number="13">13</button>
                                    <button type="button" class="number-btn" data-number="14">14</button>
                                    <button type="button" class="number-btn" data-number="15">15</button>
                                    <button type="button" class="number-btn" data-number="16">16</button>
                                    <button type="button" class="number-btn" data-number="17">17</button>
                                    <button type="button" class="number-btn" data-number="18">18</button>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                <input id="betStake" type="number" class="stake-input" step="0.01" value="10.00" />
                            </div>

                            <div class="action-group">
                                <button id="placeBetBtn" class="btn-primary" style="width: 100%; margin-bottom: 8px;">Roll Dice</button>
                                <button id="resetBtn" class="btn-danger" style="width: 100%;">Reset Game</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Roll history</h2>
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