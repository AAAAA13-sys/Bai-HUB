// blessing.js
document.addEventListener('DOMContentLoaded', () => {
    // Only run on index.php
    if (!window.location.pathname.endsWith('index.php') && !window.location.pathname.endsWith('/')) {
        // Allow it on the root path too if it's the index
        if (window.location.pathname.split('/').pop() !== '') return;
    }

    const checkBlessing = async () => {
        try {
            const response = await fetch('api/blessing_api.php?action=check');
            const data = await response.json();

            if (data.eligible) {
                showBlessingCard(data.reason);
            }
        } catch (err) {
            console.error('Error checking blessing eligibility:', err);
        }
    };

    const showBlessingCard = (reason) => {
        let title = "A DIVINE BLESSING";
        let text = "The universe smiles upon you! Claim your free credits now.";
        let icon = "✨";

        if (reason === 'broke') {
            title = "CASINO RESCUE";
            text = "Down on your luck? Here's a boost to get you back in the game!";
            icon = "🙏";
        } else if (reason === 'high_roller') {
            title = "LOYALTY REWARD";
            text = "A true High Roller deserves a tribute. Accept this gift!";
            icon = "👑";
        } else if (reason === 'fresh') {
            title = "WELCOME GIFT";
            text = "Welcome to Bai-HUB! Start your journey with some extra luck.";
            icon = "🎁";
        }

        Swal.fire({
            title: '',
            html: `
                <div class="blessing-card">
                    <div class="blessing-icon">${icon}</div>
                    <div class="blessing-title">${title}</div>
                    <p class="blessing-text">${text}</p>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'CLAIM BLESSING',
            customClass: {
                popup: 'blessing-modal-content',
                confirmButton: 'blessing-button'
            },
            allowOutsideClick: false,
            backdrop: `rgba(0,0,0,0.8)`
        }).then((result) => {
            if (result.isConfirmed) {
                claimBlessing();
            }
        });
    };

    const claimBlessing = async () => {
        try {
            const response = await fetch('api/blessing_api.php?action=claim');
            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'BLESSED!',
                    text: `You have received ${data.amount} credits. May luck be with you!`,
                    confirmButtonColor: '#4ac47d'
                }).then(() => {
                    window.location.reload();
                });
            }
        } catch (err) {
            Swal.fire('Error', 'The blessing was interrupted. Try again soon.', 'error');
        }
    };

    // Initial check
    setTimeout(checkBlessing, 1000);
});
