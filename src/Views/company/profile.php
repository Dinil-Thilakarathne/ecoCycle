<?php
// Example: Profile data (usually from DB)
$company = [
    "name" => "EcoWaste Company",
    "type" => "Waste Management",
    "reg_number" => "REG-2023-001234",
    "description" => "Leading waste management company specializing in recyclable materials collection and processing.",
    "email" => "contact@ecowaste.com",
    "phone" => "011 2256845",
    "website" => "www.ecowaste.com",
    "address" => "123 Green Street, ABC District, XY City",
    "verification" => [
        "Email Verified" => true,
        "Phone Verified" => true,
        "Business License" => true
    ]
];

$showToast = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $company["name"] = $_POST["name"];
    $company["type"] = $_POST["type"];
    $company["reg_number"] = $_POST["reg_number"];
    $company["description"] = $_POST["description"];
    $company["email"] = $_POST["email"];
    $company["phone"] = $_POST["phone"];
    $company["website"] = $_POST["website"];
    $company["address"] = $_POST["address"];
    $showToast = true;
}
?>

<main class="content">

     <header class="page-header">
            <div class="page-header__content">
                    <h2 class="page-header__title">Company Profile</h2>
                    <p class="page-header__description">Update your profile here!</p>
            </div>
    </header>

    <!-- Company Info -->
  
    <a href="#editModal" class="edit-btn">✏️ Edit Profile</a>
    
    <div class=p-info-card>
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Company Information</h3>
        <div class="form-group"><label>Name</label>
          <input type="text" value="<?= htmlspecialchars($company['name']) ?>" disabled>
        </div>
        <div class="form-group"><label>Type</label>
          <input type="text" value="<?= htmlspecialchars($company['type']) ?>" disabled>
        </div>
        <div class="form-group"><label>Registration</label>
          <input type="text" value="<?= htmlspecialchars($company['reg_number']) ?>" disabled>
        </div>
        <div class="form-group"><label>Description</label>
          <textarea disabled><?= htmlspecialchars($company['description']) ?></textarea>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Contact Information</h3>
        <div class="form-group"><label>Email</label>
          <input type="email" value="<?= htmlspecialchars($company['email']) ?>" disabled>
        </div>
        <div class="form-group"><label>Phone</label>
          <input type="tel" value="<?= htmlspecialchars($company['phone']) ?>" disabled>
        </div>
        <div class="form-group"><label>Website</label>
          <input type="text" value="<?= htmlspecialchars($company['website']) ?>" disabled>
        </div>
        <div class="form-group"><label>Address</label>
          <textarea disabled><?= htmlspecialchars($company['address']) ?></textarea>
        </div>
      </div>
    </div>

    <!-- Verification -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Verification Status</h3>
      <ul>
        <?php foreach($company['verification'] as $item => $status): ?>
          <li><?= $item ?>:
            <span class="<?= $status ? 'verified' : 'not-verified' ?>">
              <?= $status ? 'Verified' : 'Not Verified' ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Security -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
      <p><button class="p-btn">Change Password</button></p>
      <p><button class="p-btn">Two-Factor Authentication</button></p>
      <p><button class="p-btn-delete">Delete Account</button></p>
    </div>
</main>


<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Profile</h2>
    <form method="POST">
      <div class="form-group"><label class="form-lable">Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>"></div>
      <div class="form-group"><label class="form-lable">Type</label>
        <input type="text" name="type" value="<?= htmlspecialchars($company['type']) ?>"></div>
      <div class="form-group"><label class="form-lable">Registration</label>
        <input type="text" name="reg_number" value="<?= htmlspecialchars($company['reg_number']) ?>"></div>
      <div class="form-group"><label class="form-lable">Description</label>
        <textarea name="description"><?= htmlspecialchars($company['description']) ?></textarea></div>
      <div class="form-group"><label for="email" class="form-lable">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($company['email']) ?>"></div>
      <div class="form-group"><label for="phone" class="form-lable">Phone</label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" name="phone" value="<?= htmlspecialchars($company['phone']) ?>"></div>
      <div class="form-group"><label class="form-lable">Website</label>
        <input type="text" name="website" value="<?= htmlspecialchars($company['website']) ?>"></div>
      <div class="form-group"><label class="form-lable">Address</label>
        <textarea name="address"><?= htmlspecialchars($company['address']) ?></textarea></div>
      <button type="submit" class="p-submit">Save Changes</button>
    </form>
  </div>
</div>

<!-- Toast Notification -->
<?php if ($showToast): ?>
<div class="toast">✅ Profile updated successfully!</div>
<?php endif; ?>
