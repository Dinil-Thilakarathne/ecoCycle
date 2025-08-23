<?php
// Payment data (in a real application, this would come from your database/models)
$payments = [
    [
        'id' => 'PAY001',
        'type' => 'payout',
        'amount' => 125.50,
        'recipient' => 'Alice Johnson',
        'status' => 'completed',
        'date' => '2024-01-15'
    ],
    [
        'id' => 'PAY002',
        'type' => 'payment',
        'amount' => 450.00,
        'recipient' => 'GreenTech Co.',
        'status' => 'pending',
        'date' => '2024-01-15'
    ],
    [
        'id' => 'PAY003',
        'type' => 'payout',
        'amount' => 67.25,
        'recipient' => 'Bob Smith',
        'status' => 'completed',
        'date' => '2024-01-14'
    ],
    [
        'id' => 'PAY004',
        'type' => 'payment',
        'amount' => 320.00,
        'recipient' => 'EcoWaste Solutions',
        'status' => 'completed',
        'date' => '2024-01-14'
    ],
    [
        'id' => 'PAY005',
        'type' => 'payment',
        'amount' => 275.75,
        'recipient' => 'RecycleCorp Ltd.',
        'status' => 'completed',
        'date' => '2024-01-13'
    ],
    [
        'id' => 'PAY006',
        'type' => 'payout',
        'amount' => 89.30,
        'recipient' => 'Charlie Davis',
        'status' => 'pending',
        'date' => '2024-01-13'
    ],
];

// Calculate totals
$totalPayouts = 0;
$totalPayments = 0;
$pendingCount = 0;

foreach ($payments as $payment) {
    if ($payment['type'] === 'payout' && $payment['status'] === 'completed') {
        $totalPayouts += $payment['amount'];
    }
    if ($payment['type'] === 'payment' && $payment['status'] === 'completed') {
        $totalPayments += $payment['amount'];
    }
    if ($payment['status'] === 'pending') {
        $pendingCount++;
    }
}

// $netRevenue = $totalPayments - $totalPayouts;
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
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Payment Overview</h2>
            <p class="page-header__description">Manage customer payouts and company payments</p>
        </div>
        <button class="btn btn-primary">
            <i class="fa-solid fa-credit-card"></i>
            Process Payments
        </button>
    </div>

    <!-- Statistics Grid (feature-card components) -->
    <?php
    $paymentStatCards = [
        [
            'title' => 'Total Payouts',
            'value' => '$' . number_format($totalPayouts, 2),
            'icon' => 'fa-solid fa-arrow-trend-down',
            'period' => 'To customers',
        ],
        [
            'title' => 'Total Payments',
            'value' => '$' . number_format($totalPayments, 2),
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
            'value' => '$' . number_format($netRevenue, 2),
            'icon' => 'fa-solid fa-arrow-trend-up',
            'period' => 'After payouts',
        ],
    ];
    ?>
    <div class="stats-grid">
        <?php foreach ($paymentStatCards as $card): ?>
            <feature-card title="<?= htmlspecialchars($card['title']) ?>" value="<?= htmlspecialchars($card['value']) ?>"
                icon="<?= htmlspecialchars($card['icon']) ?>"
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
                            <td class="font-medium"><?= htmlspecialchars($payment['id']) ?></td>
                            <td>
                                <div class="cell-with-icon">
                                    <?php if ($payment['type'] === 'payout'): ?>
                                        <i class="fa-solid fa-arrow-trend-down" style="color: #dc2626;"></i>
                                        Payout
                                    <?php else: ?>
                                        <i class="fa-solid fa-arrow-trend-up" style="color: #16a34a;"></i>
                                        Payment
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>$<?= number_format($payment['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($payment['recipient']) ?></td>
                            <td><?= htmlspecialchars($payment['date']) ?></td>
                            <td>
                                <?= getStatusTag($payment['status']) ?>
                            </td>
                            <td>
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-primary"
                                        onclick="processPayment('<?= htmlspecialchars($payment['id']) ?>')">
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