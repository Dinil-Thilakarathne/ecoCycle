<?php
$payments = $payments ?? [];
$payments = is_array($payments) ? $payments : [];
$summary = $paymentSummary ?? [];

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
                        <tr>
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
                                    <button class="btn btn-sm btn-outline">
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

    function createModal({ title, content, buttons = [], width = '520px' }) {
        const backdrop = document.createElement('div');
        backdrop.className = 'simple-modal-backdrop';
        backdrop.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2000;padding:1rem;';

        const dialog = document.createElement('div');
        dialog.style.cssText = `background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(15,23,42,0.16);width:min(${width},100%);max-width:${width};padding:1.75rem;display:flex;flex-direction:column;gap:1.5rem;`;

        const header = document.createElement('div');
        header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:1rem;';
        const titleEl = document.createElement('h3');
        titleEl.textContent = title;
        titleEl.style.cssText = 'margin:0;font-size:1.25rem;font-weight:600;color:#111827;';
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.innerHTML = '&times;';
        closeButton.style.cssText = 'border:none;background:transparent;font-size:1.75rem;line-height:1;color:#6b7280;cursor:pointer;padding:0 0 0.25rem 0;';

        const body = document.createElement('div');
        body.style.cssText = 'max-height:60vh;overflow:auto;';
        body.appendChild(content);

        const footer = document.createElement('div');
        footer.style.cssText = 'display:flex;justify-content:flex-end;gap:0.75rem;';

        function closeModal() {
            backdrop.remove();
        }

        closeButton.addEventListener('click', closeModal);
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) {
                closeModal();
            }
        });

        buttons.forEach((buttonConfig) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = buttonConfig.label;

            const variant = buttonConfig.variant || 'secondary';
            let styles = 'padding:0.6rem 1.25rem;border-radius:6px;font-weight:600;border:none;cursor:pointer;';
            if (variant === 'primary') {
                styles += 'background:#16a34a;color:#fff;';
            } else if (variant === 'danger') {
                styles += 'background:#dc2626;color:#fff;';
            } else {
                styles += 'background:#6b7280;color:#fff;';
            }
            btn.style.cssText = styles;

            btn.addEventListener('click', function () {
                if (typeof buttonConfig.onClick === 'function') {
                    buttonConfig.onClick(closeModal);
                } else {
                    closeModal();
                }
            });

            footer.appendChild(btn);
        });

        header.appendChild(titleEl);
        header.appendChild(closeButton);
        dialog.appendChild(header);
        dialog.appendChild(body);
        dialog.appendChild(footer);
        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);

        return {
            close: closeModal,
            element: backdrop,
        };
    }

    function processPayment(paymentId) {
        console.log('Processing payment ' + paymentId);

        // Find payment data from the table row
        const row = document.querySelector(`tr:has(td.font-medium:first-child)`);
        let paymentData = {
            id: paymentId,
            type: 'Unknown',
            amount: '0.00',
            recipient: 'Unknown'
        };

        // Try to extract data from the row if available
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach(r => {
            const idCell = r.querySelector('td.font-medium');
            if (idCell && idCell.textContent.trim() === paymentId) {
                const cells = r.querySelectorAll('td');
                if (cells.length >= 4) {
                    paymentData.type = cells[1].textContent.trim();
                    paymentData.amount = cells[2].textContent.trim();
                    paymentData.recipient = cells[3].textContent.trim();
                }
            }
        });

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
                                <strong style="color:#16a34a;font-size:1.1rem;">${escapeHtml(paymentData.amount)}</strong>
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
                    <select id="paymentMethod" style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;">
                        <option value="">Select payment method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="online">Online Payment</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Reference Number</label>
                    <input type="text" id="referenceNumber" placeholder="Enter reference or transaction number" 
                        style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;" />
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Notes (Optional)</label>
                    <textarea id="paymentNotes" rows="3" placeholder="Add any additional notes about this payment..."
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

        createModal({
            title: 'Process Payment',
            content: container,
            buttons: [
                {
                    label: 'Cancel',
                    variant: 'secondary',
                    onClick: (close) => close()
                },
                {
                    label: 'Process Payment',
                    variant: 'primary',
                    onClick: (close) => {
                        const paymentMethod = document.getElementById('paymentMethod').value;
                        const referenceNumber = document.getElementById('referenceNumber').value;
                        const notes = document.getElementById('paymentNotes').value;

                        if (!paymentMethod) {
                            showToast('Please select a payment method', 'error');
                            return;
                        }

                        // Here you would typically make an API call to process the payment
                        console.log('Processing payment with:', {
                            paymentId,
                            paymentMethod,
                            referenceNumber,
                            notes
                        });

                        showToast('Payment processed successfully!', 'success');
                        close();

                        // In a real application, you would update the UI to reflect the processed payment
                        // For now, we'll just show a success message
                    }
                }
            ],
            width: '540px'
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
                    <select id="batchPaymentType" style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;">
                        <option value="">Select payment type</option>
                        <option value="all">All Pending</option>
                        <option value="payout">Payouts Only</option>
                        <option value="payment">Payments Only</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Batch Processing Method</label>
                    <select id="batchMethod" style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;">
                        <option value="">Select method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="bulk_payout">Bulk Payout Service</option>
                        <option value="manual">Manual Processing</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Batch Reference</label>
                    <input type="text" id="batchReference" placeholder="Enter batch reference number" 
                        style="width:100%;padding:0.625rem;border:2px solid #d1d5db;border-radius:6px;font-size:0.95rem;" />
                </div>

                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#111827;">Processing Date</label>
                    <input type="date" id="processingDate" value="${new Date().toISOString().split('T')[0]}"
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

        createModal({
            title: 'Batch Payment Processing',
            content: container,
            buttons: [
                {
                    label: 'Cancel',
                    variant: 'secondary',
                    onClick: (close) => close()
                },
                {
                    label: 'Process Batch',
                    variant: 'primary',
                    onClick: (close) => {
                        const paymentType = document.getElementById('batchPaymentType').value;
                        const batchMethod = document.getElementById('batchMethod').value;
                        const batchReference = document.getElementById('batchReference').value;
                        const processingDate = document.getElementById('processingDate').value;

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

                        showToast('Batch payment processing initiated!', 'success');
                        close();

                        // In a real application, you would update the UI to reflect the processed payments
                    }
                }
            ],
            width: '560px'
        });
    }
</script>