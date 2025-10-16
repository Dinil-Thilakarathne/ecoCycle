


<?php

// Dummy profile (normally DB)
if (!isset($_SESSION['profile'])) {
    $_SESSION['profile'] = [
        "profile_pic" => "default.png",
        "firstName" => "Sarah",
        "lastName" => "Anderson",
        "email" => "sarah@example.com",
        "phone" => "+1 (555) 123-4567",
        "address" => "sri rd",
        "postalCode" => "222",
        "bankAccount" => "sampath",
        "password" => password_hash("12345", PASSWORD_BCRYPT),
    ];
}
$profile = &$_SESSION['profile'];

// Upload photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadPhoto'])) {
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);

        $fileName = time() . "_" . basename($_FILES['photo']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            $profile['profile_pic'] = $targetFile;
            $msg = "✅ Profile photo updated!";
        } else {
            $msg = "❌ Error uploading photo!";
        }
    }
}

// Remove photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['removePhoto'])) {
    $profile['profile_pic'] = "default.png";
    $msg = "✅ Profile photo removed!";
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveProfile'])) {
  $errors = [];
  $firstName = trim($_POST['firstName']);
  $lastName = trim($_POST['lastName']);
  $email = trim($_POST['email']);
  $phone = trim($_POST['phone']);
  $address = trim($_POST['address']);
  $postalCode = trim($_POST['postalCode']);
  $bankAccount = trim($_POST['bankAccount']);

  // Required fields
  if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $address === '' || $postalCode === '' || $bankAccount === '') {
    $errors[] = "All fields are required.";
  }
  // Email validation
  if (strpos($email, '@') === false) {
    $errors[] = "Email must contain '@'.";
  }
  // Phone validation: only digits, length 10
  // Phone validation: must start with 0, only digits, length 10
  if (!preg_match('/^0\d{9}$/', $phone)) {
    $errors[] = "Phone number must start with 0 and be exactly 10 digits.";
  }
  // Postal code: only digits, max 5 digits
  if (!preg_match('/^\d{1,5}$/', $postalCode)) {
    $errors[] = "Postal code must be numeric and up to 5 digits.";
  }

  if (empty($errors)) {
    $profile['firstName'] = $firstName;
    $profile['lastName'] = $lastName;
    $profile['email'] = $email;
    $profile['phone'] = $phone;
    $profile['address'] = $address;
    $profile['postalCode'] = $postalCode;
    $profile['bankAccount'] = $bankAccount;
    $msg = "✅ Profile updated!";
  } else {
    $msg = '<span style="color:red">❌ ' . implode('<br>', $errors) . '</span>';
  }
}

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatePassword'])) {
    $current = $_POST['currentPassword'];
    $new = $_POST['newPassword'];
    $confirm = $_POST['confirmPassword'];

    if (password_verify($current, $profile['password'])) {
        if ($new === $confirm) {
            $profile['password'] = password_hash($new, PASSWORD_BCRYPT);
            $msg = "✅ Password updated!";
        } else {
            $msg = "❌ New passwords do not match!";
        }
    } else {
        $msg = "❌ Current password is wrong!";
    }
}
?>


<script>
  function openModal() {
    document.getElementById("password-modal").style.display = "flex";
    document.body.style.overflow = "hidden";
  }
  function closeModal() {
    document.getElementById("password-modal").style.display = "none";
    document.body.style.overflow = "auto";
  }
</script>




<div class="dashboard-page">
  <?php if (isset($msg)): ?>
    <div class="alert" style="margin-bottom:2rem;"> <?= $msg ?> </div>
  <?php endif; ?>

  <div class="page-header">
    <div class="header-content">
      <h1 class="profile-title">Profile</h1>
      <p class="subtitle">Manage your personal information and account settings</p>
    </div>
    <!-- Profile Photo Card -->
    <div class="card" style="display: flex; flex-direction: column; align-items: center; gap: 2rem; padding: 2rem 1.5rem; min-width: 260px; max-width: 340px; box-shadow: 0 2px 12px rgba(167, 228, 26, 0.08);">
      <div style="width: 100%; display: flex; flex-direction: column; align-items: center;">
        <img src="<?= $profile['profile_pic'] ?>" class="avatar" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem; border: 4px solid var(--primary-100, #e0f2fe);">
        <h2 class="profile-photo-title">Profile Photo</h2>
      </div>
      <form method="POST" enctype="multipart/form-data" style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
        <input type="file" name="photo" accept="image/*" required class="input-file">
        <button class="btn btn-primary" type="submit" name="uploadPhoto" style="width:100%;">Upload</button>
      </form>
      <form method="POST" style="width: 100%; display: flex; justify-content: center;">
        <button class="btn btn-outline" type="submit" name="removePhoto" style="width: 100%;">Remove Photo</button>
      </form>
    </div>

    <!-- Profile Info Card -->
    <div class="card" style="padding: 2rem 2.5rem; max-width: 600px; width: 100%;">
      <form method="POST" id="profileForm">
        <h2 class="section-title" style="margin-bottom: 2rem;">Personal Information</h2>
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem 2rem; margin-bottom: 2rem;">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" value="<?= $profile['firstName'] ?>" required disabled>
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" value="<?= $profile['lastName'] ?>" required disabled>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= $profile['email'] ?>" required pattern="[^@\s]+@[^@\s]+\.[^@\s]+" disabled>
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= $profile['phone'] ?>" required pattern="0\d{9}" maxlength="10" title="Phone number must start with 0 and be exactly 10 digits" disabled>
          </div>
          <div class="form-group" style="grid-column: span 2;">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= $profile['address'] ?>" required disabled>
          </div>
          <div class="form-group">
            <label for="postalCode">Postal Code</label>
            <input type="text" id="postalCode" name="postalCode" value="<?= $profile['postalCode'] ?>" required pattern="\d{1,5}" maxlength="5" title="Postal code must be numeric and up to 5 digits" disabled>
          </div>
          <div class="form-group">
            <label for="bankAccount">Bank Account</label>
            <input type="text" id="bankAccount" name="bankAccount" value="<?= $profile['bankAccount'] ?>" required disabled>
          </div>
        </div>
        <div class="action-buttons" style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button class="btn btn-primary" type="button" id="editBtn">Edit</button>
          <button class="btn btn-primary" type="submit" name="saveProfile" id="saveBtn" style="display:none;">Save Changes</button>
          <button type="button" class="btn btn-outline" onclick="openModal()">Change Password</button>
        </div>
      </form>
      <script>
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const form = document.getElementById('profileForm');
        const inputs = form.querySelectorAll('input');
        editBtn.addEventListener('click', function() {
          inputs.forEach(input => input.disabled = false);
          editBtn.style.display = 'none';
          saveBtn.style.display = 'inline-block';
        });
        form.addEventListener('submit', function() {
          // Do not disable fields before submit, let PHP handle update
          // After submit, page reloads and disables fields again
        });
      </script>
    </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeEditModal()">&times;</a>
    <h2>Edit Profile</h2>
    <form method="POST">
      <div class="form-group"><label class="form-lable">Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>"></div>
  <!-- Removed Type field from edit modal -->
      <div class="form-group"><label class="form-lable">NIC</label>
        <input type="text" name="nic" value="<?= htmlspecialchars($customer['nic']) ?>" maxlength="15" pattern="[0-9Vv]{1,15}" title="NIC should be up to 15 characters (numbers and V)"></div>
      <div class="form-group"><label class="form-lable">Description</label>
        <textarea name="description"><?= htmlspecialchars($customer['description']) ?></textarea></div>
      <div class="form-group"><label class="form-lable">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>"></div>
      <div class="form-group"><label class="form-lable">Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" pattern="0[0-9]{9,}" maxlength="10" title="Phone number must start with 0 and contain only numbers (10 digits)"></div>
      <div class="form-group"><label class="form-lable">Address</label>
        <textarea name="address"><?= htmlspecialchars($customer['address']) ?></textarea></div>
      <button type="submit" class="p-submit" name="saveProfile">Save Changes</button>
    </form>
  </div>
</div>




<!-- Password Modal -->
<div id="password-modal" class="modal" style="display:none; align-items: center; justify-content: center;">
  <div class="modal-content" style="max-width: 400px; width: 100%; padding: 2rem 2rem 1.5rem 2rem; position: relative; border-radius: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,0.18);">
    <span class="close" onclick="closeModal()" style="position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer;">&times;</span>
    <h2 class="section-title" style="margin-top: 0; margin-bottom: 1.5rem;">Change Password</h2>
    <form method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
      <div class="form-group">
        <label for="currentPassword">Current Password</label>
        <input type="password" id="currentPassword" name="currentPassword" required>
      </div>
      <div class="form-group">
        <label for="newPassword">New Password</label>
        <input type="password" id="newPassword" name="newPassword" required>
      </div>
      <div class="form-group">
        <label for="confirmPassword">Confirm New Password</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required>
      </div>
      <button class="btn btn-primary" type="submit" name="updatePassword" style="margin-top: 0.5rem;">Update
        Password</button>
    </form>
  </div>
</div>


