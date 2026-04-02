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

$payments = $payments ?? [];

// Calculate stats from real data
$total_received = 0;
foreach ($payments as $p) {
    if (($p['status'] ?? '') === 'completed') {
        $total_received += ($p['amount'] ?? 0);
    }
}
$transaction_count = count($payments);

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
        <div class="page-header__content">
            <h2 class="page-header__title">Payments</h2>
            <p class="page-header__description">Manage your billing and view transaction history</p>
        </div>
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
                    <h3 class="feature-card__title">Total Earnings</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                </div>
                <p class="feature-card__body">
                    <?= formatCurrency($total_received) ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">All time</span>
                </div>
            </div>
            <!-- Subscription Status card removed -->
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Transactions</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
                <p class="feature-card__body">
                    <?= $transaction_count ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">Total payouts</span>
                </div>
            </div>
        </div>


        <!-- Payment Methods Section Removed -->

        <!-- Invoice History Section -->
        <div class="section">
            <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h2 class="section-title">Transaction History</h2>
                    <p class="section-subtitle">All your past transactions and payouts</p>
                </div>
                <div id="liveIndicator" style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:#64748b;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 2s infinite;"></span>
                    Live
                </div>
            </div>
            <div id="transactionTable" style="background:#fff;border-radius:1rem;box-shadow:0 2px 12px rgba(34,197,94,0.08);padding:1.2rem;margin-top:1rem;">
                <!-- Populated by JS -->
                <div style="text-align:center;padding:2rem;color:#64748b;">Loading transactions...</div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
</style>

<script>
(function () {
    const API_URL = '/api/customer/payments';
    let allPayments = <?= json_encode(array_values($payments ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let pollInterval = null;
    let hasPending = false;

    function escHtml(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtDate(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        return isNaN(d) ? v : d.toLocaleDateString(undefined, {month:'short',day:'2-digit',year:'numeric'});
    }
    function fmtCurrency(n) {
        return 'Rs ' + parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits:2,maximumFractionDigits:2});
    }

    function renderTable(payments) {
        const el = document.getElementById('transactionTable');
        if (!el) return;

        if (!payments || !payments.length) {
            el.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:2rem;color:#64748b;">No transactions found.</div>`;
            return;
        }

        const header = `
            <div style="display:grid;grid-template-columns:1.2fr 0.9fr 0.7fr 0.6fr;gap:0.15rem;">
                <div style="font-weight:600;color:#1e293b;padding:0.5rem 0;">Transaction ID</div>
                <div style="font-weight:600;color:#1e293b;padding:0.5rem 0;">Date</div>
                <div style="font-weight:600;color:#1e293b;padding:0.5rem 0;">Amount</div>
                <div style="font-weight:600;color:#1e293b;padding:0.5rem 0;text-align:center;">Status</div>
            </div>`;

        const rows = payments.map(p => {
            const isCompleted = (p.status || '').toLowerCase() === 'completed';
            const tagClass = isCompleted ? 'success' : 'warning';
            return `
                <div style="display:grid;grid-template-columns:1.2fr 0.9fr 0.7fr 0.6fr;gap:0.15rem;border-top:1px solid #f1f5f9;">
                    <div style="padding:0.75rem 0;">
                        <strong>${escHtml(p.txnId || p.id)}</strong>
                        <div style="color:#64748b;font-size:0.9em;">${escHtml(ucfirst(p.type || 'Payout'))}</div>
                    </div>
                    <div style="padding:0.75rem 0;color:#475569;">${escHtml(fmtDate(p.date))}</div>
                    <div style="padding:0.75rem 0;color:#22c55e;font-weight:500;">${escHtml(fmtCurrency(p.amount))}</div>
                    <div style="padding:0.75rem 0;text-align:center;">
                        <span class="tag ${tagClass}">${escHtml(ucfirst(p.status))}</span>
                    </div>
                </div>`;
        }).join('');

        el.innerHTML = header + rows;
    }

    function updateStats(payments) {
        const totalEarned   = payments.filter(p => p.status === 'completed').reduce((s, p) => s + parseFloat(p.amount || 0), 0);
        const totalCount    = payments.length;
        const cards = document.querySelectorAll('.feature-card .feature-card__body');
        if (cards[0]) cards[0].textContent = fmtCurrency(totalEarned);
        if (cards[1]) cards[1].textContent  = totalCount;
    }

    function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    async function fetchAndRefresh() {
        try {
            const res  = await fetch(API_URL, { credentials: 'same-origin' });
            if (!res.ok) return;
            const json = await res.json();
            const fresh = json.data || [];

            // Detect any status change from pending → completed
            const prevStatuses = Object.fromEntries(allPayments.map(p => [p.id, p.status]));
            allPayments = fresh;
            fresh.forEach(p => {
                if (prevStatuses[p.id] && prevStatuses[p.id] !== 'completed' && p.status === 'completed') {
                    showToast(`✅ Payout of ${fmtCurrency(p.amount)} has been received!`, 'success');
                }
            });

            renderTable(allPayments);
            updateStats(allPayments);

            // Stop polling if no pending payouts remain
            hasPending = fresh.some(p => ['pending','processing'].includes(p.status || ''));
            if (!hasPending && pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
                const liveEl = document.getElementById('liveIndicator');
                if (liveEl) liveEl.style.display = 'none';
            }
        } catch (e) {
            // Silently ignore network errors
        }
    }

    function showToast(msg, type = 'info') {
        if (typeof window.__createToast === 'function') {
            window.__createToast(msg, type, 6000);
        }
    }

    // Initial render from PHP data
    renderTable(allPayments);
    updateStats(allPayments);

    // Start polling only if there are pending payouts
    hasPending = allPayments.some(p => ['pending','processing'].includes(p.status || ''));
    if (hasPending) {
        pollInterval = setInterval(fetchAndRefresh, 5000);
    } else {
        const liveEl = document.getElementById('liveIndicator');
        if (liveEl) liveEl.style.display = 'none';
    }
})();
</script>


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