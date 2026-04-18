<?php
$flashActiveModal = session()->getFlash('active_modal');
$flashErrors = session()->getFlash('errors');
$flashStatus = session()->getFlash('status');
$flashOld = session()->getFlash('old');

$activeModal = $flashActiveModal ?? ($activeModal ?? '');
$errors = is_array($flashErrors) ? $flashErrors : (is_array($validationErrors ?? null) ? $validationErrors : ($errors ?? []));
$customer = is_array($userProfile ?? null) ? $userProfile : [];
$statusMessage = is_string($flashStatus) ? $flashStatus : (is_string($statusMessage ?? null) ? $statusMessage : '');
$oldInput = is_array($flashOld) ? $flashOld : (is_array($oldInput ?? null) ? $oldInput : []);

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
      <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Customer Information</h3>
      <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 25px;">
        <div class="profile-picture" style="margin: 0;">
          <img src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Profile Picture" width="100" style="border-radius: 50%; object-fit: cover; height: 100px;">
        </div>
        <form method="POST" action="/customer/profile" enctype="multipart/form-data" style="margin: 0;">
          <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <button type="button" class="btn btn-outline" style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 6px 12px; color: #555; display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" onclick="document.getElementById('photoUploadInput').click()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg> Change Photo
          </button>
          <input type="file" id="photoUploadInput" name="photo" accept="image/*" style="display: none;" onchange="this.form.submit()">
          <input type="hidden" name="uploadPhoto" value="1">
        </form>
      </div>
      <div class="form-group"><label>First Name</label>
        <input type="text" value="<?= htmlspecialchars($displayFirstName) ?>" disabled>
      </div>
      <div class="form-group"><label>Last Name</label>
        <input type="text" value="<?= htmlspecialchars($displayLastName) ?>" disabled>
      </div>
    </div>

    <div class="pc-card" style="position: relative;">
      <button type="button" class="btn btn-outline"
          style="position: absolute; right: 20px; top: 20px; background:#e0f0ff; border: none; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 5px; color: #333;"
          onclick="openModal('editModal')">
          <span style="color: #ffaa00;">✏️</span> Edit Profile
      </button>
  
      <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Contact Information</h3>
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
      <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Security & Privacy</h3>

      <button type="button" class="btn btn-primary"
       style="margin-bottom: 10px"
       onclick="openModal('passwordModal')">
       Change Password
      </button>

      <form method="POST" id="deleteAccountForm" onsubmit="handleDeleteAccountSubmit(event)">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <button type="submit" name="deleteAccount" id="deleteAccountBtn" class="btn p-btn-delete">Delete Account</button>
      </form>
    </div>

    <div class="pc-card" style="position: relative;">
      <button type="button" class="btn btn-outline"
          style="position: absolute; right: 20px; top: 20px; background:#e0f0ff; border: none; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 5px; color: #333;"
          onclick="openModal('bankdetail')">
          <span style="color: #ffaa00;">✏️</span> Edit Bank Details
      </button>

      <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Bank Details</h3>
      <div class="bank-summary" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div class="form-group"><label>Bank Name</label>
          <input type="text" value="<?= htmlspecialchars($displayBankName) ?>" disabled>
        </div>
        <div class="form-group"><label>Account Number</label>
          <input type="text" value="<?= htmlspecialchars($displayBankAccount) ?>" disabled>
        </div>
        <div class="form-group"><label>User's Name</label>
          <input type="text" value="<?= htmlspecialchars($displayBankHolder) ?>" disabled>
        </div>
        <div class="form-group"><label>Bank Branch</label>
          <input type="text" value="<?= htmlspecialchars($displayBankBranch) ?>" disabled>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content" style="max-width: 600px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
  <button type="button" class="close" onclick="closeModal(this)" style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
    <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Edit Profile</h2>
    <?php if (!empty($errors) && ($activeModal ?? '') === '#editModal'): ?>
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
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
          <input type="tel" name="phone" value="<?= htmlspecialchars($editPhone) ?>" pattern="0[0-9]{9}" maxlength="10" required>
        </div>
        <div class="form-group"><label class="form-lable">Address</label>
          <textarea name="address" required style="height: 80px;"><?= htmlspecialchars($editAddress) ?></textarea>
        </div>
      </div>
      <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
        <button type="submit" class="btn btn-primary outline" name="saveProfile" style="width: auto; padding: 10px 20px; background: #d1f2e0; color: #1e7045; border: 1px solid #1e7045; border-radius: 8px;">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Bank Details Modal -->
<div id="bankdetail" class="form-modal">
  <div class="form-modal-content" style="max-width: 600px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
    <button type="button" class="close" onclick="closeModal(this)" style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
    <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Bank Details</h2>
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
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div class="form-group"><label class="form-lable">Bank Name</label>
          <input type="text" name="bankName" value="<?= htmlspecialchars($displayBankName) ?>">
        </div>
        <div class="form-group"><label class="form-lable">Account Number</label>
          <input type="text" name="bankAccount" value="<?= htmlspecialchars($displayBankAccount) ?>">
        </div>
        <div class="form-group"><label class="form-lable">User's Name</label>
          <input type="text" name="holderName" value="<?= htmlspecialchars($displayBankHolder) ?>">
        </div>
        <div class="form-group"><label class="form-lable">Bank Branch</label>
          <input type="text" name="branch" value="<?= htmlspecialchars($displayBankBranch) ?>">
        </div>
      </div>
      <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
        <button type="submit" name="saveBankDetails" class="btn btn-primary outline" style="width: auto; padding: 10px 20px; background: #d1f2e0; color: #1e7045; border: 1px solid #1e7045; border-radius: 8px;">Save Details</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="form-modal">
  <div class="form-modal-content" style="max-width: 500px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
    <button type="button" class="close" onclick="closeModal(this)" style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
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
    <form method="POST" onsubmit="return validatePasswordForm()">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div style="display: flex; flex-direction: column; gap: 15px;">
        <div class="form-group"><label class="form-lable">Current Password</label>
          <input type="password" name="currentPassword" required>
        </div>
        <div class="form-group"><label class="form-lable">New Password</label>
          <input type="password" name="newPassword" id="newPassword" minlength="8" required oninput="checkPasswordMatch()">
        </div>
        <div class="form-group"><label class="form-lable">Confirm New Password</label>
          <input type="password" name="confirmPassword" id="confirmPassword" minlength="8" required oninput="checkPasswordMatch()">
          <small id="passwordMatchMessage" style="display:block; margin-top:5px; font-weight:bold;"></small>
        </div>
      </div>
      <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
        <button type="submit" class="btn btn-primary outline" name="updatePassword" style="width: auto; padding: 10px 20px; background: #d1f2e0; color: #1e7045; border: 1px solid #1e7045; border-radius: 8px;">Update Password</button>
      </div>
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
function checkPasswordMatch() {
    const password = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const message = document.getElementById("passwordMatchMessage");

    if (confirmPassword === "") {
        message.innerText = "";
    } else if (password !== confirmPassword) {
        message.innerText = "Passwords do not match!";
        message.style.color = "red";
    } else {
        message.innerText = "Passwords match!";
        message.style.color = "green";
    }
}

function validatePasswordForm() {
    const password = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    if (password !== confirmPassword) {
        alert("New Password and Confirm Password do not match!");
        return false;
    }
    return true;
}

let isDeleteConfirmed = false;
async function handleDeleteAccountSubmit(event) {
    if (isDeleteConfirmed) return; // Allow normal submission if already confirmed
    event.preventDefault();

    const confirmed = await createConfirmModal({
        title: 'Delete Account',
        message: 'Are you sure you want to delete your account? This action cannot be undone.',
        confirmLabel: 'Delete',
        cancelLabel: 'Cancel'
    });

    if (confirmed) {
        isDeleteConfirmed = true;
        // Submit the form programmatically while preserving the submitter button's payload
        const form = event.target;
        const btn = document.getElementById('deleteAccountBtn');
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(btn);
        } else {
            // Fallback for very old browsers, append hidden input manually
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'deleteAccount';
            hidden.value = '';
            form.appendChild(hidden);
            form.submit();
        }
    }
}

// Simple HTML escape to avoid injection
function escapeHtml(s) {
    if (!s) return '';
    return ('' + s).replace(/[&"'<>]/g, function (c) { return { '&': '&amp;', '"': '&quot;', '\'': '&#39;', '<': '&lt;', '>': '&gt;' }[c]; });
}

// Simple confirm modal that returns a Promise<boolean>
function createConfirmModal({ title = 'Confirm', message = '', confirmLabel = 'OK', cancelLabel = 'Cancel' } = {}) {
    return new Promise((resolve) => {
        const backdrop = document.createElement('div');
        backdrop.className = 'simple-modal-backdrop';
        backdrop.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2000;padding:1rem;';

        const dialog = document.createElement('div');
        dialog.style.cssText = 'background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(15,23,42,0.16);width:480px;max-width:100%;padding:1.25rem;';

        const header = document.createElement('div');
        header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;';
        const titleEl = document.createElement('h3');
        titleEl.textContent = title;
        titleEl.style.cssText = 'margin:0;font-size:1.05rem;font-weight:700;color:#111827;';

        const body = document.createElement('div');
        body.innerHTML = `<p style="margin:0 0 1rem 0;color:#374151;">${escapeHtml(message)}</p>`;

        const footer = document.createElement('div');
        footer.style.cssText = 'display:flex;justify-content:flex-end;gap:0.5rem;';

        const btnCancel = document.createElement('button');
        btnCancel.type = 'button';
        btnCancel.textContent = cancelLabel;
        btnCancel.style.cssText = 'padding:0.5rem 0.85rem;border-radius:8px;border:none;background:#6b7280;color:#fff;cursor:pointer;';

        const btnConfirm = document.createElement('button');
        btnConfirm.type = 'button';
        btnConfirm.textContent = confirmLabel;
        btnConfirm.style.cssText = 'padding:0.5rem 0.85rem;border-radius:8px;border:none;background:#dc2626;color:#fff;cursor:pointer;';

        btnCancel.addEventListener('click', () => {
            backdrop.remove();
            resolve(false);
        });

        btnConfirm.addEventListener('click', () => {
            backdrop.remove();
            resolve(true);
        });

        footer.appendChild(btnCancel);
        footer.appendChild(btnConfirm);

        header.appendChild(titleEl);
        dialog.appendChild(header);
        dialog.appendChild(body);
        dialog.appendChild(footer);
        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);
    });
}

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

document.addEventListener('DOMContentLoaded', function() {
    const activeModal = <?= json_encode($activeModal) ?>;
    if (activeModal) {
        openModal(activeModal.replace('#', ''));
    }
});
</script>


<?php if (!empty($errors) && !empty($activeModal)): ?>
<?php endif; ?>