  <!-- Profile Card -->
  <div class="feature-card">
    <div>
      <h2>Personal & Job Information</h2>
      <p>Update your contact information and current work details</p>
    </div>

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
      <div>
        <label for="daily-target">Daily Collection Target (kg)</label>
        <input type="number" id="daily-target" value="50">
      </div>
      <div>
        <label for="language">Preferred Language</label>
        <select id="language">
          <option selected>English</option>
          <option>Sinhala</option>
          <option>Tamil</option>
        </select>
      </div>
      <div class="full-width">
        <button type="submit" class="save">Save Changes</button>
      </div>
    </form>
  </div>

</body>
</html>
