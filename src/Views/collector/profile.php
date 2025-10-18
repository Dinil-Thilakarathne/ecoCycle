<?php
$collector = is_array($collectorProfile ?? null) ? $collectorProfile : null;

if (!is_array($collector)) {
  $collector = [
    "name" => "John Doe",
    "email" => "collector@example.com",
    "phone" => "0771234567",
    "address" => "45 Blue Street, Colombo",
    "bank" => [
      "account_name" => "John Doe",
      "account_number" => "1234567890",
      "bank_name" => "National Bank",
      "branch" => "Colombo Main"
    ]
  ];
}

$collector = array_merge([
  "name" => "",
  "email" => "",
  "phone" => "",
  "address" => "",
  "bank" => [],
], $collector);

$collector['bank'] = array_merge([
  "account_name" => "",
  "account_number" => "",
  "bank_name" => "",
  "branch" => "",
], is_array($collector['bank'] ?? null) ? $collector['bank'] : []);

$toastMessage = $toastMessage ?? "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST['action'] ?? '';
  switch ($action) {
    case "update_info":
      $collector["name"] = $_POST["name"];
      $collector["email"] = $_POST["email"];
      $collector["phone"] = $_POST["phone"];
      $collector["address"] = $_POST["address"];
      $toastMessage = "✅ Collector info updated successfully!";
      break;
    case "change_password":
      $toastMessage = "🔐 Password changed successfully!";
      break;
    case "two_factor":
      $mode = $_POST["mode"] ?? "enabled";
      $toastMessage = $mode === "enabled"
        ? "📲 Two-Factor Authentication enabled!"
        : "📲 Two-Factor Authentication disabled!";
      break;
    case "delete_account":
      $toastMessage = "🗑️ Account deleted successfully!";
      break;
  }
}
?>

<main class="content">

  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Collector Profile</h2>
      <p class="page-header__description">Update your profile and security settings.</p>
    </div>
  </header>

  <!-- Collector Info -->
  <a href="#editModal" class="edit-btn">✏️ Edit Profile</a>

  <div class="p-info-card">
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Collector Information</h3>
      <div class="form-group"><label>Name</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['name'] ?? '')) ?>" disabled>
      </div>
      <div class="form-group"><label>Email</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['email'] ?? '')) ?>" disabled>
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['phone'] ?? '')) ?>" disabled>
      </div>
      <div class="form-group"><label>Address</label>
        <textarea disabled><?= htmlspecialchars((string) ($collector['address'] ?? '')) ?></textarea>
      </div>
    </div>

    <!-- Bank Info -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Bank Details</h3>
      <div class="form-group"><label>Account Name</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['bank']['account_name'] ?? '')) ?>"
          disabled>
      </div>
      <div class="form-group"><label>Account Number</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['bank']['account_number'] ?? '')) ?>"
          disabled>
      </div>
      <div class="form-group"><label>Bank Name</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['bank']['bank_name'] ?? '')) ?>" disabled>
      </div>
      <div class="form-group"><label>Branch</label>
        <input type="text" value="<?= htmlspecialchars((string) ($collector['bank']['branch'] ?? '')) ?>" disabled>
      </div>
    </div>
  </div>

  <!-- Security -->
  <div class="pc-card">
    <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
    <p><button class="p-btn" onclick="openModal('passwordModal')">Change Password</button></p>
    <p><button class="p-btn" onclick="openModal('twoFactorModal')">Two-Factor Authentication</button></p>
    <p><button class="p-btn-delete" onclick="openModal('deleteModal')">Delete Account</button></p>
  </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeModal('editModal')">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Profile</h2>
    <form method="POST">
      <input type="hidden" name="action" value="update_info">
      <div class="form-group"><label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars((string) ($collector['name'] ?? '')) ?>">
      </div>
      <div class="form-group"><label>Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars((string) ($collector['email'] ?? '')) ?>">
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars((string) ($collector['phone'] ?? '')) ?>">
      </div>
      <div class="form-group"><label>Address</label>
        <textarea name="address"><?= htmlspecialchars((string) ($collector['address'] ?? '')) ?></textarea>
      </div>
      <button type="submit" class="p-submit">Save Changes</button>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeModal('passwordModal')">&times;</a>
    <h2>Change Password</h2>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group"><label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-group"><label>New Password</label>
        <input type="password" name="new_password" required>
      </div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
      </div>
      <button type="submit" class="p-submit">Save</button>
    </form>
  </div>
</div>

<!-- Two-Factor Modal -->
<div id="twoFactorModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeModal('twoFactorModal')">&times;</a>
    <h2>Two-Factor Authentication</h2>
    <form method="POST">
      <input type="hidden" name="action" value="two_factor">
      <label><input type="radio" name="mode" value="enabled" checked> Enable 2FA</label><br>
      <label><input type="radio" name="mode" value="disabled"> Disable 2FA</label>
      <button type="submit" class="p-submit">Apply</button>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeModal('deleteModal')">&times;</a>
    <h2>Delete Account</h2>
    <p style="color:red;">⚠️ This action cannot be undone. Confirm your password.</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete_account">
      <div class="form-group"><label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="p-btn-delete">Confirm Delete</button>
    </form>
  </div>
</div>

<!-- Toast -->
<?php if ($toastMessage): ?>
  <div class="toast"><?= htmlspecialchars($toastMessage) ?></div>
<?php endif; ?>

<script>
  function openModal(id) {
    document.getElementById(id).style.display = "block";
  }
  function closeModal(id) {
    document.getElementById(id).style.display = "none";
  }
</script>