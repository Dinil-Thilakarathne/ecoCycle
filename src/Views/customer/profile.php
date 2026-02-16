<?php
$customer = is_array($userProfile ?? null) ? $userProfile : [];
$errors = is_array($validationErrors ?? null) ? $validationErrors : ($errors ?? []);
$statusMessage = is_string($statusMessage ?? null) ? $statusMessage : '';
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];

$displayFirstName = $customer['firstName'] ?? '';
$displayLastName = $customer['lastName'] ?? '';
$displayName = trim($customer['name'] ?? ($displayFirstName . ' ' . $displayLastName));
$displayName = $displayName !== '' ? $displayName : 'N/A';

$displayEmail = $customer['email'] ?? '';
$displayPhone = $customer['phone'] ?? '';
$displayAddress = $customer['address'] ?? '';
$displayPostal = $customer['postalCode'] ?? '';
$displayNic = $customer['nic'] ?? ($customer['metadata']['nic'] ?? '');
$displayDescription = $customer['description'] ?? '';

$bank = is_array($customer['bank'] ?? null) ? $customer['bank'] : [];
$displayBankName = $bank['bankName'] ?? '';
$displayBankBranch = $bank['branch'] ?? '';
$displayBankHolder = $bank['holderName'] ?? '';
$displayBankAccount = $customer['bankAccount'] ?? ($bank['accountNumber'] ?? '');

$editFirstName = $oldInput['firstName'] ?? $displayFirstName;
$editLastName = $oldInput['lastName'] ?? $displayLastName;
$editEmail = $oldInput['email'] ?? $displayEmail;
$editPhone = $oldInput['phone'] ?? $displayPhone;
$editAddress = $oldInput['address'] ?? $displayAddress;
$editPostalCode = $oldInput['postalCode'] ?? $displayPostal;
$editBankAccount = $oldInput['bankAccount'] ?? $displayBankAccount;

$profileImage = $customer['profile_pic'] ?? ($customer['profileImage'] ?? ($customer['profileImagePath'] ?? null));
if (is_string($profileImage) && preg_match('#^https?://#i', $profileImage)) {
  $profileImageSrc = $profileImage;
} elseif (is_string($profileImage) && $profileImage !== '') {
  $profileImageSrc = '/' . ltrim($profileImage, '/');
} else {
  $profileImageSrc = '/assets/avatar.png';
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

$csrfToken = csrf_token();
?>



<main class="content profile-page">
  <header class="page-header">
    <div class="page-header__content">
      <h1 class="page-header__title">Customer Profile</h1>
      <p class="page-header__description">Update your profile here!</p>
    </div>
  </header>

  <div class="p-info-card">
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Customer Information</h3>
      <div class="profile-picture">
        <img src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Profile Picture" width="100">
      </div>
      <div class="form-group"><label>First Name</label>
        <input type="text" value="<?= htmlspecialchars($displayFirstName) ?>" disabled>
      </div>
      <div class="form-group"><label>Last Name</label>
        <input type="text" value="<?= htmlspecialchars($displayLastName) ?>" disabled>
      </div>
    </div>

    <div class="pc-card">
      <button type="button" class="btn btn-outline"
          style="position: absolute; right: 6%; top: 0%; background:var(--info-light);"
          onclick="openModal('editModal')">
          ✏️ Edit Profile
      </button>
  
      <h3 style="font-size: 20px; font-weight: bold;">Contact Information</h3>
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
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">About You</h3>
      <textarea disabled><?= htmlspecialchars($displayDescription ?: 'Tell us a bit about yourself...') ?></textarea>
    </div>

    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Bank Details</h3>
      <div class="bank-summary">
        <div class="form-group"><label>Bank Name</label>
          <input type="text" value="<?= htmlspecialchars($displayBankName) ?>" disabled>
        </div>
        <div class="form-group"><label>Branch</label>
          <input type="text" value="<?= htmlspecialchars($displayBankBranch) ?>" disabled>
        </div>
        <div class="form-group"><label>Account Holder</label>
          <input type="text" value="<?= htmlspecialchars($displayBankHolder) ?>" disabled>
        </div>
        <div class="form-group"><label>Account Number</label>
          <input type="text" value="<?= htmlspecialchars($displayBankAccount) ?>" disabled>
        </div>
      </div>
      <div class="waste-tags">
        <button type="button" class="btn btn-outline"
            style="margin-bottom: 5px; background:var(--info-light);"
            onclick="openModal('bankdetail')">
            Edit Bank Details
        </button>
      </div>
    </div>
  </div>

  <div class="pc-card">
    <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
  
      <button type="button" class="btn btn-primary"
       style="margin-bottom: 5px"
       onclick="openModal('passwordModal')">
       Change Password
      </button>
    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <button type="submit" name="deleteAccount" class="btn p-btn-delete">Delete Account</button>
    </form>
  </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
  <button type="button" class="close" onclick="closeModal(this)">&times;</button>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Profile</h2>
    <?php if (!empty($errors) && ($activeModal ?? '') === '#editModal'): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label>Profile Picture</label>
        <input type="file" name="photo" accept="image/*">
      </div>
      <div class="form-actions">
        <button type="submit" name="uploadPhoto" class="btn btn-outline" style="width:100%; margin-bottom:8px;">Upload
          Photo</button>
        <?php if ($profileImage && $profileImageSrc !== 'assets/avatar.png'): ?>
          <button type="submit" name="removePhoto" class="btn btn-outline p-btn-delete" style="width:100%;">Remove
            Photo</button>
        <?php endif; ?>
      </div>
      <div class="form-group"><label class="form-lable">First Name</label>
        <input type="text" name="firstName" value="<?= htmlspecialchars($editFirstName) ?>" required>
      </div>
      <div class="form-group"><label class="form-lable">Last Name</label>
        <input type="text" name="lastName" value="<?= htmlspecialchars($editLastName) ?>" required>
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
      <div class="form-group"><label class="form-lable">About You</label>
        <textarea name="description"><?= htmlspecialchars($displayDescription) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary outline" name="saveProfile" style="width:100%;">Save Changes</button>
    </form>
  </div>
</div>

<!-- Bank Details Modal -->
<div id="bankdetail" class="form-modal">
  <div class="form-modal-content">
    <button type="button" class="close" onclick="closeModal(this)">&times;</button>
    <h2 style="font-size: 20px; font-weight: bold;">Bank Details</h2>
    <?php if (!empty($errors) && ($activeModal ?? '') === '#bankdetail'): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label class="form-lable">Bank Name</label>
        <input type="text" name="bankName" value="<?= htmlspecialchars($displayBankName) ?>">
      </div>
      <div class="form-group"><label class="form-lable">Branch</label>
        <input type="text" name="branch" value="<?= htmlspecialchars($displayBankBranch) ?>">
      </div>
      <div class="form-group"><label class="form-lable">Account Holder's Name</label>
        <input type="text" name="holderName" value="<?= htmlspecialchars($displayBankHolder) ?>">
      </div>
      <div class="form-group"><label class="form-lable">Account Number</label>
        <input type="text" name="bankAccount" value="<?= htmlspecialchars($displayBankAccount) ?>">
      </div>
      <button type="submit" name="saveBankDetails" class="btn btn-primary outline" style="width: 100%">Save Details</button>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="form-modal">
  <div class="form-modal-content">
    <button type="button" class="close" onclick="closeModal(this)">&times;</button>
    <h2 style="font-size: 20px; font-weight: bold;">Change Password</h2>
    <?php if (!empty($errors) && ($activeModal ?? '') === '#passwordModal'): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label>Current Password</label>
        <input type="password" name="currentPassword" required>
      </div>
      <div class="form-group"><label>New Password</label>
        <input type="password" name="newPassword" minlength="8" required>
      </div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirmPassword" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-primary outline" name="updatePassword" style="width:100%;">Update
        Password</button>
    </form>
  </div>
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
<script>
function openModal(id) {
  console.log("Opening modal:", id); // DEBUG
  const modal = document.getElementById(id);
  if (modal) {
    modal.style.display = "block";
  } else {
    console.error("Modal not found:", id);
  }
}

function closeModal(element) {
  const modal = element.closest('.form-modal');
  if (modal) {
    modal.style.display = "none";
  }
}

window.onclick = function(event) {
  document.querySelectorAll('.form-modal').forEach(modal => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
};
</script>


<?php if (!empty($errors) && !empty($activeModal)): ?>
<?php endif; ?>