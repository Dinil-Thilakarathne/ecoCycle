<?php

// Handle form submissions if any exist in the future

function formatDate($date)
{
    return date('M d, Y', strtotime($date));
}

function formatCurrency($amount)
{
    return 'Rs ' . number_format($amount, 2);
}
?>

<div style="background: var(--neutral-1);">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Payments</h2>
            <p class="page-header__description">Manage your billing and view transaction history</p>
        </div>
    </header>

    <?php if (isset($success_message)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>


    <div class="billing-content" style="background: var(--neutral-1); padding: 2rem; border-radius: 1.5rem;">
        <!-- Feature Cards (Stats) -->
        <div class="stats-grid" style="margin-bottom:2.5rem;">
            <!-- Next Payment card removed -->
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Total Earnings</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                </div>
                <p class="feature-card__body">
                    <?= formatCurrency($total_received) ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">All time</span>
                </div>
            </div>
            <!-- Subscription Status card removed -->
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title">Transactions</h3>
                    <div class="feature-card__icon"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
                <p class="feature-card__body">
                    <?= $transaction_count ?>
                </p>
                <div class="feature-card__footer">
                    <span class="tag success">Total payouts</span>
                </div>
            </div>
        </div>


        <!-- Payment Methods Section Removed -->

        <!-- Invoice History Section -->
        <div class="activity-card" style="margin-top: var(--space-8); border: 0; border-radius: 0; padding: 0; background: transparent; box-shadow: none;">
            <div class="activity-card__header" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <h3 class="activity-card__title">
                        <i class="fa-solid fa-dollar-sign" style="margin-right: var(--space-2);"></i>
                        Recent Transactions
                    </h3>
                    <p class="activity-card__description">Latest payment transactions and their status</p>
                </div>
                <div id="liveIndicator" style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:#64748b;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 2s infinite;"></span>
                    Live
                </div>
            </div>
            <div class="activity-card__content" id="transactionTable">
                <div style="text-align:center;padding:2rem;color:#64748b;">Loading transactions...</div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
</style>

<script>
(function () {
    const API_URL = '/api/customer/payments';
    let allPayments = <?= json_encode(array_values($payments ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let pollInterval = null;
    let hasPending = false;

    function escHtml(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtDate(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        return isNaN(d) ? v : d.toLocaleDateString(undefined, {month:'short',day:'2-digit',year:'numeric'});
    }
    function fmtTime(v) {
        if (!v) return '-';
        const d = new Date(String(v).replace(' ', 'T'));
        return isNaN(d) ? '-' : d.toLocaleTimeString(undefined, {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    function fmtCurrency(n) {
        return 'Rs ' + parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits:2,maximumFractionDigits:2});
    }
    function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    function openModal(options = {}) {
        if (window.Modal && typeof window.Modal.open === 'function') {
            return window.Modal.open(options);
        }
        return null;
    }

    function openPaymentDetailsModal(payment = {}) {
        const details = [
            { label: 'Amount', value: fmtCurrency(payment.amount) },
            { label: 'Date', value: fmtDate(payment.date) },
            { label: 'Time', value: fmtTime(payment.date) },
            { label: 'Status', value: ucfirst(String(payment.status || 'pending').toLowerCase()) },
        ];

        const list = document.createElement('div');
        list.style.cssText = 'display:grid;gap:0.9rem;';
        details.forEach(item => {
            const block = document.createElement('div');
            block.innerHTML = `
                <span style="display:block;color:#6b7280;font-size:0.85rem;margin-bottom:0.2rem;">${escHtml(item.label)}</span>
                <strong style="color:#111827;">${escHtml(item.value)}</strong>
            `;
            list.appendChild(block);
        });

        const modal = openModal({
            title: 'Payment Details',
            size: 'sm',
            content: list,
            actions: [{ label: 'Close', variant: 'plain' }]
        });

        if (!modal) {
            alert(
                `Payment Details\n\n` +
                `Amount: ${fmtCurrency(payment.amount)}\n` +
                `Date: ${fmtDate(payment.date)}\n` +
                `Time: ${fmtTime(payment.date)}\n` +
                `Status: ${ucfirst(String(payment.status || 'pending').toLowerCase())}`
            );
        }
    }

    function renderTable(payments) {
        const el = document.getElementById('transactionTable');
        if (!el) return;

        const hasRows = Array.isArray(payments) && payments.length > 0;
        const rows = hasRows
            ? payments.map((p, index) => {
                const status = String(p.status || '').toLowerCase();
                const tagClass = status === 'completed' ? 'completed' : (status === 'failed' ? 'danger' : 'pending');
                return `
                    <tr>
                        <td class="font-medium">${index + 1}</td>
                        <td>${escHtml(fmtCurrency(p.amount))}</td>
                        <td>${escHtml(fmtDate(p.date))}</td>
                        <td>
                            <span class="tag ${tagClass}">${escHtml(ucfirst(p.status || 'pending'))}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline js-view-payment" data-index="${index}">View Details</button>
                        </td>
                    </tr>`;
            }).join('')
            : `<tr>
                    <td colspan="5" style="text-align:center; padding: var(--space-16); color: var(--neutral-500);">No transactions found.</td>
               </tr>`;

        el.innerHTML = `
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>`;

        el.querySelectorAll('.js-view-payment').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.getAttribute('data-index'));
                const payment = Array.isArray(payments) ? payments[idx] : null;
                if (!payment) {
                    showToast('Unable to load payment details.', 'error');
                    return;
                }
                openPaymentDetailsModal(payment);
            });
        });
    }

    function updateStats(payments) {
        const totalEarned   = payments.reduce((s, p) => s + parseFloat(p.amount || 0), 0);
        const totalCount    = payments.length;
        const cards = document.querySelectorAll('.feature-card .feature-card__body');
        if (cards[0]) cards[0].textContent = fmtCurrency(totalEarned);
        if (cards[1]) cards[1].textContent  = totalCount;
    }

    async function fetchAndRefresh() {
        try {
            const res  = await fetch(API_URL, { credentials: 'same-origin' });
            if (!res.ok) return;
            const json = await res.json();
            const fresh = json.data || [];

            // Detect any status change from pending → completed
            const prevStatuses = Object.fromEntries(allPayments.map(p => [p.id, p.status]));
            allPayments = fresh;
            fresh.forEach(p => {
                if (prevStatuses[p.id] && prevStatuses[p.id] !== 'completed' && p.status === 'completed') {
                    showToast(`✅ Payout of ${fmtCurrency(p.amount)} has been received!`, 'success');
                }
            });

            renderTable(allPayments);
            updateStats(allPayments);

            // Stop polling if no pending payouts remain
            hasPending = fresh.some(p => ['pending','processing'].includes(p.status || ''));
            if (!hasPending && pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
                const liveEl = document.getElementById('liveIndicator');
                if (liveEl) liveEl.style.display = 'none';
            }
        } catch (e) {
            // Silently ignore network errors
        }
    }

    function showToast(msg, type = 'info') {
        if (typeof window.__createToast === 'function') {
            window.__createToast(msg, type, 6000);
        }
    }

    // Initial render from PHP data
    renderTable(allPayments);
    updateStats(allPayments);

    // Start polling only if there are pending payouts
    hasPending = allPayments.some(p => ['pending','processing'].includes(p.status || ''));
    if (hasPending) {
        pollInterval = setInterval(fetchAndRefresh, 5000);
    } else {
        const liveEl = document.getElementById('liveIndicator');
        if (liveEl) liveEl.style.display = 'none';
    }
})();
</script>

