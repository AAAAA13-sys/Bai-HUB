/**
 * Shared utility for game action confirmations with a "Don't show again" option.
 * @param {string} title - The title of the confirmation dialog.
 * @param {string} html - The HTML content of the dialog.
 * @param {string} confirmBtnText - The text for the confirm button.
 * @returns {Promise<boolean>} - Resolves to true if confirmed or if suppression is active.
 */
async function confirmGameAction(title, html, confirmBtnText = 'Confirm') {
    const HIDE_KEY = 'hideConfirmUntil';
    const suppressionTime = localStorage.getItem(HIDE_KEY);
    
    if (suppressionTime && Date.now() < parseInt(suppressionTime)) {
        return true;
    }

    const { value: result } = await Swal.fire({
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
            return document.getElementById('dontShowCheckbox').checked;
        }
    });

    if (result !== undefined) {
        // result is true if checkbox was checked, false otherwise
        if (result === true) {
            localStorage.setItem(HIDE_KEY, Date.now() + 10 * 60 * 1000);
        }
        return true; // Action confirmed
    }

    return false; // Action cancelled
}
