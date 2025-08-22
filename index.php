<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holographic Podcast & Live Radio</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <div class="background-animation"></div>
    <div class="main-container">
        <header class="main-header">
            <div class="logo">HOLO-CAST</div>
            <nav class="main-nav">
                <a href="#" class="nav-item active">Home</a>
                <a href="#" class="nav-item">Explore</a>
                <a href="#" class="nav-item">Favorites</a>
                <a href="#" class="nav-item">History</a>
            </nav>
            <div class="user-profile">
                <button id="login-btn" class="glow-button">Login</button>
            </div>
        </header>

        <main class="content-area">
            <!-- Content will be loaded here dynamically -->
        </main>

        <!-- Auth Modal -->
        <div id="auth-modal" class="modal-container" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="tab-links">
                        <button class="tab-link active" data-tab="login-tab">Login</button>
                        <button class="tab-link" data-tab="register-tab">Register</button>
                    </div>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Login Tab -->
                    <div id="login-tab" class="tab-content active">
                        <form id="login-form">
                            <input type="text" name="login" placeholder="Username or Email" required>
                            <input type="password" name="password" placeholder="Password" required>
                            <button type="submit" class="glow-button">Login</button>
                        </form>
                    </div>
                    <!-- Register Tab -->
                    <div id="register-tab" class="tab-content">
                        <form id="register-form">
                            <input type="text" name="username" placeholder="Username" required>
                            <input type="email" name="email" placeholder="Email" required>
                            <input type="password" name="password" placeholder="Password" required>
                            <button type="submit" class="glow-button">Register</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-play-bar">
            <div class="episode-info">
                <img src="public/img/episode-thumbnail.png" alt="Episode Thumbnail" class="thumbnail">
                <div class="details">
                    <div class="title">Episode Title</div>
                    <div class="artist">Artist Name</div>
                </div>
            </div>
            <div class="player-controls">
                <!-- Player controls will go here -->
            </div>
            <div class="waveform-visualizer">
                <!-- 3D waveform visualizer will go here -->
            </div>
        </div>
    </div>
    <script src="public/js/app.js"></script>
</body>
</html>
