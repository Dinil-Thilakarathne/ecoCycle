<?php

// Initialize payment step
if (!isset($_SESSION['paymentStep'])) $_SESSION['paymentStep'] = 1;

// Initialize payment history (dummy data)
if (!isset($_SESSION['paymentHistory'])) {
    $_SESSION['paymentHistory'] = [
        [
            'date' => '2025-08-01 10:20',
            'card' => '**** **** **** 1234',
            'amount' => 32.39,
            'status' => 'Completed'
        ]
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_step'])) {
        $_SESSION['paymentStep'] = 2;
        $_SESSION['paymentData'] = $_POST;
    } elseif (isset($_POST['prev_step'])) {
        if ($_SESSION['paymentStep'] == 3) {
            $_SESSION['paymentStep'] = 2;
        } else {
            $_SESSION['paymentStep'] = max(1, $_SESSION['paymentStep']-1);
        }
    } elseif (isset($_POST['confirm_payment'])) {
        $_SESSION['paymentStep'] = 3;
        // Save payment to history
        $data = $_SESSION['paymentData'];
        $_SESSION['paymentHistory'][] = [
            'date' => date('Y-m-d H:i'),
            'card' => '**** **** **** ' . substr($data['card_number'], -4),
            'amount' => 32.39,
            'status' => 'Completed'
        ];
    }
}

$step = $_SESSION['paymentStep'];
$history = $_SESSION['paymentHistory'];
?>



<div class="dashboard-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Payment</h1>
            <p class="page-subtitle">Secure payment processing for your service</p>
        </div>
    </div>

    <!-- Progress -->
    <div class="progress" style="margin-bottom:2.5rem;">
            <div class="progress-step <?= $step>=1?'active':'' ?>">1</div>
            <div class="progress-bar <?= $step>=2?'active':'' ?>"></div>
            <div class="progress-step <?= $step>=2?'active':'' ?>">2</div>
            <div class="progress-bar <?= $step>=3?'active':'' ?>"></div>
            <div class="progress-step <?= $step>=3?'active':'' ?>">3</div>
    </div>

    <form method="post">
        <?php if($step==1): ?>
        <div class="card" style="max-width:420px;margin:0 auto 2rem auto;">
            <h3 style="text-align:center;margin-bottom:1.5rem;">Payment Information</h3>
            <div class="form-grid" style="gap:1rem;">
                <div>
                    <label>Card Number</label>
                    <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required>
                </div>
                <div>
                    <label>Expiry</label>
                    <input type="text" name="expiry" placeholder="MM/YY" required>
                </div>
                <div>
                    <label>CVC</label>
                    <input type="text" name="cvc" placeholder="123" required>
                </div>
                <div>
                    <label>Cardholder Name</label>
                    <input type="text" name="cardholder" placeholder="John Doe" required>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
                <button type="submit" name="next_step" class="btn btn-primary">Continue to Review</button>
            </div>
        </div>

        <?php elseif($step==2): ?>
        <div class="card" style="max-width:420px;margin:0 auto 2rem auto;">
            <h3 style="text-align:center;margin-bottom:1.5rem;">Review & Confirm</h3>
            <div style="margin-bottom:1.5rem;">
                <p><strong>Card:</strong> <?= htmlspecialchars($_SESSION['paymentData']['card_number']) ?></p>
                <p><strong>Amount:</strong> $32.39</p>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem;">
                <button type="submit" name="prev_step" class="btn btn-secondary">Back</button>
                <button type="submit" name="confirm_payment" class="btn btn-primary">Confirm Payment</button>
            </div>
        </div>

        <?php elseif($step==3): ?>
        <div class="card text-center" style="max-width:420px;margin:0 auto 2rem auto;text-align:center;">
            <span class="success-icon">&#10003;</span>
            <h3 style="margin-bottom:1rem;">Payment Successful!</h3>
            <p>Your payment has been completed.</p>
            <div style="display:flex;justify-content:center;gap:1rem;margin-top:1.5rem;">
                <button type="submit" name="prev_step" class="btn btn-secondary">Back</button>
                <a href="/dashboard.php" style="text-decoration:none;"><button type="button" class="btn btn-primary">Go to Dashboard</button></a>
            </div>
        </div>
        <?php endif; ?>
    </form>

    <div class="table-section" style="margin-top:2.5rem;">
        <div class="section-header">
            <h2 class="section-title">Payment History</h2>
        </div>
        <div class="table-container" style="max-width:600px;margin:0 auto;">
            <div class="card" style="padding:0;box-shadow:none;border:none;">
                <?php if(count($history)>0): ?>
                <table class="data-table" style="width:100%;min-width:400px;">
                    <thead>
                        <tr>
                            <th style="width:140px;">Date</th>
                            <th style="width:160px;">Card</th>
                            <th style="width:100px;text-align:right;">Amount</th>
                            <th style="width:100px;text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $row): ?>
                        <tr>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['card'] ?></td>
                            <td style="text-align:right;">$<?= $row['amount'] ?></td>
                            <td style="text-align:center;">
                                <span class="status-badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="padding:2rem;text-align:center;">No payments yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

