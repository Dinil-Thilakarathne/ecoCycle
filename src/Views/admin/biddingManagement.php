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
                        $lotId = $round['lotId'] ?? '';
                        $wasteCategory = $round['wasteCategory'] ?? '';
                        $quantity = $round['quantity'] ?? '';
                        $unit = $round['unit'] ?? '';
                        $currentBid = isset($round['currentHighestBid']) ? (float) $round['currentHighestBid'] : 0;
                        $biddingCompany = $round['biddingCompany'] ?? '—';
                        $status = $round['status'] ?? 'pending';
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
                                <div style="display: flex; gap: 8px;">
                                    <?php if ($status === 'completed'): ?>
                                        <button class="icon-button approve"
                                            onclick="approveWinner('<?= htmlspecialchars($roundId) ?>')" title="Approve">
                                            <i class="fa-solid fa-user-check"></i>
                                        </button>
                                        <button class="icon-button suspend"
                                            onclick="rejectBid('<?= htmlspecialchars($roundId) ?>')" title="Reject">
                                            <i class="fa-solid fa-user-times"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="icon-button"
                                            onclick="viewBiddingDetails(this, '<?= htmlspecialchars($roundId) ?>')"
                                            title="View Details">
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
    const BIDDING_STATUS_BADGES = {
        active: '<div class="tag online">Active</div>',
        completed: '<div class="tag assigned">Completed</div>',
        awarded: '<div class="tag assigned">Awarded</div>',
        cancelled: '<div class="tag warning">Cancelled</div>'
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderStatusBadge(status) {
        const key = (status || '').toString().toLowerCase();
        if (key in BIDDING_STATUS_BADGES) {
            return BIDDING_STATUS_BADGES[key];
        }
        return '<div class="tag secondary">' + escapeHtml(status || 'Pending') + '</div>';
    }

    function formatCurrency(value) {
        const num = Number(value);
        if (Number.isNaN(num)) {
            return 'Rs 0.00';
        }
        return 'Rs ' + num.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function parseEndTime(raw) {
        if (!raw) {
            return null;
        }
        const candidate = raw instanceof Date ? raw : new Date(String(raw).replace(' ', 'T'));
        const time = candidate.getTime();
        return Number.isNaN(time) ? null : candidate;
    }

    function formatTimeRemainingText(endValue) {
        const end = parseEndTime(endValue);
        if (!end) {
            return 'N/A';
        }

        const diffSeconds = Math.floor((end.getTime() - Date.now()) / 1000);
        if (diffSeconds <= 0) {
            return 'Ended';
        }

        const hours = Math.floor(diffSeconds / 3600);
        const minutes = Math.floor((diffSeconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    }

    function createNewLot() {
        // Build modal for creating a new lot (client-side only)
        const modal = document.createElement('div');
        modal.className = 'simple-modal-backdrop';
        modal.style.cssText = 'position:fixed;left:0;top:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);z-index:1000;';

        const categories = Array.isArray(window.__WASTE_CATEGORIES) ? window.__WASTE_CATEGORIES : [];

        modal.innerHTML = `
            <div style="background:#fff;padding:1.25rem;border-radius:8px;max-width:640px;width:96%;">
                <h3 style="margin:0 0 0.75rem 0;">Create New Lot</h3>
                <form id="createLotForm">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:6px;">Lot ID</label>
                            <input name="lotId" required placeholder="LOT123" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:6px;">Waste Category</label>
                            <select name="wasteCategory" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;">
                                <option value="">Select category</option>
                                ${categories.map(c => `<option value="${c}">${c}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:6px;">Quantity</label>
                            <input type="number" name="quantity" min="100" step="100" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:6px;">Unit</label>
                            <select name="unit" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;">
                                <option value="kg">kg</option>
                                <option value="tons">tons</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:6px;">Starting Bid (Rs)</label>
                            <input type="number" name="startingBid" min="0" step="0.01" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:6px;">End Time</label>
                            <input type="datetime-local" name="endTime" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
                        <button type="button" id="createLotCancel" style="padding:8px 12px;border-radius:6px;background:#6b7280;color:#fff;border:none;">Cancel</button>
                        <button type="submit" id="createLotSubmit" style="padding:8px 12px;border-radius:6px;background:#0ea5e9;color:#fff;border:none;">Create Lot</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        function close() { modal.remove(); }

        document.getElementById('createLotCancel').addEventListener('click', close);

        // Wire category -> starting bid default using config minimums
        (function wireCategoryDefaults() {
            try {
                const form = modal.querySelector('#createLotForm');
                const categorySelect = form.querySelector('select[name="wasteCategory"]');
                const startingBidInput = form.querySelector('input[name="startingBid"]');
                if (!categorySelect || !startingBidInput) return;

                categorySelect.addEventListener('change', function () {
                    const cat = (this.value || '').toString().trim();
                    if (!cat) {
                        startingBidInput.removeAttribute('min');
                        return;
                    }
                    const minMap = window.__MINIMUM_BIDS || {};
                    const minValRaw = minMap[cat.toLowerCase()];
                    const minVal = typeof minValRaw !== 'undefined' && minValRaw !== null ? parseFloat(minValRaw) : NaN;
                    if (!isNaN(minVal)) {
                        // set visible value and enforce min
                        startingBidInput.value = Number(minVal).toFixed(2);
                        startingBidInput.setAttribute('min', String(minVal));
                    } else {
                        startingBidInput.removeAttribute('min');
                    }
                });
            } catch (err) {
                console.warn('Failed to wire category defaults', err);
            }
        })();

        document.getElementById('createLotForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            const lotId = (fd.get('lotId') || '').toString().trim();
            const wasteCategory = (fd.get('wasteCategory') || '').toString().trim();
            const quantity = parseFloat(fd.get('quantity')) || 0;
            const unit = (fd.get('unit') || '').toString().trim();
            const startingBid = parseFloat(fd.get('startingBid')) || 0;
            const endTimeRaw = fd.get('endTime');

            const errors = [];
            if (!lotId) errors.push('Lot ID is required');
            if (!wasteCategory) errors.push('Waste category is required');
            if (!(quantity > 0)) errors.push('Quantity must be greater than zero');
            if (!unit) errors.push('Unit is required');
            if (!(startingBid >= 0)) errors.push('Starting bid must be zero or more');
            if (!endTimeRaw) errors.push('End time is required');

            if (Array.isArray(window.__BIDDING_DATA) && window.__BIDDING_DATA.some(r => (r.lotId || '').toString().toLowerCase() === lotId.toLowerCase())) {
                errors.push('Lot ID already exists');
            }

            const endTime = endTimeRaw ? new Date(endTimeRaw) : null;
            if (endTime && endTime <= new Date()) {
                errors.push('End time must be in the future');
            }

            try {
                const minMap = window.__MINIMUM_BIDS || {};
                const minValRaw = minMap[(wasteCategory || '').toLowerCase()];
                const minVal = typeof minValRaw !== 'undefined' && minValRaw !== null ? parseFloat(minValRaw) : NaN;
                if (!isNaN(minVal) && startingBid < minVal) {
                    errors.push(`Starting bid must be at least Rs ${Number(minVal).toFixed(2)} for ${wasteCategory}`);
                }
            } catch (err) {
                // ignore validation errors tied to missing configuration
            }

            if (errors.length) {
                window.__createToast(errors.join('\n'), 'error', 6000);
                return;
            }

            const submitBtn = modal.querySelector('#createLotSubmit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = 'Creating...';
            }

            const payload = {
                lotId,
                wasteCategory,
                quantity,
                unit,
                startingBid,
                endTime: endTime ? endTime.toISOString() : null
            };

            fetch('/api/bidding/rounds', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
                .then(res =>
                    res
                        .json()
                        .catch(() => ({}))
                        .then(body => {
                            if (!res.ok || !body || !body.success) {
                                const message = body && body.message ? body.message : `Request failed (${res.status})`;
                                const detail = body && body.errors ? Object.values(body.errors).join('\n') : '';
                                throw new Error(detail ? `${message}\n${detail}` : message);
                            }
                            return body;
                        })
                )
                .then(data => {
                    const round = data.round || {};

                    window.__BIDDING_DATA = Array.isArray(window.__BIDDING_DATA) ? window.__BIDDING_DATA : [];
                    window.__BIDDING_DATA.unshift(round);

                    const tbody = document.querySelector('.data-table tbody');
                    if (tbody) {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', round.id);
                        tr.innerHTML = `
                            <td class="font-medium">${escapeHtml(round.lotId)}</td>
                            <td>${escapeHtml(round.wasteCategory)}</td>
                            <td>${escapeHtml(String(round.quantity))} ${escapeHtml(round.unit)}</td>
                            <td><div class="cell-with-icon">${formatCurrency(round.currentHighestBid)}</div></td>
                            <td>${escapeHtml(round.biddingCompany || '')}</td>
                            <td><div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${formatTimeRemainingText(round.endTime)}</div></td>
                            <td>${renderStatusBadge(round.status)}</td>
                            <td>
                                <div style="display:flex;gap:8px;">
                                    <button class="icon-button" onclick="viewBiddingDetails(this,'${round.id}')" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                </div>
                            </td>
                        `;
                        tbody.insertBefore(tr, tbody.firstChild);
                    }

                    window.__createToast('New lot created', 'success');
                    close();
                })
                .catch(err => {
                    console.error('Create lot error', err);
                    window.__createToast('Create lot failed: ' + err.message, 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Create Lot';
                    }
                });
        });
    }

    function approveWinner(biddingId) {
        // Fire-and-forget: call API and update UI on completion; no blocking prompts
        fetch('/api/bidding/approve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ biddingId: biddingId })
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) {
                    window.__createToast('Approve failed: ' + (data && data.error ? data.error : 'Unknown error'), 'error');
                    return;
                }

                const round = data.round || {};
                if (window.__BIDDING_DATA && Array.isArray(window.__BIDDING_DATA)) {
                    const idx = window.__BIDDING_DATA.findIndex(r => (r.id || '') === (biddingId || ''));
                    if (idx !== -1) window.__BIDDING_DATA[idx] = Object.assign({}, window.__BIDDING_DATA[idx], round);
                }

                const row = document.querySelector(`tr[data-id="${biddingId}"]`);
                if (row) {
                    const statusCell = row.querySelectorAll('td')[6];
                    if (statusCell) statusCell.innerHTML = renderStatusBadge(round.status || 'awarded');
                    const companyCell = row.querySelectorAll('td')[4];
                    if (companyCell && round.awardedCompany) companyCell.textContent = round.awardedCompany;
                    const bidCell = row.querySelectorAll('td')[3];
                    if (bidCell && round.currentHighestBid !== undefined) {
                        bidCell.innerHTML = `<div class="cell-with-icon">${formatCurrency(round.currentHighestBid)}</div>`;
                    }
                    const timerCell = row.querySelectorAll('td')[5];
                    if (timerCell) {
                        const label = formatTimeRemainingText(round.endTime);
                        timerCell.innerHTML = `<div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${label}</div>`;
                    }
                }

                window.__createToast('Bid approved', 'success');
            })
            .catch(err => {
                console.error('Approve error', err);
                window.__createToast('Approve failed: ' + err.message, 'error');
            });
    }

    function rejectBid(biddingId) {
        // Optional non-blocking rejection with silent prompt capture via a small inline modal-like UX
        // For now: call API without asking for reason; keep UX simple and non-blocking
        fetch('/api/bidding/reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ biddingId: biddingId })
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) {
                    window.__createToast('Reject failed: ' + (data && data.error ? data.error : 'Unknown error'), 'error');
                    return;
                }

                const round = data.round || {};
                if (window.__BIDDING_DATA && Array.isArray(window.__BIDDING_DATA)) {
                    const idx = window.__BIDDING_DATA.findIndex(r => (r.id || '') === (biddingId || ''));
                    if (idx !== -1) window.__BIDDING_DATA[idx] = Object.assign({}, window.__BIDDING_DATA[idx], round);
                }

                const row = document.querySelector(`tr[data-id="${biddingId}"]`);
                if (row) {
                    const statusCell = row.querySelectorAll('td')[6];
                    if (statusCell) statusCell.innerHTML = renderStatusBadge(round.status || 'cancelled');
                    const timerCell = row.querySelectorAll('td')[5];
                    if (timerCell) {
                        const label = formatTimeRemainingText(round.endTime);
                        timerCell.innerHTML = `<div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${label}</div>`;
                    }
                }

                window.__createToast('Bid rejected', 'success');
            })
            .catch(err => {
                console.error('Reject error', err);
                window.__createToast('Reject failed: ' + err.message, 'error');
            });
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
    // This can be disabled by setting HOT_RELOADER_PAGE_AUTO_REFRESH=false in the environment.
    // (function () {
    //     const envVal = (typeof window !== 'undefined' && (window.HOT_RELOADER_PAGE_AUTO_REFRESH !== undefined)) ? window.HOT_RELOADER_PAGE_AUTO_REFRESH : null;
    //     // Server-side fallback: rendered from PHP env value
    //     const serverToggle = <?= (getenv('HOT_RELOADER_PAGE_AUTO_REFRESH') === false || getenv('HOT_RELOADER_PAGE_AUTO_REFRESH') === 'false') ? 'false' : 'true' ?>;
    //     const enabled = envVal === null ? serverToggle : Boolean(envVal);

    //     if (!enabled) return;

    //     // (In a real application, you might use WebSockets or Server-Sent Events for real-time updates)
    //     setInterval(function () {
    //         // Only refresh if there are active bidding rounds
    //         const hasActiveBids = <?= $activeRoundCount > 0 ? 'true' : 'false' ?>;
    //         if (hasActiveBids) {
    //             location.reload();
    //         }
    //     }, 30000);
    // })();
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