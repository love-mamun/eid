document.addEventListener('DOMContentLoaded', () => {
    // --- Element Selectors ---
    const userProfileDiv = document.querySelector('.user-profile');
    const contentArea = document.querySelector('.content-area');

    // Modals
    const authModal = document.getElementById('auth-modal');
    const episodeModal = document.getElementById('episode-modal');

    // Forms
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const episodeForm = document.getElementById('episode-form');

    // Player Controls
    const playPauseBtn = document.getElementById('play-pause-btn');
    const rewindBtn = document.getElementById('rewind-btn');
    const forwardBtn = document.getElementById('forward-btn');
    const progressBar = document.getElementById('progress-bar');
    const volumeSlider = document.getElementById('volume-slider');
    const currentTimeEl = document.getElementById('current-time');
    const durationTimeEl = document.getElementById('duration-time');
    const playerEpisodeTitle = document.querySelector('.quick-play-bar .episode-info .details .title');
    const playerEpisodeArtist = document.querySelector('.quick-play-bar .episode-info .details .artist');

    // Canvas for Waveform
    const canvas = document.getElementById('waveform-canvas');
    const canvasCtx = canvas.getContext('2d');

    // --- State ---
    let currentUser = null;
    let episodesCache = [];
    let userFavorites = new Set();
    const audio = new Audio();
    let animationFrameId;

    // --- Waveform Animation ---
    function renderWaveform() {
        const width = canvas.width;
        const height = canvas.height;
        const time = Date.now() * 0.005; // Time component for animation

        canvasCtx.clearRect(0, 0, width, height);
        canvasCtx.lineWidth = 2;
        canvasCtx.strokeStyle = 'rgba(0, 255, 255, 0.7)';
        canvasCtx.beginPath();

        const amplitude = audio.paused ? 0 : height / 4; // Wave amplitude
        const frequency = 0.05; // Wave frequency
        const sliceWidth = width / 200; // Draw 200 segments

        for (let i = 0; i < 200; i++) {
            const x = i * sliceWidth;
            const y = height / 2 + Math.sin(i * frequency + time) * amplitude * (1 - i / 200);
            if (i === 0) {
                canvasCtx.moveTo(x, y);
            } else {
                canvasCtx.lineTo(x, y);
            }
        }
        canvasCtx.stroke();
        animationFrameId = requestAnimationFrame(renderWaveform);
    }

    function stopWaveform() {
        cancelAnimationFrame(animationFrameId);
        // Draw a flat line when stopped
        canvasCtx.clearRect(0, 0, canvas.width, canvas.height);
        canvasCtx.lineWidth = 2;
        canvasCtx.strokeStyle = 'rgba(0, 255, 255, 0.3)';
        canvasCtx.beginPath();
        canvasCtx.moveTo(0, canvas.height / 2);
        canvasCtx.lineTo(canvas.width, canvas.height / 2);
        canvasCtx.stroke();
    }


    // --- Player Logic ---
    function playAudio() {
        audio.play();
        playPauseBtn.textContent = 'Pause';
        playPauseBtn.classList.remove('play');
        renderWaveform();
    }

    function pauseAudio() {
        audio.pause();
        playPauseBtn.textContent = 'Play';
        playPauseBtn.classList.add('play');
        stopWaveform();
    }

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
    }

    function loadTrack(episode) {
        playerEpisodeTitle.textContent = episode.title;
        playerEpisodeArtist.textContent = episode.author_name;
        audio.src = episode.file_path;

        audio.addEventListener('loadedmetadata', () => {
            progressBar.max = audio.duration;
            durationTimeEl.textContent = formatTime(audio.duration);
        });

        playAudio();
    }

    playPauseBtn.addEventListener('click', () => {
        if (!audio.src) return;
        if (audio.paused) playAudio();
        else pauseAudio();
    });

    rewindBtn.addEventListener('click', () => audio.currentTime -= 10);
    forwardBtn.addEventListener('click', () => audio.currentTime += 30);
    volumeSlider.addEventListener('input', (e) => audio.volume = e.target.value / 100);
    progressBar.addEventListener('input', (e) => audio.currentTime = e.target.value);
    audio.addEventListener('timeupdate', () => {
        progressBar.value = audio.currentTime;
        currentTimeEl.textContent = formatTime(audio.currentTime);
    });
    audio.addEventListener('ended', pauseAudio);


    // --- UI Update & Event Handlers ---
    async function loadFavorites() {
        if (!currentUser) return;
        try {
            const response = await fetch('/api/v1/favorites');
            const favorites = await response.json();
            userFavorites = new Set(favorites.map(fav => fav.id));
            // We need to re-render episodes to show the correct favorite status
            displayEpisodes(episodesCache);
        } catch (error) {
            console.error('Failed to load favorites:', error);
        }
    }

    async function handleFavoriteToggle(episodeId, button) {
        if (!currentUser) {
            alert('Please log in to favorite episodes.');
            return;
        }
        try {
            const response = await fetch(`/api/v1/episodes/${episodeId}/favorite`, { method: 'POST' });
            const result = await response.json();
            if (response.ok) {
                if (result.status === 'added') {
                    userFavorites.add(episodeId);
                    button.classList.add('is-favorite');
                } else {
                    userFavorites.delete(episodeId);
                    button.classList.remove('is-favorite');
                }
            }
        } catch (error) {
            console.error('Failed to toggle favorite:', error);
        }
    }

    function updateUIForLogin(user) {
        currentUser = user;
        userProfileDiv.innerHTML = `<span class="username">Welcome, ${user.username}</span><button id="logout-btn" class="glow-button">Logout</button>`;
        if (user.role === 'admin') setupAdminUI();
        document.getElementById('logout-btn').addEventListener('click', handleLogout);
        loadFavorites();
    }

    function updateUIForLogout() {
        currentUser = null;
        userFavorites.clear();
        userProfileDiv.innerHTML = `<button id="login-btn" class="glow-button">Login</button>`;
        document.getElementById('login-btn').addEventListener('click', () => authModal.style.display = 'flex');
        const adminControls = document.getElementById('admin-controls');
        if (adminControls) adminControls.remove();
        // Re-render episodes to remove favorite statuses
        displayEpisodes(episodesCache);
    }

    function setupAdminUI() {
        const adminControls = document.createElement('div');
        adminControls.id = 'admin-controls';
        adminControls.innerHTML = `<button id="add-episode-btn" class="glow-button">Add Episode</button>`;
        userProfileDiv.prepend(adminControls);
        document.getElementById('add-episode-btn').addEventListener('click', () => episodeModal.style.display = 'flex');
    }

    // --- Episode Loading ---
    function displayEpisodes(episodes) {
        contentArea.innerHTML = '';
        if (!episodes || episodes.length === 0) {
            contentArea.innerHTML = '<p class="placeholder-text">No episodes available.</p>';
            return;
        }

        episodesCache = episodes;
        episodes.forEach(episode => {
            const isFavorite = userFavorites.has(episode.id);
            const panel = document.createElement('div');
            panel.className = 'episode-panel';
            panel.innerHTML = `
                <div class="panel-header">
                    <h3 class="panel-title">${episode.title}</h3>
                    <span class="panel-author">by ${episode.author_name}</span>
                </div>
                <p class="panel-description">${episode.description || ''}</p>
                <div class="panel-footer">
                    <div class="actions">
                        <button class="play-button" title="Play">▶</button>
                        <button class="favorite-btn ${isFavorite ? 'is-favorite' : ''}" title="Favorite">♥</button>
                        <button class="add-to-playlist-btn" title="Add to playlist">+</button>
                    </div>
                    <span class="duration">Duration: ${Math.floor(episode.duration / 60)} min</span>
                </div>
            `;
            panel.querySelector('.play-button').addEventListener('click', () => loadTrack(episode));
            panel.querySelector('.favorite-btn').addEventListener('click', (e) => handleFavoriteToggle(episode.id, e.target));
            panel.querySelector('.add-to-playlist-btn').addEventListener('click', () => alert('Playlist functionality coming soon!'));
            contentArea.appendChild(panel);
        });
    }

    async function loadEpisodes() {
        try {
            const response = await fetch('/api/v1/episodes');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const episodes = await response.json();
            displayEpisodes(episodes);
        } catch (error) {
            console.error('Failed to load episodes:', error);
            contentArea.innerHTML = '<p class="placeholder-text">Failed to load episodes.</p>';
        }
    }

    // --- Initial & Event Setup ---
    document.getElementById('login-btn').addEventListener('click', () => authModal.style.display = 'flex');
    authModal.querySelector('.close-btn').addEventListener('click', () => authModal.style.display = 'none');
    episodeModal.querySelector('.close-btn').addEventListener('click', () => episodeModal.style.display = 'none');
    window.addEventListener('click', (event) => {
        if (event.target === authModal) authModal.style.display = 'none';
        if (event.target === episodeModal) episodeModal.style.display = 'none';
    });

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        const data = Object.fromEntries(formData.entries());
        const response = await fetch('/api/v1/users/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const result = await response.json();
        if (response.ok) {
            authModal.style.display = 'none';
            updateUIForLogin(result.user);
        } else {
            alert(`Error: ${result.error}`);
        }
    });

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(registerForm);
        const data = Object.fromEntries(formData.entries());
        const response = await fetch('/api/v1/users/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const result = await response.json();
        if (response.ok) {
            alert('Registration successful! Please log in.');
            authModal.querySelector('.tab-link[data-tab="login-tab"]').click();
        } else {
            alert(`Error: ${result.error}`);
        }
    });

    episodeForm.addEventListener('submit', async(e) => {
        e.preventDefault();
        const formData = new FormData(episodeForm);
        const data = Object.fromEntries(formData.entries());
        const response = await fetch('/api/v1/episodes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const result = await response.json();
        if (response.ok) {
            alert('Episode saved successfully!');
            episodeModal.style.display = 'none';
            episodeForm.reset();
            loadEpisodes();
        } else {
            alert(`Error: ${result.error}`);
        }
    });

    loadEpisodes();
    stopWaveform(); // Initial call to draw the flat line
});
