<?php
// Initialize with empty arrays - data will be loaded via API
$invoices = [];
$summary = ['total' => format_rs(0), 'pending' => 0, 'completed' => 0];
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Invoices & Payments</h2>
            <p class="page-header__description">Manage your invoices and payment history</p>
        </div>
    </header>

    <div class="purchases-grid">
        <!-- Pending Invoices -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Pending Invoices</h2>
            <div id="pendingInvoicesContainer">
                <p style="text-align: center; color: #888; padding: 20px;">Loading invoices...</p>
            </div>
        </div>

        <!-- Invoice Summary -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Invoice Summary</h2>
            <div class="total" id="totalAmount">Loading...</div>
            <h2 style="font-size: 20px; font-weight: bold;">Total Invoices</h2>
            <div class="summary-box">
                <div class="box blue"><span id="pendingCount">0</span> <span>Pending</span></div>
                <div class="box purple"><span id="completedCount">0</span> <span>Completed</span></div>
            </div>
        </div>
    </div>

    <!-- Invoice History -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">All Invoices</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
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
        <br>
        <p style="color: #666; font-size: 14px;">
            <strong>Note:</strong> To pay this invoice, please transfer the amount to our bank account and contact
            support with your payment reference.
        </p>
        <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn btn-primary"
            style="width:100%;">Close</button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", async () => {
        const API_URL = '/api/company/invoices';

        // Fetch invoices
        async function loadInvoices() {
            try {
                const response = await fetch(API_URL, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load invoices');
                }

                const result = await response.json();
                const invoices = result.data || [];

                renderInvoices(invoices);
                calculateSummary(invoices);

            } catch (error) {
                console.error('Error loading invoices:', error);
                document.getElementById('pendingInvoicesContainer').innerHTML =
                    '<p style="text-align: center; color: #d32f2f; padding: 20px;">Failed to load invoices. Please refresh the page.</p>';
                document.getElementById('invoiceHistoryBody').innerHTML =
                    '<tr><td colspan="6" style="text-align: center; color: #d32f2f; padding: 20px;">Failed to load invoice history.</td></tr>';
            }
        }

        // Render pending invoices
        function renderInvoices(invoices) {
            const pendingInvoices = invoices.filter(inv => inv.status === 'pending');
            const container = document.getElementById('pendingInvoicesContainer');

            if (pendingInvoices.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #888; padding: 20px;">No pending invoices</p>';
            } else {
                container.innerHTML = pendingInvoices.map(invoice => `
                <div class="purchase-box" data-invoice-id="${invoice.id}">
                    <h3 style="font-size: 18px; font-weight: bold;">${escapeHtml(invoice.notes || 'Invoice')}</h3>
                    <p>ID: ${escapeHtml(invoice.id)}</p>
                    <p>Amount: <strong>Rs. ${parseFloat(invoice.amount).toFixed(2)}</strong></p>
                    <p>Reference: ${escapeHtml(invoice.txnId || 'N/A')}</p>
                    <p>Date: ${formatDate(invoice.date || invoice.createdAt)}</p>
                    <span class="tag pending" style="position: absolute; top: 15px; right: 20px;">PENDING</span>
                    <button class="btn btn-primary outline view-invoice-btn" style="width: 100%; margin-top: 15px;" data-invoice='${JSON.stringify(invoice)}'>
                        View Details
                    </button>
                </div>
            `).join('');

                // Attach event listeners
                container.querySelectorAll('.view-invoice-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const invoice = JSON.parse(this.getAttribute('data-invoice'));
                        showInvoiceDetails(invoice);
                    });
                });
            }

            // Render all invoices in table
            renderInvoiceTable(invoices);
        }

        // Render invoice history table
        function renderInvoiceTable(invoices) {
            const tbody = document.getElementById('invoiceHistoryBody');

            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888; padding: 20px;">No invoices found</td></tr>';
            } else {
                tbody.innerHTML = invoices.map(invoice => `
                <tr>
                    <td>${escapeHtml(invoice.id)}</td>
                    <td>${escapeHtml(invoice.notes || 'Invoice')}</td>
                    <td class="price">Rs. ${parseFloat(invoice.amount).toFixed(2)}</td>
                    <td>
                        <span class="tag ${invoice.status === 'completed' ? 'completed' : invoice.status === 'failed' ? 'failed' : 'pending'}">
                            ${escapeHtml(invoice.status.toUpperCase())}
                        </span>
                    </td>
                    <td>${formatDate(invoice.date || invoice.createdAt)}</td>
                    <td>
                        <button class="btn btn-primary outline" style="padding: 5px 10px; font-size: 12px;" onclick='showInvoiceDetails(${JSON.stringify(invoice)})'>
                            View
                        </button>
                    </td>
                </tr>
            `).join('');
            }
        }

        // Calculate and display summary
        function calculateSummary(invoices) {
            const pending = invoices.filter(inv => inv.status === 'pending').length;
            const completed = invoices.filter(inv => inv.status === 'completed').length;
            const total = invoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);

            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('completedCount').textContent = completed;
            document.getElementById('totalAmount').textContent = `Rs. ${total.toFixed(2)}`;
        }

        // Show invoice details modal
        window.showInvoiceDetails = function (invoice) {
            const modal = document.getElementById('paymentModal');
            const detailsDiv = document.getElementById('invoiceDetails');

            detailsDiv.innerHTML = `
            <p><strong>Invoice ID:</strong> ${escapeHtml(invoice.id)}</p>
            <p><strong>Description:</strong> ${escapeHtml(invoice.notes || 'N/A')}</p>
            <p><strong>Amount:</strong> Rs. ${parseFloat(invoice.amount).toFixed(2)}</p>
            <p><strong>Status:</strong> <span class="tag ${invoice.status}">${escapeHtml(invoice.status.toUpperCase())}</span></p>
            <p><strong>Reference:</strong> ${escapeHtml(invoice.txnId || 'N/A')}</p>
            <p><strong>Date:</strong> ${formatDate(invoice.date || invoice.createdAt)}</p>
            ${invoice.gatewayResponse ? `<p><strong>Payment Method:</strong> ${escapeHtml(invoice.gatewayResponse)}</p>` : ''}
        `;

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
            div.textContent = text;
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