<h1>Collector Profile</h1>
<p class="subtitle">Manage your collector account and work details</p>

<div class="tabs">
  <div class="tab active">Profile</div>
  <div class="tab">Assignments</div>
  <div class="tab">Preferences</div>
  <div class="tab">Security</div>
</div>

<div class="card">
  <h2>Personal & Job Information</h2>
  <p>Update your contact information and current work details</p>

  <div class="profile-photo">
    <img src="https://via.placeholder.com/60" alt="Profile Photo">
    <div class="photo-buttons">
      <button>Change Photo</button>
      <button>Remove Photo</button>
    </div>
  </div>

  <form>
    <div>
      <label for="first-name">First Name</label>
      <input type="text" id="first-name" value="John">
    </div>
    <div>
      <label for="last-name">Last Name</label>
      <input type="text" id="last-name" value="Doe">
    </div>
    <div>
      <label for="email">Email Address</label>
      <input type="email" id="email" value="john.collector@example.com">
    </div>
    <div>
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" value="+1 (555) 987-6543">
    </div>
    <div>
      <label for="assigned-area">Assigned Area</label>
      <input type="text" id="assigned-area" value="Downtown Sector 5">
    </div>
    <div>
      <label for="daily-target">Daily Collection Target (kg)</label>
      <input type="number" id="daily-target" value="50">
    </div>
    <div>
      <label for="language">Preferred Language</label>
      <select id="language">
        <option selected>English</option>
        <option>Spanish</option>
        <option>French</option>
      </select>
    </div>
    <div>
      <label for="timezone">Timezone</label>
      <select id="timezone">
        <option selected>Pacific Standard Time</option>
        <option>Eastern Standard Time</option>
        <option>Central European Time</option>
      </select>
    </div>
    <div class="full-width">
      <button type="submit" class="save">Save Changes</button>
    </div>
  </form>
</div>
