<?php
$payments = $payments ?? [];
$payments = is_array($payments) ? $payments : [];
$summary = $paymentSummary ?? [];
$csrfToken = function_exists('csrf_token') ? csrf_token() : '';

$totalPayouts = isset($summary['total_payouts']) ? (float) $summary['total_payouts'] : 0.0;
$totalPayments = isset($summary['total_payments']) ? (float) $summary['total_payments'] : 0.0;
$pendingCount = isset($summary['pending_count']) ? (int) $summary['pending_count'] : 0;
$completedCount = 0;
$failedCount = 0;

// Calculate counts for tabs
foreach ($payments as $payment) {
    $type = $payment['type'] ?? '';
    $status = strtolower($payment['status'] ?? '');
    $amount = isset($payment['amount']) ? (float) $payment['amount'] : 0.0;

    if ($status === 'completed') {
        $completedCount++;
        // If summary is missing, accumulate totals here
        if ($summary === []) {
             if ($type === 'payout') $totalPayouts += $amount;
             else if ($type === 'payment') $totalPayments += $amount;
        }
    } else if ($status === 'pending') {
        if ($summary === []) $pendingCount++;
    } else if ($status === 'failed') {
        $failedCount++;
    }
}

$netRevenue = $totalPayments - $totalPayouts;

function getStatusTag($status)
{
    switch (strtolower((string)$status)) {
        case 'completed':
            return '<div class="tag completed">Completed</div>';
        case 'pending':
            return '<div class="tag pending">Pending</div>';
        case 'failed':
            return '<div class="tag danger">Failed</div>';
        default:
            return '<div class="tag secondary">' . htmlspecialchars((string)$status) . '</div>';
    }
}
?>

<div class="payment-management-page">
    <!-- Page Header -->
    <page-header title="Payment Overview" description="Manage customer payouts and company payments">
        <button class="btn btn-outline" onclick="refreshPayments()">
            <i class="fa-solid fa-rotate"></i>
            Refresh
        </button>
    </page-header>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <feature-card unwrap title="Total Payouts"
            value="Rs <?= number_format($totalPayouts, 2) ?>" icon="fa-solid fa-arrow-trend-down"
            period="To customers"></feature-card>
        <feature-card unwrap title="Total Income"
            value="Rs <?= number_format($totalPayments, 2) ?>" icon="fa-solid fa-arrow-trend-up"
            period="From companies"></feature-card>
        <feature-card unwrap title="Pending Transactions"
            value="<?= $pendingCount ?>" icon="fa-solid fa-dollar-sign"
            period="Awaiting processing"></feature-card>
        <feature-card unwrap title="Net Revenue"
            value="Rs <?= number_format($netRevenue, 2) ?>" icon="fa-solid fa-arrow-trend-up"
            period="After payouts"></feature-card>
    </div>

    <!-- Recent Transactions Card -->
    <div class="activity-card" style="margin-top: var(--space-8);">
        <div class="activity-card__header">
            <div>
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-dollar-sign" style="margin-right: var(--space-2);"></i>
                    Recent Transactions
                </h3>
                <p class="activity-card__description">Latest payment transactions and their status</p>
            </div>
        </div>

        <div class="activity-card__content">
            <!-- Status Tabs -->
            <div class="tabs" style="margin-bottom: var(--space-4);">
                <div class="tabs-list">
                    <button class="tabs-trigger active" onclick="switchTab('all')" id="tab-all">
                        All (<?= count($payments) ?>)
                    </button>
                    <button class="tabs-trigger" onclick="switchTab('pending')" id="tab-pending">
                        Pending (<?= $pendingCount ?>)
                    </button>
                    <button class="tabs-trigger" onclick="switchTab('completed')" id="tab-completed">
                        Completed (<?= $completedCount ?>)
                    </button>
                    <button class="tabs-trigger" onclick="switchTab('failed')" id="tab-failed">
                        Failed (<?= $failedCount ?>)
                    </button>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Recipient</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">
                                    No payment records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr class="payment-row" data-payment-id="<?= htmlspecialchars($payment['id'] ?? '') ?>"
                                    data-recipient-id="<?= htmlspecialchars((string) ($payment['recipientId'] ?? '')) ?>"
                                    data-recipient-name="<?= htmlspecialchars($payment['recipient'] ?? $payment['recipientName'] ?? '') ?>"
                                    data-amount="<?= htmlspecialchars(number_format((float) ($payment['amount'] ?? 0), 2, '.', '')) ?>"
                                    data-type="<?= htmlspecialchars($payment['type'] ?? '') ?>"
                                    data-status="<?= htmlspecialchars($payment['status'] ?? '') ?>">
                                    <td class="font-medium"><?= htmlspecialchars($payment['id'] ?? '') ?></td>
                                    <td>
                                        <div class="cell-with-icon">
                                            <?php if (($payment['type'] ?? '') === 'payout'): ?>
                                                <i class="fa-solid fa-arrow-trend-down" style="color: #dc2626;"></i>
                                                Payout
                                            <?php else: ?>
                                                <i class="fa-solid fa-arrow-trend-up" style="color: #16a34a;"></i>
                                                Payment
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>Rs <?= number_format((float) ($payment['amount'] ?? 0), 2) ?></td>
                                    <td><?= htmlspecialchars($payment['recipient'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($payment['date'] ?? '') ?></td>
                                    <td>
                                        <?= getStatusTag($payment['status'] ?? '') ?>
                                    </td>
                                    <td>
                                        <?php if (($payment['status'] ?? '') === 'pending' && ($payment['type'] ?? '') === 'payout'): ?>
                                            <button class="btn btn-sm btn-primary rounded"
                                                onclick="processPayment('<?= htmlspecialchars($payment['id'] ?? '') ?>')">
                                                Process
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline rounded"
                                                onclick="viewPaymentDetails('<?= htmlspecialchars($payment['id'] ?? '') ?>')">
                                                View Details
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    window.allPayments = <?= json_encode($payments) ?>;
    window.activeStatusTab = 'all';

    const paymentIcons = {
        payout: '<i class="fa-solid fa-arrow-trend-down" style="color: #dc2626;"></i> Payout',
        payment: '<i class="fa-solid fa-arrow-trend-up" style="color: #16a34a;"></i> Payment',
        refund: '<i class="fa-solid fa-rotate-left" style="color: #0ea5e9;"></i> Refund'
    };

    function showToast(message, type = 'info') {
        if (typeof window.__createToast === 'function') {
            window.__createToast(message, type, 5000);
        } else {
            const prefix = type === 'error' ? 'Error: ' : '';
            alert(prefix + message);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function openModal(options = {}) {
        if (window.Modal && typeof window.Modal.open === 'function') {
            return window.Modal.open(options);
        }
        console.error('ModalManager unavailable.');
        return null;
    }

    async function paymentApi(path, { method = 'GET', body } = {}) {
        const response = await fetch(path, {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...(method !== 'GET' ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
            credentials: 'same-origin',
        });

        let payload = {};
        try { payload = await response.json(); } catch (e) {}

        if (!response.ok) {
            throw new Error(payload.message || `API error ${response.status}`);
        }
        return payload;
    }

    const updatePayment = (id, data) => paymentApi(`/api/payments/${encodeURIComponent(id)}`, { method: 'PUT', body: data });
    const fetchPaymentDetails = (id) => paymentApi(`/api/payments/${encodeURIComponent(id)}`);
    const fetchPaymentsRaw = () => paymentApi('/api/payments');

    async function refreshPayments() {
        const btn = document.querySelector('button[onclick="refreshPayments()"]');
        const icon = btn ? btn.querySelector('i') : null;

        if (icon) icon.classList.add('fa-spin');
        if (btn) btn.disabled = true;

        try {
            const { data } = await fetchPaymentsRaw();
            window.allPayments = data || [];
            updateTabCounts();
            renderPaymentTable(window.allPayments);
            showToast('Payments updated', 'success');
        } catch (error) {
            showToast('Failed to refresh payments', 'error');
        } finally {
            if (icon) icon.classList.remove('fa-spin');
            if (btn) btn.disabled = false;
        }
    }

    function switchTab(status) {
        window.activeStatusTab = status;

        // Update UI classes
        document.querySelectorAll('.tabs-trigger').forEach(el => el.classList.remove('active'));
        const activeTab = document.getElementById(`tab-${status}`);
        if (activeTab) activeTab.classList.add('active');

        renderPaymentTable(window.allPayments);
    }

    function updateTabCounts() {
        const counts = { all: 0, pending: 0, completed: 0, failed: 0 };
        window.allPayments.forEach(p => {
            counts.all++;
            const s = (p.status || '').toLowerCase();
            if (counts.hasOwnProperty(s)) counts[s]++;
        });

        Object.keys(counts).forEach(s => {
            const el = document.getElementById(`tab-${s}`);
            if (el) el.textContent = `${s.charAt(0).toUpperCase() + s.slice(1)} (${counts[s]})`;
        });
    }

    function renderPaymentTable(payments) {
        const tbody = document.querySelector('.data-table tbody');
        if (!tbody) return;

        // Filter by active status
        const filtered = window.activeStatusTab === 'all'
            ? payments
            : payments.filter(p => (p.status || '').toLowerCase() === window.activeStatusTab);

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">
                        No ${window.activeStatusTab === 'all' ? '' : window.activeStatusTab} transactions found.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(payment => {
            const amount = typeof payment.amount === 'number' ? payment.amount : parseFloat(payment.amount || '0');
            const status = payment.status || 'pending';
            const type = payment.type || 'payment';

            const safeId = escapeHtml(payment.id || '');
            const safeRecipientName = escapeHtml(payment.recipient || payment.recipientName || '');

            return `
                <tr class="payment-row"
                    data-payment-id="${safeId}"
                    data-recipient-id="${escapeHtml(payment.recipientId || payment.recipient_id || '')}"
                    data-recipient-name="${safeRecipientName}"
                    data-amount="${amount.toFixed(2)}"
                    data-type="${escapeHtml(type)}"
                    data-status="${escapeHtml(status)}">
                    <td class="font-medium">${safeId}</td>
                    <td>${renderTypeCell(type)}</td>
                    <td>${formatCurrency(amount)}</td>
                    <td>${safeRecipientName}</td>
                    <td>${escapeHtml(payment.date || '')}</td>
                    <td>${renderStatusBadge(status)}</td>
                    <td>
                        ${(status === 'pending' && type === 'payout')
                            ? `<button class="btn btn-sm btn-primary rounded" onclick="processPayment('${safeId}')">Process</button>`
                            : `<button class="btn btn-sm btn-outline rounded" onclick="viewPaymentDetails('${safeId}')">View Details</button>`
                        }
                    </td>
                </tr>
            `;
        }).join('');
    }

    function findPaymentRow(paymentId) {
        return document.querySelector(`tr[data-payment-id="${CSS.escape(paymentId)}"]`);
    }

    function formatCurrency(amount) {
        const val = typeof amount === 'number' ? amount : parseFloat(amount || '0');
        return `Rs ${val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function renderStatusBadge(status) {
        const normalized = (status || '').toLowerCase();
        if (normalized === 'completed') return '<div class="tag completed">Completed</div>';
        if (normalized === 'pending') return '<div class="tag pending">Pending</div>';
        if (normalized === 'failed') return '<div class="tag danger">Failed</div>';
        return `<div class="tag secondary">${escapeHtml(status || 'N/A')}</div>`;
    }

    function renderTypeCell(type) {
        const normalized = (type || '').toLowerCase();
        return `<div class="cell-with-icon">${paymentIcons[normalized] || escapeHtml(type)}</div>`;
    }

    function updatePaymentRow(row, record) {
        if (!row || !record) return;
        
        // Update the global state too
        const idx = window.allPayments.findIndex(p => p.id === record.id);
        if (idx !== -1) window.allPayments[idx] = record;
        
        updateTabCounts();
        renderPaymentTable(window.allPayments);
    }

    function processPayment(paymentId) {
        const row = findPaymentRow(paymentId);
        if (!row) return;

        const ds = row.dataset;
        const paymentData = {
            id: paymentId,
            type: ds.type || 'payout',
            amount: Number(ds.amount || 0),
            recipient: ds.recipientName || 'Unknown'
        };

        const container = document.createElement('div');
        container.innerHTML = `
            <div style="display:grid;gap:1rem;">
                <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:#f0fdf4;border-radius:10px;border:1px solid #86efac;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#16a34a;display:flex;align-items:center;justify-content:center;color:#fff;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:0.75rem;color:#166534;">Payout To</div>
                        <div style="font-weight:600;">${escapeHtml(paymentData.recipient)}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.75rem;color:#166534;">Amount</div>
                        <div style="font-weight:700;">${formatCurrency(paymentData.amount)}</div>
                    </div>
                </div>
                <p style="font-size:0.875rem;color:#4b5563;">Marking this as paid will record it in the system ledger.</p>
            </div>
        `;

        openModal({
            title: 'Confirm Payment',
            size: 'sm',
            content: container,
            actions: [
                { label: 'Cancel', variant: 'plain' },
                {
                    label: 'Mark as Paid',
                    variant: 'primary',
                    dismiss: false,
                    loadingLabel: 'Saving...',
                    onClick: async ({ close, setLoading }) => {
                        setLoading(true);
                        try {
                            const { data } = await updatePayment(paymentId, {
                                status: 'completed',
                                processed_at: new Date().toISOString()
                            });
                            updatePaymentRow(row, data || {});
                            showToast('Payment processed', 'success');
                            close();
                        } catch (e) {
                            showToast(e.message, 'error');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        });
    }

    async function viewPaymentDetails(paymentId) {
        try {
            const { data } = await fetchPaymentDetails(paymentId);
            const list = document.createElement('div');
            list.style.display = 'grid';
            list.style.gap = '0.75rem';
            
            const fields = [
                ['ID', data.id],
                ['Recipient', data.recipient || data.recipientName],
                ['Amount', formatCurrency(data.amount)],
                ['Status', (data.status || '').toUpperCase()],
                ['Date', data.date]
            ];

            fields.forEach(([l, v]) => {
                const item = document.createElement('div');
                item.innerHTML = `<span style="font-size:0.75rem;color:#6b7280;display:block;">${l}</span><strong>${escapeHtml(v)}</strong>`;
                list.appendChild(item);
            });

            openModal({ title: 'Transaction Details', content: list, actions: [{ label: 'Close', variant: 'plain' }] });
        } catch (e) { showToast(e.message, 'error'); }
    }
</script>