<?php
$minimumBids = $minimumBids ?? [];
$availableWasteLots = $availableWasteLots ?? [];
consoleLog('Available Waste Lots: ' . print_r($availableWasteLots, true));
$biddingHistory = $biddingHistory ?? [];
$formErrors = $formErrors ?? [];
$formSuccess = $formSuccess ?? null;
?>
<script>
    window.__COMPANY_BIDDING_STATE = {
        minimumBids: <?= json_encode($minimumBids, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        lots: <?= json_encode($availableWasteLots, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        history: <?= json_encode($biddingHistory, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        companyId: <?= isset($user['id']) ? (int) $user['id'] : 0 ?>
    };
</script>
<!-- Delete Confirm Modal (inserted so JS can find it) -->
<div id="delete-bid-modal" class="simple-modal-backdrop"
    style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2000;padding:1rem;">
    <div class="simple-modal-dialog"
        style="background:#fff;border-radius:12px;box-shadow:0 20px 45px rgba(15,23,42,0.16);width:420px;max-width:96%;padding:1.25rem;display:flex;flex-direction:column;gap:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:1.125rem;font-weight:600;color:#111827;">Cancel Bid</h3>
            <button type="button" id="delete-bid-close"
                style="border:none;background:transparent;font-size:1.25rem;line-height:1;color:#6b7280;cursor:pointer;">&times;</button>
        </div>
        <div style="margin-top:0.75rem;color:#374151;">Are you sure you want to cancel this bid? This action cannot be
            undone.</div>
        <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1rem;">
            <button type="button" id="delete-bid-cancel"
                style="padding:0.6rem 1.25rem;border-radius:6px;font-weight:600;border:none;background:#6b7280;color:#fff;">Cancel</button>
            <button type="button" id="delete-bid-confirm"
                style="padding:0.6rem 1.25rem;border-radius:6px;font-weight:600;border:none;background:#dc2626;color:#fff;">Yes,
                Cancel Bid</button>
        </div>
    </div>
</div>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Place your bid here!</h2>
            <p class="page-header__description">Submit bids for available waste lots</p>
        </div>
    </header>

    <div class="top-section">
        <!-- New Bid Form -->
        <form class="bid-form" method="post" action="">
            <h2 style="font-size: 20px; font-weight: bold;">New Bid Submission</h2>

            <!-- Show validation errors -->
            <?php if (!empty($formErrors)): ?>
                <div class="error-box" style="color:red; margin-bottom:10px;">
                    <ul>
                        <?php foreach ($formErrors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!empty($formSuccess)): ?>
                <p style="color:green; font-weight:bold;"><?= htmlspecialchars($formSuccess) ?></p>
            <?php endif; ?>

            <label>Waste Lot</label>
            <select name="lot_id" id="lot_id" required>
                <option value="">Select waste lot…</option>
                <?php foreach ($availableWasteLots as $lot): ?>
                    <option value="<?= htmlspecialchars($lot['id'] ?? '') ?>"
                        data-category="<?= htmlspecialchars(strtolower((string) ($lot['category'] ?? ''))) ?>"
                        data-quantity="<?= htmlspecialchars((string) ($lot['quantity'] ?? 0)) ?>"
                        data-unit="<?= htmlspecialchars(($lot['unit'] ?? 'kg')) ?>">
                        <?= htmlspecialchars((($lot['lotId'] ?? '') !== '' ? $lot['lotId'] : ($lot['id'] ?? 'Lot')) . ' • ' . ($lot['category'] ?? 'Unknown')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Waste Type</label>
            <div class="input-readonly" id="waste_type_display"
                style="padding:10px;border:1px dashed #d1d5db;border-radius:6px;background:#f9fafb;font-weight:600;color:#6b7280;">
                Select a lot first…
            </div>
            <input type="hidden" name="waste_type" id="waste_type_hidden" value="">

            <label>Bid Amount (per 1kg)</label>
            <input type="number" id="bid_amount" name="bid_amount" step="50" placeholder="Enter bid amount" required min="0">

            <label>Waste Amount (kg)</label>
            <input type="number" id="waste_amount" name="waste_amount" step="any" required
                placeholder="Waste amount" min="100" max="10000" readonly style="padding:10px;border:1px dashed #d1d5db;border-radius:6px;background:#f9fafb;font-weight:600;color:#6b7280;">

            <label>Total Bid Amount</label>
            <div class="input-readonly" id="bid_total_display"
                style="padding:10px;border:1px dashed #d1d5db;border-radius:6px;background:#f9fafb;font-weight:600;">
                Rs. 0.00
            </div>

            <button class="btn btn-primary outline" style="width: 100%; margin-top: 15px;" type="submit">Place
                Bid</button>
        </form>


        <div class="available-waste" style="min-height: 540px;">
            <h2 style="font-size: 20px; font-weight: bold;">Available Waste Lots</h2>

            <?php foreach ($availableWasteLots as $lot): ?>
                <div class="waste-lots" data-lot-id="<?= htmlspecialchars($lot['id'] ?? '') ?>">
                    <div class="lot-header">
                        <span class="waste-type"><?= htmlspecialchars($lot['category'] ?? 'Unknown') ?></span>
                        <span class="tag <?= strtolower($lot['status'] ?? 'available') ?>" data-role="lot-status">
                            <?= htmlspecialchars(ucfirst($lot['status'] ?? 'available')) ?>
                        </span>
                    </div>
                    <div class="lot-details">
                        <p data-role="lot-quantity"><strong>Quantity:</strong>
                            <?= htmlspecialchars(number_format($lot['quantity'] ?? 0) . ' ' . ($lot['unit'] ?? 'kg')) ?></p>
                            <!-- Show end date/time if available -->
                        <p data-role="lot-end-time"><strong>Ends:</strong>
                            <?= htmlspecialchars(!empty($lot['endTime']) ? date('Y-m-d H:i', strtotime($lot['endTime'])) : 'TBD') ?>
                        </p>

                        <?php
                        $lotCurrentBid = isset($lot['currentHighestBid']) ? (float) $lot['currentHighestBid'] : 0.0;
                        if ($lotCurrentBid <= 0) {
                            if (isset($lot['reservePrice']) && $lot['reservePrice'] > 0) {
                                $lotCurrentBid = (float) $lot['reservePrice'];
                            } elseif (isset($lot['startingBid'], $lot['quantity'])) {
                                $lotCurrentBid = (float) $lot['startingBid'] * (float) $lot['quantity'];
                            }
                        }
                        ?>

                        <p data-role="lot-current-bid"><strong>Current Bid:</strong>
                            <?= htmlspecialchars(format_rs($lotCurrentBid)) ?>
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary outline" data-action="prefill-bid"
                        data-lot-id="<?= htmlspecialchars($lot['id'] ?? '') ?>" style="width: 100%; margin-top: 8px;">Bid on
                        this lot</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bidding History -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">View Bidding History</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bid ID</th>
                    <th>Waste Type</th>
                    <th>Quantity</th>
                    <th>Bid Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update/Cancel</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($biddingHistory as $bid): ?>
                    <tr data-bid-id="<?= htmlspecialchars($bid['id'] ?? '') ?>">
                        <td><?= htmlspecialchars($bid['displayId'] ?? ('BID' . $bid['id'])) ?></td>
                        <td><?= htmlspecialchars($bid['category'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars(number_format($bid['quantity'] ?? 0) . ' ' . ($bid['unit'] ?? 'kg')) ?>
                        </td>
                        <td class="bid-amount-cell"><?= htmlspecialchars(format_rs($bid['amount'] ?? 0)) ?></td>
                        <td><span
                                class="tag <?= strtolower($bid['status'] ?? 'pending') ?>"><?= htmlspecialchars($bid['status'] ?? 'Pending') ?></span>
                        </td>
                        <td><?= htmlspecialchars($bid['createdAt'] ? date('Y-m-d', strtotime($bid['createdAt'])) : 'N/A') ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php
                                $status = $bid['status'] ?? '';
                                $roundStatus = $bid['roundStatus'] ?? '';
                                $allowActions = $status === 'Leading' || $status === 'Active' || ($status === 'Lost' && $roundStatus === 'active');
                                ?>
                                    <?php if ($allowActions): ?>
                                    <button class="icon-button" title="Edit Bid" data-action="edit-bid"
                                        data-bid-id="<?= htmlspecialchars($bid['id']) ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </button>

                                    <button class="icon-button danger" title="Cancel Bid" data-action="request-delete-bid"
                                        data-bid-id="<?= htmlspecialchars($bid['id']) ?>">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="icon-button" disabled
                                        title="Cannot edit - bid is <?= htmlspecialchars(strtolower($bid['status'] ?? 'closed')) ?>"
                                        style="opacity: 0.4; cursor: not-allowed;">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </button>

                                    <button class="icon-button danger" disabled
                                        title="Cannot cancel - bid is <?= htmlspecialchars(strtolower($bid['status'] ?? 'closed')) ?>"
                                        style="opacity: 0.4; cursor: not-allowed;">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    (function () {
        const state = window.__COMPANY_BIDDING_STATE || {};
        const minBids = state.minimumBids || {};
        const form = document.querySelector('.bid-form');
        if (!form) {
            return;
        }

        const lotSelect = form.querySelector('#lot_id');
        const wasteTypeDisplay = form.querySelector('#waste_type_display');
        const wasteTypeHidden = form.querySelector('#waste_type_hidden');
        const bidAmountInput = form.querySelector('#bid_amount');
        const wasteAmountInput = form.querySelector('input[name="waste_amount"]');
        const submitBtn = form.querySelector('button[type="submit"]');
        const totalDisplay = form.querySelector('#bid_total_display');

        function cssEscape(value) {
            if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
                return CSS.escape(value);
            }
            return String(value).replace(/[^a-zA-Z0-9_\-]/g, '\\$&');
        }

        function formatRs(value) {
            const num = Number(value);
            if (!Number.isFinite(num)) {
                return 'Rs. 0.00';
            }
            return 'Rs. ' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function resolveLotReservePrice(lot) {
            if (!lot) {
                return null;
            }
            const direct = lot.reservePrice ?? lot.reserve_price;
            const directNumber = Number(direct);
            if (Number.isFinite(directNumber) && directNumber > 0) {
                return directNumber;
            }
            const starting = Number(lot.startingBid ?? lot.starting_bid);
            const quantity = Number(lot.quantity);
            if (Number.isFinite(starting) && Number.isFinite(quantity) && starting > 0 && quantity > 0) {
                return starting * quantity;
            }
            return null;
        }

        function getLotDisplayBid(lot) {
            if (!lot) {
                return 0;
            }
            const current = Number(lot.currentHighestBid ?? lot.current_highest_bid);
            if (Number.isFinite(current) && current > 0) {
                return current;
            }
            const reserve = resolveLotReservePrice(lot);
            return reserve !== null ? reserve : 0;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function toast(message, type) {
            if (typeof window.__createToast === 'function') {
                window.__createToast(message, type || 'info', 5000);
            } else {
                alert(message);
            }
        }

        function updateMinBidFromWasteType() {
            if (!wasteTypeHidden || !bidAmountInput) {
                return;
            }
            const selected = (wasteTypeHidden.value || '').toLowerCase();
            const minVal = selected && Object.prototype.hasOwnProperty.call(minBids, selected)
                ? parseFloat(minBids[selected])
                : null;

            if (minVal !== null && !Number.isNaN(minVal)) {
                bidAmountInput.min = String(minVal);
                bidAmountInput.placeholder = 'Minimum: ' + minVal;
            } else {
                bidAmountInput.removeAttribute('min');
                bidAmountInput.placeholder = 'Enter bid amount';
            }
            updateTotalBid();
        }

        function syncWasteTypeFromLot() {
            if (!lotSelect || !wasteTypeDisplay || !wasteTypeHidden) {
                return;
            }
            const option = lotSelect.options[lotSelect.selectedIndex];
            if (!option || !option.value) {
                // No lot selected - reset waste type display
                wasteTypeDisplay.textContent = 'Select a lot first…';
                wasteTypeDisplay.style.color = '#6b7280';
                wasteTypeHidden.value = '';
                return;
            }

            // Lot selected - sync waste type
            const category = (option.dataset.category || '').toLowerCase();
            if (category) {
                // Capitalize first letter for display
                const displayCategory = category.charAt(0).toUpperCase() + category.slice(1);
                wasteTypeDisplay.textContent = displayCategory;
                wasteTypeDisplay.style.color = '#111827';
                wasteTypeHidden.value = category;
                updateMinBidFromWasteType();
            }

            // If lot option provides quantity, set default and max for waste amount
            if (option.dataset.quantity) {
                const q = parseFloat(option.dataset.quantity);
                if (Number.isFinite(q) && q > 0 && wasteAmountInput) {
                    // Set maximum to lot quantity and default value to that quantity
                    wasteAmountInput.max = String(q);
                    // Only set the default value if current value is empty or greater than max
                    const current = parseFloat(wasteAmountInput.value || '0');
                    if (!current || current <= 0 || current > q) {
                        wasteAmountInput.value = String(q);
                    }
                }
            }
        }

        if (lotSelect) {
            lotSelect.addEventListener('change', () => {
                syncWasteTypeFromLot();
                updateTotalBid();
            });
        }

        function updateTotalBid() {
            if (!totalDisplay) {
                return;
            }
            const bidVal = parseFloat(bidAmountInput ? bidAmountInput.value : '');
            const wasteVal = parseFloat(wasteAmountInput ? wasteAmountInput.value : '');
            if (Number.isFinite(bidVal) && bidVal > 0 && Number.isFinite(wasteVal) && wasteVal > 0) {
                const total = bidVal * wasteVal;
                totalDisplay.textContent = formatRs(total);
            } else {
                totalDisplay.textContent = 'Rs. 0.00';
            }
        }

        if (bidAmountInput) {
            bidAmountInput.addEventListener('input', updateTotalBid);
        }

        if (wasteAmountInput) {
            wasteAmountInput.addEventListener('input', () => {
                // Clamp to max if provided
                try {
                    const max = parseFloat(wasteAmountInput.max || '');
                    let val = parseFloat(wasteAmountInput.value || '0');
                    if (Number.isFinite(max) && max > 0 && Number.isFinite(val)) {
                        if (val > max) {
                            val = max;
                            wasteAmountInput.value = String(val);
                        }
                    }
                } catch (e) {
                    // ignore
                }
                updateTotalBid();
            });
        }

        document.querySelectorAll('[data-action="prefill-bid"]').forEach(button => {
            button.addEventListener('click', () => {
                const lotId = button.getAttribute('data-lot-id') || '';
                if (!lotId || !lotSelect) {
                    return;
                }
                lotSelect.value = lotId;
                syncWasteTypeFromLot();
                updateTotalBid();
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        function upsertLotCard(lot) {
            if (!lot || !lot.id) {
                return;
            }
            const selector = `.waste-lots[data-lot-id="${cssEscape(String(lot.id))}"]`;
            const card = document.querySelector(selector);
            if (card) {
                const bidEl = card.querySelector('[data-role="lot-current-bid"]');
                if (bidEl) {
                    bidEl.innerHTML = '<strong>Current Bid:</strong> ' + formatRs(getLotDisplayBid(lot));
                }
                const statusEl = card.querySelector('[data-role="lot-status"]');
                if (statusEl) {
                    const statusText = lot.status ? String(lot.status) : 'active';
                    statusEl.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
                    statusEl.className = 'tag ' + statusText.toLowerCase();
                }
            }
            if (lotSelect) {
                const option = lotSelect.querySelector(`option[value="${cssEscape(String(lot.id))}"]`);
                if (option) {
                    option.dataset.category = (lot.category || '').toString().toLowerCase();
                    option.textContent = `${lot.lotId || lot.id} • ${lot.category || 'Unknown'}`;
                }
            }
            if (Array.isArray(state.lots)) {
                const idx = state.lots.findIndex(item => {
                    if (!item) {
                        return false;
                    }
                    const itemId = item.id !== undefined && item.id !== null ? item.id : item.lotId;
                    return itemId == lot.id;
                });
                if (idx !== -1) {
                    state.lots[idx] = Object.assign({}, state.lots[idx], lot);
                } else {
                    state.lots.unshift(lot);
                }
            }
        }

        function renderBidRow(bid) {
            const status = bid.status || 'Pending';
            const statusClass = status.toLowerCase();
            const roundStatus = bid.roundStatus || '';
            const allowActions = status === 'Leading' || status === 'Active' || (status === 'Lost' && roundStatus === 'active');
            const quantityNumber = Number(bid.quantity);
            const quantityLabel = Number.isFinite(quantityNumber)
                ? quantityNumber.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' ' + (bid.unit || 'kg')
                : escapeHtml(bid.quantity || '');
            const createdDate = bid.createdAt ? escapeHtml(String(bid.createdAt).substring(0, 10)) : 'N/A';
            const editHref = '?action=edit&id=' + encodeURIComponent(String(bid.id));
            const deleteHref = '?action=delete&id=' + encodeURIComponent(String(bid.id));

            const actionsHtml = allowActions
                ? `<div class="action-buttons">
                    <button class="icon-button" title="Edit Bid" data-action="edit-bid" data-bid-id="${escapeHtml(String(bid.id))}">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="icon-button danger" title="Cancel Bid" data-action="request-delete-bid" data-bid-id="${escapeHtml(String(bid.id))}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>`
                : `<div class="action-buttons">
                    <button class="icon-button" disabled title="Cannot edit - bid is ${escapeHtml(status.toLowerCase())}" style="opacity: 0.4; cursor: not-allowed;">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="icon-button danger" disabled title="Cannot cancel - bid is ${escapeHtml(status.toLowerCase())}" style="opacity: 0.4; cursor: not-allowed;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>`;

            return `<tr data-bid-id="${escapeHtml(String(bid.id))}">
                <td>${escapeHtml(bid.displayId || ('BID' + String(bid.id)))}</td>
                <td>${escapeHtml(bid.category || 'Unknown')}</td>
                <td>${quantityLabel}</td>
                <td>${formatRs(bid.amount)}</td>
                <td><span class="tag ${statusClass}">${escapeHtml(status)}</span></td>
                <td>${createdDate}</td>
                <td>${actionsHtml}</td>
            </tr>`;
        }

        function upsertBidHistoryRow(bid) {
            if (!bid || !bid.id) {
                return;
            }
            const tbody = document.querySelector('.activity-card .data-table tbody');
            if (!tbody) {
                return;
            }

            const emptyRow = tbody.querySelector('tr[data-empty="true"]');
            if (emptyRow) {
                emptyRow.remove();
            }

            const existing = tbody.querySelector(`tr[data-bid-id="${cssEscape(String(bid.id))}"]`);
            const html = renderBidRow(bid);
            if (existing) {
                existing.outerHTML = html;
            } else {
                const temp = document.createElement('tbody');
                temp.innerHTML = html;
                const newRow = temp.firstElementChild;
                tbody.insertBefore(newRow, tbody.firstChild);
            }

            if (Array.isArray(state.history)) {
                const idx = state.history.findIndex(item => item.id == bid.id);
                if (idx !== -1) {
                    state.history[idx] = bid;
                } else {
                    state.history.unshift(bid);
                }
            }
        }

        function removeBidHistoryRow(bidId) {
            if (!bidId) {
                return;
            }

            const tbody = document.querySelector('.activity-card .data-table tbody');
            if (tbody) {
                const selector = `tr[data-bid-id="${cssEscape(String(bidId))}"]`;
                const row = tbody.querySelector(selector);
                if (row) {
                    row.remove();
                }
                if (!tbody.querySelector('tr')) {
                    const empty = document.createElement('tr');
                    empty.innerHTML = '<td colspan="7" style="text-align:center;padding:1rem;color:#6b7280;">No bids found.</td>';
                    empty.setAttribute('data-empty', 'true');
                    tbody.appendChild(empty);
                }
            }

            if (Array.isArray(state.history)) {
                state.history = state.history.filter(item => String(item?.id) !== String(bidId));
            }
        }

        form.addEventListener('submit', event => {
            event.preventDefault();

            const lotId = lotSelect ? lotSelect.value.trim() : '';
            const wasteType = (wasteTypeHidden ? wasteTypeHidden.value : '').trim().toLowerCase();
            const bidPerUnit = parseFloat(bidAmountInput ? bidAmountInput.value : '');
            const wasteAmount = parseFloat(wasteAmountInput ? wasteAmountInput.value : '');

            const errors = [];
            if (!lotId) {
                errors.push('Select a waste lot.');
            }
            if (!wasteType) {
                errors.push('Waste type not determined. Please reselect the lot.');
            }
            if (!(bidPerUnit > 0)) {
                errors.push('Enter a valid bid amount per unit.');
            }
            if (!(wasteAmount > 0)) {
                errors.push('Enter a valid waste amount.');
            }

            const minVal = wasteType && Object.prototype.hasOwnProperty.call(minBids, wasteType)
                ? parseFloat(minBids[wasteType])
                : null;
            if (minVal !== null && bidPerUnit < minVal) {
                errors.push('Bid must be at least ' + formatRs(minVal) + ' per unit for ' + wasteType + '.');
            }

            // Enforce waste amount cannot exceed lot quantity (client-side check)
            try {
                const option = lotSelect ? lotSelect.options[lotSelect.selectedIndex] : null;
                if (option && option.dataset && option.dataset.quantity) {
                    const lotQty = parseFloat(option.dataset.quantity || '0');
                    if (Number.isFinite(lotQty) && wasteAmount > lotQty) {
                        errors.push('Waste amount cannot exceed the available lot quantity of ' + lotQty + '.');
                    }
                }
            } catch (e) {
                // ignore
            }

            if (errors.length) {
                toast(errors.join('\n'), 'error');
                return;
            }

            const payload = {
                roundId: lotId,
                wasteType: wasteType,
                bidPerUnit: bidPerUnit,
                wasteAmount: wasteAmount
            };

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = 'Placing...';
            }

            fetch('/api/company/bids', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
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
                                throw new Error(detail ? message + '\n' + detail : message);
                            }
                            return body;
                        })
                )
                .then(data => {
                    if (data.bid) {
                        upsertBidHistoryRow(data.bid);
                    }
                    if (data.lot) {
                        upsertLotCard(data.lot);
                    }
                    toast('Bid placed successfully.', 'success');
                    form.reset();
                    updateMinBidFromWasteType();
                    updateTotalBid();
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Place Bid';
                    }
                })
                .catch(err => {
                    console.error('Place bid error', err);
                    toast(err.message || 'Failed to place bid.', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Place Bid';
                    }
                });
        });

        window.__COMPANY_BIDDING_UPDATERS = window.__COMPANY_BIDDING_UPDATERS || {};
        Object.assign(window.__COMPANY_BIDDING_UPDATERS, {
            upsertLotCard,
            upsertBidHistoryRow,
            removeBidHistoryRow,
            resolveLotReservePrice,
            getLotDisplayBid,
            cssEscape,
            toast,
            updateTotalBid,
            syncWasteTypeFromLot
        });

        // Initialize form state
        syncWasteTypeFromLot();
        updateTotalBid();
    })();
</script>
<!-- Edit Bid Modal -->
<div id="edit-bid-modal" class="user-modal" role="dialog" aria-modal="true">
    <div class="user-modal__dialog">
        <button class="close" aria-label="Close">&times;</button>
        <h3>Edit Bid</h3>
        <form id="edit-bid-form">
            <input type="hidden" id="edit-bid-id" name="bid_id" />
            <div style="display:flex;gap:8px;align-items:center;">
                <label style="width:120px;">Bid per unit</label>
                <input type="number" id="edit-bid-per-unit" name="bidPerUnit" step="100" required />
            </div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                <label style="width:120px;">Waste amount</label>
                <input type="number" id="edit-waste-amount" name="wasteAmount" step="any" readonly
                    style="background-color: #f3f4f6; color: #6b7280; cursor: not-allowed;" />
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn" id="edit-bid-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>

    (function () {
        const modal = document.getElementById('edit-bid-modal');
        const updaters = window.__COMPANY_BIDDING_UPDATERS || {};
        const toast = updaters.toast || ((msg) => alert(msg));
        const upsertBidHistoryRow = updaters.upsertBidHistoryRow || (() => { });
        const upsertLotCard = updaters.upsertLotCard || (() => { });
        const removeBidHistoryRow = updaters.removeBidHistoryRow || (() => { });
        const cssEscape = updaters.cssEscape || ((v) => String(v));

        if (!modal) return;

        function openEditModal(bid) {
            if (!bid) return;
            // Ensure any inline display:none is cleared so CSS can control visibility
            try { modal.style.display = ''; } catch (e) { }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.getElementById('edit-bid-id').value = bid.id || '';
            // Try to split amount into per-unit and amount if possible using lot qty
            const qty = Number(bid.quantity || 1);
            const perUnit = qty > 0 ? (Number(bid.amount || 0) / qty) : Number(bid.amount || 0);
            document.getElementById('edit-bid-per-unit').value = perUnit.toFixed(2);
            document.getElementById('edit-waste-amount').value = Number(bid.quantity || 0);
        }

        function closeEditModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            // reset form fields so next open is clean
            try {
                const form = modal.querySelector('#edit-bid-form');
                if (form) form.reset();
            } catch (e) {
                // ignore
            }
            // force hide as a fallback in case CSS is overridden elsewhere
            try { modal.style.display = 'none'; } catch (e) { }
        }

        modal.querySelector('.close').addEventListener('click', closeEditModal);
        document.getElementById('edit-bid-cancel').addEventListener('click', closeEditModal);

        // Delegate edit/delete buttons
        document.addEventListener('click', function (e) {
            const editBtn = e.target.closest('[data-action="edit-bid"]');
            if (editBtn) {
                const bidId = editBtn.getAttribute('data-bid-id');
                // Find bid in state.history
                const bid = (window.__COMPANY_BIDDING_STATE || {}).history || [];
                const found = bid.find(b => String(b.id) === String(bidId));
                openEditModal(found);
                return;
            }

            const delBtn = e.target.closest('[data-action="delete-bid"]');
            if (delBtn) {
                const bidId = delBtn.getAttribute('data-bid-id');
                if (!confirm('Cancel this bid?')) return;
                fetch('/api/company/bids/' + encodeURIComponent(bidId), {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(res => res.json().catch(() => ({})).then(body => ({ res, body })))
                    .then(({ res, body }) => {
                        if (!res.ok || !body || !body.success) {
                            throw new Error((body && body.message) ? body.message : 'Delete failed');
                        }
                        if (typeof removeBidHistoryRow === 'function') {
                            removeBidHistoryRow(bidId);
                        } else {
                            const row = document.querySelector(`tr[data-bid-id="${cssEscape(String(bidId))}"]`);
                            if (row) row.remove();
                        }
                        if (body.lot && typeof upsertLotCard === 'function') {
                            upsertLotCard(body.lot);
                        }
                        toast('Bid cancelled.', 'success');
                    })
                    .catch(err => {
                        console.error('Delete bid error', err);
                        toast(err.message || 'Failed to cancel bid.', 'error');
                    });
                return;
            }
        });

        // Submit edit form
        document.getElementById('edit-bid-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const bidId = document.getElementById('edit-bid-id').value;
            const bidPerUnit = parseFloat(document.getElementById('edit-bid-per-unit').value || '0');
            const wasteAmount = parseFloat(document.getElementById('edit-waste-amount').value || '0');
            if (!bidId || !(bidPerUnit > 0) || !(wasteAmount > 0)) {
                toast('Enter valid values.', 'error');
                return;
            }

            const payload = { bidPerUnit: bidPerUnit, wasteAmount: wasteAmount };
            fetch('/api/company/bids/' + encodeURIComponent(bidId), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(res => res.json().catch(() => ({})).then(body => ({ res, body })))
                .then(({ res, body }) => {
                    if (!res.ok || !body || !body.success) {
                        const detail = body && body.errors ? Object.values(body.errors).join('\n') : '';
                        throw new Error((body && body.message ? body.message : 'Update failed') + (detail ? '\n' + detail : ''));
                    }
                    const updated = body.bid;
                    if (updated) {
                        upsertBidHistoryRow(updated);
                    }
                    if (body.lot) {
                        upsertLotCard(body.lot);
                    }
                    // Ensure modal is closed immediately, then show success toast
                    closeEditModal();
                    toast('Bid updated.', 'success');
                })
                .catch(err => {
                    console.error('Update bid error', err);
                    toast(err.message || 'Failed to update bid.', 'error');
                });
        });
    })();
</script>

<script>

    (function () {
        const deleteModal = document.getElementById('delete-bid-modal');
        const deleteConfirmBtn = document.getElementById('delete-bid-confirm');
        const deleteCancelBtn = document.getElementById('delete-bid-cancel');
        const deleteCloseBtn = document.getElementById('delete-bid-close');
        const updaters = window.__COMPANY_BIDDING_UPDATERS || {};
        const toast = updaters.toast || ((msg) => alert(msg));
        const removeBidHistoryRow = updaters.removeBidHistoryRow || (() => { });
        const upsertLotCard = updaters.upsertLotCard || (() => { });
        const cssEscape = updaters.cssEscape || ((v) => String(v));

        let pendingDeleteId = null;

        function escapeSelector(value) {
            const helpers = window.__COMPANY_BIDDING_UPDATERS || {};
            if (helpers && typeof helpers.cssEscape === 'function') {
                return helpers.cssEscape(value);
            }
            if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
                return CSS.escape(value);
            }
            return String(value).replace(/[^a-zA-Z0-9_\-]/g, '\\$&');
        }

        function openDeleteModal(bidId) {
            pendingDeleteId = String(bidId);
            try { deleteModal.style.display = 'flex'; } catch (e) { deleteModal.style.display = ''; }
            // center modal (backdrop uses flex)
            deleteModal.querySelector('.simple-modal-dialog').scrollTop = 0;
        }

        function closeDeleteModal() {
            pendingDeleteId = null;
            try { deleteModal.style.display = 'none'; } catch (e) { deleteModal.style.display = 'none'; }
        }

        // Open modal from request button
        document.addEventListener('click', function (e) {
            const req = e.target.closest('[data-action="request-delete-bid"]');
            if (req) {
                const bidId = req.getAttribute('data-bid-id');
                if (!bidId) return;
                openDeleteModal(bidId);
                return;
            }
        });

        deleteCancelBtn.addEventListener('click', closeDeleteModal);
        deleteCloseBtn.addEventListener('click', closeDeleteModal);

        deleteConfirmBtn.addEventListener('click', function () {
            if (!pendingDeleteId) return;
            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.dataset.originalText = deleteConfirmBtn.textContent;
            deleteConfirmBtn.textContent = 'Cancelling...';

            fetch('/api/company/bids/' + encodeURIComponent(pendingDeleteId), {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(res => res.json().catch(() => ({})).then(body => ({ res, body })))
                .then(({ res, body }) => {
                    if (!res.ok || !body || !body.success) {
                        throw new Error((body && body.message) ? body.message : 'Delete failed');
                    }
                    if (typeof removeBidHistoryRow === 'function') {
                        removeBidHistoryRow(pendingDeleteId);
                    } else {
                        const row = document.querySelector(`tr[data-bid-id="${escapeSelector(String(pendingDeleteId))}"]`);
                        if (row) row.remove();
                    }
                    if (body.lot && typeof upsertLotCard === 'function') {
                        upsertLotCard(body.lot);
                    }
                    closeDeleteModal();
                    toast('Bid cancelled.', 'success');
                })
                .catch(err => {
                    console.error('Delete bid error', err);
                    toast(err.message || 'Failed to cancel bid.', 'error');
                })
                .finally(() => {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.textContent = deleteConfirmBtn.dataset.originalText || 'Yes, Cancel Bid';
                });
        });
    })();
</script>