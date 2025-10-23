<?php


$current_plan = [
    'name' => 'Premium Plan',
    'price' => 29.99,
    'billing_date' => '2024-01-15',
    'next_billing' => '2024-02-15',
    'status' => 'active'
];

$current_subscription = [
    'plan' => 'Basic Plan',
    'price' => 29.99,
    'renewal_date' => 'December 15, 2024',
    'status' => 'active'
];

$payment_methods = [
    [
        'id' => 1,
        'type' => 'card',
        'last_four' => '4242',
        'brand' => 'Visa',
        'expiry' => '12/26',
        'is_default' => true
    ],
    [
        'id' => 2,
        'type' => 'paypal',
        'email' => 'user@example.com',
        'is_default' => false
    ]
];

$invoice_history = [
    [
        'id' => 'INV-2024-01-01',
        'date' => '2024-01-01',
        'amount' => 29.99,
        'status' => 'paid',
        'description' => 'Premium Plan - January'
    ],
    [
        'id' => 'INV-2023-12-01',
        'date' => '2023-12-01',
        'amount' => 29.99,
        'status' => 'paid',
        'description' => 'Premium Plan - December'
    ],
    [
        'id' => 'INV-2023-11-01',
        'date' => '2023-11-01',
        'amount' => 19.99,
        'status' => 'paid',
        'description' => 'Basic Plan - November'
    ]
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_plan':
                $success_message = "Plan changed successfully!";
                break;
            case 'cancel_subscription':
                $success_message = "Subscription cancelled successfully!";
                break;
            case 'add_payment_method':
                $success_message = "Payment method added successfully!";
                break;
            case 'remove_payment_method':
                $success_message = "Payment method removed successfully!";
                break;
        }
    }
}

function formatDate($date)
{
    return date('M d, Y', strtotime($date));
}

function formatCurrency($amount)
{
    return 'Rs ' . number_format($amount, 2);
}
?>

<div class="container" style="background: var(--neutral-1);">
    <header class="page-header">

        <h1><b>Manage your subscription and billing information</b></h1>
    </header>

    <?php if (isset($success_message)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>


    <div class="billing-content" style="background: var(--neutral-1); padding: 2rem; border-radius: 1.5rem;">
        <!-- Feature Cards (Stats) -->
        <div class="stats-grid" style="margin-bottom:2.5rem;">
            <!-- Next Payment card removed -->
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Total Paid</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                </div>
                <p class="feature-card__body">
                    <?= formatCurrency(array_sum(array_column($invoice_history, 'amount'))) ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">All time</span>
                </div>
            </div>
            <!-- Subscription Status card removed -->
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Invoices</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
                <p class="feature-card__body">
                    <?= count($invoice_history) ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">Total invoices</span>
                </div>
            </div>
        </div>


        <!-- Payment Methods Section Removed -->

        <!-- Invoice History Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Invoice History</h2>
                <p class="section-subtitle">All your past payments and invoices</p>
            </div>
            <div class="invoice-grid"
                style="display: grid; grid-template-columns: 1.2fr 0.9fr 0.7fr 0.6fr; gap: 0.15rem; background: #fff; border-radius: 1rem; box-shadow: 0 2px 12px rgba(34,197,94,0.08); padding: 1.2rem; margin-top: 1rem;">
                <div class="invoice-header" style="font-weight:600;color:#1e293b;">Invoice</div>
                <div class="invoice-header" style="font-weight:600;color:#1e293b;">Date</div>
                <div class="invoice-header" style="font-weight:600;color:#1e293b;">Amount</div>
                <div class="invoice-header" style="font-weight:600;color:#1e293b;text-align:center;">Actions</div>
                <?php foreach ($invoice_history as $invoice): ?>
                    <div class="invoice-cell" style="padding:0.75rem 0; border-bottom:1px solid #f1f5f9;">
                        <strong><?php echo htmlspecialchars($invoice['id']); ?></strong>
                        <div class="invoice-desc" style="color:#64748b;font-size:0.95em;">
                            <?php echo htmlspecialchars($invoice['description']); ?> </div>
                    </div>
                    <div class="invoice-cell" style="padding:0.75rem 0; border-bottom:1px solid #f1f5f9; color:#475569;">
                        <?php echo formatDate($invoice['date']); ?>
                    </div>
                    <div class="invoice-cell"
                        style="padding:0.75rem 0; border-bottom:1px solid #f1f5f9; color:#22c55e;font-weight:500;">
                        <?php echo formatCurrency($invoice['amount']); ?>
                    </div>
                    <div class="invoice-cell"
                        style="padding:0.75rem 0; border-bottom:1px solid #f1f5f9; text-align:center;">
                        <button class="btn btn-outline btn-sm" style="min-width:90px;"
                            onclick="downloadInvoice('<?php echo $invoice['id']; ?>')">Download</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Change Plan Modal -->
<div id="changePlanModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Change Plan</h2>
            <span class="close" onclick="hideChangePlanModal()">&times;</span>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="change_plan">
            <div class="plan-options">
                <div class="plan-option">
                    <input type="radio" name="plan" value="basic" id="basic">
                    <label for="basic" class="plan-label">
                        <h3>Basic Plan</h3>
                        <p class="plan-price">Rs 1,999/month</p>
                        <p>Essential waste management features</p>
                    </label>
                </div>
                <div class="plan-option">
                    <input type="radio" name="plan" value="premium" id="premium" checked>
                    <label for="premium" class="plan-label">
                        <h3>Premium Plan</h3>
                        <p class="plan-price">Rs 2,999/month</p>
                        <p>All features + priority support</p>
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="hideChangePlanModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Change Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Payment Method Modal -->
<div id="addPaymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Payment Method</h2>
            <span class="close" onclick="hideAddPaymentModal()">&times;</span>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="add_payment_method">

            <div class="payment-type-selector">
                <button type="button" class="payment-type-btn active" onclick="selectPaymentType('card')">
                    Credit Card
                </button>
                <button type="button" class="payment-type-btn" onclick="selectPaymentType('paypal')">
                    PayPal
                </button>
            </div>

            <div id="cardForm" class="payment-form">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" name="card_number" placeholder="1234 5678 9012 3456">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" name="expiry" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" name="cvv" placeholder="123">
                    </div>
                </div>
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" name="holder_name" placeholder="John Doe">
                </div>
            </div>

            <div id="paypalForm" class="payment-form" style="display: none;">
                <div class="form-group">
                    <label>PayPal Email</label>
                    <input type="email" name="paypal_email" placeholder="user@example.com">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="hideAddPaymentModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Payment Method</button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Subscription Modal -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Cancel Subscription</h2>
            <span class="close" onclick="hideCancelModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel your subscription? You'll lose access to premium features.</p>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="cancel_subscription">
            <div class="modal-actions">
                <button type="button" onclick="hideCancelModal()" class="btn btn-outline">Keep Subscription</button>
                <button type="submit" class="btn btn-primary" style="background:#dc2626;border-color:#dc2626;">Cancel
                    Subscription</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showChangePlanModal() {
        document.getElementById('changePlanModal').style.display = 'block';
    }

    function hideChangePlanModal() {
        document.getElementById('changePlanModal').style.display = 'none';
    }

    function showAddPaymentModal() {
        document.getElementById('addPaymentModal').style.display = 'block';
    }

    function hideAddPaymentModal() {
        document.getElementById('addPaymentModal').style.display = 'none';
    }

    function showCancelModal() {
        document.getElementById('cancelModal').style.display = 'block';
    }

    function hideCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }

    function selectPaymentType(type) {
        document.querySelectorAll('.payment-type-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        if (type === 'card') {
            document.getElementById('cardForm').style.display = 'block';
            document.getElementById('paypalForm').style.display = 'none';
        } else {
            document.getElementById('cardForm').style.display = 'none';
            document.getElementById('paypalForm').style.display = 'block';
        }
    }

    function editPaymentMethod(id) {
        alert('Edit payment method ' + id);
    }

    function removePaymentMethod(id) {
        if (confirm('Are you sure you want to remove this payment method?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                    <input type="hidden" name="action" value="remove_payment_method">
                    <input type="hidden" name="method_id" value="${id}">
                `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function downloadInvoice(invoiceId) {
        // In real application, this would download the invoice
        alert('Downloading invoice: ' + invoiceId);
    }

    // Close modals when clicking outside
    window.onclick = function (event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
</script>