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
    $profile['firstName'] = $_POST['firstName'];
    $profile['lastName'] = $_POST['lastName'];
    $profile['email'] = $_POST['email'];
    $profile['phone'] = $_POST['phone'];
    $profile['address'] = $_POST['address'];
    $profile['postalCode'] = $_POST['postalCode'];
    $profile['bankAccount'] = $_POST['bankAccount'];
    $msg = "✅ Profile updated!";
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
      <h1 class="page-title">Profile</h1>
      <p class="page-subtitle">Manage your personal information and account settings</p>
    </div>
  </div>

  <div class="cards-grid">
    <!-- Profile Photo Card -->
    <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.5rem;min-width:280px;">
      <div style="width:100%;display:flex;flex-direction:column;align-items:center;">
        <img src="<?= $profile['profile_pic'] ?>" class="avatar" style="box-shadow:0 2px 12px rgba(34,197,94,0.08);margin-bottom:1rem;">
        <h2 style="margin-bottom:0.5rem;font-size:1.25rem;font-weight:600;color:#1e293b;">Profile Photo</h2>
      </div>
      <form method="POST" enctype="multipart/form-data" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:0.5rem;">
        <input type="file" name="photo" accept="image/*" required style="margin-bottom:0.5rem;">
        <button class="btn btn-primary" type="submit" name="uploadPhoto" style="width:100%;">Upload</button>
      </form>
      <form method="POST" style="width:100%;display:flex;justify-content:center;">
        <button class="btn btn-outline" type="submit" name="removePhoto" style="width:100%;">Remove Photo</button>
      </form>
    </div>

    <!-- Profile Info Card -->
    <div class="card" style="min-width:320px;">
      <h2 style="margin-bottom:1.5rem;font-size:1.25rem;font-weight:600;color:#1e293b;">Personal Information</h2>
      <form method="POST">
        <div class="form-grid" style="margin-bottom:1.5rem;">
          <div>
            <label>First Name</label>
            <input type="text" name="firstName" value="<?= $profile['firstName'] ?>">
          </div>
          <div>
            <label>Last Name</label>
            <input type="text" name="lastName" value="<?= $profile['lastName'] ?>">
          </div>
          <div>
            <label>Email</label>
            <input type="email" name="email" value="<?= $profile['email'] ?>">
          </div>
          <div>
            <label>Phone</label>
            <input type="text" name="phone" value="<?= $profile['phone'] ?>">
          </div>
          <div>
            <label>Address</label>
            <input type="text" name="address" value="<?= $profile['address'] ?>">
          </div>
          <div>
            <label>Postal Code</label>
            <input type="text" name="postalCode" value="<?= $profile['postalCode'] ?>">
          </div>
          <div>
            <label>Bank Account</label>
            <input type="text" name="bankAccount" value="<?= $profile['bankAccount'] ?>">
          </div>
        </div>
        <div class="action-buttons" style="margin-top:0.5rem;justify-content:flex-end;">
          <button class="btn btn-primary" type="submit" name="saveProfile">Save Changes</button>
          <button type="button" class="btn btn-outline" onclick="openModal()">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Password Modal -->
<div id="password-modal" class="modal" style="display:none;align-items:center;justify-content:center;">
  <div class="modal-content" style="max-width:400px;width:100%;padding:2rem 2rem 1.5rem 2rem;position:relative;">
    <span class="close" onclick="closeModal()" style="position:absolute;top:1rem;right:1.5rem;font-size:1.5rem;cursor:pointer;">&times;</span>
    <h2 style="margin-top:0;margin-bottom:1.5rem;font-size:1.25rem;font-weight:600;color:#1e293b;">Change Password</h2>
    <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
      <div>
        <label>Current Password</label>
        <input type="password" name="currentPassword" required>
      </div>
      <div>
        <label>New Password</label>
        <input type="password" name="newPassword" required>
      </div>
      <div>
        <label>Confirm New Password</label>
        <input type="password" name="confirmPassword" required>
      </div>
      <button class="btn btn-primary" type="submit" name="updatePassword" style="margin-top:0.5rem;">Update Password</button>
    </form>
  </div>
</div>


