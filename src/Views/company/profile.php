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
        <a href="#photoUploadModal" class="btn btn-outline"
          style="padding: 8px 16px; margin-bottom: 5px; background:var(--info-light);">Change Photo
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
      <div class="form-group"><label>Phone Number</label>
        <input type="tel" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Landline</label>
        <input type="tel" value="<?= htmlspecialchars($metadata['land_phone'] ?? '') ?>" disabled>
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

    </div>

    <!-- Security -->
    <div class="pc-card">
      <h3 style="font-size: 20px; font-weight: bold;">Security & Privacy</h3>
      <p><a href="#passwordModal" class="btn btn-primary" style="margin-bottom: 5px">Change Password</a></p>
      <a href="/api/profile/delete" class="p-btn-delete" onclick="return confirmDeleteProfile(event)">Delete Account</a>
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

    <form id="editProfileForm" method="POST" enctype="multipart/form-data" action="/api/profile/update">
      <input type="hidden" name="_token" value="<?= app('session')->token() ?>">

      <div class="form-group"><label>Name<span class="required-star">*</span></label>
        <input type="text" name="companyName" value="<?= htmlspecialchars($metadata['companyName'] ?? '') ?>" required>
        <span class="field-error"></span>
      </div>
      <div class="form-group"><label>Registration Number<span class="required-star">*</span></label>
        <input type="text" name="reg_number" value="<?= htmlspecialchars($metadata['reg_number'] ?? '') ?>" required
          minlength="7" maxlength="10">
        <span class="field-error"></span>
      </div>
      <div class="form-group"><label>Website</label>
        <input type="text" name="website" value="<?= htmlspecialchars($metadata['website'] ?? '') ?>">
        <span class="field-error"></span>
      </div>

      <div class="form-group"><label>Contact Person<span class="required-star">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
        <span class="field-error"></span>
      </div>
      <div class="form-group"><label>Email Address<span class="required-star">*</span></label>
        <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" required>
        <span class="field-error"></span>
      </div>
      <div class="form-group"><label>Phone Number<span class="required-star">*</span></label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" name="phone"
          value="<?= htmlspecialchars($company['phone'] ?? '') ?>" required>
      </div>
      <div class="form-group"><label>Landline</label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" name="land_phone"
          value="<?= htmlspecialchars($metadata['land_phone'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Address<span class="required-star">*</span></label>
        <textarea name="address" required><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label>Waste Types<span class="required-star">*</span></label>
        <input type="text" name="waste_types" value="<?= htmlspecialchars(implode(', ', $wasteTypes)) ?>"
          placeholder="Plastic, Organic, Metal, Glass, Paper" required>
        <span class="field-error"></span>
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

    <form id="bankDetailsForm" method="POST" enctype="multipart/form-data" action="/api/profile/bankDetails">
      <div class="form-group"><label class="form-lable">Bank Name</label>
        <input type="text" name="bank_name" value="<?= htmlspecialchars($bankDetails['name'] ?? '') ?>" required>
      </div>
      <div class="form-group"><label class="form-lable">Account Number</label>
        <input type="text" name="bank_account_number"
          value="<?= htmlspecialchars($bankDetails['account_number'] ?? '') ?>" required minlength="8" maxlength="14">
        <span class="field-error"></span>
      </div>
      <div class="form-group"><label class="form-lable">User's Name</label>
        <input type="text" name="bank_account_name" value="<?= htmlspecialchars($bankDetails['user'] ?? '') ?>"
          required>
      </div>
      <div class="form-group"><label class="form-lable">Bank Branch</label>
        <input type="text" name="bank_branch" value="<?= htmlspecialchars($bankDetails['branch'] ?? '') ?>" required>
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
        <input type="password" name="password" placeholder="min 8 characters" required minlength="8">
        <span class="field-error"></span>
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

    /* ── Helpers ──────────────────────────────────────────── */
    const msgBox = document.getElementById('profileMessage');

    function showMessage(msg, isError = false) {
      msgBox.innerHTML = `<div class="${isError ? 'error-box' : 'success-box'}">${msg}</div>`;
    }

    function setError(input, msg) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      const span = input.closest('.form-group')?.querySelector('.field-error');
      if (span) span.textContent = msg;
    }

    function setValid(input) {
      input.classList.remove('is-invalid');
      input.classList.add('is-valid');
      const span = input.closest('.form-group')?.querySelector('.field-error');
      if (span) span.textContent = '';
    }

    function attachLiveValidation(form, rules) {
      rules.forEach(({ name, validate }) => {
        const el = form.elements[name];
        if (!el) return;
        el.addEventListener('blur', () => {
          const result = validate(el.value.trim(), form);
          result === true ? setValid(el) : setError(el, result);
        });
        el.addEventListener('input', () => {
          if (el.classList.contains('is-invalid')) {
            const result = validate(el.value.trim(), form);
            result === true ? setValid(el) : setError(el, result);
          }
        });
      });
    }

    function validateAll(form, rules) {
      let valid = true;
      rules.forEach(({ name, validate }) => {
        const el = form.elements[name];
        if (!el) return;
        const result = validate(el.value.trim(), form);
        if (result === true) { setValid(el); } else { setError(el, result); valid = false; }
      });
      return valid;
    }

    /* ── Edit Profile Rules ───────────────────────────────── */
    const editRules = [
      {
        name: 'website',
        validate: v => {
          if (!v) return true;
          if (!/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/\S*)?$/.test(v))
            return 'Enter a valid website (e.g. example.com or https://example.com).';
          return true;
        }
      },
      {
        name: 'email',
        validate: v => {
          if (!v) return 'Email address is required.';
          if (!/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/.test(v))
            return 'Enter a valid email address.';
          if (/\.{2,}/.test(v))
            return 'Email must not contain consecutive dots.';
          return true;
        }
      },
      {
        name: 'waste_types',
        validate: v => {
          if (!v) return 'At least one waste type is required.';
          const allowed = ['plastic', 'organic', 'metal', 'glass', 'paper'];
          const entered = v.split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
          const invalid = entered.filter(t => !allowed.includes(t));
          if (invalid.length)
            return `Invalid type(s): "${invalid.join('", "')}". Allowed: Plastic, Organic, Metal, Glass, Paper.`;
          return true;
        }
      }
    ];

    const editForm = document.getElementById('editProfileForm');
    if (editForm) {
      attachLiveValidation(editForm, editRules);
      editForm.addEventListener('submit', e => {
        if (!validateAll(editForm, editRules)) e.preventDefault();
      });
    }

    /* ── Bank Details Rules ───────────────────────────────── */
    const bankRules = [
      {
        name: 'bank_account_number',
        validate: v => {
          if (!v) return 'Account number is required.';
          if (!/^\d+$/.test(v)) return 'Account number must contain digits only.';
          if (v.length < 6 || v.length > 20) return 'Account number must be 6–20 digits.';
          return true;
        }
      }
    ];

    const bankForm = document.getElementById('bankDetailsForm');
    if (bankForm) {
      attachLiveValidation(bankForm, bankRules);
      bankForm.addEventListener('submit', e => {
        if (!validateAll(bankForm, bankRules)) e.preventDefault();
      });
    }

    /* ── Photo Preview ────────────────────────────────────── */
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
        document.getElementById('profileImageDisplay').src = e.target.result;
      };
      reader.readAsDataURL(file);
    });

    /* ── Delete Confirmation ──────────────────────────────── */
    window.confirmDeleteProfile = function (event) {
      event.preventDefault();
      const confirmed = confirm(
        "Are you sure you want to delete your account?\n\nThis action is PERMANENT and cannot be undone."
      );
      if (confirmed) window.location.href = event.currentTarget.href;
      return false;
    };

  })();
</script>