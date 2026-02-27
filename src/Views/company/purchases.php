<?php
// We receive data from CompanyDashboardController:
// $acceptedPurchases, $purchaseSummary, $purchaseHistory

$summary = $purchaseSummary ?? ['total' => format_rs(0), 'active_orders' => 0, 'completed' => 0];
$purchases = $acceptedPurchases ?? [];
$history = $purchaseHistory ?? [];
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Invoices &amp; Purchased Lots</h2>
            <p class="page-header__description">Manage your invoices and track your purchased waste lots</p>
        </div>
    </header>

    <div class="purchases-grid" style="margin-bottom: 24px;">
        <!-- Purchased Lots Summary -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Purchases Summary</h2>
            <div class="total"><?= htmlspecialchars($summary['total'] ?? 'Rs. 0') ?></div>
            <h2 style="font-size: 16px; font-weight: bold; margin-top: 10px;">Won Lots</h2>
            <div class="summary-box">
                <div class="box blue"><span><?= (int) ($summary['active_orders'] ?? 0) ?></span> <span>Pending
                        Collection</span></div>
                <div class="box purple"><span><?= (int) ($summary['completed'] ?? 0) ?></span> <span>Completed</span>
                </div>
            </div>
        </div>

        <!-- Pending Invoices (Loaded via API) -->
        <div class="c-purchase-card" style="grid-column: span 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-size: 20px; font-weight: bold; margin: 0;">Pending Invoices</h2>
                <div style="font-size: 14px; color: #666;">
                    Total Invoices: <span id="totalAmount" style="font-weight: bold; color: #333;">Rs. 0.00</span>
                </div>
            </div>
            <div id="pendingInvoicesContainer"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(560px, 1fr)); gap: 15px; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                <p style="text-align: center; color: #888; padding: 20px; grid-column: 1/-1;">Loading invoices...</p>
            </div>
        </div>
    </div>

    <!-- My Purchased Lots (Ready for Pickup) -->
    <div class="activity-card" style="margin-bottom: 24px;">
        <div class="activity-card__header">
            <h3 class="activity-card__title">Ready for Collection</h3>
        </div>

        <?php
        $readyForPickup = array_filter($history, function ($p) {
            return strtolower($p['delivery_status'] ?? '') === 'ready_for_pickup';
        });
        ?>

        <?php if (empty($readyForPickup)): ?>
            <div style="padding: 20px; text-align: center; color: #666;">
                <p>No lots currently ready for collection.</p>
                <p style="font-size: 13px; margin-top: 5px;">Lots appear here after your invoice payments are verified by
                    the Admin.</p>
            </div>
        <?php else: ?>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; padding: 15px;">
                <?php foreach ($readyForPickup as $lot): ?>
                    <div class="purchase-box" style="border: 2px solid #10b981; background: #f0fdf4;">
                        <h3 style="font-size: 18px; font-weight: bold; color: #065f46;">Lot <?= htmlspecialchars($lot['id']) ?>
                        </h3>
                        <p style="margin: 5px 0;"><strong>Category:</strong> <?= htmlspecialchars($lot['type']) ?></p>
                        <p style="margin: 5px 0;"><strong>Quantity:</strong> <?= htmlspecialchars($lot['amount']) ?></p>
                        <span class="tag completed" style="position: absolute; top: 15px; right: 20px;">Ready To Collect</span>
                        <div
                            style="margin-top: 15px; padding: 10px; background: white; border-radius: 6px; font-size: 13px; color: #374151;">
                            <strong>Collection Instructions:</strong><br>
                            Please arrange transport to collect this lot from the nearest facility. Reference the Lot ID upon
                            arrival.
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Invoice/Purchase History -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">All Invoices & Transactions</h3>
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
    <div class="form-modal-content">
        <a href="#" class="closePayment" style="float:right;font-size:22px;">&times;</a>
        <h2 style="font-size:22px;font-weight:bold;">Invoice Details</h2>
        <div id="invoiceDetails"></div>

        <!-- Payment Reference Form (shown for pending/processing invoices) -->
        <div id="paymentRefSection" style="display:none; margin-top:20px;">
            <hr style="margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:12px;">Submit Payment Reference</h3>
            <p style="color:#6b7280;font-size:13px;margin-bottom:14px;">
                Transfer the amount to our bank account, then enter your bank reference or transaction ID below.
            </p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div>
                    <label style="font-weight:600;font-size:14px;display:block;margin-bottom:4px;">Transaction /
                        Reference ID <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="payRefTxnId" placeholder="e.g. TRF-20240227-001234"
                        style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-weight:600;font-size:14px;display:block;margin-bottom:4px;">Payment
                        Method</label>
                    <select id="payRefMethod"
                        style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Online Transfer">Online Transfer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div id="payRefError" style="color:#dc2626;font-size:13px;display:none;"></div>
                <button id="submitPayRefBtn" class="btn btn-primary" style="width:100%;margin-top:4px;">
                    Submit Payment Reference
                </button>
            </div>
        </div>

        <!-- Success message (shown after submission) -->
        <div id="paymentRefSuccess"
            style="display:none;margin-top:16px;padding:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;color:#166534;font-size:14px;">
            ✅ Payment reference submitted! We will confirm receipt and update your invoice status shortly.
        </div>

        <br>
        <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn btn-primary"
            style="width:100%;margin-top:8px;">Close</button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", async () => {
        const API_URL = '/api/company/invoices';
        let allInvoices = [];

        // Fetch invoices
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

        // Render pending/processing invoices as cards
        function renderInvoices(invoices) {
            const actionable = invoices.filter(inv => inv.status === 'pending' || inv.status === 'processing');
            const container = document.getElementById('pendingInvoicesContainer');

            if (actionable.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #888; padding: 20px;">No pending invoices</p>';
            } else {
                container.innerHTML = actionable.map(invoice => {
                    const isPending = invoice.status === 'pending';
                    const isProcessing = invoice.status === 'processing';
                    const badgeColor = isPending ? '#f59e0b' : '#3b82f6';
                    const badgeLabel = isPending ? 'PENDING' : 'REFERENCE SUBMITTED';
                    const tagClass = isPending ? 'pending' : 'processing';
                    return `
                    <div class="purchase-box" data-invoice-id="${invoice.id}">
                        <h3 style="font-size: 18px; font-weight: bold;">${escapeHtml(invoice.notes || 'Invoice')}</h3>
                        <p>ID: ${escapeHtml(invoice.id)}</p>
                        <p>Amount: <strong>Rs. ${parseFloat(invoice.amount).toFixed(2)}</strong></p>
                        <p>Reference: ${escapeHtml(invoice.txnId || 'Not yet submitted')}</p>
                        <p>Date: ${formatDate(invoice.date || invoice.createdAt)}</p>
                        <span class="tag ${tagClass}" style="position: absolute; top: 15px; right: 20px;">${badgeLabel}</span>
                        <button class="btn btn-primary outline view-invoice-btn" style="width: 100%; margin-top: 15px;" data-invoice='${JSON.stringify(invoice)}'>
                            ${isPending ? 'Submit Payment Reference' : 'View / Update Reference'}
                        </button>
                    </div>
                    `;
                }).join('');

                // Attach event listeners
                container.querySelectorAll('.view-invoice-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const invoice = JSON.parse(this.getAttribute('data-invoice'));
                        showInvoiceDetails(invoice);
                    });
                });
            }

            renderInvoiceTable(invoices);
        }

        // Render invoice history table
        function renderInvoiceTable(invoices) {
            const tbody = document.getElementById('invoiceHistoryBody');

            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888; padding: 20px;">No invoices found</td></tr>';
            } else {
                tbody.innerHTML = invoices.map(invoice => {
                    const statusClass = invoice.status === 'completed' ? 'completed'
                        : invoice.status === 'failed' ? 'failed'
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
                                ${canAct ? 'Pay / View' : 'View'}
                            </button>
                        </td>
                    </tr>
                    `;
                }).join('');
            }
        }

        // Calculate and display summary
        function calculateSummary(invoices) {
            const pending = invoices.filter(inv => inv.status === 'pending' || inv.status === 'processing').length;
            const completed = invoices.filter(inv => inv.status === 'completed').length;
            const total = invoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);

            const pendingEl = document.getElementById('pendingCount');
            if (pendingEl) pendingEl.textContent = pending;

            const completedEl = document.getElementById('completedCount');
            if (completedEl) completedEl.textContent = completed;

            const totalEl = document.getElementById('totalAmount');
            if (totalEl) totalEl.textContent = `Rs. ${total.toFixed(2)}`;
        }

        // Show invoice details modal
        window.showInvoiceDetails = function (invoice) {
            const modal = document.getElementById('paymentModal');
            const detailsDiv = document.getElementById('invoiceDetails');
            const refSection = document.getElementById('paymentRefSection');
            const successSection = document.getElementById('paymentRefSuccess');
            const errorDiv = document.getElementById('payRefError');

            const canPay = invoice.status === 'pending' || invoice.status === 'processing';
            const statusMap = { pending: 'Pending', processing: 'Reference Submitted', completed: 'Completed', failed: 'Failed' };
            const statusClass = invoice.status === 'completed' ? 'completed'
                : invoice.status === 'processing' ? 'processing'
                    : invoice.status === 'failed' ? 'failed'
                        : 'pending';

            detailsDiv.innerHTML = `
            <p><strong>Invoice ID:</strong> ${escapeHtml(invoice.id)}</p>
            <p><strong>Description:</strong> ${escapeHtml(invoice.notes || 'N/A')}</p>
            <p><strong>Amount:</strong> Rs. ${parseFloat(invoice.amount).toFixed(2)}</p>
            <p><strong>Status:</strong> <span class="tag ${statusClass}">${statusMap[invoice.status] || invoice.status}</span></p>
            <p><strong>Reference:</strong> ${escapeHtml(invoice.txnId || 'Not yet submitted')}</p>
            <p><strong>Date:</strong> ${formatDate(invoice.date || invoice.createdAt)}</p>
            ${invoice.gatewayResponse ? `<p><strong>Payment Method:</strong> ${escapeHtml(invoice.gatewayResponse)}</p>` : ''}
        `;

            // Reset form state
            document.getElementById('payRefTxnId').value = invoice.txnId || '';
            errorDiv.style.display = 'none';
            successSection.style.display = 'none';
            refSection.style.display = canPay ? 'block' : 'none';

            // Wire up submit button for this specific invoice
            const submitBtn = document.getElementById('submitPayRefBtn');
            const newBtn = submitBtn.cloneNode(true); // Remove old listeners
            submitBtn.parentNode.replaceChild(newBtn, submitBtn);

            newBtn.addEventListener('click', async () => {
                const txnId = document.getElementById('payRefTxnId').value.trim();
                const paymentMethod = document.getElementById('payRefMethod').value;
                const errDiv = document.getElementById('payRefError');

                if (!txnId) {
                    errDiv.textContent = 'Please enter your bank reference or transaction ID.';
                    errDiv.style.display = 'block';
                    return;
                }

                errDiv.style.display = 'none';
                newBtn.disabled = true;
                newBtn.textContent = 'Submitting...';

                try {
                    const res = await fetch(`/api/company/invoices/${encodeURIComponent(String(invoice.id))}/pay`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ txnId, paymentMethod })
                    });

                    const body = await res.json().catch(() => ({}));
                    if (!res.ok || !body.success) {
                        throw new Error(body.message || `Request failed (${res.status})`);
                    }

                    // Update in-memory list and re-render
                    const updated = body.data || {};
                    const idx = allInvoices.findIndex(inv => String(inv.id) === String(invoice.id));
                    if (idx !== -1) {
                        allInvoices[idx] = Object.assign({}, allInvoices[idx], {
                            status: updated.status || 'processing',
                            txnId: txnId,
                            gatewayResponse: paymentMethod
                        });
                    }
                    renderInvoices(allInvoices);
                    calculateSummary(allInvoices);

                    refSection.style.display = 'none';
                    successSection.style.display = 'block';

                } catch (err) {
                    errDiv.textContent = err.message || 'Failed to submit. Please try again.';
                    errDiv.style.display = 'block';
                } finally {
                    newBtn.disabled = false;
                    newBtn.textContent = 'Submit Payment Reference';
                }
            });

            modal.style.display = 'flex';
        };

        // Close modal
        document.querySelector('.closePayment').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('paymentModal').style.display = 'none';
        });

        // Helper functions
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