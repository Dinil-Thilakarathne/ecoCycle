<?php
$payments = $payments ?? [];
$payments = is_array($payments) ? $payments : [];
$summary = $paymentSummary ?? [];

$totalPayouts = isset($summary['total_payouts']) ? (float) $summary['total_payouts'] : 0.0;
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
        <button class="btn btn-primary">
            <i class="fa-solid fa-credit-card"></i>
            Process Payments
        </button>
    </page-header>

    <!-- Statistics Grid (feature-card components) -->
    <?php
    $paymentStatCards = [
        [
            'title' => 'Total Payouts',
            'value' => 'Rs ' . number_format($totalPayouts, 2),
            'icon' => 'fa-solid fa-arrow-trend-down',
            'period' => 'To customers',
        ],
        [
            'title' => 'Total Payments',
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
    function processPayment(paymentId) {
        // Placeholder for payment processing functionality
        console.log('Processing payment ' + paymentId);
        alert('Payment processing functionality would be implemented here');
    }
</script>