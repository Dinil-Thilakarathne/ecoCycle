<!-- Page Header -->
<page-header title="Settings" description="Manage your account and app preferences">
    <div data-header-action style="display: flex; gap: var(--space-2);">
        <button class="btn btn-primary" onclick="saveAllSettings()">
            <i class="fa-solid fa-floppy-disk"></i>
            Save All Changes
        </button>
    </div>
</page-header>

<main>

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
</main>

<script>
    function saveAllSettings() {
        // Gather all settings and save them
        console.log('Saving all settings...');
        alert('All settings have been saved successfully!');
        // In production, this would make an API call to save the settings
    }
</script>