<?php
$collector = is_array($collectorProfile ?? null) ? $collectorProfile : [];
$errors = is_array($validationErrors ?? null) ? $validationErrors : ($errors ?? []);
$statusMessage = is_string($statusMessage ?? null) ? $statusMessage : '';
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];

$vehicle = is_array($vehicleInfo ?? null) ? $vehicleInfo : [];
$certificationsData = $certifications ?? [];

$displayName = trim($collector['name'] ?? '');
$displayName = $displayName !== '' ? $displayName : 'N/A';

$displayEmail = $collector['email'] ?? '';
$displayPhone = $collector['phone'] ?? '';
$displayAddress = $collector['address'] ?? '';
$displayPostal = $collector['postalCode'] ?? '';
$displayDescription = $collector['description'] ?? '';

// Parse metadata (may be JSON string or array)
if (isset($collector['metadata'])) {
  if (is_string($collector['metadata'])) {
    $metadata = json_decode($collector['metadata'], true) ?? [];
  } elseif (is_array($collector['metadata'])) {
    $metadata = $collector['metadata'];
  } else {
    $metadata = [];
  }
} else {
  $metadata = [];
}

$vehiclePreference = $metadata['vehiclePreference'] ?? '';
$serviceAreas = $metadata['serviceAreas'] ?? [];
if (!is_array($serviceAreas)) {
  $serviceAreas = is_string($serviceAreas) ? array_filter(array_map('trim', explode(',', $serviceAreas))) : [];
}
$licenseNumber = $metadata['licenseNumber'] ?? '';

$bankDetails = is_array($collector['bank'] ?? null) ? $collector['bank'] : [];
// Normalize bank details keys
$bankDetails = array_merge([
  'name' => $bankDetails['bankName'] ?? $bankDetails['name'] ?? '',
  'account_number' => $bankDetails['accountNumber'] ?? $bankDetails['account_number'] ?? $collector['bankAccount'] ?? '',
  'user' => $bankDetails['holderName'] ?? $bankDetails['user'] ?? '',
  'branch' => $bankDetails['branch'] ?? $bankDetails['bank_branch'] ?? '',
], $bankDetails);

$editName = $oldInput['name'] ?? ($collector['name'] ?? '');
$editEmail = $oldInput['email'] ?? $displayEmail;
$editPhone = $oldInput['phone'] ?? $displayPhone;
$editAddress = $oldInput['address'] ?? $displayAddress;
$editPostalCode = $oldInput['postalCode'] ?? $displayPostal;
$editDescription = $oldInput['description'] ?? $displayDescription;

$editVehiclePreference = $oldInput['vehiclePreference'] ?? $vehiclePreference;
$editServiceAreas = $oldInput['serviceArea'] ?? implode(', ', $serviceAreas);
$editLicenseNumber = $oldInput['licenseNumber'] ?? $licenseNumber;

$editBankName = $oldInput['bank_name'] ?? $bankDetails['name'] ?? '';
$editBankAccount = $oldInput['bank_account_number'] ?? $bankDetails['account_number'] ?? '';
$editBankHolder = $oldInput['bank_account_name'] ?? $bankDetails['user'] ?? '';
$editBankBranch = $oldInput['bank_branch'] ?? $bankDetails['branch'] ?? '';

$profileImage = null;
$sessionProfileImage = session('profileImagePath');
if (is_string($sessionProfileImage) && trim($sessionProfileImage) !== '') {
  $profileImage = trim($sessionProfileImage);
}

if ($profileImage === null) {
foreach (['profileImagePath', 'profileImage', 'profile_image_path', 'profile_pic'] as $imageKey) {
  $candidate = $collector[$imageKey] ?? null;
  if (is_string($candidate) && trim($candidate) !== '') {
    $profileImage = trim($candidate);
    break;
  }
}
}

$hasCustomProfileImage = false;
if (is_string($profileImage) && preg_match('#^https?://#i', $profileImage)) {
  $profileImageSrc = $profileImage;
  $hasCustomProfileImage = true;
} elseif (is_string($profileImage) && $profileImage !== '') {
  $profileImageSrc = asset(ltrim($profileImage, '/'));
  $hasCustomProfileImage = true;
} else {
  $profileImageSrc = asset('assets/avatar.png');
}

if ($hasCustomProfileImage) {
  $profileImageSrc .= (str_contains($profileImageSrc, '?') ? '&' : '?') . 'v=' . time();
}

$vehicleDisplay = [];
if (is_array($vehicle)) {
  foreach ($vehicle as $key => $value) {
    if (is_scalar($value)) {
      $stringValue = trim((string) $value);
      if ($stringValue !== '') {
        $label = ucwords(str_replace(['_', '-'], ' ', (string) $key));
        $vehicleDisplay[$label] = $stringValue;
      }
    }
  }
}

if (is_string($certificationsData)) {
  $decodedCerts = json_decode($certificationsData, true);
  $certificationsData = is_array($decodedCerts) ? $decodedCerts : [$certificationsData];
}

$certificationsList = [];
if (is_array($certificationsData)) {
  foreach ($certificationsData as $cert) {
    if (is_string($cert)) {
      $trimmed = trim($cert);
      if ($trimmed !== '') {
        $certificationsList[] = $trimmed;
      }
    } elseif (is_array($cert) && isset($cert['name'])) {
      $name = trim((string) $cert['name']);
      if ($name !== '') {
        $certificationsList[] = $name;
      }
    }
  }
}

$toastMessage = $toastMessage ?? '';
if ($toastMessage === '' && $statusMessage !== '') {
  $toastMessage = $statusMessage;
}
if ($toastMessage === '' && !empty($errors)) {
  $toastMessage = $errors[0];
  $toastType = 'error';
}
$toastType = $toastType ?? (empty($errors) ? 'success' : 'error');
$showToast = $showToast ?? ($toastMessage !== '');
$activeModal = is_string($activeModal ?? null) ? $activeModal : ($active_modal ?? '');

$csrfToken = csrf_token();
?>

<main class="content profile-page">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Collector Profile</h2>
      <p class="page-header__description">Update your profile here!</p>
    </div>
  </header>


  <div class="p-info-card">
    <div class="pc-card">
      <h3 class="collector-section-title">Collector Information</h3>
      <div class="profile-picture collector-profile-picture-wrap">
        <img id="profileImageDisplay" src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Profile Picture" 
             class="collector-profile-photo-main">
        <a href="#photoUploadModal" class="btn btn-outline collector-photo-change-btn">
          Change Photo
        </a>
      </div>
      <div class="form-group"><label>First Name</label>
        <input type="text" value="<?= htmlspecialchars($displayName) ?>" disabled>
      </div>
      <div class="form-group"><label>Second Name</label>
        <input type="text" value="<?= htmlspecialchars($displayName) ?>" disabled>
      </div>
      <div class="form-group"><label>License Number</label>
        <input type="text" value="<?= htmlspecialchars($licenseNumber) ?>" disabled>
      </div>
      <!-- <div class="form-group"><label>Service Areas</label>
        <input type="text" value="<?= htmlspecialchars(implode(', ', $serviceAreas)) ?>" disabled>
      </div> -->
    </div>

    <div class="pc-card">
      <a href="#editModal" class="btn btn-outline collector-edit-profile-btn">✏️ Edit Profile</a>
      <h3 class="collector-section-title">Contact Information</h3>
      <div class="form-group"><label>Email</label>
        <input type="email" value="<?= htmlspecialchars($displayEmail) ?>" disabled>
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" value="<?= htmlspecialchars($displayPhone) ?>" disabled>
      </div>
      <div class="form-group"><label>Address</label>
        <textarea disabled><?= htmlspecialchars($displayAddress) ?></textarea>
      </div>
    </div>
  </div>

  <div class="p-info-card">
    <div class="pc-card security-card">
      <h3 class="collector-section-title">Security & Privacy</h3>
      <p><a href="#passwordModal" class="btn btn-primary collector-change-password-btn">Change Password</a></p>
      <p><a href="/api/profile/delete" class="p-btn-delete" onclick="return confirmDeleteProfile(event)">Delete Account</a></p>
    </div>

    <div class="pc-card">
      <h3 class="collector-section-title">Bank Details</h3>
      <div class="collector-bank-grid">
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
      <div class="waste-tags">
        <p><a href="#bankdetail" class="btn btn-outline collector-edit-bank-btn">Edit
            Bank Details</a></p>
      </div>
    </div>
  </div>
<!-- 
  <?php if (!empty($vehicleDisplay) || !empty($certificationsList)): ?>
    <div class="p-info-card">
      <?php if (!empty($vehicleDisplay)): ?>
        <div class="pc-card">
          <h3 class="collector-section-title">Vehicle Information</h3>
          <div class="vehicle-info-grid">
            <?php foreach ($vehicleDisplay as $label => $value): ?>
              <div class="vehicle-info-item">
                <span class="vehicle-info-label"><?= htmlspecialchars($label) ?></span>
                <span class="vehicle-info-value"><?= htmlspecialchars($value) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?> -->
<!-- 
      <?php if (!empty($certificationsList)): ?>
        <div class="pc-card">
          <h3 class="collector-section-title">Certifications</h3>
          <div class="certification-list">
            <?php foreach ($certificationsList as $cert): ?>
              <span class="certification-badge"><?= htmlspecialchars($cert) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?> -->
    </div>
  <?php endif; ?>
</main>

<!-- Photo Upload Modal -->
<div id="photoUploadModal" class="form-modal">
  <div class="form-modal-content collector-photo-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 class="collector-modal-title collector-modal-title-spaced">Change Profile Photo</h2>
    
        <div class="collector-photo-preview-wrap">
      <img id="photoPreview" src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Preview" 
          class="collector-photo-preview">
    </div>

    <form method="POST" enctype="multipart/form-data" action="/collector/profile" id="photoUploadForm">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      
      <div class="form-group">
        <label class="form-lable">Select New Photo</label>
        <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg,image/gif" 
               required class="collector-file-input">
        <small class="collector-file-hint">
          Accepted formats: JPG, PNG, GIF (Max 5MB)
        </small>
      </div>

      <div class="form-actions collector-photo-actions">
        <button type="submit" name="uploadPhoto" class="btn btn-primary collector-flex-1">
          ✓ Upload Photo
        </button>
        <?php if ($hasCustomProfileImage): ?>
          <button type="submit" name="removePhoto" class="btn btn-outline collector-remove-photo-btn"
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
    <h2 class="collector-modal-title">Edit Profile</h2>
    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST" action="/collector/profile">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <div class="form-group"><label class="form-lable">First Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($editName) ?>" required>
      </div>
      <div class="form-group"><label class="form-lable">Second Name</label>
        <input type="text" name="secondName" value="<?= htmlspecialchars($editName) ?>" required>
      </div>
      <div class="form-group"><label class="form-lable">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($editEmail) ?>" required>
      </div>
      <div class="form-group"><label class="form-lable">Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($editPhone) ?>" pattern="0[0-9]{9}" maxlength="10"
          required>
      </div>
      <div class="form-group"><label class="form-lable">Address</label>
        <textarea name="address" required><?= htmlspecialchars($editAddress) ?></textarea>
      </div>
      <div class="form-group"><label class="form-lable">License Number</label>
        <input type="text" name="licenseNumber" value="<?= htmlspecialchars($editLicenseNumber) ?>">
      </div>

      <button type="submit" class="btn btn-primary outline collector-full-width" name="saveProfile">Save Changes</button>
    </form>
  </div>
</div>

<!-- Upload Photo Script -->
<script>
  // Preview selected image before upload
  document.getElementById('photoInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      // Validate file size (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        e.target.value = '';
        return;
      }

      // Validate file type
      if (!['image/jpeg', 'image/png', 'image/jpg', 'image/gif'].includes(file.type)) {
        alert('Please select a valid image file (JPG, PNG, or GIF)');
        e.target.value = '';
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = function(event) {
        document.getElementById('photoPreview').src = event.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // Handle form submission with loading state
  document.getElementById('photoUploadForm')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[name="uploadPhoto"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = '⏳ Uploading...';
    }
  });
</script>

<!-- Bank Details Modal -->
<div id="bankdetail" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 class="collector-modal-title">Bank Details</h2>
    <form method="POST" action="/api/profile/bankDetails">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label class="form-lable">Bank Name</label>
        <input type="text" name="bank_name" value="<?= htmlspecialchars($editBankName) ?>">
      </div>
      <div class="form-group"><label class="form-lable">Account Number</label>
        <input type="text" name="bank_account_number" value="<?= htmlspecialchars($editBankAccount) ?>">
      </div>
      <div class="form-group"><label class="form-lable">User's Name</label>
        <input type="text" name="bank_account_name" value="<?= htmlspecialchars($editBankHolder) ?>">
      </div>
      <div class="form-group"><label class="form-lable">Bank Branch</label>
        <input type="text" name="bank_branch" value="<?= htmlspecialchars($editBankBranch) ?>">
      </div>
      <button type="submit" class="btn btn-primary outline collector-full-width">Save Details</button>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 class="collector-modal-title">Change Password</h2>
    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST" action="/api/profile/password">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label>New Password</label>
        <input type="password" name="password" minlength="6" required>
      </div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirm_password" minlength="6" required>
      </div>
      <button type="submit" class="btn btn-primary outline collector-full-width">Change Password</button>
    </form>
  </div>

  <?php if ($showToast && $toastMessage !== ''): ?>
    <script>
      (function () {
        const triggerToast = function () {
          if (typeof window.__createToast === 'function') {
            window.__createToast(
              <?= json_encode($toastMessage, JSON_UNESCAPED_UNICODE) ?>,
              <?= json_encode($toastType === 'error' ? 'error' : 'success') ?>,
              4000
            );
          }
        };
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', triggerToast);
        } else {
          triggerToast();
        }
      })();
    </script>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <script>
      window.location.hash = <?= json_encode($activeModal !== '' ? $activeModal : '#editModal') ?>;
    </script>
  <?php endif; ?>

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