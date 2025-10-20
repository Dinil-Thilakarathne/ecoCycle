<?php
$collector = is_array($collectorProfile ?? null) ? $collectorProfile : [];
$bankdetails = is_array($bankDetails ?? null) ? $bankDetails : [];
$wasteTypes = $wasteTypes ?? ($collector['waste_types'] ?? []);
if (!is_array($wasteTypes)) {
  $wasteTypes = [];
}
$verification = $verification ?? ($collector['verification'] ?? []);
if (!is_array($verification)) {
  $verification = [];
}
$errors = $errors ?? [];
$showToast = $showToast ?? false;
?>

<main class="content">
  <header class="page-header"> <div class="page-header__content"> 
    <h2 class="page-header__title"> <i class="fa-solid fa-user" style="margin-right:8px;"></i>Collector Profile</h2> 
    <p class="page-header__description">Update your profile and security settings.</p> </div> </header>

  <!-- Edit Button -->
  <a href="#editModal" class="btn btn-outline"
     style="position: absolute; right: 6%; top: 24%; background:var(--info-light);">
     ✏ Edit Profile
  </a>

  <!-- Collecter Information -->
  <div class="pc-card">
    <h3 style="font-size: 20px; font-weight: bold;">Collector Information</h3>

    <!-- Profile Image Section -->
    <div style="display: flex; align-items: center; gap: 20px; margin: 15px 0;">
      <!-- Profile Image Preview -->
      <div style="text-align: center;">
        <?php 
          // Use uploaded image if available, otherwise show default
          $profilePic = !empty($collector['profile_picture']) 
            ? htmlspecialchars($collector['profile_picture']) 
            : 'C:\Users\gjgld\OneDrive\Desktop\download.jpeg';
        ?>
        <img 
          src="<?= $profilePic ?>" 
          alt="Profile Picture" 
          style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc; padding: 4px; background: #fff;">
      </div>

      <!-- Upload New Image Form -->
      <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
        <!--<label style="font-weight: 500;">Upload New Image</label>-->
        <div style="display: flex; align-items: center; gap: 10px;">
          <input 
            type="file" 
            name="profile_picture" 
            accept="image/*" 
            required 
            style="flex: none; width: auto;">
          <button 
            type="submit" 
            class="btn btn-primary outline" 
            style="padding: 6px 85px; cursor: pointer;">
            Upload Your Image
          </button>
        </div>
      </form>
    </div>


    <!-- Collector Info Fields -->
    <div class="form-group"><label>Name</label>
      <input type="text" value="<?= htmlspecialchars($collector['name'] ?? 'N/A') ?>" disabled>
    </div>
    <div class="form-group"><label>Email</label>
      <input type="email" value="<?= htmlspecialchars($collector['email'] ?? '') ?>" disabled>
    </div>
    <div class="form-group"><label>Collector ID</label>
      <input type="text" value="<?= htmlspecialchars($collector['collector_id'] ?? 'N/A') ?>" disabled>
    </div>
    <div class="form-group"><label>Address</label>
      <textarea disabled><?= htmlspecialchars($collector['address'] ?? '') ?></textarea>
    </div>
  </div>
<!--</div>-->


  <!-- Bank Details -->
  <div class="pc-card">
    <h3 style="font-size: 20px; font-weight: bold;">Bank Details</h3>
    <p>
      <a href="#bankdetail" class="btn btn-outline" 
         style="margin-bottom: 5px; background:var(--info-light);">
         See Bank Details
      </a>
    </p>
  </div>

  <!-- Security -->
   <div class="pc-card"> <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3> 
       <p><button class="p-btn" onclick="#passwordModel">Change Password</button></p>
        <p><button class="p-btn" onclick="openModal('twoFactorModal')">Two-Factor Authentication</button></p>
         <p><button class="p-btn-delete" onclick="openModal('deleteModal')">Delete Account</button></p> 
    </div>

</main> 

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2 style="font-size: 20px; font-weight: bold;">Edit Collector Profile</h2>
    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group"><label>Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($collector['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Collector ID</label>
        <input type="text" name="collector_id" value="<?= htmlspecialchars($collector['collector_id'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Description</label>
        <textarea name="description"><?= htmlspecialchars($collector['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($collector['email'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" 
               name="phone" value="<?= htmlspecialchars($collector['phone'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Address</label>
        <textarea name="address"><?= htmlspecialchars($collector['address'] ?? '') ?></textarea>
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
    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group"><label>Bank Name</label>
        <input type="text" name="bank" value="<?= htmlspecialchars($bankdetails['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Account Number</label>
        <input type="text" name="number" value="<?= htmlspecialchars($bankdetails['account_number'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Account Holder</label>
        <input type="text" name="user" value="<?= htmlspecialchars($bankdetails['user'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Bank Branch</label>
        <input type="text" name="branch" value="<?= htmlspecialchars($bankdetails['branch'] ?? '') ?>">
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
    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group"><label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-group"><label>New Password</label>
        <input type="password" name="new_password" required>
      </div>
      <div class="form-group"><label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
      </div>
      <button type="submit" class="btn btn-primary outline" style="width:100%;">Update Password</button>
    </form>
  </div>
</div>

<!-- Two-Factor Modal --> 
 <div id="twoFactorModal" class="form-modal"> <div class="form-modal-content"> 
  <a href="#" class="close" onclick="closeModal('twoFactorModal')">&times;</a> <h2>Two-Factor Authentication</h2> 
  <form method="POST"> <input type="hidden" name="action" value="two_factor"> <label><input type="radio" name="mode" value="enabled" checked> Enable 2FA</label><br> <label><input type="radio" name="mode" value="disabled"> Disable 2FA</label> <button type="submit" class="p-submit">Apply</button> </form> </div> </div>
 
  <!-- Delete Modal -->
   <div id="deleteModal" class="form-modal"> <div class="form-modal-content"> 
    <a href="#" class="close" onclick="closeModal('deleteModal')">&times;</a> <h2>Delete Account</h2> 
    <p style="color:red;">⚠️ This action cannot be undone. Confirm your password.</p> <form method="POST"> 
      <input type="hidden" name="action" value="delete_account"> <div class="form-group"><label>Password</label> 
      <input type="password" name="password" required></div> <button type="submit" class="p-btn-delete">Confirm Delete</button>
     </form> 
    </div> 
  </div>

<!-- Toast Notification -->
<?php if ($showToast): ?>
  <div class="toast">✅ Collector profile updated successfully!</div>
<?php endif; ?>
