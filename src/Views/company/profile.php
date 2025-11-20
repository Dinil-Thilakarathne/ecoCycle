<?php
$company = is_array($companyProfile ?? null) ? $companyProfile : [];
$bankdetails = is_array($bankDetails ?? null) ? $bankDetails : [];
$wasteTypes = $wasteTypes ?? ($company['waste_types'] ?? []);
if (!is_array($wasteTypes)) $wasteTypes = [];
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
      <h3>Company Information</h3>
      <div class="profile-picture">
        <img src="<?= htmlspecialchars($company['profile_picture'] ?? 'assets/avatar.png') ?>" width="100" alt="Profile Picture">
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" value="<?= htmlspecialchars($company['name'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Type</label>
        <input type="text" value="<?= htmlspecialchars($company['type'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Registration</label>
        <input type="text" value="<?= htmlspecialchars($company['reg_number'] ?? 'N/A') ?>" disabled>
      </div>
      <div class="form-group"><label>Description</label>
        <textarea disabled><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="pc-card">
      <a href="#editModal" class="btn btn-outline" style="position: absolute; right: 6%; top: 2%; background:var(--info-light);">✏️ Edit Profile</a>
      <h3>Contact Information</h3>
      <div class="form-group"><label>Email</label>
        <input type="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Land Phone</label>
        <input type="tel" value="011-1234567" disabled>
      </div>
      <div class="form-group"><label>Website</label>
        <input type="text" value="<?= htmlspecialchars($company['website'] ?? '') ?>" disabled>
      </div>
      <div class="form-group"><label>Address</label>
        <textarea disabled><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <!-- Waste Types -->
  <div class="pc-card">
    <h3>Waste Types Collected</h3>
    <div class="waste-tags">
      <?php foreach ($wasteTypes as $w): ?>
        <span class="wastetag"><?= htmlspecialchars($w) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

</main>

<!-- Edit Modal -->
<div id="editModal" class="form-modal">
  <div class="form-modal-content">
    <a href="#" class="close">&times;</a>
    <h2>Edit Profile</h2>
    <div id="profileMessage"></div>

    <form id="editProfileForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= $csrf ?>">

      <div class="form-group"><label>Profile Picture</label>
        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
      </div>
      <div class="form-group"><label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Type</label>
        <input type="text" name="type" value="<?= htmlspecialchars($company['type'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Registration</label>
        <input type="text" name="reg_number" value="<?= htmlspecialchars($company['reg_number'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Description</label>
        <textarea name="description"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Phone</label>
        <input type="tel" pattern="[0-9]{10}" maxlength="10" name="phone" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Website</label>
        <input type="text" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
      </div>
      <div class="form-group"><label>Address</label>
        <textarea name="address"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
      </div>
      <div class="form-group"><label>Waste Types</label>
        <input type="text" name="waste_types" value="<?= htmlspecialchars(implode(', ', $wasteTypes)) ?>">
      </div>

      <button type="submit" class="btn btn-primary outline" style="width:100%;">Save Changes</button>
    </form>
  </div>
</div>

<script>
(function() {
    const csrfToken = <?= json_encode($csrf) ?>;
    const form = document.getElementById('editProfileForm');
    const msgBox = document.getElementById('profileMessage');

    if (!form) return;

    function showMessage(msg, isError = false) {
        msgBox.innerHTML = `<div class="${isError ? 'error-box' : 'success-box'}">${msg}</div>`;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        msgBox.innerHTML = '';

        const formData = new FormData(form);
        formData.set('_token', csrfToken);

        try {
            const res = await fetch('/api/company/profile/update', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });

            const contentType = res.headers.get('content-type') || '';

            if (!res.ok) {
                if (contentType.includes('application/json')) {
                    const json = await res.json();
                    showMessage(json.message || 'Error', true);
                } else {
                    const text = await res.text();
                    console.error('Non-JSON response:', text);
                    showMessage('Server returned HTML response. Check console.', true);
                }
                return;
            }

            if (contentType.includes('application/json')) {
                const json = await res.json();
                showMessage(json.message || 'Profile updated successfully', false);
                // Optionally update page UI here
            }

        } catch (err) {
            console.error(err);
            showMessage('Request failed: ' + err.message, true);
        }
    });
})();
</script>

