<?php
$company = is_array($companyProfile ?? null) ? $companyProfile : [];

if (isset($company['metadata'])) {
    if (is_string($company['metadata'])) {
        $metadata = json_decode($company['metadata'], true) ?? [];
    } elseif (is_array($company['metadata'])) {
        $metadata = $company['metadata'];
    } else {
        $metadata = [];
    }
} else {
    $metadata = [];
}

$wasteTypes = $metadata['waste_types'] ?? [];
if (!is_array($wasteTypes)) {
    $wasteTypes = [];
}

$bankDetails = is_array($bankDetails ?? null) ? $bankDetails : [];
$errors = $errors ?? [];
$showToast = $showToast ?? false;
$csrf = app('session')->token();
?>


<main class="content">

  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Company Profile</h2>
      <p class="page-header__description">Update your profile here!</p>
    </div>
  </header>

  <!-- Company Info Card -->
  <div class="p-info-card">
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Company Information</h3>
      <div class="profile-picture">
        <img src="<?= htmlspecialchars($company['profile_picture'] ?? 'assets/avatar.png') ?>" width="100"
          alt="Profile Picture">
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" value="<?= htmlspecialchars($metadata['companyName'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Type</label>
        <input type="text" value="<?= htmlspecialchars($metadata['type'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Registration</label>
        <input type="text" value="<?= htmlspecialchars($metadata['reg_number'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Website</label>
        <input type="text" value="<?= htmlspecialchars($metadata['website'] ?? '') ?>" disabled>
      </div>
    </div>

    

    <!-- Contact Info -->
    <div class="pc-card">
      <a href="#editModal" class="btn btn-outline"
        style="position: absolute; right: 6%; top: 2%; background:var(--info-light);">✏️ Edit Profile</a>
      <h3 style="font-size: 20px; font-weight: bold;">Contact Information</h3>
      <div class="form-group"><label>Contact Person</label>
        <input type="text" value="<?= htmlspecialchars($company['name'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Email</label>
        <input type="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Land Phone</label>
        <input type="tel" value="011-1234567" disabled>
      </div>
      <div class="form-group"><label>Address</label>
        <textarea disabled><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="p-info-card">
    <!-- Waste Types -->
    <div>
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Waste Types Collected</h3>
        <div class="waste-tags">
          <?php foreach ($wasteTypes as $w): ?>
            <span class="wastetag"><?= htmlspecialchars($w) ?></span>
          <?php endforeach; ?>
        </div>
      </div>

    <!-- Security -->
      <div class="pc-card">
        <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
        <p><a href="#passwordModal" class="btn btn-primary" style="margin-bottom: 5px">Change Password</a></p>
        <a href="/api/profile/delete" class="p-btn-delete" onclick="return confirmDeleteProfile(event)">Delete Account</a>

      </div>
    </div>

    <!--Bank Details-->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Bank Details</h3>
      <div style="display: grid; grid-template-columns: 2fr 2fr; gap: 20px;">
      <div class="form-group"><label>Bank Name</label>
        <input type="text" value="<?= htmlspecialchars($bankDetails['name'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Account Number</label>
        <input type="text" value="<?= htmlspecialchars($bankDetails['account_number'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>User's Name</label>
        <input type="text" value="<?= htmlspecialchars($bankDetails['user'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Bank Branch</label>
        <input type="text" value="<?= htmlspecialchars($bankDetails['branch'] ?? '') ?>" disabled>
      </div>
      </div>

      <p><a href="#bankdetail" class="btn btn-outline" style="margin-bottom: 5px; background:var(--info-light); ">Edit Bank Details</a></p>
      
    </div>
  </div>

  

</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Profile</h2>
    <div id="profileMessage"></div>

    <form method="POST" enctype="multipart/form-data" action="/api/profile/update">
      <input type="hidden" name="_token" value="<?= app('session')->token() ?>">

      <div class="form-group"><label>Profile Picture</label>
        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" name="companyName" value="<?= htmlspecialchars($metadata['companyName'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Type</label>
        <input type="text" name="type" value="<?= htmlspecialchars($metadata['type'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Registration</label>
        <input type="text" name="reg_number" value="<?= htmlspecialchars($metadata['reg_number'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Website</label>
        <input type="text" name="website" value="<?= htmlspecialchars($metadata['website'] ?? '') ?>">
      </div>
      
      <div class="form-group"><label>Contact Person</label>
        <input type="text" name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" name="phone"
          value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Address</label>
        <textarea name="address"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label>Waste Types</label>
        <input type="text" name="waste_types" value="<?= htmlspecialchars(implode(', ', $wasteTypes)) ?>" placeholder="Plastic, Organic, Metal, Glass, Paper" required>
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
    
    <form method="POST" enctype="multipart/form-data" action="/api/profile/bankDetails">
      <div class="form-group"><label class="form-lable">Bank Name</label>
        <input type="text" name="bank_name" value="<?= htmlspecialchars($bankDetails['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Account Number</label>
        <input type="text" name="bank_account_number" value="<?= htmlspecialchars($bankDetails['account_number'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">User's Name</label>
        <input type="text" name="bank_account_name" value="<?= htmlspecialchars($bankDetails['user'] ?? '') ?>">
      </div>
      <div class="form-group"><label class="form-lable">Bank Branch</label>
        <input type="text" name="bank_branch" value="<?= htmlspecialchars($bankDetails['branch'] ?? '') ?>">
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
    
    <form method="POST" enctype="multipart/form-data" action="/api/profile/password">
      <div class="form-group"><label>New Password</label>
        <input type="password" name="password" placeholder="Leave empty to keep current" required>
      </div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
      </div>
      <button type="submit" class="btn btn-primary outline" style="width:100%;">Update Password</button>
    </form>
  </div>
</div>

<!-- Toast Notification -->
<?php if ($showToast): ?>
  <div class="toast">Profile updated successfully!</div>
<?php endif; ?>

<script>
  (function () {
    const msgBox = document.getElementById('profileMessage');

    function showMessage(msg, isError = false) {
      msgBox.innerHTML = `<div class="${isError ? 'error-box' : 'success-box'}">${msg}</div>`;
    }

  })();
</script>

<script>
function confirmDeleteProfile(event) {
    event.preventDefault();

    const confirmDelete = confirm(
        "Are you sure you want to delete your account?\n\n" +
        "This action is PERMANENT and cannot be undone."
    );

    if (confirmDelete) {
        window.location.href = event.currentTarget.href;
    }

    return false;
}
</script>
