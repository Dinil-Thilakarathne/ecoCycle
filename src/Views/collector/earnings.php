<?php
// Initialize with empty arrays - data will be loaded via API
$earnings = [];
$summary = ['total' => 0, 'pending' => 0, 'completed' => 0];
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">My Earnings</h2>
            <p class="page-header__description">Track your commission payments from completed pickups</p>
        </div>
    </header>

    <div class="feature-cards" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <!-- Total Earnings -->
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Total Earnings</div>
                <div class="feature-card__icon"><i class="fa-solid fa-coins"></i></div>
            </div>
            <div class="feature-card__body">
                <span id="totalEarnings" style="font-size: 28px; font-weight: bold; color: #10b981;">Rs. 0.00</span>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Pending</div>
                <div class="feature-card__icon"><i class="fa-solid fa-clock"></i></div>
            </div>
            <div class="feature-card__body">
                <span id="pendingCount" style="font-size: 28px; font-weight: bold;">0</span>
            </div>
        </div>

        <!-- Completed Payments -->
        <div class="feature-card">
            <div class="feature-card__header">
                <div class="feature-card__title">Completed</div>
                <div class="feature-card__icon"><i class="fa-solid fa-check-circle"></i></div>
            </div>
            <div class="feature-card__body">
                <span id="completedCount" style="font-size: 28px; font-weight: bold;">0</span>
            </div>
        </div>
    </div>

    <!-- Earnings History -->
    <div class="activity-card" style="margin-top: 2rem;">
        <div class="activity-card__header">
            <h3 class="activity-card__title">Earnings History</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="earningsHistoryBody">
                <tr>
                    <td colspan="6" style="text-align: center; color: #888; padding: 20px;">Loading earnings...</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<!-- Payment Details Modal -->
<div id="paymentModal" class="form-modal">
    <div class="form-modal-content">
        <a href="#" class="closePayment" style="float:right;font-size:22px;">&times;</a>
        <h2 style="font-size:22px;font-weight:bold;">Payment Details</h2>
        <div id="paymentDetails"></div>
        <br>
        <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn btn-primary"
            style="width:100%;">Close</button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", async () => {
        const API_URL = '/api/collector/payments';

        // Fetch earnings
        async function loadEarnings() {
            try {
                const response = await fetch(API_URL, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load earnings');
                }

                const result = await response.json();
                const earnings = result.data || [];

                calculateSummary(earnings);
                renderEarningsTable(earnings);

            } catch (error) {
                console.error('Error loading earnings:', error);
                document.getElementById('earningsHistoryBody').innerHTML =
                    '<tr><td colspan="6" style="text-align: center; color: #d32f2f; padding: 20px;">Failed to load earnings. Please refresh the page.</td></tr>';
            }
        }

        // Calculate and display summary
        function calculateSummary(earnings) {
            const pending = earnings.filter(e => e.status === 'pending').length;
            const completed = earnings.filter(e => e.status === 'completed').length;
            const total = earnings.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0);

            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('completedCount').textContent = completed;
            document.getElementById('totalEarnings').textContent = `Rs. ${total.toFixed(2)}`;
        }

        // Render earnings history table
        function renderEarningsTable(earnings) {
            const tbody = document.getElementById('earningsHistoryBody');

            if (earnings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888; padding: 20px;">No earnings found. Complete pickups to earn commissions!</td></tr>';
            } else {
                tbody.innerHTML = earnings.map(payment => `
                <tr>
                    <td>${escapeHtml(payment.id)}</td>
                    <td>${escapeHtml(payment.notes || 'Commission')}</td>
                    <td class="price" style="font-weight: bold; color: #10b981;">Rs. ${parseFloat(payment.amount).toFixed(2)}</td>
                    <td>
                        <span class="tag ${payment.status === 'completed' ? 'completed' : payment.status === 'failed' ? 'failed' : 'pending'}">
                            ${escapeHtml(payment.status.toUpperCase())}
                        </span>
                    </td>
                    <td>${formatDate(payment.date || payment.createdAt)}</td>
                    <td>
                        <button class="btn btn-primary outline" style="padding: 5px 10px; font-size: 12px;" onclick='showPaymentDetails(${JSON.stringify(payment)})'>
                            View
                        </button>
                    </td>
                </tr>
            `).join('');
            }
        }

        // Show payment details modal
        window.showPaymentDetails = function (payment) {
            const modal = document.getElementById('paymentModal');
            const detailsDiv = document.getElementById('paymentDetails');

            detailsDiv.innerHTML = `
            <p><strong>Payment ID:</strong> ${escapeHtml(payment.id)}</p>
            <p><strong>Description:</strong> ${escapeHtml(payment.notes || 'N/A')}</p>
            <p><strong>Amount:</strong> <span style="color: #10b981; font-weight: bold;">Rs. ${parseFloat(payment.amount).toFixed(2)}</span></p>
            <p><strong>Status:</strong> <span class="tag ${payment.status}">${escapeHtml(payment.status.toUpperCase())}</span></p>
            <p><strong>Reference:</strong> ${escapeHtml(payment.txnId || 'N/A')}</p>
            <p><strong>Date:</strong> ${formatDate(payment.date || payment.createdAt)}</p>
            ${payment.gatewayResponse ? `<p><strong>Details:</strong> ${escapeHtml(payment.gatewayResponse)}</p>` : ''}
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

        // Load earnings on page load
        await loadEarnings();

        // Auto-refresh every 30 seconds
        setInterval(loadEarnings, 30000);
    });
</script>