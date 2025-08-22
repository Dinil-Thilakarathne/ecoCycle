<?php
// Handle step switching
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;
$processing = false;

if ($step === 3 && isset($_POST['process_payment'])) {
    // Simulate processing delay
    $processing = true;
    sleep(2); // just for demo; remove in production
}
?>


<div class="container">
    <h1 class="title">Complete Your Payment</h1>
    <p class="subtitle">Secure payment processing for your waste management service</p>

    <!-- Progress Bar -->
    <div class="progress-container">
        <div class="progress-step <?= ($step >= 1) ? 'active' : '' ?>">1</div>
        <div class="progress-line <?= ($step >= 2) ? 'active' : '' ?>"></div>
        <div class="progress-step <?= ($step >= 2) ? 'active' : '' ?>">2</div>
        <div class="progress-line <?= ($step >= 3) ? 'active' : '' ?>"></div>
        <div class="progress-step <?= ($step >= 3) ? 'active' : '' ?>">3</div>
    </div>

    <?php if ($step === 1): ?>
        <form method="post">
            <div class="card">
                <h2>Payment Information</h2>
                <div class="secure-note">🔒 Your payment information is encrypted and secure.</div>

                <label>Card Number</label>
                <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required>

                <div class="row">
                    <div>
                        <label>Expiry Date</label>
                        <input type="text" name="expiry" placeholder="MM/YY" required>
                    </div>
                    <div>
                        <label>Security Code</label>
                        <input type="text" name="cvc" placeholder="123" required>
                    </div>
                </div>

                <label>Cardholder Name</label>
                <input type="text" name="cardholder" placeholder="John Doe" required>

                <label>Country</label>
                <select name="country" required>
                    <option value="">Select country</option>
                    <option value="us">United States</option>
                    <option value="ca">Canada</option>
                    <option value="uk">United Kingdom</option>
                </select>

                <div class="summary">
                    <p><span>Premium Plan (Monthly)</span> <span>$29.99</span></p>
                    <p><span>Tax</span> <span>$2.40</span></p>
                    <hr>
                    <p class="total"><span>Total</span> <span>$32.39</span></p>
                </div>

                <button type="submit" name="step" value="2" class="btn">Continue to Review</button>
            </div>
        </form>
    <?php elseif ($step === 2): ?>
        <form method="post">
            <div class="card">
                <h2>Review Your Order</h2>
                <div class="review-box">
                    <h4>Premium Plan</h4>
                    <ul>
                        <li>• Unlimited pickups</li>
                        <li>• Priority support</li>
                        <li>• Advanced analytics</li>
                        <li>• Special waste handling</li>
                    </ul>
                </div>

                <div class="review-box">
                    <h4>Payment Method</h4>
                    <p>💳 •••• •••• •••• 3456</p>
                </div>

                <div class="summary">
                    <p><span>Premium Plan (Monthly)</span> <span>$29.99</span></p>
                    <p><span>Tax</span> <span>$2.40</span></p>
                    <hr>
                    <p class="total"><span>Total</span> <span>$32.39</span></p>
                </div>

                <div class="actions">
                    <button type="submit" name="step" value="1" class="btn outline">Back</button>
                    <button type="submit" name="step" value="3" name="process_payment" class="btn">Confirm Payment</button>
                </div>
            </div>
        </form>
    <?php elseif ($step === 3 && !$processing): ?>
        <div class="card success">
            <div class="icon">✅</div>
            <h2>Payment Successful!</h2>
            <p>Your Premium plan subscription has been activated.</p>
            <div class="details">
                <p><strong>Transaction ID:</strong> TXN-2024-001-789</p>
                <p><strong>Amount:</strong> $32.39</p>
                <p><strong>Next billing:</strong> February 15, 2024</p>
            </div>
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

