<header>
    <div class="breadcrumb">
        Dashboard &gt; <span>Settings</span>
    </div>
</header>

<main>
    <h1>Settings</h1>
    <p class="subtitle">Manage your account and app preferences</p>

    <!-- Account Settings -->
    <div class="settings-section">
        <h2>Account</h2>
        <div class="setting-item">
            <label>Full Name</label>
            <input type="text" value="Alex Johnson">
        </div>
        <div class="setting-item">
            <label>Email Address</label>
            <input type="email" value="alex@example.com">
        </div>
        <div class="setting-item">
            <label>Password</label>
            <input type="password" value="password123">
        </div>
        <button class="save-btn">Save Changes</button>
    </div>

    <!-- Preferences -->
    <div class="settings-section">
        <h2>Preferences</h2>
        <div class="setting-item">
            <label>Language</label>
            <select>
                <option>English</option>
                <option>Spanish</option>
                <option>French</option>
            </select>
        </div>
        <div class="setting-item">
            <label>Dark Mode</label>
            <div class="toggle">
                <input type="checkbox" id="darkMode">
                <span class="slider"></span>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="settings-section">
        <h2>Notifications</h2>
        <div class="setting-item">
            <label>Email Notifications</label>
            <div class="toggle">
                <input type="checkbox" checked id="emailNotif">
                <span class="slider"></span>
            </div>
        </div>
        <div class="setting-item">
            <label>Push Notifications</label>
            <div class="toggle">
                <input type="checkbox" id="pushNotif">
                <span class="slider"></span>
            </div>
        </div>
    </div>