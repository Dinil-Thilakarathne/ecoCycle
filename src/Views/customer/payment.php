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
    $total_received += ($p['amount'] ?? 0);
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

<div style="background: var(--neutral-1);">
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
        <div class="activity-card" style="margin-top: var(--space-8); border: 0; border-radius: 0; padding: 0; background: transparent; box-shadow: none;">
            <div class="activity-card__header" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <h3 class="activity-card__title">
                        <i class="fa-solid fa-dollar-sign" style="margin-right: var(--space-2);"></i>
                        Recent Transactions
                    </h3>
                    <p class="activity-card__description">Latest payment transactions and their status</p>
                </div>
                <div id="liveIndicator" style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:#64748b;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 2s infinite;"></span>
                    Live
                </div>
            </div>
            <div class="activity-card__content" id="transactionTable">
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
    function fmtTime(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        return isNaN(d) ? '-' : d.toLocaleTimeString(undefined, {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    function fmtCurrency(n) {
        return 'Rs ' + parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits:2,maximumFractionDigits:2});
    }
    function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    function openModal(options = {}) {
        if (window.Modal && typeof window.Modal.open === 'function') {
            return window.Modal.open(options);
        }
        return null;
    }

    function openPaymentDetailsModal(payment = {}) {
        const details = [
            { label: 'Amount', value: fmtCurrency(payment.amount) },
            { label: 'Date', value: fmtDate(payment.date) },
            { label: 'Time', value: fmtTime(payment.date) },
            { label: 'Status', value: ucfirst(String(payment.status || 'pending').toLowerCase()) },
        ];

        const list = document.createElement('div');
        list.style.cssText = 'display:grid;gap:0.9rem;';
        details.forEach(item => {
            const block = document.createElement('div');
            block.innerHTML = `
                <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.2rem;">${escHtml(item.label)}</span>
                <strong style="color:#111827;">${escHtml(item.value)}</strong>
            `;
            list.appendChild(block);
        });

        const modal = openModal({
            title: 'Payment Details',
            size: 'sm',
            content: list,
            actions: [{ label: 'Close', variant: 'plain' }]
        });

        if (!modal) {
            alert(
                `Payment Details\n\n` +
                `Amount: ${fmtCurrency(payment.amount)}\n` +
                `Date: ${fmtDate(payment.date)}\n` +
                `Time: ${fmtTime(payment.date)}\n` +
                `Status: ${ucfirst(String(payment.status || 'pending').toLowerCase())}`
            );
        }
    }

    function renderTable(payments) {
        const el = document.getElementById('transactionTable');
        if (!el) return;

        const hasRows = Array.isArray(payments) && payments.length > 0;
        const rows = hasRows
            ? payments.map((p, index) => {
                const status = String(p.status || '').toLowerCase();
                const tagClass = status === 'completed' ? 'completed' : (status === 'failed' ? 'danger' : 'pending');
                return `
                    <tr>
                        <td class="font-medium">${index + 1}</td>
                        <td>${escHtml(fmtCurrency(p.amount))}</td>
                        <td>${escHtml(fmtDate(p.date))}</td>
                        <td>
                            <span class="tag ${tagClass}">${escHtml(ucfirst(p.status || 'pending'))}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline js-view-payment" data-index="${index}">View Details</button>
                        </td>
                    </tr>`;
            }).join('')
            : `<tr>
                    <td colspan="5" style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">No transactions found.</td>
               </tr>`;

        el.innerHTML = `
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>`;

        el.querySelectorAll('.js-view-payment').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.getAttribute('data-index'));
                const payment = Array.isArray(payments) ? payments[idx] : null;
                if (!payment) {
                    showToast('Unable to load payment details.', 'error');
                    return;
                }
                openPaymentDetailsModal(payment);
            });
        });
    }

    function updateStats(payments) {
        const totalEarned   = payments.reduce((s, p) => s + parseFloat(p.amount || 0), 0);
        const totalCount    = payments.length;
        const cards = document.querySelectorAll('.feature-card .feature-card__body');
        if (cards[0]) cards[0].textContent = fmtCurrency(totalEarned);
        if (cards[1]) cards[1].textContent  = totalCount;
    }

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