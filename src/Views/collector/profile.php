<?php
// Example: Collector data (usually from DB)
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

$toastMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    switch($action) {
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

// ====== Handle profile picture upload ======
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["profile_pic"])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // create folder if not exists
    }

    $fileName = basename($_FILES["profile_pic"]["name"]);
    $targetFile = $targetDir . time() . "_" . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // validate image
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if ($check === false) {
        $toastMessage = "❌ File is not an image.";
    } elseif ($_FILES["profile_pic"]["size"] > 2000000) {
        $toastMessage = "⚠️ Image too large (max 2MB).";
    } elseif (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
        $toastMessage = "⚠️ Only JPG, JPEG & PNG allowed.";
    } else {
        // move uploaded file
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
            $collector["profile_pic"] = $targetFile;
            $toastMessage = "✅ Profile picture updated successfully!";
        } else {
            $toastMessage = "❌ Error uploading file.";
        }
    }
}
?>

<main class="content">
  <header class="page-header">
    <div class="page-header__content">
    
      <h2 class="page-header__title">
        <i class="fa-solid fa-user" style="margin-right:8px;"></i>Collector Profile</h2>
      <p class="page-header__description">Update your profile and security settings.</p>
    </div>
  </header>

  


<!-- Profile Card -->
   
    <!-- Profile Image Card -->
 <div class="pc-card">
  <h3 style="font-size: 20px; font-weight: bold;">Profile Picture</h3>

  <div style="display: flex; align-items: center; gap: 20px; margin-top: 10px;">

    <!-- Profile Image -->
    <div style="text-align: center;">
      <?php if (!empty($collector['profile_pic'])): ?>
        <img src="<?= htmlspecialchars($collector['profile_pic']) ?>" 
             alt="Profile Image" 
             style="width:120px; height:120px; border-radius:50%; object-fit:cover;">
      <?php else: ?>
        <div style="width:120px; height:120px; border-radius:50%; background:#ddd; display:flex; align-items:center; justify-content:center; font-size:50px; color:#888;">
          👤
        </div>
      <?php endif; ?>
    </div>

    <!-- Upload Form -->
    <form method="POST" enctype="multipart/form-data" class="upload-form" style="display: flex; flex-direction: column; gap: 8px;">
      <label style="font-weight: 500;">Upload New Image</label>
      <div style="display: flex; align-items: center; gap: 10px;">
  <input 
    type="file" 
    name="profile_pic" 
    accept="image/*" 
    required 
    style="flex: none; width: auto;"
  >
  <button 
    type="submit" 
    class="p-submit" 
    style="padding: 6px 95px; cursor: pointer; white-space: nowrap;">
    Upload Image
  </button>
</div>
      </div>
    </form>
  <div>
      </div>



      
    <!-- Collector Information -->

      <h3 style="font-size: 20px; font-weight: bold; margin-top: 20px;">Collector Information</h3>
    
      <div class="form-group"><label>Name</label>
        <input type="text" value="<?= htmlspecialchars($collector['name']) ?>" disabled>
      </div>
      <div class="form-group"><label>Email</label>
        <input type="text" value="<?= htmlspecialchars($collector['email']) ?>" disabled>
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="text" value="<?= htmlspecialchars($collector['phone']) ?>" disabled>
      </div>
      <div class="form-group"><label>Address</label>
        <textarea disabled><?= htmlspecialchars($collector['address']) ?></textarea>
      </div>
      <!-- Collector Info -->
      <a href="#editModal" class="edit-btn">✏️ Edit Profile</a>
     
    
        </div>
  

     
<button class="p-submit" onclick="openModal('editBankModal')">Edit Bank Details</button>

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
        <input type="text" name="name" value="<?= htmlspecialchars($collector['name']) ?>"></div>
      <div class="form-group"><label>Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars($collector['email']) ?>"></div>
      <div class="form-group"><label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($collector['phone']) ?>"></div>
      <div class="form-group"><label>Address</label>
        <textarea name="address"><?= htmlspecialchars($collector['address']) ?></textarea></div>
      <button type="submit" class="p-submit">Save Changes</button>
    </form>
  </div>
</div>

<!-- Edit Bank Details Modal -->
<div id="editBankModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close" onclick="closeModal('editBankModal')">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Bank Details</h2>

    <form method="POST">
      <input type="hidden" name="action" value="update_bank">

      <div class="form-group">
        <label>Account Name</label>
        <input 
          type="text" 
          name="account_name" 
          value="<?= htmlspecialchars($collector['bank']['account_name']) ?>" 
          required>
      </div>

      <div class="form-group">
        <label>Account Number</label>
        <input 
          type="text" 
          name="account_number" 
          value="<?= htmlspecialchars($collector['bank']['account_number']) ?>" 
          required>
      </div>

      <div class="form-group">
        <label>Bank Name</label>
        <input 
          type="text" 
          name="bank_name" 
          value="<?= htmlspecialchars($collector['bank']['bank_name']) ?>" 
          required>
      </div>

      <div class="form-group">
        <label>Branch</label>
        <input 
          type="text" 
          name="branch" 
          value="<?= htmlspecialchars($collector['bank']['branch']) ?>" 
          required>
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
        <input type="password" name="current_password" required></div>
      <div class="form-group"><label>New Password</label>
        <input type="password" name="new_password" required></div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirm_password" required></div>
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
        <input type="password" name="password" required></div>
      <button type="submit" class="p-btn-delete">Confirm Delete</button>
    </form>
  </div>
</div>

<!-- Toast-->
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

<style>

</style>