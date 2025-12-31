<?php
$categories = $categories ?? [];
?>

<div>
    <page-header title="Waste Categories & Pricing" description="Manage waste types and their purchase prices">
        <button class="btn btn-primary" onclick="openWasteCategoryModal()">
            <i class="fa-solid fa-plus"></i>
            Add New Category
        </button>
    </page-header>

    <div class="activity-card">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-tags" style="margin-right: var(--space-2);"></i>
                Active Categories
            </h3>
        </div>
        <div class="activity-card__content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Unit</th>
                        <th>Color Code</th>
                        <th>Purchase Price (Per Unit)</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--neutral-500);padding:2rem;">
                                No waste categories found. Click "Add New Category" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="category-row" data-id="<?= htmlspecialchars($cat['id']) ?>"
                                data-name="<?= htmlspecialchars($cat['name']) ?>"
                                data-unit="<?= htmlspecialchars($cat['unit'] ?? 'kg') ?>"
                                data-color="<?= htmlspecialchars($cat['color'] ?? '#000000') ?>"
                                data-price="<?= htmlspecialchars($cat['price_per_unit'] ?? 0) ?>">

                                <td class="font-medium">
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        <span
                                            style="display:inline-block;width:12px;height:12px;border-radius:50%;background-color:<?= htmlspecialchars($cat['color'] ?? '#ccc') ?>;"></span>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($cat['unit'] ?? 'kg') ?></td>
                                <td>
                                    <code style="background:#f3f4f6;padding:0.2rem 0.4rem;border-radius:4px;font-size:0.85rem;">
                                                                <?= htmlspecialchars($cat['color'] ?? 'N/A') ?>
                                                            </code>
                                </td>
                                <td>
                                    <span style="font-weight:600;color:var(--primary-600);">
                                        Rs <?= number_format((float) ($cat['price_per_unit'] ?? 0), 2) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div class="action-buttons" style="justify-content: flex-end;">
                                        <button class="icon-button" title="Edit" onclick="editCategory(<?= htmlspecialchars(json_encode([
                                            'id' => $cat['id'],
                                            'name' => $cat['name'],
                                            'unit' => $cat['unit'] ?? 'kg',
                                            'color' => $cat['color'] ?? '#000000',
                                            'price' => $cat['price_per_unit'] ?? 0
                                        ])) ?>)">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="icon-button danger" title="Delete"
                                            onclick="deleteCategory(<?= htmlspecialchars($cat['id']) ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/js/admin/waste_categories.js"></script>