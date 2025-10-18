<?php
$minimumBids = $minimumBids ?? [];
$availableWasteLots = $availableWasteLots ?? [];
$biddingHistory = $biddingHistory ?? [];
$formErrors = $formErrors ?? [];
$formSuccess = $formSuccess ?? null;
?>

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

            <label>Waste Type</label>
            <select name="waste_type" id="waste_type" required>
                <option value="">Select waste type…</option>
                <?php foreach ($minimumBids as $type => $min): ?>
                    <option value="<?= htmlspecialchars($type) ?>"><?= ucfirst($type) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Bid for 1kg of waste</label>
            <input type="number" id="bid_amount" name="bid_amount" step="10" placeholder="Enter bid amount" required>

            <label>Waste Amount (kg)</label>
            <input type="number" name="waste_amount" step="1" required placeholder="Enter waste amount" min="10"
                max="10000">

            <button class="btn btn-primary outline" style="width: 100%; margin-top: 15px;" type="submit">Place
                Bid</button>
        </form>


        <div class="available-waste">
            <h2 style="font-size: 20px; font-weight: bold;">Available Waste Lots</h2>

            <?php foreach ($availableWasteLots as $lot): ?>
                <div class="waste-lots">
                    <div class="lot-header">
                        <span class="waste-type"><?= htmlspecialchars($lot['category'] ?? 'Unknown') ?></span>
                        <span class="tag <?= strtolower($lot['status'] ?? 'available') ?>">
                            <?= htmlspecialchars(ucfirst($lot['status'] ?? 'available')) ?>
                        </span>
                    </div>
                    <div class="lot-details">
                        <p><strong>Quantity:</strong>
                            <?= htmlspecialchars(number_format($lot['quantity'] ?? 0) . ' ' . ($lot['unit'] ?? 'kg')) ?></p>
                        <p><strong>Current Bid:</strong> <?= htmlspecialchars(format_rs($lot['currentHighestBid'] ?? 0)) ?>
                        </p>
                    </div>
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
                    <tr>
                        <td><?= htmlspecialchars($bid['displayId'] ?? ('BID' . $bid['id'])) ?></td>
                        <td><?= htmlspecialchars($bid['category'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars(number_format($bid['quantity'] ?? 0) . ' ' . ($bid['unit'] ?? 'kg')) ?>
                        </td>
                        <td><?= htmlspecialchars(format_rs($bid['amount'] ?? 0)) ?></td>
                        <td><span
                                class="tag <?= strtolower($bid['status'] ?? 'pending') ?>"><?= htmlspecialchars($bid['status'] ?? 'Pending') ?></span>
                        </td>
                        <td><?= htmlspecialchars($bid['createdAt'] ? date('Y-m-d', strtotime($bid['createdAt'])) : 'N/A') ?>
                        </td>
                        <td>
                            <?php if (($bid['status'] ?? '') === 'Leading' || ($bid['status'] ?? '') === 'Active'): ?>
                                <!-- Update -->
                                <a href="?action=edit&id=<?= $bid['id']; ?>" class="c-action-icon edit" title="Edit Bid">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <!-- Delete -->
                                <a href="?action=delete&id=<?= $bid['id']; ?>" class="c-action-icon delete" title="Cancel Bid"
                                    onclick="return confirm('Cancel this bid?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Pass PHP minimum bids to JS for dynamic validation
    const minBids = <?= json_encode($minimumBids) ?>;

    document.getElementById('waste_type').addEventListener('change', function () {
        let selected = this.value;
        let bidInput = document.getElementById('bid_amount');

        if (selected && minBids[selected]) {
            bidInput.min = minBids[selected];
            bidInput.placeholder = "Minimum: " + minBids[selected];
        } else {
            bidInput.min = 500;
            bidInput.placeholder = "Enter bid amount";
        }
    });
</script>