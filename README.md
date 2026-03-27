# Bai-HUB Casino Platform

Bai-HUB is a premium casino gaming platform featuring a unified global credit system and a dynamic bonus engine. Designed for a high-end gambling experience, it integrates three core games (Blackjack, Dice, and Slots) into a single "Universal Wallet."

## Key Features

### 🎰 Game Selection
- **Dice Betting**: Multiple betting patterns (Odd/Even, High/Low) with 2x payouts, or exact sum predictions for a 10x jackpot.
- **Blackjack**: Professional 21 against the dealer with Hit, Stand, and **Double Down** actions. Features premium card rendering with suit-specific colors (♣♥♦♠).
- **Slot Machine**: Fast-paced reels with **Wild (Star)** symbols and variable multipliers. Support for "Lucky Reels" with enhanced jackpot weights.

### 💰 Universal Wallet & Systems
- **Global Balance**: Credits are shared across all games in real-time. No more per-game credit management.
- **Lucky 10th Mechanic**: Every **10th bet** platform-wide triggers a "Lucky Shot" with a 50% chance to gain a significant edge (Dice re-roll, Dealer stands on 15, or Richer reels).
- **Divine Blessing**: A homepage-exclusive bonus system that rescues "Broke" players and rewards "High-Rollers" with 100-300 free credits (15-min cooldown).
- **Enhanced Security**: Double-confirmation flow on all game resets with automatic redirection to the homepage to prevent accidental balance loss.

### 🛡️ Professional UI/UX
- **Premium Aesthetics**: Glassmorphism modals, smooth CSS animations, and a curated dark-mode color palette.
- **Dynamic Ads**: Context-aware promotional banners with rotating slogans and an interactive captcha claim system for extra credits.

## Technical Architecture

- **Backend / API**: Persistent PHP logic with session-based and cookie-linked and anonymous state tracking.
- **Unified Storage**: Centralized `global_balance.json` for wait-free balance syncing across different game instances.
- **Self-Healing Layer**: Automated directory and file creation on first run to ensure 100% uptime and easy deployment.

## Recent Updates

### Mar 28, 2026
- **Global Wallet Migration**: Transitioned from per-game JSONs to a unified global storage architecture.
- **Blessing & Luck Engine**: Implemented the homepage Blessing modal and cross-game "Lucky 10th" bias logic.
- **Blackjack UI Refinement**: Restored card suit emojis and added red/white color coding for high visual clarity.
- **Reset Flow Overhaul**: Added a mandatory double-confirmation security step for all system resets.
- **Slot API Refactor**: Fixed sync issues with history and balance variables during the migration.

### Mar 27, 2026
- **Enhanced Platform Stability**: Implemented a "self-healing" JSON storage system that automatically generates the required directory structure and data files if they are missing.
- **Ad System Overhaul**: Improved ad targeting to be game-specific and added a dynamic carousel of persuasive, high-conversion slogans.
- **Security & Fair Play**: Added a mandatory 10-minute cooldown on ad credit claims and implemented a bet confirmation step for the Dice game to prevent accidental losses.
- **Asset Migration**: Reorganized the codebase by moving all client-side JavaScript assets into a dedicated `scripts/` directory.

### Mar 26, 2026
- **Bug Fixes**: Resolved various known bugs in the scoring and result evaluation logic across all three games.
- **Code Optimization**: Merged the latest development branch and optimized internal API response structures.

### Mar 24, 2026
- **Initial Documentation**: Created the first comprehensive project guide and early-stage core logic updates to the gambling engine.

---

