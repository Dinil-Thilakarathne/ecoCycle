<?php
$acceptedPurchases = $acceptedPurchases ?? [];
$purchaseSummary = $purchaseSummary ?? ['total' => format_rs(0), 'active_orders' => 0, 'completed' => 0];
$purchaseHistory = $purchaseHistory ?? [];
?>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Purchases</h2>
            <p class="page-header__description">Manage your accepted purchases and payment history</p>
        </div>
    </header>

    <div class="purchases-grid">
        <!-- Accepted Purchases -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Accepted Purchases</h2>
            <?php foreach ($acceptedPurchases as $purchase): ?>
                <div class="purchase-box">
                    <h3 style="font-size: 18px; font-weight: bold;"><?= htmlspecialchars($purchase['type'] ?? 'Unknown') ?>
                    </h3>
                    <p>ID: <?= htmlspecialchars($purchase['id'] ?? 'N/A') ?></p>
                    <p>Amount: <strong><?= htmlspecialchars($purchase['amount'] ?? '0 kg') ?></strong></p>
                    <p>Price: <span class="price"><?= htmlspecialchars($purchase['price'] ?? format_rs(0)) ?></span></p>
                    <p>Pickup Date: <?= htmlspecialchars($purchase['pickup_date'] ?? 'TBD') ?></p>
                    <span class="tag <?= strtolower(str_replace(' ', '-', $purchase['status'] ?? 'pending')) ?>"
                        style="position: absolute; top: 15px; right: 20px;"><?= strtoupper($purchase['status'] ?? 'Confirmed') ?>
                    </span>
                    <a href="#paymentModal" class="btn btn-primary outline" style="width: 100%; margin-top: 15px;">Make
                        Payment</a>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Purchase Summary -->
        <div class="c-purchase-card">
            <h2 style="font-size: 20px; font-weight: bold;">Purchase Summary</h2>
            <div class="total"><?= htmlspecialchars($purchaseSummary['total']) ?></div>
            <h2 style="font-size: 20px; font-weight: bold;">Total purchases</h2>
            <div class="summary-box">
                <div class="box blue"><?= (int) ($purchaseSummary['active_orders'] ?? 0) ?> <span>Active Orders</span>
                </div>
                <div class="box purple"><?= (int) ($purchaseSummary['completed'] ?? 0) ?> <span>Completed</span></div>
            </div>
        </div>
    </div>

    <!-- Purchase History -->
    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">Purchase History</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Purchase ID</th>
                    <th>Waste Type</th>
                    <th>Amount</th>
                    <th>Price</th>
                    <th>Delivery Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchaseHistory as $history): ?>
                    <tr>
                        <td><?= htmlspecialchars($history['id'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($history['type'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($history['amount'] ?? '0 kg') ?></td>
                        <td class="price"><?= htmlspecialchars($history['price'] ?? format_rs(0)) ?></td>
                        <td><span
                                class="tag completed"><?= htmlspecialchars($history['delivery_status'] ?? 'Completed') ?></span>
                        </td>
                        <td><?= htmlspecialchars($history['date'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Payment Modal -->
<div id="paymentModal" class="form-modal">
    <div class="form-modal-content">
        <a href="#" class="closePayment" style="float:right;font-size:22px;">&times;</a>

        <h2 style="font-size:22px;font-weight:bold;">Make Payment</h2>

        <div id="paymentPurchaseDetails"></div>
        <br>

        <form id="paymentForm">
            <input type="hidden" name="purchase_id" id="purchase_id">

            <div class="form-group">
                <label>Select Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="">-- Select Method --</option>
                    <option value="card">Credit / Debit Card</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>

            <!-- Dynamic payment inputs -->
            <div id="paymentFields"></div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Confirm Payment</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        document.querySelectorAll(".btn.btn-primary.outline").forEach(btn => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();

                document.getElementById("paymentModal").style.display = "block";
            });
        });

        document.querySelector(".closePayment").onclick = function () {
            document.getElementById("paymentModal").style.display = "none";
        };
    });

</script>
<script>
    document.addEventListener("DOMContentLoaded", () => {

        // OPEN MODAL + FILL DETAILS
        document.querySelectorAll(".btn.btn-primary.outline").forEach(btn => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();

                const purchaseBox = this.closest(".purchase-box");

                const purchaseId = purchaseBox.querySelector("p:nth-child(2)").innerText.replace("ID: ", "").trim();
                const amount = purchaseBox.querySelector("p:nth-child(3)").innerText.replace("Amount: ", "").trim();
                const price = purchaseBox.querySelector(".price").innerText.trim();
                const type = purchaseBox.querySelector("h3").innerText.trim();
                const pickupDate = purchaseBox.querySelector("p:nth-child(5)").innerText.replace("Pickup Date: ", "").trim();

                // Fill modal fields
                document.getElementById("purchase_id").value = purchaseId;

                document.getElementById("paymentPurchaseDetails").innerHTML = `
        <p><strong>Purchase ID:</strong> ${purchaseId}</p>
        <p><strong>Waste Type:</strong> ${type}</p>
        <p><strong>Amount:</strong> ${amount}</p>
        <p><strong>Total Price:</strong> ${price}</p>
        <p><strong>Pickup Date:</strong> ${pickupDate}</p>
      `;

                // Show modal
                document.getElementById("paymentModal").style.display = "flex";
            });
        });

        // CLOSE MODAL
        document.querySelector(".closePayment").onclick = function () {
            document.getElementById("paymentModal").style.display = "none";
        };


        // Payment method dynamic fields
        const methodSelect = document.getElementById("payment_method");
        const fieldsContainer = document.getElementById("paymentFields");

        methodSelect.addEventListener("change", function () {
            const value = this.value;

            if (value === "card") {
                fieldsContainer.innerHTML = `
        <div class="form-group">
          <label>Card Number</label>
          <input type="text" name="card_number" maxlength="16" required>
        </div>
        <div class="form-group">
          <label>Expiry</label>
          <input type="month" name="expiry" required>
        </div>
        <div class="form-group">
          <label>Name in the card</label>
          <input type="text" name="user_name" maxlength="4" required>
        </div>

      `;
            } else if (value === "bank") {
                fieldsContainer.innerHTML = `
        <div class="form-group">
          <label>Bank Account Number</label>
          <input type="text" name="account_number" required>
        </div>
        <div class="form-group">
          <label>Bank Name</label>
          <input type="text" name="bank_name" required>
        </div>
      `;
            } else {
                fieldsContainer.innerHTML = "";
            }
        });

    });
</script>