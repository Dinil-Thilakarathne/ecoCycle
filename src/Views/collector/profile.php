  <!-- Profile Card 
  <div class="profile-card">
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
        <input type="text" id="first-name" required pattern="[A-Za-z\s\-]+" minlength="2" maxlength="50" value="John">
      </div>
      <div>
        <label for="last-name">Last Name</label>
        <input type="text" id="last-name" required pattern="[A-Za-z\s\-]+" minlength="2" maxlength="50" value="Doe">
      </div>
      <div>
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required placeholder="name@example.com" value="john.collector@example.com">
      </div>
      <div>
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" required pattern="[\d\+\-\(\)\s]+" maxlength="13" value="+94 763079441" >
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
</html>-->

<?php
// Example: Collector profile data (usually from DB)
$collector = [
    "first_name" => "John",
    "last_name" => "Doe",
    "email" => "john.collector@example.com",
    "phone" => "+94 763079441",
    "address" => "45 Green Street, Colombo, Sri Lanka",
    "daily_target" => 50,
    "language" => "English",
     "bank" => [
        "bank_name" => "Commercial Bank",
        "account_number" => "1234567890",
        "branch" => "Colombo Main",
        "ifsc_code" => "COMB12345"
    ],
    "verification" => [
        "Email Verified" => true,
        "Phone Verified" => true,
        "ID Proof" => true,
        "Background Check" => false
    ]
];

$showToast = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $collector["first_name"] = $_POST["first_name"];
    $collector["last_name"] = $_POST["last_name"];
    $collector["email"] = $_POST["email"];
    $collector["phone"] = $_POST["phone"];
    $collector["address"] = $_POST["address"];
    $collector["daily_target"] = $_POST["daily_target"];
    $collector["language"] = $_POST["language"];

    $collector["bank"]["bank_name"] = $_POST["bank_name"];
    $collector["bank"]["account_number"] = $_POST["account_number"];
    $collector["bank"]["branch"] = $_POST["branch"];
    $collector["bank"]["ifsc_code"] = $_POST["ifsc_code"];

    $showToast = true;
}
?>

<main class="content">

     <header class="page-header">
            <div class="page-header__content">
                    <h2 class="page-header__title">Collector Profile</h2>
                    <p class="page-header__description">Manage and update your profile details here!</p>
            </div>
              <!-- Edit Button -->
                <a href="#editModal" class="edit-btn">✏️ Edit Profile</a>

    </header>

    <!-- Edit Button 
    <a href="#editModal" class="edit-btn">✏️ Edit Profile</a>-->
    
    <!-- Profile Image Section -->
<div class="profile-card">
  <div class="profile-image">
    <img src="collector.jpg" alt="Collector Profile">
  </div>
  <div class="profile-details">
    <h2><?= htmlspecialchars($collector['first_name'] . " " . $collector['last_name']) ?></h2>
    <p><?= htmlspecialchars($collector['email']) ?></p>
    <p><?= htmlspecialchars($collector['phone']) ?></p>
  </div>
</div>

    <div class="p-info-card">
      <!-- Personal Info -->
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Personal Information</h3>
        <div class="form-group"><label>First Name</label>
          <input type="text" value="<?= htmlspecialchars($collector['first_name']) ?>" disabled>
        </div>
        <div class="form-group"><label>Last Name</label>
          <input type="text" value="<?= htmlspecialchars($collector['last_name']) ?>" disabled>
        </div>
        <div class="form-group"><label>Address</label>
          <textarea disabled><?= htmlspecialchars($collector['address']) ?></textarea>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Contact Information</h3>
        <div class="form-group"><label>Email</label>
          <input type="text" value="<?= htmlspecialchars($collector['email']) ?>" disabled>
        </div>
        <div class="form-group"><label>Phone</label>
          <input type="text" value="<?= htmlspecialchars($collector['phone']) ?>" disabled>
        </div>
        <div class="form-group"><label>Preferred Language</label>
          <input type="text" value="<?= htmlspecialchars($collector['language']) ?>" disabled>
        </div>
      </div>
    </div>

     <!-- Bank Details -->
<div class="pc-card">
  <h3 style="font-size: 20px; font-weight: bold;">Bank Details</h3>
  <div class="form-group"><label>Bank Name</label>
    <input type="text" value="<?= htmlspecialchars($collector['bank']['bank_name']) ?>" disabled>
  </div>
  <div class="form-group"><label>Account Number</label>
    <input type="text" value="<?= htmlspecialchars($collector['bank']['account_number']) ?>" disabled>
  </div>
  <div class="form-group"><label>Branch</label>
    <input type="text" value="<?= htmlspecialchars($collector['bank']['branch']) ?>" disabled>
  </div>
  <div class="form-group"><label>IFSC Code</label>
    <input type="text" value="<?= htmlspecialchars($collector['bank']['ifsc_code']) ?>" disabled>
  </div>
</div>

<div class="form-group"><label>Confirm Password</label>
  <input type="password" name="confirm_password" required>
</div>
<?php 
  $maskedAcc = str_repeat("X", strlen($collector['bank']['account_number']) - 4) 
               . substr($collector['bank']['account_number'], -4);
?>
<input type="text" value="<?= htmlspecialchars($maskedAcc) ?>" disabled>



    <!-- Work Info 
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Work Information</h3>
      <div class="form-group"><label>Daily Collection Target (kg)</label>
       <input type="text" value="<?= htmlspecialchars($collector['daily_target']) ?>" disabled>
      </div>
    </div>-->

    <!-- Verification 
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Verification Status</h3>
      <ul>
        <?php foreach($collector['verification'] as $item => $status): ?>
          <li><?= $item ?>:
            <span class="<?= $status ? 'verified' : 'not-verified' ?>">
              <?= $status ? 'Verified' : 'Not Verified' ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>-->

    <!-- Security -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
      <p><button class="p-btn">Change Password</button></p>
      <!--<p><button class="p-btn">Two-Factor Authentication</button></p>-->
      <p><button class="p-btn-delete">Delete Account</button></p>
    </div>
</main>



<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2>Edit Profile</h2>
    <form method="POST">
      <div class="form-group"><label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($collector['first_name']) ?>"></div>
      <div class="form-group"><label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($collector['last_name']) ?>"></div>
      <div class="form-group"><label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($collector['email']) ?>"></div>
      <div class="form-group"><label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($collector['phone']) ?>"></div>
      <div class="form-group"><label>Address</label>
        <textarea name="address"><?= htmlspecialchars($collector['address']) ?></textarea></div>
      <div class="form-group"><label>Daily Target (kg)</label>
        <input type="number" name="daily_target" value="<?= htmlspecialchars($collector['daily_target']) ?>"></div>
      <div class="form-group"><label>Preferred Language</label>
        <select name="language">
          <option <?= $collector['language']=="English" ? "selected" : "" ?>>English</option>
          <option <?= $collector['language']=="Sinhala" ? "selected" : "" ?>>Sinhala</option>
          <option <?= $collector['language']=="Tamil" ? "selected" : "" ?>>Tamil</option>
        </select></div>
      <button type="submit" class="p-submit">Save Changes</button>
    </form>
  </div>
</div>

<!-- Toast Notification -->
<?php if ($showToast): ?>
<div class="toast">✅ Profile updated successfully!</div>
<?php endif; ?>



