<?php
// Centralized dummy data (amounts in Rs)
$dummy = require base_path('config/dummy.php');
$biddingRounds = $dummy['bidding_rounds'];

// Expose client-side bidding data for modal lookups
?>
<script>
    window.__BIDDING_DATA = <?php echo json_encode($biddingRounds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php

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
    <page-header title="Bidding Management" description="Monitor and manage active bidding rounds">
        <div data-header-action style="display: flex; gap: var(--space-2);">
            <button class="btn btn-primary" onclick="createNewLot()">
                <i class="fa-solid fa-box" style="margin-right: 8px;"></i>
                Create New Lot
            </button>
        </div>
    </page-header>


    <!-- Statistics Cards (data-driven using feature-card component) -->
    <?php
    $bidStatCards = [
        [
            'title' => 'Active Bidding Rounds',
            'value' => count($activeRounds),
            'icon' => 'fa-solid fa-gavel',
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
            'value' => count($completedRounds),
            'icon' => 'fa-solid fa-box',
            'change' => '',
            'period' => 'Finished today',
            'negative' => false,
        ],
    ];
    ?>
    <div class="stats-grid">
        <?php foreach ($bidStatCards as $card): ?>
            <feature-card unwrap title="<?= htmlspecialchars($card['title']) ?>"
                value="<?= htmlspecialchars($card['value']) ?>" icon="<?= htmlspecialchars($card['icon']) ?>"
                period="<?= htmlspecialchars($card['period']) ?>" <?php if (strlen(trim($card['change']))): ?>change="<?= htmlspecialchars($card['change']) ?>" <?php endif; ?>     <?php if ($card['negative']): ?>change-negative<?php endif; ?>></feature-card>
        <?php endforeach; ?>
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
                            <tr data-id="<?= htmlspecialchars($round['id']) ?>">
                                <td class="font-medium"><?= htmlspecialchars($round['lotId']) ?></td>
                                <td><?= htmlspecialchars($round['wasteCategory']) ?></td>
                                <td>
                                    <?= htmlspecialchars($round['quantity']) ?>     <?= htmlspecialchars($round['unit']) ?>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        Rs <?= htmlspecialchars(number_format($round['currentHighestBid'], 2)) ?>
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
                                            <button class="icon-button approve" onclick="approveWinner('<?= $round['id'] ?>')"
                                                title="Approve">
                                                <i class="fa-solid fa-user-check"></i>
                                            </button>
                                            <button class="icon-button suspend" onclick="rejectBid('<?= $round['id'] ?>')"
                                                title="Reject">
                                                <i class="fa-solid fa-user-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="icon-button"
                                                onclick="viewBiddingDetails(this, '<?= $round['id'] ?>')" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
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
        // legacy signature: viewBiddingDetails(biddingId)
        if (arguments.length === 1) {
            biddingId = arguments[0];
            // try to find a row element
            const row = document.querySelector(`tr[data-id="${biddingId}"]`);
            openBiddingModal(row, biddingId);
            return;
        }

        // new signature: viewBiddingDetails(el, biddingId)
        const el = arguments[0];
        biddingId = arguments[1];
        const row = el && el.closest ? el.closest('tr') : document.querySelector(`tr[data-id="${biddingId}"]`);
        openBiddingModal(row, biddingId);
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

<!-- Bidding Details Modal -->
<div id="bidding-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close">&times;</button>
        <h3>Bidding Round Details</h3>
        <div class="user-modal__grid" id="bidding-detail-grid">
            <div><strong>Lot ID</strong></div>
            <div class="bd-lotid"></div>
            <div><strong>Waste Category</strong></div>
            <div class="bd-category"></div>
            <div><strong>Quantity</strong></div>
            <div class="bd-quantity"></div>
            <div><strong>Current Highest Bid</strong></div>
            <div class="bd-currentbid"></div>
            <div><strong>Leading Company</strong></div>
            <div class="bd-company"></div>
            <div><strong>Time Remaining</strong></div>
            <div class="bd-timer"></div>
            <div><strong>Status</strong></div>
            <div class="bd-status"></div>
            <div><strong>Notes</strong></div>
            <div class="bd-notes"></div>
        </div>
    </div>
</div>

<script>
    function openBiddingModal(row, biddingId) {
        // lookup in-memory first
        let record = null;
        try {
            if (window.__BIDDING_DATA && Array.isArray(window.__BIDDING_DATA)) {
                record = window.__BIDDING_DATA.find(r => (r.id || '').toString() === (biddingId || '').toString()) || null;
            }
        } catch (e) {
            console.warn('bidding lookup failed', e);
            record = null;
        }

        // fallback to reading table cells
        if (!record && row) {
            const cells = row.querySelectorAll('td');
            record = {
                id: biddingId,
                lotId: (cells[0] && cells[0].textContent.trim()) || '',
                wasteCategory: (cells[1] && cells[1].textContent.trim()) || '',
                quantity: (cells[2] && cells[2].textContent.trim()) || '',
                currentHighestBid: (cells[3] && cells[3].textContent.trim()) || '',
                biddingCompany: (cells[4] && cells[4].textContent.trim()) || '',
                timeRemaining: (cells[5] && cells[5].textContent.trim()) || '',
                status: (cells[6] && cells[6].textContent.trim()) || ''
            };
        }

        const modal = document.getElementById('bidding-detail-modal');
        if (!modal) return;

        // Do not open if we couldn't resolve a record or row
        if (!record) return;

        const setText = (sel, txt) => {
            const el = modal.querySelector(sel);
            if (!el) return;
            if (!txt || String(txt).trim() === '') {
                const lbl = el.previousElementSibling;
                if (lbl) lbl.style.display = 'none';
                el.style.display = 'none';
            } else {
                const lbl = el.previousElementSibling;
                if (lbl) lbl.style.display = '';
                el.style.display = '';
                el.textContent = String(txt).trim();
            }
        };

        setText('.bd-lotid', record.lotId || record.lotId === 0 ? record.lotId : '');
        setText('.bd-category', record.wasteCategory || '');
        setText('.bd-quantity', record.quantity || '');
        setText('.bd-currentbid', record.currentHighestBid ? ('Rs ' + parseFloat(record.currentHighestBid).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : '');
        setText('.bd-company', record.biddingCompany || '');
        setText('.bd-timer', record.timeRemaining || '');
        setText('.bd-status', record.status || '');
        setText('.bd-notes', record.notes || '');

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    // Close modal (reuse existing delegated handler pattern from users.php if present)
    document.addEventListener('click', function (e) {
        const modal = document.getElementById('bidding-detail-modal');
        if (!modal) return;
        if (e.target.matches('#bidding-detail-modal .close') || e.target.matches('#bidding-detail-modal')) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }
    });
</script>