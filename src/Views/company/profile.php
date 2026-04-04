<?php
$company = is_array($companyProfile ?? null) ? $companyProfile : [];
$profileImageSrc = $companyProfile['profile_picture'] ?? '/assets/avatar.png';

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
      <div class="profile-picture"
        style="display: flex; align-items: center; justify-content: center; gap: 20px; margin: 20px 0;">
        <img id="profileImageDisplay" src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Profile Picture"
          style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb;">
        <a href="#photoUploadModal" class="btn btn-outline" style="font-size: 14px; padding: 8px 16px;">Change Photo
        </a>
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" value="<?= htmlspecialchars($metadata['companyName'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Registration Number</label>
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
        <a href="/api/profile/delete" class="p-btn-delete" onclick="return confirmDeleteProfile(event)">Delete
          Account</a>

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

      <p><a href="#bankdetail" class="btn btn-outline" style="margin-bottom: 5px; background:var(--info-light); ">Edit
          Bank Details</a></p>

    </div>
  </div>

</main>

<!-- Photo Upload Modal -->
<div id="photoUploadModal" class="form-modal">
  <div class="form-modal-content" style="max-width: 500px;">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Change Profile Photo</h2>

    <div style="text-align: center; margin-bottom: 20px;">
      <img id="photoPreview" src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Preview"
        style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb;">
    </div>

    <form method="POST" enctype="multipart/form-data" action="/company/profile/photo" id="photoUploadForm">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-group">
        <label class="form-lable">Select New Photo</label>
        <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg,image/gif" required
          style="padding: 10px;">
        <small style="color: #6b7280; display: block; margin-top: 5px;">
          Accepted formats: JPG, PNG, GIF (Max 5MB)
        </small>
      </div>

      <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
        <button type="submit" name="uploadPhoto" class="btn btn-primary" style="flex: 1;">
          ✓ Upload Photo
        </button>
        <?php if ($profileImage && $profileImageSrc !== '/assets/avatar.png'): ?>
          <button type="submit" name="removePhoto" class="btn btn-outline"
            style="flex: 1; background: #fee2e2; color: #dc2626;"
            onclick="return confirm('Are you sure you want to remove your profile photo?');">
            🗑️ Remove Photo
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Profile</h2>
    <div id="profileMessage"></div>

    <form method="POST" enctype="multipart/form-data" action="/api/profile/update">
      <input type="hidden" name="_token" value="<?= app('session')->token() ?>">

      <div class="form-group"><label>Name</label>
        <input type="text" name="companyName" value="<?= htmlspecialchars($metadata['companyName'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Registration Number</label>
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
        <input type="text" name="waste_types" value="<?= htmlspecialchars(implode(', ', $wasteTypes)) ?>"
          placeholder="Plastic, Organic, Metal, Glass, Paper" required>
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
        <input type="text" name="bank_account_number"
          value="<?= htmlspecialchars($bankDetails['account_number'] ?? '') ?>">
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

  // Live preview before upload
  document.getElementById('photoInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be under 5MB.');
      this.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('photoPreview').src = e.target.result;
      document.getElementById('profileImageDisplay').src = e.target.result; // updates main display too
    };
    reader.readAsDataURL(file);
  });

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