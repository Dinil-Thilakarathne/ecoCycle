<?php
$company = is_array($companyProfile ?? null) ? $companyProfile : [];
$bankdetails = is_array($bankDetails ?? null) ? $bankDetails : [];
$wasteTypes = $wasteTypes ?? ($company['waste_types'] ?? []);
if (!is_array($wasteTypes)) {
  $wasteTypes = [];
}
$verification = $verification ?? ($company['verification'] ?? []);
if (!is_array($verification)) {
  $verification = [];
}
$errors = $errors ?? [];
$showToast = $showToast ?? false;
?>

<main class="content">

  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Company Profile</h2>
      <p class="page-header__description">Update your profile here!</p>
    </div>
  </header>

  <!-- Company Info -->

  <a href="#editModal" class="btn btn-outline"
    style="position: absolute; right: 6%; top: 15%; background:var(--info-light);">✏️ Edit Profile</a>

  <div class=p-info-card>
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Company Information</h3>
      <div class="profile-picture">
        <img src="<?= htmlspecialchars($company['profile_picture'] ?? 'assets/img/default-company.png') ?>"
          alt="Profile Picture" width="100">
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" value="<?= htmlspecialchars($company['name'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Type</label>
        <input type="text" value="<?= htmlspecialchars($company['type'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Registration</label>
        <input type="text" value="<?= htmlspecialchars($company['reg_number'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Description</label>
        <textarea disabled><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Contact Information</h3>
      <div class="form-group"><label>Email</label>
        <input type="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Website</label>
        <input type="text" value="<?= htmlspecialchars($company['website'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Address</label>
        <textarea disabled><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="p-info-card">
    <!-- Waste Types -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Waste Types Collected</h3>
      <div class="waste-tags">
        <?php foreach ($wasteTypes as $w): ?>
          <span class="wastetag"><?= htmlspecialchars($w) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <!--Bank Details-->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Bank Details</h3>
      <div class="waste-tags">
        <p><a href="#bankdetail" class="btn btn-outline" style="margin-bottom: 5px; background:var(--info-light); ">See
            Bank Details</a></p>
      </div>
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
        <input type="text" name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Type</label>
        <input type="text" name="type" value="<?= htmlspecialchars($company['type'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Registration</label>
        <input type="text" name="reg_number" value="<?= htmlspecialchars($company['reg_number'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Description</label>
        <textarea name="description"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label for="email" class="form-lable">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
      </div>
      <div class="form-group"><label for="phone" class="form-lable">Phone</label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" name="phone"
          value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Website</label>
        <input type="text" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Address</label>
        <textarea name="address"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label>Waste Types (comma-separated)</label>
        <input type="text" name="waste_types" value="<?= htmlspecialchars(implode(", ", $wasteTypes)) ?>">
      </div>
      <button type="submit" class="btn btn-primary outline" style="width:100%;">Save Changes</button>
    </form>
  </div>
</div>

<!-- Bank Details Modal -->
<div id="bankdetail" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Bank Details</h2>
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
      <div class="form-group"><label class="form-lable">Bank Name</label>
        <input type="text" name="bank" value="<?= htmlspecialchars($bankdetails['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Account Number</label>
        <input type="text" name="number" value="<?= htmlspecialchars($bankdetails['account_number'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">User's Name</label>
        <input type="text" name="user" value="<?= htmlspecialchars($bankdetails['user'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Bank Branch</label>
        <input type="text" name="branch" value="<?= htmlspecialchars($bankdetails['branch'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary outline" style="width: 100%">Save Details</button>
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