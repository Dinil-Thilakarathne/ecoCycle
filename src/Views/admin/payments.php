<?php
$payments = $payments ?? [];
$payments = is_array($payments) ? $payments : [];
$summary = $paymentSummary ?? [];
$csrfToken = function_exists('csrf_token') ? csrf_token() : '';

$totalPayouts = isset($summary['total_payouts']) ? (float) $summary['total_payouts'] : 1000.0;
$totalPayments = isset($summary['total_payments']) ? (float) $summary['total_payments'] : 0.0;
$pendingCount = isset($summary['pending_count']) ? (int) $summary['pending_count'] : 0;

// Fallback to calculating from provided payments if summary missing
if ($summary === [] && !empty($payments)) {
    foreach ($payments as $payment) {
        $type = $payment['type'] ?? '';
        $status = $payment['status'] ?? '';
        $amount = isset($payment['amount']) ? (float) $payment['amount'] : 0.0;
        if ($type === 'payout' && $status === 'completed') {
            $totalPayouts += $amount;
        }
        if ($type === 'payment' && $status === 'completed') {
            $totalPayments += $amount;
        }
        if ($status === 'pending') {
            $pendingCount++;
        }
    }
}

$netRevenue = $totalPayments - $totalPayouts;

function getStatusTag($status)
{
    switch ($status) {
        case 'completed':
            return '<div class="tag completed">Completed</div>';
        case 'pending':
            return '<div class="tag pending">Pending</div>';
        case 'failed':
            return '<div class="tag danger">Failed</div>';
        default:
            return '<div class="tag secondary">' . htmlspecialchars($status) . '</div>';
    }
}
?>

<div>
    <!-- Page Header -->
    <page-header title="Payment Overview" description="Manage customer payouts and company payments">
        <button class="btn btn-outline" onclick="refreshPayments()" style="margin-right: var(--space-2);">
            <i class="fa-solid fa-rotate"></i>
            Refresh
        </button>
        <button class="btn btn-primary" onclick="openBatchPaymentModal()">
            <i class="fa-solid fa-credit-card"></i>
            Process Payments
        </button>
    </page-header>

    <!-- Statistics Grid (feature-card components) -->
    <?php
    $paymentStatCards = [
        [
            'title' => 'Total Payouts',
            'value' => 'Rs 10,000.00', // TODO: need to change
            'icon' => 'fa-solid fa-arrow-trend-down',
            'period' => 'To customers',
        ],
        [
            'title' => 'Total Income',
            'value' => 'Rs ' . number_format($totalPayments, 2),
            'icon' => 'fa-solid fa-arrow-trend-up',
            'period' => 'From companies',
        ],
        [
            'title' => 'Pending Transactions',
            'value' => $pendingCount,
            'icon' => 'fa-solid fa-dollar-sign',
            'period' => 'Awaiting processing',
        ],
        [
            'title' => 'Net Revenue',
            'value' => 'Rs ' . number_format($netRevenue, 2),
            'icon' => 'fa-solid fa-arrow-trend-up',
            'period' => 'After payouts',
        ],
    ];
    ?>
    <div class="stats-grid">
        <?php foreach ($paymentStatCards as $card): ?>
            <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>"
                value="<?= htmlspecialchars($card['value']) ?>" icon="<?= htmlspecialchars($card['icon']) ?>"
                period="<?= htmlspecialchars($card['period']) ?>"></feature-card>
        <?php endforeach; ?>
    </div>

    <!-- Recent Transactions Card -->
    <div class="activity-card" style="margin-top: var(--space-8);">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-dollar-sign" style="margin-right: var(--space-2);"></i>
                Recent Transactions
            </h3>
            <p class="activity-card__description">Latest payment transactions and their status</p>
        </div>
        <div class="activity-card__content">
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
                    <?php foreach ($payments as $payment): ?>
                        <tr class="payment-row"
                            data-payment-id="<?= htmlspecialchars($payment['id'] ?? '') ?>"
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
                                <?php if (($payment['status'] ?? '') === 'pending'): ?>
                                    <button class="btn btn-sm btn-primary"
                                        onclick="processPayment('<?= htmlspecialchars($payment['id'] ?? '') ?>')">
                                        Process
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline"
                                        onclick="viewPaymentDetails('<?= htmlspecialchars($payment['id'] ?? '') ?>')">
                                        View Details
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">
                                No payment records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;

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

        console.error('ModalManager is unavailable. Ensure the modal script is loaded.');
        showToast('Modal component is unavailable right now.', 'error');
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
        try {
            payload = await response.json();
        } catch (error) {
            // ignore JSON parse errors; payload remains {}
        }

        if (!response.ok) {
            const message = payload && payload.message ? payload.message : `Payment API failed (${response.status})`;
            throw new Error(message);
        }

        return payload;
    }

    const recordPayment = (data) => paymentApi('/api/payments', { method: 'POST', body: data });
    const updatePayment = (id, data) => paymentApi(`/api/payments/${encodeURIComponent(id)}`, { method: 'PUT', body: data });
    const fetchPaymentDetails = (id) => paymentApi(`/api/payments/${encodeURIComponent(id)}`);
    const fetchPayments = () => paymentApi('/api/payments');

    async function refreshPayments() {
        const btn = document.querySelector('button[onclick="refreshPayments()"]');
        const icon = btn ? btn.querySelector('i') : null;
        
        if (icon) icon.classList.add('fa-spin');
        if (btn) btn.disabled = true;

        try {
            const { data } = await fetchPayments();
            renderPaymentTable(data || []);
            showToast('Payment list updated', 'success');
        } catch (error) {
            showToast('Failed to refresh payments', 'error');
        } finally {
            if (icon) icon.classList.remove('fa-spin');
            if (btn) btn.disabled = false;
        }
    }

    function renderPaymentTable(payments) {
        const tbody = document.querySelector('.data-table tbody');
        if (!tbody) return;

        if (!payments || payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">
                        No payment records found.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = payments.map(payment => {
            const amount = typeof payment.amount === 'number' ? payment.amount : parseFloat(payment.amount || '0');
            const status = payment.status || 'pending';
            const type = payment.type || 'payment';
            
            // Escape attributes
            const safeId = escapeHtml(payment.id || '');
            const safeRecipientId = escapeHtml(payment.recipientId || payment.recipient_id || '');
            const safeRecipientName = escapeHtml(payment.recipient || payment.recipientName || '');
            const safeAmount = amount.toFixed(2);
            const safeType = escapeHtml(type);
            const safeStatus = escapeHtml(status);

            return `
                <tr class="payment-row"
                    data-payment-id="${safeId}"
                    data-recipient-id="${safeRecipientId}"
                    data-recipient-name="${safeRecipientName}"
                    data-amount="${safeAmount}"
                    data-type="${safeType}"
                    data-status="${safeStatus}">
                    <td class="font-medium">${safeId}</td>
                    <td>${renderTypeCell(type)}</td>
                    <td>${formatCurrency(amount)}</td>
                    <td>${safeRecipientName}</td>
                    <td>${escapeHtml(payment.date || '')}</td>
                    <td>${renderStatusBadge(status)}</td>
                    <td>
                        ${status === 'pending' 
                            ? `<button class="btn btn-sm btn-primary" onclick="processPayment('${safeId}')">Process</button>`
                            : `<button class="btn btn-sm btn-outline" onclick="viewPaymentDetails('${safeId}')">View Details</button>`
                        }
                    </td>
                </tr>
            `;
        }).join('');
    }

    function findPaymentRow(paymentId) {
        if (!paymentId) {
            return null;
        }
        return document.querySelector(`tr[data-payment-id="${CSS.escape(paymentId)}"]`);
    }

    function formatCurrency(amount) {
        const value = typeof amount === 'number' ? amount : parseFloat(amount || '0');
        return `Rs ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function renderStatusBadge(status) {
        const normalized = (status || '').toLowerCase();
        if (normalized === 'completed') {
            return '<div class="tag completed">Completed</div>';
        }
        if (normalized === 'pending') {
            return '<div class="tag pending">Pending</div>';
        }
        if (normalized === 'failed') {
            return '<div class="tag danger">Failed</div>';
        }
        return `<div class="tag secondary">${escapeHtml(status || 'N/A')}</div>`;
    }

    function renderTypeCell(type) {
        const normalized = (type || '').toLowerCase();
        return `<div class="cell-with-icon">${paymentIcons[normalized] || escapeHtml(type || 'Unknown')}</div>`;
    }

    function getFieldValue(container, selector) {
        const element = container ? container.querySelector(selector) : null;
        return element ? element.value : '';
    }

    function updatePaymentRow(row, record) {
        if (!row || !record) {
            return;
        }

        const cells = row.querySelectorAll('td');
        const amountNumber = typeof record.amount === 'number' ? record.amount : parseFloat(record.amount || '0');
        const status = record.status || 'completed';

        row.dataset.paymentId = record.id || '';
        row.dataset.recipientId = record.recipientId || record.recipient_id || '';
        row.dataset.recipientName = record.recipient || record.recipientName || record.recipient_name || '';
        row.dataset.amount = amountNumber.toFixed(2);
        row.dataset.type = record.type || '';
        row.dataset.status = status;

        if (cells[0]) {
            cells[0].textContent = record.id || '';
        }
        if (cells[1]) {
            cells[1].innerHTML = renderTypeCell(record.type || 'payment');
        }
        if (cells[2]) {
            cells[2].textContent = formatCurrency(amountNumber);
        }
        if (cells[3]) {
            cells[3].textContent = record.recipient || record.recipientName || '';
        }
        if (cells[4]) {
            cells[4].textContent = record.date || new Date().toISOString().slice(0, 19).replace('T', ' ');
        }
        if (cells[5]) {
            cells[5].innerHTML = renderStatusBadge(status);
        }
        if (cells[6]) {
            cells[6].innerHTML = `<button class="btn btn-sm btn-outline" onclick="viewPaymentDetails('${escapeHtml(record.id || '')}')">View Details</button>`;
        }
    }

    function processPayment(paymentId) {
        const row = findPaymentRow(paymentId);
        if (!row) {
            showToast('Unable to locate the selected payment row.', 'error');
            return;
        }

        const dataset = row.dataset || {};
        const recipientId = Number(dataset.recipientId || '0');
        const amountValue = Number(dataset.amount || '0');

        if (!recipientId) {
            showToast('Recipient information is missing for this payment.', 'error');
            return;
        }

        const paymentData = {
            id: paymentId,
            type: dataset.type || 'payout',
            amount: amountValue,
            recipient: dataset.recipientName || 'Unknown recipient'
        };

        const container = document.createElement('div');
        container.innerHTML = `
            <div style="display:grid;gap:1.5rem;">
                <div style="background:#f9fafb;padding:1rem;border-radius:8px;border:1px solid #e5e7eb;">
                    <div style="display:grid;gap:1rem;">
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.25rem;">Transaction ID</span>
                            <strong style="font-size:1rem;color:#111827;">${escapeHtml(paymentData.id)}</strong>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;">
                            <div>
                                <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.25rem;">Type</span>
                                <strong style="color:#111827;">${escapeHtml(paymentData.type)}</strong>
                            </div>
                            <div>
                                <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.25rem;">Amount</span>
                                <strong style="color:#16a34a;font-size:1.1rem;">${escapeHtml(formatCurrency(paymentData.amount))}</strong>
                            </div>
                        </div>
                        <div>
                            <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.25rem;">Recipient</span>
                            <strong style="color:#111827;">${escapeHtml(paymentData.recipient)}</strong>
                        </div>
                    </div>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Payment Method</label>
                    <select data-payment-field="method" style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;">
                        <option value="">Select payment method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="online">Online Payment</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Reference Number</label>
                    <input type="text" data-payment-field="reference" placeholder="Enter reference or transaction number" 
                        style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;" />
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Notes (Optional)</label>
                    <textarea data-payment-field="notes" rows="3" placeholder="Add any additional notes about this payment..."
                        style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;resize:vertical;font-family:inherit;font-size:0.95rem;"></textarea>
                </div>

                <div style="background:#fef3c7;padding:1rem;border-radius:8px;border:1px solid #fde047;">
                    <div style="display:flex;gap:0.75rem;align-items:start;">
                        <i class="fa-solid fa-circle-exclamation" style="color:#ca8a04;margin-top:0.125rem;"></i>
                        <p style="margin:0;color:#713f12;font-size:0.9rem;line-height:1.5;">
                            <strong>Important:</strong> Please verify all payment details before processing. This action will mark the payment as completed.
                        </p>
                    </div>
                </div>
            </div>
        `;

        openModal({
            title: 'Process Payment',
            size: 'md',
            content: container,
            actions: [
                {
                    label: 'Cancel',
                    variant: 'plain'
                },
                {
                    label: 'Process Payment',
                    variant: 'primary',
                    dismiss: false,
                    loadingLabel: 'Processing...',
                    onClick: async ({ body, close, setLoading }) => {
                        const paymentMethod = getFieldValue(body, '[data-payment-field="method"]');
                        const referenceNumber = getFieldValue(body, '[data-payment-field="reference"]');
                        const notes = getFieldValue(body, '[data-payment-field="notes"]');

                        if (!paymentMethod) {
                            showToast('Please select a payment method', 'error');
                            return;
                        }

                        setLoading(true);

                        try {
                            const payload = {
                                recipientId,
                                amount: amountValue,
                                type: paymentData.type || 'payout',
                                status: 'completed',
                                txnId: referenceNumber || undefined,
                                gatewayResponse: {
                                    method: paymentMethod,
                                    notes: notes || undefined,
                                    sourcePaymentId: paymentId
                                }
                            };

                            const { data } = await updatePayment(paymentId, payload);
                            updatePaymentRow(row, data || {});
                            showToast('Payment updated successfully', 'success');
                            close();
                        } catch (error) {
                            showToast(error.message || 'Payment processing failed', 'error');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        });
    }

    async function viewPaymentDetails(paymentId) {
        if (!paymentId) {
            showToast('Missing payment identifier', 'error');
            return;
        }

        try {
            const { data } = await fetchPaymentDetails(paymentId);
            openPaymentDetailsModal(data || {});
        } catch (error) {
            showToast(error.message || 'Failed to load payment details', 'error');
        }
    }

    function openPaymentDetailsModal(record) {
        const entries = [
            { label: 'Transaction ID', value: record.id || 'N/A' },
            { label: 'Reference', value: record.txnId || '—' },
            { label: 'Type', value: (record.type || '').toUpperCase() },
            { label: 'Amount', value: formatCurrency(record.amount || 0) },
            { label: 'Recipient', value: record.recipient || record.recipientName || 'N/A' },
            { label: 'Status', value: (record.status || '').toUpperCase() },
            { label: 'Date', value: record.date || 'N/A' }
        ];

        const list = document.createElement('div');
        list.style.cssText = 'display:grid;gap:1rem;';
        entries.forEach(entry => {
            const block = document.createElement('div');
            block.classList.add('payment-detail-entry');
            block.innerHTML = `
                <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.25rem;">${escapeHtml(entry.label)}</span>
                <strong style="color:#111827;">${escapeHtml(entry.value)}</strong>
            `;
            list.appendChild(block);
        });

        if (record.gatewayResponse) {
            const gateway = typeof record.gatewayResponse === 'object'
                ? JSON.stringify(record.gatewayResponse, null, 2)
                : String(record.gatewayResponse);
            const gatewayBlock = document.createElement('div');
            gatewayBlock.classList.add('payment-gateway-response');
            gatewayBlock.innerHTML = `
                <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.25rem;">Gateway Response</span>
                <pre style="background:#f3f4f6;padding:0.75rem;border-radius:8px;overflow:auto;white-space:pre-wrap;">${escapeHtml(gateway)}</pre>
            `;
            list.appendChild(gatewayBlock);
        }

        openModal({
            title: 'Payment Details',
            size: 'md',
            content: list,
            actions: [
                {
                    label: 'Close',
                    variant: 'plain'
                }
            ]
        });
    }

    function openBatchPaymentModal() {
        const container = document.createElement('div');
        container.innerHTML = `
            <div style="display:grid;gap:1.5rem;">
                <div style="background:#f0f9ff;padding:1rem;border-radius:8px;border:1px solid #bae6fd;">
                    <div style="display:flex;gap:0.75rem;align-items:start;">
                        <i class="fa-solid fa-circle-info" style="color:#0284c7;margin-top:0.125rem;"></i>
                        <p style="margin:0;color:#075985;font-size:0.9rem;line-height:1.5;">
                            Process multiple pending payments in a batch. This will mark all selected transactions as completed.
                        </p>
                    </div>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Payment Type</label>
                    <select data-batch-field="type" style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;">
                        <option value="">Select payment type</option>
                        <option value="all">All Pending</option>
                        <option value="payout">Payouts Only</option>
                        <option value="payment">Payments Only</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Batch Processing Method</label>
                    <select data-batch-field="method" style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;">
                        <option value="">Select method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="bulk_payout">Bulk Payout Service</option>
                        <option value="manual">Manual Processing</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Batch Reference</label>
                    <input type="text" data-batch-field="reference" placeholder="Enter batch reference number" 
                        style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;" />
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Processing Date</label>
                    <input type="date" data-batch-field="date" value="${new Date().toISOString().split('T')[0]}"
                        style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;" />
                </div>

                <div style="background:#f9fafb;padding:1rem;border-radius:8px;border:1px solid #e5e7eb;">
                    <h4 style="margin:0 0 0.75rem 0;font-size:0.95rem;color:#111827;">Summary</h4>
                    <div style="display:grid;gap:0.5rem;font-size:0.9rem;">
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:#6b7280;">Pending Transactions:</span>
                            <strong style="color:#111827;"><?= $pendingCount ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:#6b7280;">Estimated Total:</span>
                            <strong style="color:#16a34a;">Calculate on selection</strong>
                        </div>
                    </div>
                </div>

                <div style="background:#fef3c7;padding:1rem;border-radius:8px;border:1px solid #fde047;">
                    <div style="display:flex;gap:0.75rem;align-items:start;">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#ca8a04;margin-top:0.125rem;"></i>
                        <p style="margin:0;color:#713f12;font-size:0.9rem;line-height:1.5;">
                            <strong>Warning:</strong> Batch processing will affect multiple transactions. Please ensure all details are correct before proceeding.
                        </p>
                    </div>
                </div>
            </div>
        `;

        openModal({
            title: 'Batch Payment Processing',
            size: 'lg',
            content: container,
            actions: [
                {
                    label: 'Cancel',
                    variant: 'plain'
                },
                {
                    label: 'Process Batch',
                    variant: 'primary',
                    dismiss: false,
                    onClick: ({ body, close }) => {
                        const paymentType = getFieldValue(body, '[data-batch-field="type"]');
                        const batchMethod = getFieldValue(body, '[data-batch-field="method"]');
                        const batchReference = getFieldValue(body, '[data-batch-field="reference"]');
                        const processingDate = getFieldValue(body, '[data-batch-field="date"]');

                        if (!paymentType) {
                            showToast('Please select a payment type', 'error');
                            return;
                        }

                        if (!batchMethod) {
                            showToast('Please select a processing method', 'error');
                            return;
                        }

                        if (!batchReference) {
                            showToast('Please enter a batch reference', 'error');
                            return;
                        }

                        // Here you would typically make an API call to process the batch
                        console.log('Processing batch payments with:', {
                            paymentType,
                            batchMethod,
                            batchReference,
                            processingDate
                        });

                        showToast('Batch payment processing is not yet wired to the API. Please process individually for now.', 'info');
                        close();
                    }
                }
            ]
        });
    }
</script>