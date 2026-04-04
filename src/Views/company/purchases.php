<?php
// We receive data from CompanyDashboardController:
// $acceptedPurchases, $purchaseSummary, $purchaseHistory

$summary = $purchaseSummary ?? ['total' => format_rs(0), 'active_orders' => 0, 'completed' => 0];
$purchases = $acceptedPurchases ?? [];
$history = $purchaseHistory ?? [];

// Detect PayHere return status from URL (return_url / cancel_url redirect)
$paymentReturn = $_GET['payment'] ?? '';
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Invoices &amp; Purchased Lots</h2>
            <p class="page-header__description">Manage your invoices and track your purchased waste lots</p>
        </div>
    </header>

    <?php if ($paymentReturn === 'success'): ?>
    <div id="payhereBanner" style="
        display:flex; align-items:center; gap:12px;
        margin-bottom:20px; padding:14px 18px;
        background:linear-gradient(135deg,#d1fae5,#ecfdf5);
        border:1.5px solid #6ee7b7; border-radius:12px;
        color:#065f46; font-size:14px; font-weight:500;
        box-shadow: 0 2px 8px rgba(16,185,129,0.12);
    ">
        <span style="font-size:22px;">✅</span>
        <div>
            <strong>Payment Successful!</strong> Your payment was confirmed by PayHere.
            <span id="pollStatus" style="opacity:0.7; font-size:13px; margin-left:4px;">Updating invoice status…</span>
        </div>
        <button onclick="document.getElementById('payhereBanner').remove()" style="
            margin-left:auto; background:none; border:none; font-size:18px;
            cursor:pointer; color:#065f46; padding:4px;
        ">×</button>
    </div>

    <?php elseif ($paymentReturn === 'cancelled'): ?>
    <div id="payhereBanner" style="
        display:flex; align-items:center; gap:12px;
        margin-bottom:20px; padding:14px 18px;
        background:linear-gradient(135deg,#fef3c7,#fffbeb);
        border:1.5px solid #fcd34d; border-radius:12px;
        color:#92400e; font-size:14px; font-weight:500;
        box-shadow: 0 2px 8px rgba(245,158,11,0.12);
    ">
        <span style="font-size:22px;">⚠️</span>
        <div>
            <strong>Payment Cancelled.</strong> You cancelled the PayHere payment. Your invoice remains pending — you can try again anytime.
        </div>
        <button onclick="document.getElementById('payhereBanner').remove()" style="
            margin-left:auto; background:none; border:none; font-size:18px;
            cursor:pointer; color:#92400e; padding:4px;
        ">×</button>
    </div>
    <?php endif; ?>

    <div class="c-dashboard-grid" style="grid-template-columns: 65% 1fr; margin-bottom: 20px;">

        <!-- Pending Invoices (Loaded via API) -->
        <div class="available-waste"  >
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-size: 22px; font-weight: bold;">Pending Invoices</h2>
                <div style="font-size: 14px; color: #666;">
                    Total Invoices: <span id="totalAmount" style="font-weight: bold; color: #333;">Rs. 0.00</span>
                </div>
            </div>
            <div id="pendingInvoicesContainer"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(560px, 1fr)); gap: 15px; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                <p style="text-align: center; color: #888; padding: 20px; grid-column: 1/-1;">Loading invoices...</p>
            </div>
        </div>

        <!-- Purchased Lots Summary -->
        <div class="available-waste" style="height:100%; display:flex; flex-direction:column; justify-content:flex-start;">
            <h2 style="font-size: 22px; font-weight: bold;">Purchases Summary</h2>
            <div class="total"><?= htmlspecialchars($summary['total'] ?? 'Rs. 0') ?></div>
            <h2 style="font-size: 16px; font-weight: bold; margin-top: 10px;">Won Lots</h2>
            <div class="summary-box">
                <div class="box blue"><span><?= (int) ($summary['active_orders'] ?? 0) ?></span> <span>Pending
                        Collection</span></div>
                <div class="box purple"><span><?= (int) ($summary['completed'] ?? 0) ?></span> <span>Completed</span>
                </div>
            </div>
        </div>
    </div>



    <!-- Invoice/Purchase History -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">All Invoices &amp; Transactions</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Invoice Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="invoiceHistoryBody">
                <tr>
                    <td colspan="6" style="text-align: center; color: #888; padding: 20px;">Loading invoice history...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<!-- Payment Details Modal -->
<div id="paymentModal" class="form-modal">
  <div class="form-modal-content" style="background:var(--color-background-primary,#fff); border-radius:12px; max-width:420px; width:100%; padding:0; overflow:hidden; position:relative;">

    <!-- Close button top-right -->
    <button onclick="document.getElementById('paymentModal').style.display='none'"
      style="position:absolute;top:14px;right:14px;background:none;border:none;cursor:pointer;font-size:22px;line-height:1;color:#6b7280;">&times;</button>

    <!-- Header & invoice details -->
    <div style="padding:1.25rem 1.5rem 0;">
      <p style="font-size:12px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 4px;">Invoice</p>
      <h2 style="font-size:20px;font-weight:600;margin:0 0 1.25rem;">Invoice details</h2>

      <div style="background:#f9fafb;border-radius:8px;padding:1rem;">
        <div id="invoiceDetails"></div>
      </div>
    </div>

    <!-- PayHere section -->
    <div id="payhereSection" style="display:none;border-top:1px solid #e5e7eb;padding:1.25rem 1.5rem;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a56db" stroke-width="2">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
          <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
        <span style="font-size:14px;font-weight:600;">Pay online with PayHere</span>
      </div>
      <p style="font-size:12px;color:#6b7280;margin:0 0 1rem;line-height:1.5;">
        Accepts Visa, Mastercard, and AMEX via Sri Lanka's leading payment gateway.
      </p>

      <div id="payhereError" style="display:none;color:#dc2626;font-size:13px;margin-bottom:8px;padding:8px 12px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;"></div>

      <button id="payWithPayhereBtn" style="
        width:100%;padding:11px 16px;font-size:14px;font-weight:600;
        background:#1a56db;border:none;border-radius:8px;color:#fff;cursor:pointer;
        display:flex;align-items:center;justify-content:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
          <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
        Pay with PayHere
      </button>

      <p style="font-size:11px;color:#9ca3af;text-align:center;margin:10px 0 0;">
        🔒 Redirects to PayHere's secure payment page
      </p>
    </div>
  </div>
</div>


<!-- Hidden PayHere auto-submit form (built & submitted by JS) -->
<form id="payhereAutoForm" method="POST" style="display:none;"></form>

<script>
    document.addEventListener("DOMContentLoaded", async () => {
        const API_URL = '/api/company/invoices';
        let allInvoices = [];

        // Remove ?payment= param from URL without reload (keeps history clean)
        if (window.location.search.includes('payment=')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('payment');
            window.history.replaceState({}, '', url.toString());
        }

        // ── Fetch invoices ────────────────────────────────────────────────
        async function loadInvoices() {
            try {
                const response = await fetch(API_URL, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (!response.ok) throw new Error('Failed to load invoices');

                const result = await response.json();
                allInvoices = result.data || [];

                renderInvoices(allInvoices);
                calculateSummary(allInvoices);

            } catch (error) {
                console.error('Error loading invoices:', error);
                document.getElementById('pendingInvoicesContainer').innerHTML =
                    '<p style="text-align: center; color: #d32f2f; padding: 20px;">Failed to load invoices. Please refresh the page.</p>';
                document.getElementById('invoiceHistoryBody').innerHTML =
                    '<tr><td colspan="6" style="text-align: center; color: #d32f2f; padding: 20px;">Failed to load invoice history.</td></tr>';
            }
        }

        // ── Render pending/processing invoices as cards ───────────────────
        function renderInvoices(invoices) {
            const actionable = invoices.filter(inv => inv.status === 'pending' || inv.status === 'processing');
            const container  = document.getElementById('pendingInvoicesContainer');

            if (actionable.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #888; padding: 20px;">No pending invoices</p>';
            } else {
                container.innerHTML = actionable.map(invoice => {
                    const isPending  = invoice.status === 'pending';
                    const tagClass   = isPending ? 'pending' : 'processing';
                    const badgeLabel = isPending ? 'PENDING' : 'REFERENCE SUBMITTED';
                    return `
                    <div class="purchase-box" data-invoice-id="${invoice.id}">
                        <h3 style="font-size: 18px; font-weight: bold;">${escapeHtml(invoice.notes || 'Invoice')}</h3>
                        <p>ID: ${escapeHtml(invoice.id)}</p>
                        <p>Amount: <strong>Rs. ${parseFloat(invoice.amount).toFixed(2)}</strong></p>
                        <p>Reference: ${escapeHtml(invoice.txnId || 'Not yet submitted')}</p>
                        <p>Date: ${formatDate(invoice.date || invoice.createdAt)}</p>
                        <span class="tag ${tagClass}" style="position: absolute; top: 15px; right: 20px;">${badgeLabel}</span>
                        <button class="btn btn-primary outline view-invoice-btn" style="width: 100%; margin-top: 15px;" data-invoice='${JSON.stringify(invoice)}'>
                            ${isPending ? '💳 Pay Invoice' : 'View / Update Payment'}
                        </button>
                    </div>
                    `;
                }).join('');

                container.querySelectorAll('.view-invoice-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const invoice = JSON.parse(this.getAttribute('data-invoice'));
                        showInvoiceDetails(invoice);
                    });
                });
            }

            renderInvoiceTable(invoices);
        }

        // ── Render invoice history table ──────────────────────────────────
        function renderInvoiceTable(invoices) {
            const tbody = document.getElementById('invoiceHistoryBody');

            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888; padding: 20px;">No invoices found</td></tr>';
            } else {
                tbody.innerHTML = invoices.map(invoice => {
                    const statusClass = invoice.status === 'completed' ? 'completed'
                        : invoice.status === 'failed'     ? 'failed'
                        : invoice.status === 'processing' ? 'processing'
                        : 'pending';
                    const canAct = invoice.status === 'pending' || invoice.status === 'processing';
                    return `
                    <tr>
                        <td>${escapeHtml(invoice.id)}</td>
                        <td>${escapeHtml(invoice.notes || 'Invoice')}</td>
                        <td class="price">Rs. ${parseFloat(invoice.amount).toFixed(2)}</td>
                        <td>
                            <span class="tag ${statusClass}">
                                ${escapeHtml(String(invoice.status || 'pending').toUpperCase())}
                            </span>
                        </td>
                        <td>${formatDate(invoice.date || invoice.createdAt)}</td>
                        <td>
                            <button class="btn btn-primary outline" style="padding: 5px 10px; font-size: 12px;"
                                onclick='showInvoiceDetails(${JSON.stringify(invoice)})'>
                                ${canAct ? '💳 Pay / View' : 'View'}
                            </button>
                        </td>
                    </tr>
                    `;
                }).join('');
            }
        }

        // ── Summary ───────────────────────────────────────────────────────
        function calculateSummary(invoices) {
            const total = invoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);

            const pendingEl = document.getElementById('pendingCount');
            if (pendingEl) pendingEl.textContent = invoices.filter(i => i.status === 'pending' || i.status === 'processing').length;

            const completedEl = document.getElementById('completedCount');
            if (completedEl) completedEl.textContent = invoices.filter(i => i.status === 'completed').length;

            const totalEl = document.getElementById('totalAmount');
            if (totalEl) totalEl.textContent = `Rs. ${total.toFixed(2)}`;
        }

        // ── Show invoice details modal ────────────────────────────────────
        window.showInvoiceDetails = function (invoice) {
            const modal          = document.getElementById('paymentModal');
            const detailsDiv     = document.getElementById('invoiceDetails');
            const payhereSection = document.getElementById('payhereSection');
            const payhereAmtLbl  = document.getElementById('payhereAmountLabel');

            const canPay      = invoice.status === 'pending' || invoice.status === 'processing';
            const statusMap   = { pending: 'Pending', processing: 'Processing', completed: 'Completed', failed: 'Failed' };
            const statusClass = invoice.status === 'completed' ? 'completed'
                              : invoice.status === 'processing' ? 'processing'
                              : invoice.status === 'failed'     ? 'failed'
                              : 'pending';

            const amount = parseFloat(invoice.amount || 0).toFixed(2);

            detailsDiv.innerHTML = `
            <p><strong>Invoice ID:</strong> ${escapeHtml(invoice.id)}</p>
            <p><strong>Description:</strong> ${escapeHtml(invoice.notes || 'N/A')}</p>
            <p><strong>Amount:</strong> <strong style="color:#1a56db">Rs. ${amount}</strong></p>
            <p><strong>Status:</strong> <span class="tag ${statusClass}">${statusMap[invoice.status] || invoice.status}</span></p>
            <p><strong>Reference:</strong> ${escapeHtml(invoice.txnId || 'Not yet paid')}</p>
            <p><strong>Date:</strong> ${formatDate(invoice.date || invoice.createdAt)}</p>
            ${invoice.gatewayResponse ? `<p><strong>Payment Method:</strong> ${escapeHtml(
                typeof invoice.gatewayResponse === 'object'
                    ? (invoice.gatewayResponse.gateway || JSON.stringify(invoice.gatewayResponse))
                    : invoice.gatewayResponse
            )}</p>` : ''}
        `;

            // Update PayHere amount label & reset error
            if (payhereAmtLbl) payhereAmtLbl.textContent = amount;
            document.getElementById('payhereError').style.display = 'none';

            // Show PayHere section only for payable invoices
            payhereSection.style.display = canPay ? 'block' : 'none';

            // ── Wire up PayHere button ────────────────────────────────────
            const payhereBtn    = document.getElementById('payWithPayhereBtn');
            const newPayhereBtn = payhereBtn.cloneNode(true);
            payhereBtn.parentNode.replaceChild(newPayhereBtn, payhereBtn);

            // Re-sync amount label after clone
            const clonedLbl = newPayhereBtn.querySelector('#payhereAmountLabel');
            if (clonedLbl) clonedLbl.textContent = amount;

            newPayhereBtn.addEventListener('click', () => initPayhereCheckout(invoice.id, newPayhereBtn));


            modal.style.display = 'flex';
        };


        // ── PayHere Checkout initiator ────────────────────────────────────
        /**
         * Calls our backend to get a signed PayHere payload,
         * then auto-submits a hidden POST form to PayHere Sandbox.
         */
        async function initPayhereCheckout(invoiceId, btn) {
            const errDiv = document.getElementById('payhereError');
            errDiv.style.display = 'none';
            btn.disabled   = true;
            btn.innerHTML  = '<span style="display:inline-block;animation:spin 1s linear infinite;">⏳</span>&nbsp;Preparing Payment...';

            try {
                const res = await fetch(`/api/payhere/checkout/${encodeURIComponent(String(invoiceId))}`, {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const body = await res.json().catch(() => ({}));

                if (!res.ok || !body.success) {
                    throw new Error(body.message || body.error || `Server error (${res.status})`);
                }

                const payload   = body.payload;
                const actionUrl = payload.action_url;
                delete payload.action_url; // not a form field

                // Build hidden form
                const form = document.getElementById('payhereAutoForm');
                form.action   = actionUrl;
                form.innerHTML = '';

                Object.entries(payload).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = key;
                    input.value = value ?? '';
                    form.appendChild(input);
                });

                // Submit after short delay so user sees the loading state
                setTimeout(() => form.submit(), 300);

            } catch (err) {
                console.error('[PayHere]', err);
                errDiv.textContent   = err.message || 'Failed to initiate PayHere payment. Please try again.';
                errDiv.style.display = 'block';
                btn.disabled         = false;
                btn.innerHTML        = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pay with PayHere';
            }
        }

        // ── Close modal ───────────────────────────────────────────────────
        document.querySelector('.closePayment').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('paymentModal').style.display = 'none';
        });

        // ── Utility helpers ───────────────────────────────────────────────
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = String(text ?? '');
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Load invoices on page load
        await loadInvoices();
    });
</script>

<?php if ($paymentReturn === 'success'): ?>
<script>
// ── Auto-poll invoice status after successful PayHere return ─────────────────
// PayHere calls notify_url asynchronously — it may arrive a few seconds after
// the browser is redirected to return_url. Poll the invoices API until we see
// a 'completed' status, then reload the page clean (removes ?payment=success).
(function () {
    const MAX_ATTEMPTS  = 15;   // poll for up to ~45 seconds
    const INTERVAL_MS   = 3000; // every 3 seconds
    let   attempts      = 0;

    const pollEl = document.getElementById('pollStatus');

    const timer = setInterval(async () => {
        attempts++;

        try {
            const res  = await fetch('/api/company/invoices', { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();

            // Look for any invoice that just became completed
            const invoices = data.data ?? data.invoices ?? data ?? [];
            const anyCompleted = Array.isArray(invoices) &&
                invoices.some(inv => (inv.status ?? '').toLowerCase() === 'completed');

            if (anyCompleted) {
                clearInterval(timer);
                if (pollEl) pollEl.textContent = 'Invoice updated! Refreshing…';
                // Reload without the ?payment=success query param
                setTimeout(() => {
                    window.location.href = window.location.pathname;
                }, 800);
                return;
            }
        } catch (e) {
            // Network error — keep trying
        }

        // Give up after MAX_ATTEMPTS
        if (attempts >= MAX_ATTEMPTS) {
            clearInterval(timer);
            if (pollEl) pollEl.textContent = 'Refresh the page to see the updated status.';
        }
    }, INTERVAL_MS);
})();
</script>
<?php endif; ?>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>