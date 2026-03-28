/**
 * Shared utility for game action confirmations with a "Don't show again" option.
 * @param {string} title - The title of the confirmation dialog.
 * @param {string} html - The HTML content of the dialog.
 * @param {string} confirmBtnText - The text for the confirm button.
 * @returns {Promise<boolean>} - Resolves to true if confirmed or if suppression is active.
 */
async function confirmGameAction(title, html, confirmBtnText = 'Confirm') {
    const SETTINGS_KEY = 'game_confirmations';
    // Use filename (e.g., 'blackjack', 'dice', 'slot') as the key for per-game suppression
    const gameId = window.location.pathname.split('/').pop().replace('.php', '') || 'common';
    
    // Load all settings from localStorage
    let settings = {};
    try {
        const stored = localStorage.getItem(SETTINGS_KEY);
        settings = stored ? JSON.parse(stored) : {};
    } catch (e) {
        console.error('Error parsing game confirmations settings', e);
        settings = {};
    }

    const suppressionTime = settings[gameId];
    
    // If suppression is active and has not expired, auto-confirm
    if (suppressionTime && Date.now() < parseInt(suppressionTime)) {
        return true;
    }

    const result = await Swal.fire({
        title: title,
        html: html,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirmBtnText,
        confirmButtonColor: '#4ac47d',
        cancelButtonText: 'Cancel',
        footer: `
            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9em; opacity: 0.8;">
                <input type="checkbox" id="dontShowCheckbox" style="cursor: pointer;">
                <label for="dontShowCheckbox" style="cursor: pointer;">Don't want to see this in 10 minutes</label>
            </div>
        `,
        preConfirm: () => {
            // We return an object instead of a boolean.
            // Returning 'false' would prevent the modal from closing.
            return {
                dontShow: document.getElementById('dontShowCheckbox').checked
            };
        }
    });

    if (result.isConfirmed) {
        // If the user checked the "Don't show" box, save it to the JSON settings
        if (result.value && result.value.dontShow) {
            settings[gameId] = Date.now() + 10 * 60 * 1000;
            localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
        }
        return true; // Action confirmed
    }

    return false; // Action cancelled
}

