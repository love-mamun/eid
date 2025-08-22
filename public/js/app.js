document.addEventListener('DOMContentLoaded', () => {
    // --- Element Selectors ---
    const authModal = document.getElementById('auth-modal');
    const loginBtn = document.getElementById('login-btn');
    const userProfileDiv = document.querySelector('.user-profile');
    const contentArea = document.querySelector('.content-area');

    // Auth Modal Elements
    const authCloseBtn = authModal.querySelector('.close-btn');
    const tabLinks = authModal.querySelectorAll('.tab-link');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    // Episode Modal Elements
    const episodeModal = document.getElementById('episode-modal');
    const episodeForm = document.getElementById('episode-form');
    const episodeModalCloseBtn = episodeModal.querySelector('.close-btn');

    // --- State ---
    let currentUser = null;

    // --- UI Update Functions ---
    function updateUIForLogin(user) {
        currentUser = user;
        userProfileDiv.innerHTML = `
            <span class="username">Welcome, ${user.username}</span>
            <button id="logout-btn" class="glow-button">Logout</button>
        `;
        if (user.role === 'admin') {
            setupAdminUI();
        }
        document.getElementById('logout-btn').addEventListener('click', handleLogout);
    }

    function updateUIForLogout() {
        currentUser = null;
        userProfileDiv.innerHTML = `<button id="login-btn" class="glow-button">Login</button>`;
        document.getElementById('login-btn').addEventListener('click', () => {
            authModal.style.display = 'flex';
        });
        const adminControls = document.getElementById('admin-controls');
        if(adminControls) adminControls.remove();
    }

    function setupAdminUI() {
        const adminControls = document.createElement('div');
        adminControls.id = 'admin-controls';
        adminControls.innerHTML = `<button id="add-episode-btn" class="glow-button">Add Episode</button>`;
        userProfileDiv.prepend(adminControls);

        document.getElementById('add-episode-btn').addEventListener('click', () => {
            episodeModal.style.display = 'flex';
        });
    }

    // --- Modal Control ---
    loginBtn.addEventListener('click', () => authModal.style.display = 'flex');
    authCloseBtn.addEventListener('click', () => authModal.style.display = 'none');
    episodeModalCloseBtn.addEventListener('click', () => episodeModal.style.display = 'none');
    window.addEventListener('click', (event) => {
        if (event.target === authModal) authModal.style.display = 'none';
        if (event.target === episodeModal) episodeModal.style.display = 'none';
    });

    // --- Tab Switching ---
    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.getAttribute('data-tab');
            tabLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            authModal.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        });
    });

    // --- API Handlers ---
    async function handleLogout() {
        await fetch('/api/v1/users/logout', { method: 'POST' });
        updateUIForLogout();
    }

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
            document.querySelector('.tab-link[data-tab="login-tab"]').click();
        } else {
            alert(`Error: ${result.error}`);
        }
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
            alert('Login successful!');
            authModal.style.display = 'none';
            updateUIForLogin(result.user);
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
            loadEpisodes(); // Refresh the episode list
        } else {
            alert(`Error: ${result.error}`);
        }
    });

    // --- Episode Loading ---
    function displayEpisodes(episodes) {
        contentArea.innerHTML = '';
        if (episodes.length === 0) {
            contentArea.innerHTML = '<p class="placeholder-text">No episodes available.</p>';
            return;
        }
        episodes.forEach(episode => {
            const panel = document.createElement('div');
            panel.className = 'episode-panel';
            panel.innerHTML = `
                <div class="panel-header">
                    <h3 class="panel-title">${episode.title}</h3>
                    <span class="panel-author">by ${episode.author_name}</span>
                </div>
                <p class="panel-description">${episode.description}</p>
                <div class="panel-footer">
                    <span class="duration">Duration: ${Math.floor(episode.duration / 60)} min</span>
                    <button class="play-button">Play</button>
                </div>
            `;
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

    // Initial load
    loadEpisodes();
});
