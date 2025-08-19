<?php
// Example: Profile data (usually fetched from a database)
$company = [
    "name" => "EcoCycle Company.",
    "type" => "Waste Management",
    "reg_number" => "REG-2023-001234",
    "description" => "Leading waste management company specializing in recyclable materials collection and processing. 
    Committed to environmental sustainability and circular economy principles.",
    "email" => "contact@ecowaste.com",
    "phone" => "+1 (555) 123-4567",
    "website" => "www.ecocycle.com",
    "address" => "123 Green Street, Eco District, Environmental City, EC 12345",
    "verification" => [
        "Email Verified" => true,
        "Phone Verified" => true,
        "Business License" => true
    ]
];

// Handle form submission (if POST request)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $company["name"] = $_POST["name"];
    $company["type"] = $_POST["type"];
    $company["reg_number"] = $_POST["reg_number"];
    $company["description"] = $_POST["description"];
    $company["email"] = $_POST["email"];
    $company["phone"] = $_POST["phone"];
    $company["website"] = $_POST["website"];
    $company["address"] = $_POST["address"];

    // Normally you would update the database here
    echo "<p style='color: green; font-weight: bold;'>Profile updated successfully!</p>";
}
?>

<main class="content">
    <header class="header">
      <h1>Profile</h1>
      <p>Update your profile here!</p>
    </header>
        <form method="POST">
            <!-- Company Information -->
            <div class="top-section">
                <div class="p-card">
                    <h3>Company Information</h3>
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Business Type</label>
                        <input type="text" name="type" value="<?= htmlspecialchars($company['type']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Registration Number</label>
                        <input type="text" name="reg_number" value="<?= htmlspecialchars($company['reg_number']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Company Description</label>
                        <textarea name="description" rows="4"><?= htmlspecialchars($company['description']) ?></textarea>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="p-card">
                    <h3>Account Information</h3>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($company['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($company['phone']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" value="<?= htmlspecialchars($company['website']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Business Address</label>
                        <textarea name="address" rows="2"><?= htmlspecialchars($company['address']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Verification Status -->
            <div class="p-card">
                <h3>Verification Status</h3>
                <ul>
                    <?php foreach($company['verification'] as $item => $status): ?>
                        <li><?= $item ?>: <span class="verified"><?= $status ? "Verified" : "Not Verified" ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Security & Privacy -->
            <div class="p-card">
                <h3>Security & Privacy</h3>
                <button type="button">Change Password</button>
                <button type="button">Setup Two-Factor Authentication</button>
                <button type="button">Privacy Settings</button>
                <button type="button" class="btn-danger">Delete Account</button>
            </div>

            <!-- Save Button -->
            <button type="submit" class="submit">Save Changes</button>
        </form>
</main>