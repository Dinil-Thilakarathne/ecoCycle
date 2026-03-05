<?php
/**
 * Collector Availability Widget
 * Displays collector's assigned vehicle and daily availability status
 */

$user = auth();
$collectorId = $user['id'] ?? null;
$vehicleId = $user['vehicleId'] ?? null;

// Fetch today's status if available
$todayStatus = null;
if ($collectorId) {
    $statusModel = new \Models\CollectorDailyStatus();
    $todayStatus = $statusModel->getTodayStatus($collectorId);
}

$isAvailable = $todayStatus['isAvailable'] ?? true;
$notes = $todayStatus['notes'] ?? '';
$lastUpdated = $todayStatus['statusUpdatedAt'] ?? null;
?>

<div class="availability-widget">
    <div class="widget-header">
        <h3 class="widget-title">
            <i class="fa-solid fa-truck"></i>
            Daily Availability
        </h3>
        <span class="widget-date">
            <?= date('F d, Y') ?>
        </span>
    </div>

    <div class="widget-content">
        <?php if (!$vehicleId): ?>
            <div class="no-vehicle-message">
                <i class="fa-solid fa-exclamation-circle"></i>
                <p>No vehicle assigned. Please contact your administrator.</p>
            </div>
        <?php else: ?>
            <div class="availability-status">
                <div class="status-indicator <?= $isAvailable ? 'available' : 'unavailable' ?>">
                    <i class="fa-solid fa-circle"></i>
                    <span>
                        <?= $isAvailable ? 'Available' : 'Unavailable' ?>
                    </span>
                </div>

                <?php if ($lastUpdated): ?>
                    <small class="last-updated">
                        Last updated:
                        <?= date('g:i A', strtotime($lastUpdated)) ?>
                    </small>
                <?php endif; ?>
            </div>

            <form id="availability-form" class="availability-form">
                <div class="form-group">
                    <label class="form-label">Update Status</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="isAvailable" value="1" <?= $isAvailable ? 'checked' : '' ?>>
                            <span class="radio-label">
                                <i class="fa-solid fa-check-circle"></i>
                                Available
                            </span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="isAvailable" value="0" <?= !$isAvailable ? 'checked' : '' ?>>
                            <span class="radio-label">
                                <i class="fa-solid fa-times-circle"></i>
                                Unavailable
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes" class="form-label">Notes (Optional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"
                        placeholder="Add any notes about your availability..."><?= htmlspecialchars($notes) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-save"></i>
                    Update Availability
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    .availability-widget {
        background: var(--card-bg, #fff);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .widget-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .widget-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .widget-date {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .widget-content {
        padding: 1.5rem;
    }

    .no-vehicle-message {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted, #6b7280);
    }

    .no-vehicle-message i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #f59e0b;
    }

    .availability-status {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: var(--bg-secondary, #f9fafb);
        border-radius: 8px;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 1.125rem;
    }

    .status-indicator.available {
        color: #16a34a;
    }

    .status-indicator.unavailable {
        color: #dc2626;
    }

    .status-indicator i {
        font-size: 0.75rem;
    }

    .last-updated {
        display: block;
        margin-top: 0.5rem;
        color: var(--text-muted, #6b7280);
        font-size: 0.875rem;
    }

    .availability-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-label {
        font-weight: 600;
        color: var(--text-primary, #111827);
    }

    .radio-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .radio-option {
        position: relative;
        cursor: pointer;
    }

    .radio-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .radio-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem;
        border: 2px solid var(--border-color, #e5e7eb);
        border-radius: 8px;
        transition: all 0.2s;
        font-weight: 500;
    }

    .radio-option input[type="radio"]:checked+.radio-label {
        border-color: #16a34a;
        background: #f0fdf4;
        color: #16a34a;
    }

    .radio-option:first-child input[type="radio"]:checked+.radio-label {
        border-color: #16a34a;
        background: #f0fdf4;
    }

    .radio-option:last-child input[type="radio"]:checked+.radio-label {
        border-color: #dc2626;
        background: #fef2f2;
        color: #dc2626;
    }

    .form-control {
        padding: 0.75rem;
        border: 2px solid var(--border-color, #e5e7eb);
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.875rem;
        resize: vertical;
    }

    .form-control:focus {
        outline: none;
        border-color: #16a34a;
    }

    .btn-block {
        width: 100%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('availability-form');

        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const isAvailable = formData.get('isAvailable') === '1';
                const notes = formData.get('notes');

                try {
                    const response = await fetch('/api/collector/availability', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            isAvailable: isAvailable,
                            notes: notes || null
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        // Show success message
                        if (typeof window.__createToast === 'function') {
                            window.__createToast('Availability updated successfully', 'success', 3000);
                        } else {
                            alert('Availability updated successfully');
                        }

                        // Update UI
                        const statusIndicator = document.querySelector('.status-indicator');
                        if (statusIndicator) {
                            statusIndicator.className = 'status-indicator ' + (isAvailable ? 'available' : 'unavailable');
                            statusIndicator.querySelector('span').textContent = isAvailable ? 'Available' : 'Unavailable';
                        }

                        // Update last updated time
                        const lastUpdated = document.querySelector('.last-updated');
                        if (lastUpdated) {
                            const now = new Date();
                            lastUpdated.textContent = 'Last updated: ' + now.toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: '2-digit'
                            });
                        }
                    } else {
                        throw new Error(data.message || 'Failed to update availability');
                    }
                } catch (error) {
                    if (typeof window.__createToast === 'function') {
                        window.__createToast(error.message, 'error', 5000);
                    } else {
                        alert('Error: ' + error.message);
                    }
                }
            });
        }
    });
</script>