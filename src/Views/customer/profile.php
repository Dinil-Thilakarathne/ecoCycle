<?php
$profile = $userProfile ?? [];
$oldInput = $oldInput ?? [];
$errors = $validationErrors ?? [];
$statusMessage = $statusMessage ?? null;

$inputValue = function (string $key, string $default = '') use ($oldInput, $profile): string {
  if (array_key_exists($key, $oldInput) && $oldInput[$key] !== '') {
    return (string) $oldInput[$key];
  }

  if (array_key_exists($key, $profile) && $profile[$key] !== null && $profile[$key] !== '') {
    return (string) $profile[$key];
  }

  return $default;
};

$imagePath = $profile['profileImage'] ?? null;
$profileImageUrl = $imagePath ? asset($imagePath) : asset('assets/logo-icon.png');
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
  <?php if ($statusMessage): ?>
    <div class="alert" style="margin-bottom:2rem;"> <?= htmlspecialchars($statusMessage) ?> </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert" style="margin-bottom:2rem;color:#b91c1c;background:#fee2e2;padding:1rem;border-radius:0.75rem;">
      <strong>We could not save your changes:</strong>
      <ul style="margin:0.5rem 0 0 1.25rem;">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <div class="header-content">
      <h1 class="profile-title">Profile</h1>
      <p class="subtitle">Manage your personal information and account settings</p>
    </div>
  </div>

  <div class="cards-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: flex-start;">
    <!-- Profile Photo Card -->
    <div class="card"
      style="display: flex; flex-direction: column; align-items: center; gap: 2rem; padding: 2rem 1.5rem; min-width: 260px; max-width: 340px; box-shadow: 0 2px 12px rgba(167, 228, 26, 0.08);">
      <div style="width: 100%; display: flex; flex-direction: column; align-items: center;">
        <img src="<?= htmlspecialchars($profileImageUrl) ?>" class="avatar"
          style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem; border: 4px solid var(--primary-100, #e0f2fe);">
        <h2 class="profile-photo-title">Profile Photo</h2>
      </div>
      <form method="POST" action="/customer/profile" enctype="multipart/form-data"
        style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="file" name="photo" accept="image/*" required class="input-file">
        <button class="btn btn-primary" type="submit" name="uploadPhoto" style="width: 100%;">Upload</button>
      </form>
      <form method="POST" action="/customer/profile" style="width: 100%; display: flex; justify-content: center;">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button class="btn btn-outline" type="submit" name="removePhoto" style="width: 100%;">Remove Photo</button>
      </form>
    </div>

    <!-- Profile Info Card -->
    <div class="card" style="padding: 2rem 2.5rem; max-width: 600px; width: 100%;">
      <form method="POST" id="profileForm" action="/customer/profile">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <h2 class="section-title" style="margin-bottom: 2rem;">Personal Information</h2>
        <div class="form-grid"
          style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem 2rem; margin-bottom: 2rem;">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($inputValue('firstName')) ?>"
              required disabled>
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($inputValue('lastName')) ?>"
              required disabled>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($inputValue('email')) ?>" required
              pattern="[^@\s]+@[^@\s]+\.[^@\s]+" disabled>
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($inputValue('phone')) ?>" required
              pattern="0\d{9}" maxlength="10" title="Phone number must start with 0 and be exactly 10 digits" disabled>
          </div>
          <div class="form-group" style="grid-column: span 2;">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($inputValue('address')) ?>"
              required disabled>
          </div>
          <div class="form-group">
            <label for="postalCode">Postal Code</label>
            <input type="text" id="postalCode" name="postalCode"
              value="<?= htmlspecialchars($inputValue('postalCode')) ?>" required pattern="\d{1,5}" maxlength="5"
              title="Postal code must be numeric and up to 5 digits" disabled>
          </div>
          <div class="form-group">
            <label for="bankAccount">Bank Account</label>
            <input type="text" id="bankAccount" name="bankAccount"
              value="<?= htmlspecialchars($inputValue('bankAccount')) ?>" required disabled>
          </div>
        </div>
        <div class="action-buttons" style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button class="btn btn-primary" type="button" id="editBtn">Edit</button>
          <button class="btn btn-primary" type="submit" name="saveProfile" id="saveBtn" style="display:none;">Save
            Changes</button>
          <button type="button" class="btn btn-outline" onclick="openModal()">Change Password</button>
        </div>
      </form>
      <script>
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const form = document.getElementById('profileForm');
        const inputs = form.querySelectorAll('input');
        editBtn.addEventListener('click', function () {
          inputs.forEach(function (input) {
            if (input.name !== '_token') {
              input.disabled = false;
            }
          });
          editBtn.style.display = 'none';
          saveBtn.style.display = 'inline-block';
        });
      </script>
    </div>
  </div>
</div>

<!-- Password Modal -->
<div id="password-modal" class="modal" style="display:none; align-items: center; justify-content: center;">
  <div class="modal-content"
    style="max-width: 400px; width: 100%; padding: 2rem 2rem 1.5rem 2rem; position: relative; border-radius: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,0.18);">
    <span class="close" onclick="closeModal()"
      style="position: absolute; top: 1rem; right: 1.5rem; font-size: 1.5rem; cursor: pointer;">&times;</span>
    <h2 class="section-title" style="margin-top: 0; margin-bottom: 1.5rem;">Change Password</h2>
    <form method="POST" action="/customer/profile" style="display: flex; flex-direction: column; gap: 1.25rem;">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
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