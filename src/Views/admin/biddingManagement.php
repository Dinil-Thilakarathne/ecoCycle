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

    function resolveReservePrice(round) {
        if (!round) {
            return null;
        }

        const directReserve = round.reservePrice ?? round.reserve_price;
        const reserveNumber = Number(directReserve);
        if (Number.isFinite(reserveNumber) && reserveNumber > 0) {
            return reserveNumber;
        }

        const starting = Number(round.startingBid ?? round.starting_bid);
        const quantity = Number(round.quantity);

        if (Number.isFinite(starting) && Number.isFinite(quantity) && quantity > 0 && starting > 0) {
            return starting * quantity;
        }

        return null;
    }

    function getDisplayBidValue(round) {
        if (!round) {
            return 0;
        }

        const highest = Number(round.currentHighestBid ?? round.current_highest_bid);
        if (Number.isFinite(highest) && highest > 0) {
            return highest;
        }

        const reserve = resolveReservePrice(round);
        return reserve !== null ? reserve : 0;
    }

    // Render a complete table row HTML for a bidding round (used when inserting a new row)
    function renderBiddingRow(round) {
        const lotId = escapeHtml(round.lotId || round.id || '');
        const wasteCategory = escapeHtml(round.wasteCategory || '');
        const quantity = escapeHtml(String(round.quantity || '')) + ' ' + escapeHtml(round.unit || '');
        const currentBid = formatCurrency(getDisplayBidValue(round));
        const biddingCompany = escapeHtml(round.biddingCompany || '—');
        const timeRemaining = formatTimeRemainingText(round.endTime);
        const status = renderStatusBadge(round.status || 'pending');

        // Determine if edit/delete should be shown (no leading company and no bids)
        const hasLeadingCompany = !!(round.leadingCompanyId || (round.biddingCompany && String(round.biddingCompany).trim() !== '' && round.biddingCompany !== '—'));
        const startingBid = Number(round.startingBid || 0);
        const currentHighest = Number(round.currentHighestBid || 0);
        const hasBids = currentHighest > startingBid;

        let actionsHtml = '<div class="action-buttons">';
        actionsHtml += `<button class="icon-button" onclick="viewBiddingDetails(this,'${escapeHtml(round.id)}')" title="View Details"><i class="fa-solid fa-eye"></i></button>`;

        if ((round.status || '').toLowerCase() === 'completed') {
            // Completed rounds: only allow viewing details. Approval/rejection is not required here.
            // Keep a single View button so admins can inspect the round.
            // (Any award/settlement actions should be handled from a dedicated workflow if needed.)
            // Note: this mirrors the server-rendered behavior above.
            // No approve/reject buttons added.
        } else {
            if (!hasLeadingCompany && !hasBids && (round.status || '').toLowerCase() === 'active') {
                actionsHtml += `<button class="icon-button" onclick="editBiddingRound('${escapeHtml(round.id)}')" title="Edit Bid Round"><i class="fa-solid fa-edit"></i></button>`;
                actionsHtml += `<button class="icon-button danger" onclick="cancelBiddingRound('${escapeHtml(round.id)}')" title="Cancel Bid Round"><i class="fa-solid fa-trash"></i></button>`;
            }
        }

        actionsHtml += '</div>';

        return `
            <td class="font-medium">${lotId}</td>
            <td>${wasteCategory}</td>
            <td>${quantity}</td>
            <td><div class="cell-with-icon">${currentBid}</div></td>
            <td>${biddingCompany}</td>
            <td><div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${timeRemaining}</div></td>
            <td>${status}</td>
            <td>${actionsHtml}</td>
        `;
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
                        <!-- Lot ID is generated by the system and not user-editable -->
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
                            <input type="datetime-local" name="endTime" min="${new Date().toISOString().slice(0, 16)}" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
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
            const wasteCategory = (fd.get('wasteCategory') || '').toString().trim();
            const quantity = parseFloat(fd.get('quantity')) || 0;
            const unit = (fd.get('unit') || '').toString().trim();
            const startingBid = parseFloat(fd.get('startingBid')) || 0;
            const endTimeRaw = fd.get('endTime');

            const errors = [];
            if (!wasteCategory) errors.push('Waste category is required');
            if (!(quantity > 0)) errors.push('Quantity must be greater than zero');
            if (!unit) errors.push('Unit is required');
            if (!(startingBid >= 0)) errors.push('Starting bid must be zero or more');
            if (!endTimeRaw) errors.push('End time is required');

            // Lot ID uniqueness will be enforced server-side; client doesn't provide it

            const endTimeDate = endTimeRaw ? new Date(endTimeRaw) : null;
            if (endTimeRaw && (!endTimeDate || Number.isNaN(endTimeDate.getTime()))) {
                errors.push('End time is invalid');
            } else if (endTimeDate && endTimeDate <= new Date()) {
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

            const normalizedEndTime = toSqlDateTimeLocal(endTimeRaw);

            const payload = {
                // lotId intentionally omitted; server will generate
                wasteCategory,
                quantity,
                unit,
                startingBid,
                endTime: normalizedEndTime
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
                    const created = data.round || {};

                    // Ensure we have the canonical round data from the server
                    fetchBiddingRound(created.id).then((round) => {
                        if (!round) {
                            // fallback: use created object
                            round = created;
                        }

                        window.__BIDDING_DATA = Array.isArray(window.__BIDDING_DATA) ? window.__BIDDING_DATA : [];
                        // add to the beginning of cache
                        window.__BIDDING_DATA.unshift(round);

                        const tbody = document.querySelector('.data-table tbody');
                        if (tbody) {
                            const tr = document.createElement('tr');
                            tr.setAttribute('data-id', round.id);
                            tr.innerHTML = renderBiddingRow(round);
                            tbody.insertBefore(tr, tbody.firstChild);
                        }

                        window.__createToast('New lot created', 'success');
                        close();
                    }).catch(err => {
                        console.warn('Failed to fetch created round details', err);
                        // fallback insertion with minimal data
                        const round = created;
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
                                    <td><div class="cell-with-icon">${formatCurrency(getDisplayBidValue(round))}</div></td>
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

                        window.__createToast('New lot created (partial)', 'success');
                        close();
                    });
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
                    if (bidCell) {
                        bidCell.innerHTML = `<div class="cell-with-icon">${formatCurrency(getDisplayBidValue(round))}</div>`;
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

    function showToast(message, type = 'info') {
        if (typeof window.__createToast === 'function') {
            window.__createToast(message, type, 5000);
        } else {
            const prefix = type === 'error' ? 'Error: ' : '';
            alert(prefix + message);
        }
    }

    function createModal({ title, content, buttons = [], width = '520px' }) {

        const dateField = document.getElementById('end_date');
        if (dateField) {
            const minDate = new Date();
            minDate.setDate(minDate.getDate() + 1);
            dateField.min = minDate.toISOString().split('T')[0];
        }

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

    async function apiRequest(url, options = {}) {
        const opts = Object.assign({ headers: {} }, options);
        if (opts.body && !(opts.body instanceof FormData) && typeof opts.body === 'object') {
            opts.headers['Content-Type'] = opts.headers['Content-Type'] || 'application/json';
            opts.body = JSON.stringify(opts.body);
        }

        const response = await fetch(url, opts);
        let payload = null;

        try {
            payload = await response.json();
        } catch (err) {
            payload = null;
        }

        if (!response.ok || (payload && payload.success === false)) {
            const message = payload && payload.message ? payload.message : `Request failed (${response.status})`;
            let detail = '';
            if (payload && payload.errors) {
                detail = Object.values(payload.errors).join('\n');
            }
            throw new Error(detail ? `${message}\n${detail}` : message);
        }

        return payload || {};
    }

    function syncBiddingCache(round) {
        if (!Array.isArray(window.__BIDDING_DATA)) {
            window.__BIDDING_DATA = [];
        }
        const id = String(round.id);
        const index = window.__BIDDING_DATA.findIndex((item) => String(item.id) === id);
        if (index >= 0) {
            window.__BIDDING_DATA[index] = round;
        } else {
            window.__BIDDING_DATA.push(round);
        }
    }

    async function fetchBiddingRound(roundId) {
        try {
            const response = await apiRequest(`/api/bidding/rounds/${roundId}`);
            const round = response.round;
            if (round) {
                syncBiddingCache(round);
            }
            return round;
        } catch (error) {
            console.error('Failed to fetch bidding round:', error);
            return null;
        }
    }

    function formatDateTimeForInput(value) {
        const date = parseEndTime(value);
        if (!date) return '';

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function toSqlDateTimeLocal(value) {
        if (!value) return null;

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return null;
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    // Build edit-only form which allows editing only quantity, startingBid and endTime
    function buildBiddingForm(initialValues = {}) {

        const dateField = document.getElementById('end_date');
        if (dateField) {
            const minDate = new Date();
            minDate.setDate(minDate.getDate() + 1);
            dateField.min = minDate.toISOString().split('T')[0];
        }

        const defaults = {
            quantity: '',
            startingBid: '',
            endTime: '',
        };

        const values = Object.assign({}, defaults, initialValues);

        const form = document.createElement('form');
        form.innerHTML = `
            <div style="display:grid;gap:1rem;">
                    <!-- Hidden fields to ensure lotId and wasteCategory are preserved when editing -->
                    <input type="hidden" name="lotId" value="${escapeHtml(values.lotId ?? '')}" />
                    <input type="hidden" name="wasteCategory" value="${escapeHtml(values.wasteCategory ?? '')}" />
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Quantity</label>
                    <input type="number" name="quantity" min="1" step="1" required
                        value="${escapeHtml(values.quantity ?? '')}"
                        style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                </div>
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Starting Bid (Rs)</label>
                    <input type="number" name="startingBid" min="0" step="0.01" required
                        value="${escapeHtml(values.startingBid ?? '')}"
                        style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                </div>
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">End Time</label>
                    <input type="datetime-local" name="endTime" required id="end_date" min="${new Date().toISOString().slice(0, 16)}"
                        value="${escapeHtml(formatDateTimeForInput(values.endTime))}"
                        style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                </div>
            </div>
        `;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });

        return form;
    }

    function extractBiddingFormData(form) {
        const formData = new FormData(form);

        // Primary source: form inputs
        let lotId = (formData.get('lotId') || '').toString().trim();
        let wasteCategory = (formData.get('wasteCategory') || '').toString().trim();
        const endTimeRaw = formData.get('endTime');

        // Fallback: try to locate the row containing this form (if the form was built from a row)
        if ((!lotId || !wasteCategory) && form && form.closest) {
            const row = form.closest('tr');
            if (row) {
                try {
                    const cells = row.querySelectorAll('td');
                    if (!lotId && cells[0]) {
                        lotId = (cells[0].textContent || '').toString().trim();
                    }
                    if (!wasteCategory && cells[1]) {
                        wasteCategory = (cells[1].textContent || '').toString().trim();
                    }
                } catch (e) {
                    // ignore DOM read errors and continue with whatever we have
                }
            }
        }

        return {
            lotId: lotId,
            wasteCategory: wasteCategory,
            quantity: Number(formData.get('quantity')),
            unit: (formData.get('unit') || 'kg').toString(),
            startingBid: Number(formData.get('startingBid')),
            endTime: endTimeRaw ? toSqlDateTimeLocal(endTimeRaw) : null,
        };
    }

    function updateBiddingRow(round) {
        const row = document.querySelector(`tr[data-id="${round.id}"]`);
        if (!row) {
            return;
        }

        const cells = row.querySelectorAll('td');
        if (cells[0]) cells[0].textContent = round.lotId || '';
        if (cells[1]) cells[1].textContent = round.wasteCategory || '';
        if (cells[2]) cells[2].textContent = `${round.quantity || ''} ${round.unit || ''}`;
        if (cells[3]) cells[3].innerHTML = `<div class="cell-with-icon">${formatCurrency(getDisplayBidValue(round))}</div>`;
        if (cells[4]) cells[4].textContent = round.biddingCompany || '—';
        if (cells[5]) cells[5].innerHTML = `<div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${formatTimeRemainingText(round.endTime)}</div>`;
        if (cells[6]) cells[6].innerHTML = renderStatusBadge(round.status);
    }

    function removeBiddingRow(roundId) {
        const row = document.querySelector(`tr[data-id="${roundId}"]`);
        if (!row) {
            return;
        }

        // Add fade-out animation
        row.style.transition = 'opacity 0.3s ease-out';
        row.style.opacity = '0';

        setTimeout(() => {
            row.remove();

            // Check if table is now empty and show empty message
            const tbody = document.querySelector('.data-table tbody');
            if (tbody && tbody.querySelectorAll('tr:not([data-empty])').length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.setAttribute('data-empty', 'true');
                emptyRow.innerHTML = `
                    <td colspan="8" style="text-align: center; padding: var(--space-16); color: var(--neutral-500);">
                        No bidding rounds found.
                    </td>
                `;
                tbody.appendChild(emptyRow);
            }
        }, 300);
    }

    async function editBiddingRound(roundId) {
        try {
            const round = await fetchBiddingRound(roundId);
            if (!round) {
                showToast('Bidding round not found.', 'error');
                return;
            }

            if (round.status && round.status.toLowerCase() !== 'active') {
                showToast('Only active bidding rounds can be edited.', 'warning');
                return;
            }

            const form = buildBiddingForm({
                lotId: round.lotId,
                wasteCategory: round.wasteCategory,
                quantity: round.quantity,
                unit: round.unit,
                startingBid: round.startingBid || round.currentHighestBid,
                endTime: round.endTime,
            });

            createModal({
                title: `Edit Bidding Round - ${escapeHtml(round.lotId || round.id)}`,
                content: form,
                buttons: [
                    { label: 'Cancel', variant: 'secondary', onClick: (close) => close() },
                    {
                        label: 'Save Changes',
                        variant: 'primary',
                        onClick: async (close) => {
                            try {
                                const endTimeField = form.querySelector('input[name="endTime"]');
                                const rawEndTimeValue = endTimeField ? endTimeField.value : '';
                                const payload = extractBiddingFormData(form);
                                console.log('Payload for update:', payload);

                                // Only quantity, startingBid and endTime are editable here

                                if (!Number.isFinite(payload.quantity) || payload.quantity <= 0) {
                                    showToast('Quantity must be a positive number.', 'error');
                                    return;
                                }

                                if (!Number.isFinite(payload.startingBid) || payload.startingBid < 0) {
                                    showToast('Starting bid must be zero or more.', 'error');
                                    return;
                                }

                                if (!payload.endTime || !rawEndTimeValue) {
                                    showToast('End time is required.', 'error');
                                    return;
                                }

                                const endTime = new Date(rawEndTimeValue);
                                if (Number.isNaN(endTime.getTime()) || endTime <= new Date()) {
                                    showToast('End time must be in the future.', 'error');
                                    return;
                                }

                                const response = await apiRequest(`/api/bidding/rounds/${roundId}`, {
                                    method: 'PUT',
                                    body: payload
                                });

                                const updatedRound = response.round;
                                syncBiddingCache(updatedRound);
                                updateBiddingRow(updatedRound);

                                showToast('Bidding round updated successfully.', 'success');
                                close();
                            } catch (error) {
                                showToast(error.message, 'error');
                            }
                        }
                    }
                ],
                width: '580px'
            });
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function cancelBiddingRound(roundId) {
        try {
            const round = await fetchBiddingRound(roundId);
            if (!round) {
                showToast('Bidding round not found.', 'error');
                return;
            }

            if (round.status && round.status.toLowerCase() === 'cancelled') {
                showToast('Bidding round is already cancelled.', 'info');
                return;
            }

            if (round.status && round.status.toLowerCase() !== 'active') {
                showToast('Only active bidding rounds can be cancelled.', 'warning');
                return;
            }

            // Check if there's a leading company (bids have been placed)
            const hasLeadingCompany = round.biddingCompany &&
                round.biddingCompany !== '—' &&
                round.biddingCompany.trim() !== '';

            // Check if the current bid is higher than starting bid (alternative check)
            const startingBid = parseFloat(round.startingBid || 0);
            const currentBid = parseFloat(round.currentHighestBid || 0);
            const hasBids = currentBid > startingBid;

            if (hasLeadingCompany || hasBids) {
                showToast('Cannot cancel bidding round: Companies have already placed bids. Only rounds without bids can be cancelled.', 'error');
                return;
            }

            const label = escapeHtml(round.lotId || `Round #${round.id}`);
            const container = document.createElement('div');
            container.innerHTML = `
                <p style="margin:0 0 0.75rem 0;line-height:1.5;color:#374151;">
                    Are you sure you want to cancel bidding round <strong>${label}</strong>?
                </p>
                <p style="margin:0 0 0.75rem 0;color:#6b7280;font-size:0.9rem;">
                    This will immediately end the bidding process and mark the round as cancelled. The round will be removed from the active list but kept in the database for records.
                </p>
                <p style="margin:0;color:#dc2626;font-size:0.85rem;font-weight:600;">
                    ⚠️ This action cannot be undone.
                </p>
            `;

            createModal({
                title: 'Cancel Bidding Round',
                content: container,
                buttons: [
                    { label: 'Keep Active', variant: 'secondary', onClick: (close) => close() },
                    {
                        label: 'Cancel Round',
                        variant: 'danger',
                        onClick: async (close) => {
                            try {
                                const response = await apiRequest(`/api/bidding/rounds/${roundId}`, {
                                    method: 'DELETE',
                                    body: {
                                        reason: 'Cancelled by administrator'
                                    }
                                });

                                const updatedRound = response.round || Object.assign({}, round, { status: 'cancelled' });
                                syncBiddingCache(updatedRound);

                                // Remove the row from the table instead of updating it
                                removeBiddingRow(roundId);

                                showToast('Bidding round cancelled and removed from active list.', 'success');
                                close();
                            } catch (error) {
                                showToast(error.message, 'error');
                            }
                        }
                    }
                ],
                width: '480px'
            });
        } catch (error) {
            showToast(error.message, 'error');
        }
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