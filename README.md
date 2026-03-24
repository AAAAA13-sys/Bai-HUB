Here’s your **rewritten implementation plan in clean Markdown**, aligned exactly with your updated requirements:

---

# 🛠️ Implementation Plan & Diagnosis Report (Revised)

## 📌 Project Direction Changes

* ❌ No database (MySQL / SQLite not needed)
* ✅ Use **PHP sessions + JSON file storage (per game)**
* ✅ Keep **separate game balances (not shared credits)**
* ✅ Modular structure:

  * `blackjack.php` + `blackjack-script.js`
  * `dice.php` + `dice-script.js`
  * `slot.php` + `slot-script.js`
* ✅ Shared global styles in one CSS file
* ✅ Clear, transparent **winning probabilities shown in UI**

---

# 🔍 Diagnosis of Current Issues

## 1. Blackjack ALL-IN Bug

**Root Cause:**

* `number_format($score, 2)` adds commas (e.g., `1,000.00`)
* JavaScript `parseFloat("1,000.00")` → returns `1`

**Fix:**

```php
number_format($score, 2, '.', '')
```

---

## 2. Blackjack History Not Showing

**Root Cause:**

* PHP uses:

```html
id="pendingHistoryItem"
```

* JS expects:

```js
.history-item.pending-animation
```

**Fix:**

* Standardize to:

```html
class="history-item pending-animation"
```

---

## 3. Notification Delay (All Games)

**Root Cause:**

* POST form submission reloads page
* Notifications stored in `$_SESSION`

**Fix:**

* Replace with **Fetch API (AJAX)**
* Return JSON responses instantly

---

## 4. Missing Home Icon

**Root Cause:**

* Path mismatch

**Fix:**

* Correct path:

```html
<img src="img/icons/Home-button.svg" alt="Home">
```

---

## 5. Data Persistence

**Old Behavior:**

* Stored only in `$_SESSION`

**New Plan:**

* Use:

  * `$_SESSION` (runtime)
  * JSON files (persistent)

---

# 🧱 New Architecture

## 📁 File Structure

```
testing/
│
├── blackjack.php
├── blackjack-script.js
├── dice.php
├── dice-script.js
├── slot.php
├── slot-script.js
│
├── style.css
│
├── data/
│   ├── blackjack.json
│   ├── dice.json
│   └── slot.json
│
├── api/
│   ├── blackjack_api.php
│   ├── dice_api.php
│   └── slot_api.php
│
└── img/
    └── icons/
        └── home.svg
```

---

# 🔁 Refactoring Plan

## 1. JavaScript Separation

Each game will have its own script:

* `blackjack-script.js`
* `dice-script.js`
* `slot-script.js`

### Responsibilities:

* Handle UI updates
* Send Fetch requests
* Render results instantly
* Handle animations + history

---

## 2. API Layer (PHP)

Each game gets its own endpoint:

* `blackjack_api.php`
* `dice_api.php`
* `slot_api.php`

### Responsibilities:

* Process bets
* Calculate results
* Update JSON storage
* Return structured JSON

Example response:

```json
{
  "result": "win",
  "balance": 1250,
  "message": "You won!",
  "history": [...]
}
```

---

## 3. CSS Structure

Single file: `style.css`

### Sections:

* Variables (`:root`)
* Layout
* Game cards
* Animations
* Buttons
* History UI

---

# 💾 Data Storage (JSON-Based)

## Per Game Storage

Each game has its own file:

* `blackjack.json`
* `dice.json`
* `slot.json`

### Example Structure:

```json
{
  "user_id_123": {
    "balance": 1000,
    "history": []
  }
}
```

---

## 🧠 User Identification

* Use **cookie-based user_id**
* Persist across sessions
* No login system required

---

# 🎰 Game Fairness & Transparency

## Slot Machine (Updated Odds Display)

You will clearly display odds like:

* **Grape x3** → 1 in 10 spins
* **Wild Rules:**

  * 1–2 wilds substitute missing grapes
  * Example:

    * 🍇🍇⭐ → counts as 3 grapes
    * 🍇⭐⭐ → also counts

### Result:

* Higher perceived fairness
* More wins through wild mechanics

---

## 🎲 Dice & 🃏 Blackjack

* Show:

  * Win probability
  * House edge (if applicable)

---

# ⚙️ Implementation Steps

## Phase 1 – Bug Fixes

* Fix `number_format`
* Fix history class mismatch
* Fix home icon path

---

## Phase 2 – Structure Refactor

* Split JS per game
* Create API endpoints
* Replace POST with Fetch

---

## Phase 3 – JSON Persistence

* Implement read/write helpers
* Create per-game JSON files
* Add user_id cookie system

---

## Phase 4 – UI Improvements

* Show win probabilities
* Improve animations
* Instant notifications

---

## Phase 5 – Testing

### Manual Tests

#### ✅ ALL-IN Fix

* Reach >1000 balance
* Click ALL-IN
* Verify correct bet amount

#### ✅ AJAX Flow

* No page reloads
* Instant SweetAlert popups

#### ✅ Persistence

* Refresh browser
* Data remains

#### ✅ Slot Fairness

* Wild substitutions work correctly

---

# ⚠️ Notes

* JSON is lightweight but:

  * Not ideal for high concurrency
  * Acceptable for this project scale

* Sessions still used for:

  * Temporary state
  * Faster access

---

# ✅ Final Outcome

* ✔ No database dependency
* ✔ Clean modular structure
* ✔ Persistent per-game data
* ✔ Transparent odds
* ✔ Smooth UI (no reloads)
* ✔ Fixed bugs across all games

---

also please fix the script.js it has 821 lines of problem