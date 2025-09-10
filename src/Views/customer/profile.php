


<?php
// Profile photo upload logic
if (!isset($customer)) {
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
$showToast = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["uploadPhoto"])) {
    if (!empty($_FILES['photo']['name'])) {
      $targetDir = "uploads/";
      if (!is_dir($targetDir)) mkdir($targetDir);
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
  }
  if (isset($_POST["saveProfile"])) {
    $customer["name"] = $_POST["name"];
    $customer["nic"] = $_POST["nic"];
    $customer["description"] = $_POST["description"];
    $customer["email"] = $_POST["email"];
    $customer["phone"] = $_POST["phone"];
    $customer["address"] = $_POST["address"];
    $showToast = true;
  }
  if (isset($_POST["saveBankDetails"])) {
    $customer["bank"]["bankName"] = $_POST["bankName"];
    $customer["bank"]["branch"] = $_POST["branch"];
    $customer["bank"]["holderName"] = $_POST["holderName"];
    $customer["bank"]["accountNumber"] = $_POST["accountNumber"];
    $showToast = true;
  }
}
?>


<main class="content">
    <header class="page-header" style="margin-bottom: 0;">
        <div class="page-header__content" style="text-align:center;">
            <h2 class="page-header__title">Customer Profile</h2>
        </div>
    </header>
    <div style="text-align:center;margin-bottom:2rem;">
        <p class="page-header__description" style="font-size:1.1rem;color:#555;font-weight:500;">Update your profile here!</p>
    </div>
    <!-- Profile Photo Card -->
    <div class="pc-card" style="max-width:340px;margin:0 auto 2.5rem auto;display:flex;flex-direction:column;align-items:center;gap:1.5rem;box-shadow:0 2px 12px rgba(167,228,26,0.08);">
      <div style="width:100%;display:flex;flex-direction:column;align-items:center;">
        <img src="<?= isset($customer['profile_pic']) && $customer['profile_pic'] ? htmlspecialchars($customer['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($customer['name']) . '&background=8b5cf6&color=fff&size=128' ?>" class="avatar" style="width:120px;height:120px;object-fit:cover;border-radius:50%;margin-bottom:1rem;border:4px solid var(--primary-100,#e0f2fe);">
        <h2 class="profile-photo-title" style="font-size:1.1rem;font-weight:600;">Profile Photo</h2>
      </div>
      <form method="POST" enctype="multipart/form-data" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:0.5rem;">
        <input type="file" name="photo" accept="image/*" required class="input-file">
        <button class="btn btn-primary" type="submit" name="uploadPhoto" style="width:100%;">Upload</button>
      </form>
      <form method="POST" style="width:100%;display:flex;justify-content:center;">
        <button class="btn btn-outline" type="submit" name="removePhoto" style="width:100%;">Remove Photo</button>
      </form>
    </div>

    <div class="p-info-card" style="display:flex;flex-direction:column;align-items:center;gap:2rem;width:100%;max-width:100vw;margin:0;padding:0;">
      <div style="display:flex;flex-direction:row;gap:1.5rem;width:100%;justify-content:space-between;margin:0;padding:0;">
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
  <div class="pc-card" style="width:100%;margin:0;box-shadow:0 2px 12px rgba(167,228,26,0.10);padding:1.5rem 1.2rem;box-sizing:border-box;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 0;">Bank Details</h3>
          <button class="p-btn" style="padding: 0.3rem 1.2rem; font-size: 0.95rem;" onclick="openBankModal()">Edit</button>
        </div>
        <div class="bank-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
          <div>
            <label>Bank Name</label>
            <input type="text" value="<?= isset($customer['bank']['bankName']) ? htmlspecialchars($customer['bank']['bankName']) : '' ?>" disabled>
          </div>
          <div>
            <label>Branch</label>
            <input type="text" value="<?= isset($customer['bank']['branch']) ? htmlspecialchars($customer['bank']['branch']) : '' ?>" disabled>
          </div>
          <div>
            <label>Account Holder's Name</label>
            <input type="text" value="<?= isset($customer['bank']['holderName']) ? htmlspecialchars($customer['bank']['holderName']) : '' ?>" disabled>
          </div>
          <div>
            <label>Account Number</label>
            <input type="text" value="<?= isset($customer['bank']['accountNumber']) ? htmlspecialchars($customer['bank']['accountNumber']) : '' ?>" disabled>
          </div>
        </div>
      </div>
    </div>
    <!-- Security & Actions -->
    <div class="pc-card" style="text-align:center;margin:2.5rem auto 0 auto;max-width:600px;">
      <h3 style="font-size: 20px; font-weight: bold;">Actions</h3>
      <button class="p-btn" onclick="openEditModal()">Edit Profile</button>
      <button class="p-btn" onclick="openPasswordModal()">Change Password</button>
      <button class="p-btn-delete">Delete Account</button>
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

<!-- Bank Details Modal -->
<div id="bankModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeBankModal()">&times;</a>
    <h2>Edit Bank Details</h2>
    <form method="POST">
      <div class="form-group">
        <label class="form-lable">Bank Name</label>
        <input type="text" name="bankName" value="<?= isset($customer['bank']['bankName']) ? htmlspecialchars($customer['bank']['bankName']) : '' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-lable">Branch</label>
        <input type="text" name="branch" value="<?= isset($customer['bank']['branch']) ? htmlspecialchars($customer['bank']['branch']) : '' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-lable">Account Holder's Name</label>
        <input type="text" name="holderName" value="<?= isset($customer['bank']['holderName']) ? htmlspecialchars($customer['bank']['holderName']) : '' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-lable">Account Number</label>
  <input type="text" name="accountNumber" value="<?= isset($customer['bank']['accountNumber']) ? htmlspecialchars($customer['bank']['accountNumber']) : '' ?>" required maxlength="20" pattern="[0-9]{1,20}" title="Account number must be up to 20 digits and only numbers">
      </div>
      <button type="submit" class="p-submit" name="saveBankDetails">Save Bank Details</button>
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
      <button class="btn btn-primary" type="submit" name="updatePassword" style="margin-top: 0.5rem;">Update Password</button>
    </form>
  </div>
</div>

<script>
function openEditModal() {
  document.getElementById('editModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  document.body.style.overflow = 'auto';
}
function openBankModal() {
  document.getElementById('bankModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeBankModal() {
  document.getElementById('bankModal').style.display = 'none';
  document.body.style.overflow = 'auto';
}
function openPasswordModal() {
  document.getElementById('passwordModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closePasswordModal() {
  document.getElementById('passwordModal').style.display = 'none';
  document.body.style.overflow = 'auto';
}
</script>

<!-- Toast Notification -->
<?php if ($showToast): ?>
<div class="toast">✅ Profile updated successfully!</div>
<?php endif; ?>
