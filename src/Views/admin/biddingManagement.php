<?php
// Sample bidding data (in a real application, this would come from your database/models)
$biddingRounds = [
    [
        'id' => 'BR001',
        'lotId' => 'LOT001',
        'wasteCategory' => 'Plastic Bottles',
        'quantity' => 500,
        'unit' => 'kg',
        'currentHighestBid' => 250,
        'biddingCompany' => 'GreenTech Co.',
        'status' => 'active',
        'endTime' => '2024-01-15 14:30:00',
    ],
    [
        'id' => 'BR002',
        'lotId' => 'LOT002',
        'wasteCategory' => 'Cardboard',
        'quantity' => 1200,
        'unit' => 'kg',
        'currentHighestBid' => 180,
        'biddingCompany' => 'EcoRecycle Ltd.',
        'status' => 'active',
        'endTime' => '2024-01-15 16:00:00',
    ],
    [
        'id' => 'BR003',
        'lotId' => 'LOT003',
        'wasteCategory' => 'Aluminum Cans',
        'quantity' => 300,
        'unit' => 'kg',
        'currentHighestBid' => 450,
        'biddingCompany' => 'MetalWorks Inc.',
        'status' => 'completed',
        'endTime' => '2024-01-15 12:00:00',
    ],
    [
        'id' => 'BR004',
        'lotId' => 'LOT004',
        'wasteCategory' => 'Glass Bottles',
        'quantity' => 800,
        'unit' => 'kg',
        'currentHighestBid' => 320,
        'biddingCompany' => 'GlassRecyclers Inc.',
        'status' => 'active',
        'endTime' => '2024-01-15 18:45:00',
    ],
    [
        'id' => 'BR005',
        'lotId' => 'LOT005',
        'wasteCategory' => 'E-Waste',
        'quantity' => 150,
        'unit' => 'kg',
        'currentHighestBid' => 600,
        'biddingCompany' => 'TechRecycle Solutions',
        'status' => 'completed',
        'endTime' => '2024-01-15 10:00:00',
    ],
];

// Helper functions
function getStatusBadge($status)
{
    switch ($status) {
        case 'active':
            return '<div class="tag online">Active</div>';
        case 'completed':
            return '<div class="tag assigned">Completed</div>';
        case 'cancelled':
            return '<div class="tag warning">Cancelled</div>';
        default:
            return '<div class="tag secondary">' . htmlspecialchars($status) . '</div>';
    }
}

function formatTimeRemaining($endTime)
{
    $end = new DateTime($endTime);
    $now = new DateTime();
    $diff = $end->getTimestamp() - $now->getTimestamp();

    if ($diff <= 0)
        return 'Ended';

    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);

    return "{$hours}h {$minutes}m";
}

// Calculate statistics
$activeRounds = array_filter($biddingRounds, function ($round) {
    return $round['status'] === 'active';
});
$completedRounds = array_filter($biddingRounds, function ($round) {
    return $round['status'] === 'completed';
});
$totalBidValue = array_sum(array_column($biddingRounds, 'currentHighestBid'));
?>

<div>
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Bidding Management</h2>
            <p class="page-header__description">Monitor and manage active bidding rounds</p>
        </div>
        <button class="btn btn-primary" onclick="createNewLot()">
            <i class="fa-solid fa-box" style="margin-right: 8px;"></i>
            Create New Lot
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <!-- Active Bidding Rounds -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Active Bidding Rounds</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-gavel"></i>
                </div>
            </div>
            <p class="feature-card__body">
                <?= count($activeRounds) ?>
            </p>
        </div>

        <!-- Total Bid Value -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Total Bid Value</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>
            <p class="feature-card__body">
                $<?= number_format($totalBidValue) ?>
            </p>
        </div>

        <!-- Completed Today -->
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Completed Today</h3>
                <div class="feature-card__icon">
                    <i class="fa-solid fa-box"></i>
                </div>
            </div>
            <p class="feature-card__body">
                <?= count($completedRounds) ?>
            </p>
        </div>
    </div>

    <!-- Bidding Rounds Table -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-gavel" style="margin-right: 8px;"></i>
                Active Bidding Rounds
            </h3>
            <p class="activity-card__description">Current bidding rounds and their status</p>
        </div>
        <div class="activity-card__content">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Lot ID</th>
                            <th>Waste Category</th>
                            <th>Quantity</th>
                            <th>Current Highest Bid</th>
                            <th>Leading Company</th>
                            <th>Time Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($biddingRounds as $round): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($round['lotId']) ?></td>
                                <td><?= htmlspecialchars($round['wasteCategory']) ?></td>
                                <td>
                                    <?= htmlspecialchars($round['quantity']) ?>     <?= htmlspecialchars($round['unit']) ?>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-dollar-sign"></i>
                                        <?= htmlspecialchars($round['currentHighestBid']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($round['biddingCompany']) ?></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= formatTimeRemaining($round['endTime']) ?>
                                    </div>
                                </td>
                                <td><?= getStatusBadge($round['status']) ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($round['status'] === 'completed'): ?>
                                            <button class="btn btn-primary btn-sm outline"
                                                onclick="approveWinner('<?= $round['id'] ?>')">
                                                Approve
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="rejectBid('<?= $round['id'] ?>')">
                                                Reject
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline btn-sm"
                                                onclick="viewBiddingDetails('<?= $round['id'] ?>')">
                                                View Details
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($biddingRounds)): ?>
                            <tr>
                                <td colspan="8"
                                    style="text-align: center; padding: var(--space-16); color: var(--neutral-500);">
                                    No bidding rounds found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function createNewLot() {
        // In a real application, you would navigate to a form or open a modal
        console.log('Creating new lot...');
        alert('Create New Lot functionality - In a real application, this would open a form to create a new waste lot for bidding.');

        // You could redirect to a new lot creation page:
        // window.location.href = '/admin/lots/create';
    }

    function approveWinner(biddingId) {
        if (confirm('Are you sure you want to approve this bid winner?')) {
            console.log(`Approving winner for bidding round ${biddingId}`);
            alert(`Bid approved for ${biddingId}. In a real application, this would update the database and notify the winning company.`);

            // You would make an AJAX request here:
            /*
            fetch('/api/bidding/approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    biddingId: biddingId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to approve bid');
                }
            });
            */
        }
    }

    function rejectBid(biddingId) {
        if (confirm('Are you sure you want to reject this bid?')) {
            console.log(`Rejecting bid for bidding round ${biddingId}`);
            alert(`Bid rejected for ${biddingId}. In a real application, this would update the database and start a new bidding round.`);

            // You would make an AJAX request here:
            /*
            fetch('/api/bidding/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    biddingId: biddingId
                })
            });
            */
        }
    }

    function viewBiddingDetails(biddingId) {
        console.log(`Viewing details for bidding round ${biddingId}`);
        alert(`Viewing bidding details for ${biddingId}. In a real application, this would show detailed bidding information, bid history, and participating companies.`);

        // You could redirect to a details page:
        // window.location.href = `/admin/bidding/${biddingId}`;
    }

    // Auto-refresh the page every 30 seconds to update time remaining
    // (In a real application, you might use WebSockets or Server-Sent Events for real-time updates)
    setInterval(function () {
        // Only refresh if there are active bidding rounds
        const hasActiveBids = <?= count($activeRounds) > 0 ? 'true' : 'false' ?>;
        if (hasActiveBids) {
            location.reload();
        }
    }, 30000);
</script>