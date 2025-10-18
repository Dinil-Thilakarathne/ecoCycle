<?php
// Profile photo upload logic
if (!isset($customer) || !is_array($customer)) {
  $customer = is_array($userProfile ?? null) ? $userProfile : null;
}

if (!is_array($customer)) {
  $customer = [
    "name" => "Sarah Anderson",
    "nic" => "991234567V",
    "description" => "EcoCycle customer focused on responsible waste disposal and recycling.",
    "email" => "sarah@example.com",
    "phone" => "0771234567",
    "address" => "45 Green Lane, Eco City, EC 45678",
    "bank" => [
      "bankName" => "Eco Bank",
      "branch" => "Eco City Branch",
      "holderName" => "Sarah Anderson",
      "accountNumber" => "1234567890"
    ],
    "profile_pic" => null
  ];
}

$customer['bank'] = array_merge([
  "bankName" => "",
  "branch" => "",
  "holderName" => "",
  "accountNumber" => "",
], is_array($customer['bank'] ?? null) ? $customer['bank'] : []);

$showToast = $showToast ?? false;
$toastMessage = $toastMessage ?? '';
$toastType = $toastType ?? 'success';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["uploadPhoto"])) {
    if (!empty($_FILES['photo']['name'])) {
      $targetDir = "uploads/";
      if (!is_dir($targetDir))
        mkdir($targetDir);
      $fileName = time() . "_" . basename($_FILES['photo']['name']);
      $targetFile = $targetDir . $fileName;
      if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
        $customer['profile_pic'] = $targetFile;
        $showToast = true;
      }
    }
  }
  if (isset($_POST["removePhoto"])) {
    $customer['profile_pic'] = null;
    $showToast = true;
    $toastMessage = 'Profile photo removed';
  }
  if (isset($_POST["saveProfile"])) {
    $customer["name"] = $_POST["name"];
    $customer["nic"] = $_POST["nic"];
    $customer["description"] = $_POST["description"];
    $customer["email"] = $_POST["email"];
    $customer["phone"] = $_POST["phone"];
    $customer["address"] = $_POST["address"];
    $showToast = true;
    $toastMessage = 'Profile saved successfully';
  }
  if (isset($_POST["saveBankDetails"])) {
    $customer["bank"]["bankName"] = $_POST["bankName"];
    $customer["bank"]["branch"] = $_POST["branch"];
    $customer["bank"]["holderName"] = $_POST["holderName"];
    $customer["bank"]["accountNumber"] = $_POST["accountNumber"];
    $showToast = true;
    $toastMessage = 'Bank details updated';
  }
  // Change password handling
  if (isset($_POST['updatePassword'])) {
    $current = $_POST['currentPassword'] ?? '';
    $new = $_POST['newPassword'] ?? '';
    $confirm = $_POST['confirmPassword'] ?? '';
    // For demo purposes we store plain password in $customer['password'].
    if (!isset($customer['password']))
      $customer['password'] = 'password123';
    if ($current !== $customer['password']) {
      $showToast = true;
      $toastType = 'error';
      $toastMessage = 'Current password is incorrect';
    } elseif ($new === '' || $new !== $confirm) {
      $showToast = true;
      $toastType = 'error';
      $toastMessage = 'New passwords do not match or are empty';
    } else {
      $customer['password'] = $new;
      $showToast = true;
      $toastMessage = 'Password updated successfully';
    }
  }
  // Delete account handling
  if (isset($_POST['deleteAccount'])) {
    // In a real app you'd delete from DB and log out; here we clear the $customer demo data
    $customer = null;
    $showToast = true;
    $toastMessage = 'Account deleted';
  }
}
?>

<style>
  :root {
    --accent: #16a34a;
    /* green */
    --accent-600: #15803d;
    --muted: #6b7280;
    --card-bg: #ffffff;
    --surface: #f8fafc;
    --danger: #ef4444;
  }

  /* Modal base */
  .form-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.45);
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding: 1rem;
  }

  .form-modal.show {
    display: flex;
  }

  .form-modal-content {
    background: var(--card-bg);
    border-radius: 12px;
    max-width: 720px;
    width: 100%;
    padding: 1.25rem;
    box-shadow: 0 12px 40px rgba(2, 6, 23, 0.12);
    position: relative;
    max-height: 90vh;
    overflow: auto;
  }

  .form-modal .close {
    position: absolute;
    right: 0.75rem;
    top: 0.5rem;
    font-size: 1.5rem;
    text-decoration: none;
    color: #333;
  }

  /* Cards and layout tweaks */
  .pc-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 6px 24px rgba(15, 23, 42, 0.04);
  }

  .p-info-card {
    gap: 1.5rem;
  }

  /* Avatar */
  .avatar {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid rgba(34, 197, 94, 0.08);
    box-shadow: 0 6px 16px rgba(34, 197, 94, 0.06);
  }

  /* Form groups */
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    margin-bottom: 0.8rem;
  }

  .form-group label {
    font-weight: 600;
    color: var(--muted);
    font-size: 0.95rem;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  textarea {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border-radius: 8px;
    border: 1px solid #e6e9ee;
    background: #fff;
    font-size: 0.95rem;
    color: #111827;
    box-sizing: border-box;
  }

  textarea {
    min-height: 90px;
    resize: vertical;
  }

  input:focus,
  textarea:focus {
    outline: none;
    border-color: rgba(22, 163, 74, 0.8);
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.06);
  }

  /* File input */
  .input-file {
    width: 100%;
    padding: 0.45rem;
    border-radius: 8px;
    border: 1px dashed #d1d5db;
    background: #fbfdff;
  }

  /* Buttons */
  .btn {
    display: inline-block;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.55rem 0.9rem;
    cursor: pointer;
    text-align: center;
    border: none;
    transition: all 0.12s ease;
  }

  .btn-primary,
  .p-submit {
    background: var(--accent);
    color: #fff;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.08);
  }

  .btn-primary:hover,
  .p-submit:hover {
    background: var(--accent-600);
    transform: translateY(-1px);
  }

  .btn-outline {
    background: transparent;
    color: var(--accent);
    border: 1px solid rgba(22, 163, 74, 0.12);
  }

  .p-btn {
    background: transparent;
    border: 1px solid #e6e9ee;
    color: #111827;
    padding: 0.45rem 0.9rem;
    border-radius: 8px;
  }

  .p-btn:hover {
    box-shadow: 0 8px 20px rgba(2, 6, 23, 0.06);
  }

  .p-btn-delete {
    background: transparent;
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.08);
    padding: 0.5rem 0.9rem;
    border-radius: 8px;
  }

  /* Bank grid */
  .bank-details-grid input {
    width: 100%;
  }

  /* Utility */
  .page-header__title {
    color: #0f172a;
  }

  /* Toast */
  .toast {
    position: fixed;
    right: 20px;
    bottom: 24px;
    background: #052e19;
    color: #e6ffed;
    padding: 0.6rem 1rem;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(2, 6, 23, 0.15);
    font-weight: 600;
    z-index: 1200;
  }

  /* Form error messages */
  .form-error {
    color: #7f1d1d;
    background: #ffefef;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    font-weight: 600;
    border: 1px solid #fecaca;
  }

  @media (max-width: 480px) {
    .form-modal-content {
      padding: 0.75rem;
      max-width: 420px;
    }
  }
</style>

<main class="content">
  <header class="page-header" style="margin-bottom: 0;">
    <div class="page-header__content" style="text-align:center;">
      <h2 class="page-header__title">Customer Profile</h2>
    </div>
  </header>
  <div style="text-align:center;margin-bottom:2rem;">
    <p class="page-header__description" style="font-size:1.1rem;color:#555;font-weight:500;">Update your profile here!
    </p>
  </div>
  <!-- Profile Photo Card -->
  <div class="pc-card"
    style="max-width:340px;margin:0 auto 2.5rem auto;display:flex;flex-direction:column;align-items:center;gap:1.5rem;box-shadow:0 2px 12px rgba(167,228,26,0.08);">
    <div style="width:100%;display:flex;flex-direction:column;align-items:center;">
      <img
        src="<?= isset($customer['profile_pic']) && $customer['profile_pic'] ? htmlspecialchars($customer['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($customer['name']) . '&background=8b5cf6&color=fff&size=128' ?>"
        class="avatar"
        style="width:120px;height:120px;object-fit:cover;border-radius:50%;margin-bottom:1rem;border:4px solid var(--primary-100,#e0f2fe);">
      <h2 class="profile-photo-title" style="font-size:1.1rem;font-weight:600;">Profile Photo</h2>
    </div>
    <form method="POST" enctype="multipart/form-data"
      style="width:100%;display:flex;flex-direction:column;align-items:center;gap:0.5rem;">
      <input type="file" name="photo" accept="image/*" required class="input-file">
      <button class="btn btn-primary" type="submit" name="uploadPhoto" style="width:100%;">Upload</button>
    </form>
    <form method="POST" style="width:100%;display:flex;justify-content:center;">
      <button class="btn btn-outline" type="submit" name="removePhoto" style="width:100%;">Remove Photo</button>
    </form>
  </div>

  <div class="p-info-card"
    style="display:flex;flex-direction:column;align-items:center;gap:2rem;width:100%;max-width:100vw;margin:0;padding:0;">
    <div
      style="display:flex;flex-direction:row;gap:1.5rem;width:100%;justify-content:space-between;margin:0;padding:0;">
      <div class="pc-card" style="flex:1 1 0;min-width:260px;margin:0;padding:1.5rem 1.2rem;box-sizing:border-box;">
        <h3 style="font-size: 20px; font-weight: bold;">Customer Information</h3>
        <div class="form-group"><label>Name</label>
          <input type="text" value="<?= htmlspecialchars($customer['name']) ?>" disabled>
        </div>
        <div class="form-group"><label>NIC</label>
          <input type="text" value="<?= htmlspecialchars($customer['nic']) ?>" disabled>
        </div>
        <div class="form-group"><label>Description</label>
          <textarea disabled><?= htmlspecialchars($customer['description']) ?></textarea>
        </div>
      </div>
      <!-- Contact Info -->
      <div class="pc-card" style="flex:1 1 0;min-width:260px;margin:0;padding:1.5rem 1.2rem;box-sizing:border-box;">
        <h3 style="font-size: 20px; font-weight: bold;">Contact Information</h3>
        <div class="form-group"><label>Email</label>
          <input type="text" value="<?= htmlspecialchars($customer['email']) ?>" disabled>
        </div>
        <div class="form-group"><label>Phone</label>
          <input type="text" value="<?= htmlspecialchars($customer['phone']) ?>" disabled>
        </div>
        <div class="form-group"><label>Address</label>
          <textarea disabled><?= htmlspecialchars($customer['address']) ?></textarea>
        </div>
      </div>
    </div>
    <!-- Bank Account Details card -->
    <div class="pc-card"
      style="width:100%;margin:0;box-shadow:0 2px 12px rgba(167,228,26,0.10);padding:1.5rem 1.2rem;box-sizing:border-box;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 0;">Bank Details</h3>
        <button class="btn btn-outline" style="padding: 0.3rem 1.2rem; font-size: 0.95rem;"
          onclick="openBankModal()">Edit</button>
      </div>
      <div class="bank-details-grid"
        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
        <div>
          <label>Bank Name</label>
          <input type="text"
            value="<?= isset($customer['bank']['bankName']) ? htmlspecialchars($customer['bank']['bankName']) : '' ?>"
            disabled>
        </div>
        <div>
          <label>Branch</label>
          <input type="text"
            value="<?= isset($customer['bank']['branch']) ? htmlspecialchars($customer['bank']['branch']) : '' ?>"
            disabled>
        </div>
        <div>
          <label>Account Holder's Name</label>
          <input type="text"
            value="<?= isset($customer['bank']['holderName']) ? htmlspecialchars($customer['bank']['holderName']) : '' ?>"
            disabled>
        </div>
        <div>
          <label>Account Number</label>
          <input type="text"
            value="<?= isset($customer['bank']['accountNumber']) ? htmlspecialchars($customer['bank']['accountNumber']) : '' ?>"
            disabled>
        </div>
      </div>
    </div>
  </div>
  <!-- Security & Actions -->
  <div class="pc-card" style="text-align:center;margin:2.5rem auto 0 auto;max-width:600px;">
    <h3 style="font-size: 20px; font-weight: bold;">Actions</h3>
    <button class="btn btn-primary" onclick="openEditModal()">Edit Profile</button>
    <button class="btn btn-outline" onclick="openPasswordModal()">Change Password</button>
    <button class="btn btn-outline p-btn-delete" onclick="openDeleteModal()">Delete Account</button>
  </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeEditModal()">&times;</a>
    <h2>Edit Profile</h2>
    <form method="POST">
      <div class="form-group"><label class="form-lable">Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>">
      </div>
      <!-- Removed Type field from edit modal -->
      <div class="form-group"><label class="form-lable">NIC</label>
        <input type="text" name="nic" value="<?= htmlspecialchars($customer['nic']) ?>" maxlength="15"
          pattern="[0-9Vv]{1,15}" title="NIC should be up to 15 characters (numbers and V)">
      </div>
      <div class="form-group"><label class="form-lable">Description</label>
        <textarea name="description"><?= htmlspecialchars($customer['description']) ?></textarea>
      </div>
      <div class="form-group"><label class="form-lable">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>">
      </div>
      <div class="form-group"><label class="form-lable">Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" pattern="0[0-9]{9,}"
          maxlength="10" title="Phone number must start with 0 and contain only numbers (10 digits)">
      </div>
      <div class="form-group"><label class="form-lable">Address</label>
        <textarea name="address"><?= htmlspecialchars($customer['address']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary" name="saveProfile">Save Changes</button>
    </form>
  </div>
</div>

<!-- Bank Details Modal -->
<div id="bankModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeBankModal()">&times;</a>
    <h2>Edit Bank Details</h2>
    <form method="POST">
      <div class="form-group">
        <label class="form-lable">Bank Name</label>
        <input type="text" name="bankName"
          value="<?= isset($customer['bank']['bankName']) ? htmlspecialchars($customer['bank']['bankName']) : '' ?>"
          required>
      </div>
      <div class="form-group">
        <label class="form-lable">Branch</label>
        <input type="text" name="branch"
          value="<?= isset($customer['bank']['branch']) ? htmlspecialchars($customer['bank']['branch']) : '' ?>"
          required>
      </div>
      <div class="form-group">
        <label class="form-lable">Account Holder's Name</label>
        <input type="text" name="holderName"
          value="<?= isset($customer['bank']['holderName']) ? htmlspecialchars($customer['bank']['holderName']) : '' ?>"
          required>
      </div>
      <div class="form-group">
        <label class="form-lable">Account Number</label>
        <input type="text" name="accountNumber"
          value="<?= isset($customer['bank']['accountNumber']) ? htmlspecialchars($customer['bank']['accountNumber']) : '' ?>"
          required maxlength="20" pattern="[0-9]{1,20}" title="Account number must be up to 20 digits and only numbers">
      </div>
      <button type="submit" class="btn btn-primary" name="saveBankDetails">Save Bank Details</button>
    </form>
  </div>
</div>

<!-- Password Modal -->
<div id="passwordModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closePasswordModal()">&times;</a>
    <h2>Change Password</h2>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeDeleteModal()">&times;</a>
    <h2>Delete Account</h2>
    <p>Are you sure you want to delete your account? This action cannot be undone.</p>
    <form method="POST" style="display:flex;gap:0.6rem;justify-content:flex-end;margin-top:1rem;">
      <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
      <button type="submit" name="deleteAccount" class="btn btn-primary p-btn-delete">Delete</button>
    </form>
  </div>
</div>

<script>
  // central modal controller
  function showModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function hideModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('show');
    document.body.style.overflow = 'auto';
  }
  function openEditModal() { showModal('editModal'); }
  function closeEditModal() { hideModal('editModal'); }
  function openBankModal() { showModal('bankModal'); }
  function closeBankModal() { hideModal('bankModal'); }
  function openPasswordModal() { showModal('passwordModal'); }
  function closePasswordModal() { hideModal('passwordModal'); }
  function openDeleteModal() { showModal('deleteModal'); }
  function closeDeleteModal() { hideModal('deleteModal'); }

  ['editModal', 'bankModal', 'passwordModal', 'deleteModal'].forEach(id => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.addEventListener('click', function (e) {
      if (e.target === modal) hideModal(id);
    });
  });

  // close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      ['editModal', 'bankModal', 'passwordModal', 'deleteModal'].forEach(id => hideModal(id));
    }
  });

  // auto-close on submit (useful for normal POSTs and AJAX)
  document.querySelectorAll('.form-modal form').forEach(form => {
    form.addEventListener('submit', function () {
      const modal = form.closest('.form-modal');
      if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
      }
    });
  });

  // Password form validation: new and confirm must match and meet basic rules
  (function () {
    const pwForm = document.querySelector('#passwordModal form');
    if (!pwForm) return;
    pwForm.addEventListener('submit', function (e) {
      const newPassEl = pwForm.querySelector('input[name="newPassword"]');
      const confPassEl = pwForm.querySelector('input[name="confirmPassword"]');
      const newPass = newPassEl ? newPassEl.value.trim() : '';
      const confPass = confPassEl ? confPassEl.value.trim() : '';
      // ensure error container
      let err = pwForm.querySelector('.form-error');
      if (!err) {
        err = document.createElement('div');
        err.className = 'form-error';
        pwForm.insertBefore(err, pwForm.firstChild);
      }
      // basic validations
      if (newPass.length < 6) {
        e.preventDefault();
        err.textContent = 'New password must be at least 6 characters.';
        newPassEl.focus();
        showModal('passwordModal'); // reopen if auto-closed
        return false;
      }
      if (newPass !== confPass) {
        e.preventDefault();
        err.textContent = 'New password and confirmation do not match.';
        confPassEl.focus();
        showModal('passwordModal');
        return false;
      }
      // clear error on success
      err.textContent = '';
      return true;
    });
  })();
</script>

<!-- Toast Notification -->
<?php if ($showToast): ?>
  <?php $safeMsg = htmlspecialchars($toastMessage ?: '✅ Saved'); ?>
  <?php if ($toastType === 'error'): ?>
    <div class="toast" style="background:#3b0210;color:#ffd6d6;">⚠️ <?= $safeMsg ?></div>
  <?php else: ?>
    <div class="toast">✅ <?= $safeMsg ?></div>
  <?php endif; ?>
<?php endif; ?>