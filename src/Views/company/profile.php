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
    "profile_picture" => "uploads/default.png", // image
    "waste_types" => ["Plastic", "Paper", "Metal"],
    "verification" => [
        "Email Verified" => true,
        "Phone Verified" => true,
        "Business License" => true
    ]
];

$showToast = false;

$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ------------------- Edit Profile Validations -------------------
    if (isset($_POST["email"]) && !filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (isset($_POST["phone"]) && !preg_match("/^[0-3] [3-9]{11}$/", $_POST["phone"])) {
        $errors[] = "Phone number must be 10 digits.";
    }

    if (isset($_POST["website"]) && !filter_var($_POST["website"], FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid website URL.";
    }

    // Update company data only if no errors
    if (empty($errors)) {
        $company["name"] = $_POST["name"];
        $company["type"] = $_POST["type"];
        $company["reg_number"] = $_POST["reg_number"];
        $company["description"] = $_POST["description"];
        $company["email"] = $_POST["email"];
        $company["phone"] = $_POST["phone"];
        $company["website"] = $_POST["website"];
        $company["address"] = $_POST["address"];

        // Handle waste types
        if (isset($_POST["waste_types"])) {
            $company["waste_types"] = array_filter(array_map("trim", explode(",", $_POST["waste_types"])));
        }

        // Handle profile picture upload
        if (!empty($_FILES["profile_picture"]["name"])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir);
            $targetFile = $targetDir . basename($_FILES["profile_picture"]["name"]);
            move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile);
            $company["profile_picture"] = $targetFile;
        }

        $showToast = true;
    }
}

// ------------------- Change Password Validation -------------------
if (isset($_POST["new_password"])) {
    $newPass = $_POST["new_password"];
    $confirmPass = $_POST["confirm_password"];

    if (strlen($newPass) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match("/[0-9]/", $newPass)) {
        $errors[] = "Password must contain at least one number.";
    }
    if (!preg_match("/[!@#$%^&*]/", $newPass)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*).";
    }
    if ($newPass !== $confirmPass) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // TODO: Save hashed password in DB (example)
        // $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);
        $showToast = true;
    }
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
  
    <a href="#editModal" class="btn btn-outline" style="position: absolute; right: 6%; top: 15%; ">✏️ Edit Profile</a>
    
    <div class=p-info-card>
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Company Information</h3>
        <div class="profile-picture">
                <img src="<?= htmlspecialchars($company['profile_picture']) ?>" alt="Profile Picture" width="100">
        </div>
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

    <!-- Waste Types -->
    <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Waste Types Collected</h3>
        <div class="waste-tags">
            <?php foreach ($company['waste_types'] as $w): ?>
                <span class="wastetag"><?= htmlspecialchars($w) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Security -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
      <p><a href="#passwordModal" class="btn btn-primary" style="margin-bottom: 5px">Change Password</a></p>
      <p><button class="btn btn-primary" style="margin-bottom: 5px">Two-Factor Authentication</button></p>
      <p><button class="p-btn-delete">Delete Account</button></p>
    </div>
</main>


<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Profile</h2>
    <?php if (!empty($errors)): ?>
      <div class="error-box">
          <ul>
              <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
          </ul>
      </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group"><label>Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">
      </div>
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
      <div class="form-group"><label>Waste Types (comma-separated)</label>
        <input type="text" name="waste_types" value="<?= htmlspecialchars(implode(", ", $company['waste_types'])) ?>"></div>
      <button type="submit" class="btn btn-primary outline" style="width:100%;">Save Changes</button>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Change Password</h2>
    <?php if (!empty($errors)): ?>
      <div class="error-box">
          <ul>
              <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
          </ul>
      </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group"><label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-group"><label>New Password</label>
        <input type="password" name="new_password" required>
      </div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
      </div>
      <button type="submit" class="btn btn-primary outline" style="width:100%;">Update Password</button>
    </form>
  </div>
</div>

<!-- Toast Notification -->
<?php if ($showToast): ?>
<div class="toast">✅ Profile updated successfully!</div>
<?php endif; ?>
