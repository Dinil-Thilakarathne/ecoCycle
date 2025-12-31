/**
 * Waste Category Real-Time Updates Manager
 * Handles polling for waste category changes and updating the UI
 */

class WasteCategoryUpdateManager {
    constructor(options = {}) {
        this.pollInterval = options.pollInterval || 5000; // Poll every 5 seconds
        this.lastUpdateTime = null;
        this.listeners = {
            created: [],
            updated: [],
            deleted: [],
            refreshed: []
        };
        this.isPolling = false;
        this.pollTimeoutId = null;
    }

    /**
     * Start polling for updates
     */
    start() {
        if (this.isPolling) {
            console.warn('Polling already started');
            return;
        }

        this.isPolling = true;
        this.poll();
        console.log('[WasteCategoryUpdates] Polling started');
    }

    /**
     * Stop polling for updates
     */
    stop() {
        if (this.pollTimeoutId) {
            clearTimeout(this.pollTimeoutId);
            this.pollTimeoutId = null;
        }
        this.isPolling = false;
        console.log('[WasteCategoryUpdates] Polling stopped');
    }

    /**
     * Perform a poll request
     */
    async poll() {
        if (!this.isPolling) return;

        try {
            const url = new URL('/api/waste-categories/updates', window.location.origin);
            
            if (this.lastUpdateTime) {
                url.searchParams.append('since', this.lastUpdateTime);
            }

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            
            // Update last update time
            if (data.timestamp) {
                this.lastUpdateTime = data.timestamp;
            }

            // Process events
            if (Array.isArray(data.events) && data.events.length > 0) {
                console.log(`[WasteCategoryUpdates] Received ${data.events.length} events`);
                this.processEvents(data.events);
            }
        } catch (error) {
            console.error('[WasteCategoryUpdates] Polling error:', error);
        } finally {
            // Schedule next poll
            if (this.isPolling) {
                this.pollTimeoutId = setTimeout(() => this.poll(), this.pollInterval);
            }
        }
    }

    /**
     * Process received events
     */
    processEvents(events) {
        events.forEach(event => {
            switch (event.event_type) {
                case 'category_created':
                    this.handleCategoryCreated(event.data);
                    break;
                case 'category_updated':
                    this.handleCategoryUpdated(event.data);
                    break;
                case 'category_deleted':
                    this.handleCategoryDeleted(event.data);
                    break;
                default:
                    console.warn(`[WasteCategoryUpdates] Unknown event type: ${event.event_type}`);
            }
        });
    }

    /**
     * Handle category created event
     */
    handleCategoryCreated(data) {
        console.log('[WasteCategoryUpdates] Category created:', data);
        this.emit('created', data);
    }

    /**
     * Handle category updated event
     */
    handleCategoryUpdated(data) {
        console.log('[WasteCategoryUpdates] Category updated:', data);
        this.emit('updated', data);
    }

    /**
     * Handle category deleted event
     */
    handleCategoryDeleted(data) {
        console.log('[WasteCategoryUpdates] Category deleted:', data);
        this.emit('deleted', data);
    }

    /**
     * Register event listener
     */
    on(eventType, callback) {
        if (!this.listeners[eventType]) {
            console.warn(`[WasteCategoryUpdates] Unknown event type: ${eventType}`);
            return;
        }
        this.listeners[eventType].push(callback);
    }

    /**
     * Emit event to all listeners
     */
    emit(eventType, data) {
        if (!this.listeners[eventType]) return;
        
        this.listeners[eventType].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`[WasteCategoryUpdates] Listener error for ${eventType}:`, error);
            }
        });
    }

    /**
     * Force a full refresh of waste categories
     */
    async refreshCategories() {
        try {
            const response = await fetch('/api/waste-categories', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.emit('refreshed', data.data || []);
            return data.data || [];
        } catch (error) {
            console.error('[WasteCategoryUpdates] Refresh error:', error);
            return [];
        }
    }
}

// Export for global use
window.WasteCategoryUpdateManager = WasteCategoryUpdateManager;
