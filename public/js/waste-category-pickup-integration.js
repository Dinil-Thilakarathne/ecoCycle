/**
 * Waste Category Auto-Refresh for Pickup Forms
 * Include this script in customer/pickup.php or any view with waste category checkboxes
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWasteCategoryAutoRefresh);
    } else {
        initWasteCategoryAutoRefresh();
    }

    function initWasteCategoryAutoRefresh() {
        // Only initialize if the update manager is available
        if (typeof WasteCategoryUpdateManager === 'undefined') {
            console.warn('WasteCategoryUpdateManager not loaded');
            return;
        }

        const manager = new WasteCategoryUpdateManager({
            pollInterval: 5000  // Poll every 5 seconds
        });

        // Handle category changes
        manager.on('created', async (data) => {
            console.log('[PickupForm] New category created:', data);
            await refreshCategoryCheckboxes(manager);
            showNotification('New waste category added! Please refresh to see it.', 'success');
        });

        manager.on('updated', async (data) => {
            console.log('[PickupForm] Category updated:', data);
            await updateCategoryCheckbox(data.category);
            showNotification('Waste category information updated!', 'info');
        });

        manager.on('deleted', async (data) => {
            console.log('[PickupForm] Category deleted:', data);
            await refreshCategoryCheckboxes(manager);
            showNotification('A waste category has been removed. Please update your selections.', 'warning');
        });

        // Start polling
        manager.start();

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            manager.stop();
        });
    }

    /**
     * Refresh all category checkboxes in the page
     */
    async function refreshCategoryCheckboxes(manager) {
        try {
            const categories = await manager.refreshCategories();

            // Update the global variable if it exists
            if (window.wasteCategories) {
                window.wasteCategories = categories;
            }

            // Rebuild all checkbox grids
            const checkboxGrids = document.querySelectorAll('.checkbox-grid');
            checkboxGrids.forEach(grid => {
                // Get currently checked items (to preserve selection)
                const checkedValues = Array.from(
                    grid.querySelectorAll('input[name="wasteCategories[]"]:checked')
                ).map(input => input.value);

                // Rebuild the grid
                const gridHTML = categories.map(cat => `
                    <label>
                        <input type="checkbox" 
                               name="wasteCategories[]"
                               value="${escapeHtml((cat.id || '').toString())}"
                               ${checkedValues.includes((cat.id || '').toString()) ? 'checked' : ''}>
                        ${escapeHtml(cat.name || 'Unknown')}
                    </label>
                `).join('');

                grid.innerHTML = gridHTML;
            });

            console.log('[PickupForm] Updated category checkboxes with', categories.length, 'categories');
        } catch (error) {
            console.error('[PickupForm] Error refreshing categories:', error);
        }
    }

    /**
     * Update a specific category checkbox (for updates)
     */
    async function updateCategoryCheckbox(categoryData) {
        if (!categoryData || !categoryData.id) return;

        const categoryId = categoryData.id.toString();
        const checkboxes = document.querySelectorAll(
            `input[name="wasteCategories[]"][value="${escapeHtml(categoryId)}"]`
        );

        // Update labels if checkbox is visible
        checkboxes.forEach(checkbox => {
            const label = checkbox.closest('label');
            if (label) {
                const textNode = Array.from(label.childNodes).find(node => 
                    node.nodeType === Node.TEXT_NODE
                );
                if (textNode) {
                    textNode.textContent = categoryData.name || 'Unknown';
                }
            }
        });
    }

    /**
     * Show notification to user
     */
    function showNotification(message, type) {
        // Try to use the existing toast system
        if (typeof window.__createToast === 'function') {
            window.__createToast(message, type, 4000);
            return;
        }

        // Fallback: create a simple alert-style notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Auto-remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 4000);

        // Add animation styles if not already present
        if (!document.querySelector('#notificationStyles')) {
            const style = document.createElement('style');
            style.id = 'notificationStyles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
