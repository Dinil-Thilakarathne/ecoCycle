/**
 * Waste Category Auto-Refresh for Bidding Management
 * Include this script in admin/biddingManagement.php to enable real-time updates
 */

(function() {
    'use strict';

    // Initialize update manager when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWasteCategoryUpdates);
    } else {
        initWasteCategoryUpdates();
    }

    function initWasteCategoryUpdates() {
        // Only initialize if the update manager is available
        if (typeof WasteCategoryUpdateManager === 'undefined') {
            console.warn('WasteCategoryUpdateManager not loaded');
            return;
        }

        const manager = new WasteCategoryUpdateManager({
            pollInterval: 5000  // Poll every 5 seconds
        });

        // Update waste category dropdown
        manager.on('created', async (data) => {
            console.log('[BiddingMgmt] New category created:', data);
            await refreshCategoryDropdown(manager);
            showToast('New waste category added!', 'success');
        });

        manager.on('updated', async (data) => {
            console.log('[BiddingMgmt] Category updated:', data);
            await refreshCategoryDropdown(manager);
            showToast('Waste category updated!', 'info');
        });

        manager.on('deleted', async (data) => {
            console.log('[BiddingMgmt] Category deleted:', data);
            await refreshCategoryDropdown(manager);
            showToast('Waste category deleted!', 'warning');
        });

        // Start polling
        manager.start();

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            manager.stop();
        });
    }

    /**
     * Refresh the waste category dropdown in the create lot modal
     */
    async function refreshCategoryDropdown(manager) {
        try {
            // Get fresh categories
            const categories = await manager.refreshCategories();

            // Update the global variable
            window.__WASTE_CATEGORIES = categories.map(cat => cat.name);

            // Update any visible dropdowns
            const dropdowns = document.querySelectorAll('select[name="wasteCategory"]');
            dropdowns.forEach(dropdown => {
                const currentValue = dropdown.value;
                const currentOptions = Array.from(dropdown.options).map(opt => opt.value);

                // Rebuild options
                const optionsHTML = ['<option value="">Select category</option>'];
                optionsHTML.push(...window.__WASTE_CATEGORIES.map(
                    cat => `<option value="${escapeHtml(cat)}">${escapeHtml(cat)}</option>`
                ));

                dropdown.innerHTML = optionsHTML.join('');

                // Restore previous selection if still valid
                if (currentValue && window.__WASTE_CATEGORIES.includes(currentValue)) {
                    dropdown.value = currentValue;
                }
            });

            console.log('[BiddingMgmt] Updated category dropdown with', window.__WASTE_CATEGORIES.length, 'categories');
        } catch (error) {
            console.error('[BiddingMgmt] Error refreshing categories:', error);
        }
    }

    /**
     * Show notification toast
     */
    function showToast(message, type) {
        if (typeof window.__createToast === 'function') {
            window.__createToast(message, type, 3000);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
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
