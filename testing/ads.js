// ads.js
document.addEventListener('DOMContentLoaded', () => {
    const games = [
        { id: 'blackjack', name: 'Blackjack', icon: '🃏', subtext: 'Beat the dealer!', color: '#4ac47d' },
        { id: 'dice', name: 'Dice Betting', icon: '🎲', subtext: 'Roll for 10x wins!', color: '#ffd700' },
        { id: 'slot', name: 'Slot Machine', icon: '🎰', subtext: 'Hit the Jackpot!', color: '#ff41ff' }
    ];

    const currentPath = window.location.pathname.split('/').pop().replace('.php', '') || 'index';

    const getOtherGames = () => {
        const otherGames = games.filter(g => g.id !== currentPath);
        const shuffled = otherGames.sort(() => 0.5 - Math.random());
        const selected = shuffled.slice(0, 2);
        
        while (selected.length < 2) {
            const remaining = games.filter(g => !selected.find(sg => sg.id === g.id));
            selected.push(remaining[Math.floor(Math.random() * remaining.length)]);
        }
        return selected;
    };

    const createAdHtml = (game, side) => `
        <div class="fake-ad" id="${side}Ad" data-game="${game.id}">
            <div class="fake-ad-icon">${game.icon}</div>
            <div class="fake-ad-text">PLAY ${game.name.toUpperCase()}</div>
            <div class="fake-ad-subtext">${game.subtext}</div>
            <div class="fake-ad-button" style="background: linear-gradient(135deg, ${game.color} 0%, #3a9d62 100%)">
                GET FREE CREDITS
            </div>
        </div>
    `;

    // Initial container setup
    document.body.insertAdjacentHTML('beforeend', `
        <div class="fake-ad-container left" id="leftAdContainer"></div>
        <div class="fake-ad-container right" id="rightAdContainer"></div>
    `);

    const updateAds = () => {
        const selected = getOtherGames();
        const leftContainer = document.getElementById('leftAdContainer');
        const rightContainer = document.getElementById('rightAdContainer');

        const refreshContainer = (container, game, side) => {
            const oldAd = container.querySelector('.fake-ad');
            if (oldAd) {
                oldAd.classList.add('fade-out');
                setTimeout(() => {
                    container.innerHTML = createAdHtml(game, side);
                    container.querySelector('.fake-ad').addEventListener('click', showCaptcha);
                }, 500);
            } else {
                container.innerHTML = createAdHtml(game, side);
                container.querySelector('.fake-ad').addEventListener('click', showCaptcha);
            }
        };

        refreshContainer(leftContainer, selected[0], 'left');
        refreshContainer(rightContainer, selected[1], 'right');
    };

    const showCaptcha = async (e) => {
        const adElement = e.currentTarget;
        const targetGame = adElement.getAttribute('data-game');
        
        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const sum = num1 + num2;
        
        const { value: answer } = await Swal.fire({
            title: 'Claim Your Bonus!',
            html: `
                <div style="margin-bottom: 20px;">
                    <p>Prove you're human to get <strong>100-500</strong> bonus credits!</p>
                    <p style="font-size: 1.2rem; color: #4ac47d; font-weight: bold;">What is ${num1} + ${num2}?</p>
                </div>
            `,
            input: 'text',
            inputPlaceholder: 'Enter the sum',
            showCancelButton: true,
            confirmButtonColor: '#4ac47d',
            cancelButtonColor: '#e64545',
            inputValidator: (value) => {
                if (!value) return 'Please enter the answer!';
                if (parseInt(value) !== sum) return 'Incorrect answer! Try again.';
            }
        });
        
        if (answer) {
            try {
                const gameToCredit = (currentPath === 'index' || !currentPath) ? 'blackjack' : currentPath;
                const response = await fetch('api/add_credits.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ game: gameToCredit })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Bonus Claimed!',
                        text: `${data.creditsAdded} credits added to your ${gameToCredit} balance!`,
                        confirmButtonColor: '#4ac47d'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Something went wrong', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to add credits', 'error');
            }
        }
    };

    updateAds();

    // Change ads every 15-20 seconds
    const startRotation = () => {
        const delay = Math.floor(Math.random() * 5000) + 15000;
        setTimeout(() => {
            updateAds();
            startRotation();
        }, delay);
    };

    startRotation();
});
