<?php
// ------------------ PHP Variables ------------------

// Invoices
$invoices = [
    ["date" => "2024-06-01", "amount" => 120.00, "status" => "Paid"],
    ["date" => "2024-06-15", "amount" => 80.00,  "status" => "Pending"],
    ["date" => "2024-07-01", "amount" => 150.00, "status" => "Overdue"],
    ["date" => "2024-07-15", "amount" => 95.00,  "status" => "Paid"],
    ["date" => "2024-08-01", "amount" => 200.00, "status" => "Pending"],
];

// Billing info
$billingInfo = [
    "name"   => "John Doe",
    "address"=> "123 Main St, Cityville",
    "card"   => "**** **** **** 1234",
    "expiry" => "08/26"
];

// Totals
$totalInvoices = count($invoices);
$totalAmount   = array_sum(array_column($invoices, 'amount'));
$paidAmount    = array_sum(array_column(array_filter($invoices, fn($inv) => $inv['status'] === 'Paid'), 'amount'));
$pendingAmount = array_sum(array_column(array_filter($invoices, fn($inv) => $inv['status'] === 'Pending'), 'amount'));
?>







<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <h3>Total Amount</h3>
        <div class="amount total">$<?= number_format($totalAmount, 2) ?></div>
        <p><?= $totalInvoices ?> invoices</p>
    </div>
    <div class="summary-card">
        <h3>Paid</h3>
        <div class="amount paid">$<?= number_format($paidAmount, 2) ?></div>
        <p>Successfully processed</p>
    </div>
    <div class="summary-card">
        <h3>Pending</h3>
        <div class="amount pending">$<?= number_format($pendingAmount, 2) ?></div>
        <p>Awaiting payment</p>
    </div>
    <div class="summary-card">
        <h3>Overdue</h3>
        <div class="amount overdue">$<?= number_format($totalAmount - $paidAmount - $pendingAmount, 2) ?></div>
        <p>Requires attention</p>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-button active" data-tab="invoices">Invoices</button>
    <button class="tab-button" data-tab="billing-info">Billing Info</button>
</div>

<!-- Invoices Tab -->
<div class="tab-content active" id="invoices">
    <h2>Invoice History</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($invoices as $index => $invoice): ?>
            <tr>
                <td><?= date('M j, Y', strtotime($invoice['date'])) ?></td>
                <td>$<?= number_format($invoice['amount'], 2) ?></td>
                <td>
                    <span class="status-badge status-<?= strtolower($invoice['status']) ?>">
                        <?= htmlspecialchars($invoice['status']) ?>
                    </span>
                </td>
                <td>
                    <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="action-buttons">
        <button class="btn btn-primary">Download Report</button>
        <button class="btn btn-secondary">Export CSV</button>
    </div>
</div>

<!-- Billing Info Tab -->
<div class="tab-content" id="billing-info">
    <h2>Billing Information</h2>
    <ul class="billing-list">
        <li><strong>Full Name:</strong> <?= htmlspecialchars($billingInfo['name']) ?></li>
        <li><strong>Address:</strong> <?= htmlspecialchars($billingInfo['address']) ?></li>
        <li><strong>Payment Card:</strong> <?= htmlspecialchars($billingInfo['card']) ?></li>
        <li><strong>Expiry Date:</strong> <?= htmlspecialchars($billingInfo['expiry']) ?></li>
    </ul>
    
    <div class="action-buttons">
        <button class="btn btn-primary">Update Info</button>
        <button class="btn btn-secondary">Change Payment Method</button>
    </div>
</div>

<script>
// Tab functionality
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
        // Remove active classes
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        
        // Add active class to clicked button and related content
        button.classList.add('active');
        document.getElementById(button.getAttribute('data-tab')).classList.add('active');
    });
});

// Add some interactivity to buttons
document.querySelectorAll('.btn').forEach(button => {
    if (button.textContent.includes('View Details')) {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            alert('Invoice details would open here');
        });
    }
    
    if (button.textContent.includes('Download Report')) {
        button.addEventListener('click', () => {
            alert('Report download would start here');
        });
    }
    
    if (button.textContent.includes('Export CSV')) {
        button.addEventListener('click', () => {
            alert('CSV export would start here');
        });
    }
    
    if (button.textContent.includes('Update Info')) {
        button.addEventListener('click', () => {
            alert('Update billing info form would open here');
        });
    }
    
    if (button.textContent.includes('Change Payment')) {
        button.addEventListener('click', () => {
            alert('Payment method form would open here');
        });
    }
});
</script>

