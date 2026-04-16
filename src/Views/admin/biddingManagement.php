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
function getStatusBadge($status, $endTime = null)
{
    $status = strtolower((string) $status);

    // Safety-net: if status is still 'active' but end_time already passed, show Completed
    if ($status === 'active' && $endTime) {
        $ts = is_numeric($endTime) ? (int) $endTime : strtotime((string) $endTime);
        if ($ts && $ts <= time()) {
            $status = 'completed';
        }
    }

    switch ($status) {
        case 'active':
            return '<div class="tag online">Active</div>';
        case 'completed':
            return '<div class="tag assigned">Completed</div>';
        case 'awarded':
            return '<div class="tag assigned">Awarded</div>';

        case 'collected':
        case 'handed_over':
            return '<div class="tag assigned" style="background-color: var(--success-100); color: var(--success-700);">Collected</div>';
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
        'period' => 'Aggregate of current highest bids',
        'negative' => false,
    ],
    [
        'title' => 'Completed Rounds',
        'value' => number_format($completedRoundCount),
        'icon' => 'fa-solid fa-box',
        'change' => '',
        'period' => 'Finished today',
        'negative' => false,
    ]
];
?>
<!-- Page Header -->
<div class="page-header" style="margin-bottom: var(--space-6);">
    <div class="page-header__content">
        <h2 class="page-header__title">Bidding Management</h2>
        <p class="page-header__description">Manage waste auctions, monitor active bids, and review history</p>
    </div>
</div>

<div class="stats-grid">
    <?php foreach ($bidStatCards as $card): ?>
        <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>" value="<?= htmlspecialchars($card['value']) ?>"
            icon="<?= htmlspecialchars($card['icon']) ?>" period="<?= htmlspecialchars($card['period']) ?>" <?php if (strlen(trim($card['change']))): ?>change="<?= htmlspecialchars($card['change']) ?>" <?php endif; ?>     <?php if ($card['negative']): ?>change-negative<?php endif; ?>></feature-card>
    <?php endforeach; ?>
</div>

<!-- Active Bidding Rounds Section -->
<div class="activity-card" style="margin-bottom: var(--space-24);">
    <div class="activity-card__header" style="display: flex; justify-content: space-between;">
        <div>
            <h3 class="activity-card__title">
                <i class="fa-solid fa-gavel" style="margin-right: 8px;"></i>
                Active Bidding Rounds
            </h3>
            <p class="activity-card__description">Current ongoing auctions</p>
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
                        <th>Starting Bid</th>
                        <th>Current Highest</th>
                        <th>Leading Company</th>
                        <th>Time Remaining</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activeRounds)): ?>
                        <?php foreach ($activeRounds as $round): ?>
                            <tr data-id="<?= htmlspecialchars($round['id']) ?>"
                                data-end-time="<?= htmlspecialchars($round['endTime']) ?>">
                                <td class="font-medium"><?= htmlspecialchars($round['lotId']) ?></td>
                                <td><?= htmlspecialchars($round['category']) ?></td>
                                <td><?= htmlspecialchars($round['quantity'] . ' ' . $round['unit']) ?></td>
                                <td>
                                    <div class="cell-with-icon">
                                        Rs <?= htmlspecialchars(number_format($round['startingBid'], 2)) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <?php if (isset($round['currentHighestBid']) && $round['currentHighestBid'] > 0): ?>
                                            Rs <?= htmlspecialchars(number_format($round['currentHighestBid'], 2)) ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($round['biddingCompany'] ?: '—') ?></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeRemaining($round['endTime'])) ?>
                                    </div>
                                </td>
                                <td><?= getStatusBadge($round['status'], $round['endTime'] ?? null) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="icon-button"
                                            onclick="viewBiddingDetails(this, '<?= htmlspecialchars($round['id']) ?>')"
                                            title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <?php if ((float) $round['currentHighestBid'] <= 0): ?>
                                            <button class="icon-button"
                                                onclick="editBiddingRound('<?= htmlspecialchars($round['id']) ?>')" title="Edit">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <button class="icon-button danger"
                                                onclick="cancelBiddingRound('<?= htmlspecialchars($round['id']) ?>')"
                                                title="Cancel">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 1.5rem; color: var(--neutral-500);">
                                No active bidding rounds at the moment.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Round History Section -->
<div class="activity-card">
    <div class="activity-card__header"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 class="activity-card__title">
                <i class="fa-solid fa-history" style="margin-right: 8px;"></i>
                Round History
            </h3>
            <p class="activity-card__description">Past bidding rounds (Completed, Awarded, Cancelled)</p>
        </div>
        <div>
            <form method="GET" action="" style="display: flex; gap: 0.5rem;">
                <input type="text" name="q" placeholder="Search Lot ID or Category..."
                    value="<?= htmlspecialchars($searchQuery ?? '') ?>" class="form-input"
                    style="padding: 0.5rem; border: 1px solid var(--neutral-300); border-radius: 4px; font-size: 0.875rem;">
                <button type="submit" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Search</button>
            </form>
        </div>
    </div>
    <div class="activity-card__content">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lot ID</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Winner / Leader</th>
                        <th>Winning Bid</th>
                        <th>Ended</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($historyRounds)): ?>
                        <?php foreach ($historyRounds as $round): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($round['lotId']) ?></td>
                                <td><?= htmlspecialchars($round['wasteCategory']) ?></td>
                                <td><?= htmlspecialchars($round['quantity'] . ' ' . $round['unit']) ?></td>
                                <td>
                                    <?php if (!empty($round['awardedCompany'])): ?>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fa-solid fa-trophy" style="color: var(--warning-500);"></i>
                                            <span><?= htmlspecialchars($round['awardedCompany']) ?></span>
                                        </div>
                                    <?php elseif (!empty($round['biddingCompany'])): ?>
                                        <?= htmlspecialchars($round['biddingCompany']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--neutral-400);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $finalBid = isset($round['winningBid']) ? $round['winningBid'] : $round['currentHighestBid'];
                                    ?>
                                    Rs <?= number_format((float) $finalBid, 2) ?>
                                </td>
                                <td><?= htmlspecialchars(date('M j, Y H:i', strtotime($round['endTime'] ?? $round['updated_at']))) ?>
                                </td>
                                <td><?= getStatusBadge($round['status']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="icon-button"
                                            onclick="viewBiddingDetails(this, '<?= htmlspecialchars($round['id']) ?>')"
                                            title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 1.5rem; color: var(--neutral-500);">
                                No history records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bid Activity Log Section -->
<div class="activity-card" style="margin-top: 2rem;">
    <div class="activity-card__header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 class="activity-card__title">
                <i class="fa-solid fa-history"></i> Bid Activity Log
            </h3>
            <p class="activity-card__description">Bid activity log</p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="roundFilter" style="font-weight: 600; margin: 0;">Filter by Round:</label>
            <select id="roundFilter"
                style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; min-width: 250px;">
                <option value="">Loading...</option>
            </select>
        </div>
    </div>
    <div class="activity-card__content">
        <div id="bidLogContainer" style="max-height: 600px; overflow-y: auto;">
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p style="margin-top: 1rem;">Loading bid history...</p>
            </div>
        </div>
    </div>
</div>
</div>

<script src="/js/admin/bidding.js"></script>
<script>
    // Initialize bid history on page load
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.loadBidHistory === 'function') {
            window.loadBidHistory();
        }

        // Live timer ticker: Update the 'Time Remaining' column every 10 seconds
        function tickTimers() {
            document.querySelectorAll('table.data-table tbody tr[data-id][data-end-time]').forEach(function (row) {
                var endTime = row.getAttribute('data-end-time');
                if (!endTime) return;

                var cells = row.querySelectorAll('td');
                if (cells.length < 8) return;
                var timeCell = cells[6];

                if (typeof window.formatTimeRemainingText === 'function') {
                    var newText = window.formatTimeRemainingText(endTime);
                    // Update content if it's different
                    var currentText = timeCell.textContent.trim();
                    if (newText !== currentText && !currentText.includes('Ended')) {
                        timeCell.innerHTML = `<div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${newText}</div>`;
                    }
                }
            });
        }

        // Live status fix: scan active rows frequently and force DB sync for ended rounds
        function refreshExpiredBadges() {
            document.querySelectorAll('table.data-table tbody tr[data-id]').forEach(async function (row) {
                var cells = row.querySelectorAll('td');
                if (cells.length < 8) return;
                var timeCell = cells[6]; // "Time Remaining"
                var statusCell = cells[7]; // "Status"
                if (!timeCell || !statusCell) return;

                var id = row.getAttribute('data-id');
                var timeText = timeCell.textContent.trim().toLowerCase();
                var badgeText = statusCell.textContent.trim().toLowerCase();

                // If UI says 'ended' but status is still 'active', force a DB update for this ID
                if (timeText.indexOf('ended') !== -1 && badgeText === 'active') {
                    statusCell.innerHTML = '<div class="tag assigned"><i class="fa fa-spinner fa-spin"></i> Syncing...</div>';

                    try {
                        const res = await fetch(`/api/bidding/${id}/expire`, { method: 'POST' });
                        const body = await res.json();

                        if (body.success) {
                            statusCell.innerHTML = '<div class="tag assigned">Completed</div>';
                            console.info(`[ecoCycle] Lot ${id} successfully expired.`);
                        } else {
                            statusCell.innerHTML = '<div class="tag online">Active</div>';
                        }
                    } catch (e) {
                        statusCell.innerHTML = '<div class="tag online">Active</div>';
                    }
                }
            });
        }

        // Run immediately on load
        tickTimers();
        refreshExpiredBadges();

        // Ticker for time text (every 10 seconds for UX)
        setInterval(tickTimers, 10000);

        // Sweeper for DB status (every 30 seconds)
        setInterval(refreshExpiredBadges, 30000);

    });


</script>

<!-- Note: Bidding Details Modal is now handled generically by ModalManager in bidding.js -->