<?php
$biddingRounds = $biddingRounds ?? [];
consoleLog('Bidding Rounds:', $biddingRounds);
$biddingRounds = is_array($biddingRounds) ? $biddingRounds : [];
$bidStats = $bidStats ?? [];
$wasteCategories = $wasteCategories ?? [];
$wasteCategories = array_values(array_filter(is_array($wasteCategories) ? $wasteCategories : []));
$minimumBids = $minimumBids ?? [];
?>
<script>
    window.__BIDDING_DATA = <?php echo json_encode($biddingRounds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    window.__WASTE_CATEGORIES = <?php echo json_encode($wasteCategories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    window.__MINIMUM_BIDS = <?php echo json_encode($minimumBids, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php

// Helper functions
function getStatusBadge($status)
{
    $status = strtolower((string) $status);

    switch ($status) {
        case 'active':
            return '<div class="tag online">Active</div>';
        case 'completed':
            return '<div class="tag assigned">Completed</div>';
        case 'awarded':
            return '<div class="tag assigned">Awarded</div>';
        case 'cancelled':
            return '<div class="tag warning">Cancelled</div>';
        default:
            $label = $status ? ucfirst($status) : 'Pending';
            return '<div class="tag secondary">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

function formatTimeRemaining($endTime)
{
    if (!$endTime) {
        return 'N/A';
    }

    if ($endTime instanceof DateTimeInterface) {
        $timestamp = $endTime->getTimestamp();
    } elseif (is_numeric($endTime)) {
        $timestamp = (int) $endTime;
    } else {
        $timestamp = strtotime((string) $endTime);
    }

    if (!$timestamp) {
        return 'N/A';
    }

    $diffSeconds = $timestamp - time();
    if ($diffSeconds <= 0) {
        return 'Ended';
    }

    $hours = (int) floor($diffSeconds / 3600);
    $minutes = (int) floor(($diffSeconds % 3600) / 60);

    return sprintf('%dh %dm', $hours, $minutes);
}

$activeRoundCount = 0;
$completedRoundCount = 0;
$totalBidValue = 0.0;
$winningBidSum = 0.0;
$winningBidCount = 0;

foreach ($biddingRounds as $round) {
    $status = strtolower((string) ($round['status'] ?? ''));

    if ($status === 'active') {
        $activeRoundCount++;
    }

    if (in_array($status, ['completed', 'awarded'], true)) {
        $completedRoundCount++;
    }

    if (isset($round['currentHighestBid'])) {
        $bidValue = (float) $round['currentHighestBid'];
        $totalBidValue += $bidValue;

        if (in_array($status, ['completed', 'awarded'], true)) {
            $winningBidSum += $bidValue;
            $winningBidCount++;
        }
    }
}

$avgWinningBid = $winningBidCount > 0 ? $winningBidSum / $winningBidCount : 0.0;

$bidStatCards = [
    [
        'title' => 'Active Rounds',
        'value' => number_format($activeRoundCount),
        'icon' => 'fa-solid fa-bolt',
        'change' => '',
        'period' => 'Currently running',
        'negative' => false,
    ],
    [
        'title' => 'Total Bid Value',
        'value' => 'Rs ' . number_format($totalBidValue, 2),
        'icon' => 'fa-solid fa-dollar-sign',
        'change' => '',
        'period' => 'Aggregate of highest bids',
        'negative' => false,
    ],
    [
        'title' => 'Completed Rounds',
        'value' => number_format($completedRoundCount),
        'icon' => 'fa-solid fa-box',
        'change' => '',
        'period' => 'Finished today',
        'negative' => false,
    ],
    [
        'title' => 'Avg. Winning Bid',
        'value' => 'Rs ' . number_format($avgWinningBid, 2),
        'icon' => 'fa-solid fa-chart-line',
        'change' => '',
        'period' => 'Across completed rounds',
        'negative' => false,
    ],
];
?>
<div class="stats-grid">
    <?php foreach ($bidStatCards as $card): ?>
        <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>" value="<?= htmlspecialchars($card['value']) ?>"
            icon="<?= htmlspecialchars($card['icon']) ?>" period="<?= htmlspecialchars($card['period']) ?>" <?php if (strlen(trim($card['change']))): ?>change="<?= htmlspecialchars($card['change']) ?>" <?php endif; ?>     <?php if ($card['negative']): ?>change-negative<?php endif; ?>></feature-card>
    <?php endforeach; ?>
</div>

<!-- Bidding Rounds Table -->
<div class="activity-card">
    <div class="activity-card__header" style="display: flex; justify-content: space-between;">
        <div>
            <h3 class="activity-card__title">
                <i class="fa-solid fa-gavel" style="margin-right: 8px;"></i>
                Active Bidding Rounds
            </h3>
            <p class="activity-card__description">Current bidding rounds and their status</p>
        </div>
        <div class="activity-card__actions">
            <button type="button" onclick="createNewLot()" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span>Add New Lot</span>
            </button>
        </div>
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
                        <?php
                        $roundId = $round['id'] ?? '';
                        $status = $round['status'] ?? 'pending';

                        // Skip cancelled rounds - they shouldn't be displayed in the table
                        if (strtolower($status) === 'cancelled') {
                            continue;
                        }

                        $lotId = $round['lotId'] ?? '';
                        $wasteCategory = $round['wasteCategory'] ?? '';
                        $quantity = $round['quantity'] ?? '';
                        $unit = $round['unit'] ?? '';
                        $currentBid = isset($round['currentHighestBid']) ? (float) $round['currentHighestBid'] : 0;
                        if ($currentBid <= 0) {
                            if (isset($round['reservePrice']) && $round['reservePrice'] > 0) {
                                $currentBid = (float) $round['reservePrice'];
                            } elseif (isset($round['startingBid'], $round['quantity'])) {
                                $currentBid = (float) $round['startingBid'] * (float) $round['quantity'];
                            }
                        }
                        $biddingCompany = $round['biddingCompany'] ?? '—';
                        $endTime = $round['endTime'] ?? null;
                        ?>
                        <tr data-id="<?= htmlspecialchars($roundId) ?>">
                            <td class="font-medium"><?= htmlspecialchars($lotId) ?></td>
                            <td><?= htmlspecialchars($wasteCategory) ?></td>
                            <td>
                                <?= htmlspecialchars($quantity) ?>     <?= htmlspecialchars($unit) ?>
                            </td>
                            <td>
                                <div class="cell-with-icon">
                                    Rs <?= htmlspecialchars(number_format($currentBid, 2)) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($biddingCompany) ?></td>
                            <td>
                                <div class="cell-with-icon">
                                    <i class="fa-solid fa-clock"></i>
                                    <?= htmlspecialchars(formatTimeRemaining($endTime)) ?>
                                </div>
                            </td>
                            <td><?= getStatusBadge($status) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($status === 'completed'): ?>
                                        <button class="icon-button"
                                            onclick="viewBiddingDetails(this, '<?= htmlspecialchars($roundId) ?>')"
                                            title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="icon-button"
                                            onclick="viewBiddingDetails(this, '<?= htmlspecialchars($roundId) ?>')"
                                            title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <?php if ($status === 'active'): ?>
                                            <?php
                                            // Only allow edit/delete when there is no leading company and no bids above starting bid
                                            $hasLeadingCompany = !empty($biddingCompany) && $biddingCompany !== '—';
                                            $startingBid = isset($round['startingBid']) ? (float) $round['startingBid'] : 0;
                                            $hasBids = $currentBid > $startingBid;

                                            // Edit should only be available when there are no bids/leading company
                                            if (!$hasLeadingCompany && !$hasBids): ?>
                                                <button class="icon-button"
                                                    onclick="editBiddingRound('<?= htmlspecialchars($roundId) ?>')"
                                                    title="Edit Bid Round">
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>

                                                <button class="icon-button danger"
                                                    onclick="cancelBiddingRound('<?= htmlspecialchars($roundId) ?>')"
                                                    title="Cancel Bid Round">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
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

<script src="/js/admin/bidding.js"></script>

<!-- Note: Bidding Details Modal is now handled generically by ModalManager in bidding.js -->