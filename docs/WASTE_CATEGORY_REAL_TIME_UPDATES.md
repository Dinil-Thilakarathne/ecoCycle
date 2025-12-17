# Real-Time Waste Category Updates Implementation Guide

## Overview
This implementation adds real-time interface updates whenever waste categories are created, updated, or deleted. The system uses a polling mechanism to fetch events from the server and update the UI accordingly.

## Components

### 1. Backend Services

#### `src/Services/WasteCategoryEventService.php`
- Handles event broadcasting for waste category operations
- Logs events to the database for retrieval via polling
- Methods:
  - `broadcastCreated(array $category)`: Emit category creation event
  - `broadcastUpdated(array $category, array $oldData)`: Emit category update event
  - `broadcastDeleted(int $categoryId)`: Emit category deletion event
  - `getRecentEvents(int $limit)`: Fetch recent events for polling

#### `src/Controllers/Api/WasteCategoryUpdatesController.php`
- New API endpoints for real-time updates
- Endpoints:
  - `GET /api/waste-categories/updates`: Poll for new events
  - `GET /api/waste-categories/server-time`: Get server timestamp for synchronization

### 2. Database Schema

Create the events table using the migration files:

**PostgreSQL:**
```sql
CREATE TABLE waste_category_events (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**MySQL:**
```sql
CREATE TABLE waste_category_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at DESC)
);
```

Run the migration files from `database/postgresql/` or `database/mysql/` directory.

### 3. Frontend JavaScript

#### `public/js/waste-category-updates.js`
Client-side manager for handling real-time updates:

```javascript
// Initialize the manager
const updateManager = new WasteCategoryUpdateManager({
    pollInterval: 5000  // Poll every 5 seconds
});

// Listen for events
updateManager.on('created', (data) => {
    console.log('New category created:', data);
    // Update your UI here
});

updateManager.on('updated', (data) => {
    console.log('Category updated:', data);
    // Update your UI here
});

updateManager.on('deleted', (data) => {
    console.log('Category deleted:', data);
    // Update your UI here
});

// Start polling
updateManager.start();

// Stop polling when needed
// updateManager.stop();
```

## Integration Steps

### Step 1: Database Migration
Run the SQL migration file for your database:

For PostgreSQL:
```bash
psql -U username -d database_name < database/postgresql/create_waste_category_events.sql
```

For MySQL:
```bash
mysql -u username -p database_name < database/mysql/create_waste_category_events.sql
```

### Step 2: Update Routes Configuration
Add the new controller to your routes configuration in `config/routes/Api.php`:

```php
// Waste Category Updates
Route::get('/waste-categories/updates', 'Api\\WasteCategoryUpdatesController@getUpdates');
Route::get('/waste-categories/server-time', 'Api\\WasteCategoryUpdatesController@getServerTime');
```

### Step 3: Include JavaScript in Your Views
Add the real-time updates script to your layout or views where waste categories are displayed:

```html
<script src="/js/waste-category-updates.js"></script>
```

### Step 4: Implement UI Updates
In your view files, initialize the update manager and implement handlers for your specific UI:

Example for a waste category table:
```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    const updateManager = new WasteCategoryUpdateManager({
        pollInterval: 5000
    });

    // Refresh the full table when events occur
    const refreshTable = async () => {
        const categories = await updateManager.refreshCategories();
        // Rebuild your table with new categories
        renderCategoryTable(categories);
    };

    updateManager.on('created', refreshTable);
    updateManager.on('updated', refreshTable);
    updateManager.on('deleted', refreshTable);

    // Start polling
    updateManager.start();
});
</script>
```

## API Endpoints

### GET /api/waste-categories/updates
Polls for waste category events.

**Query Parameters:**
- `since` (optional): Unix timestamp - returns only events newer than this
- `limit` (optional): Number of events to return (max: 100, default: 50)

**Response:**
```json
{
    "events": [
        {
            "id": 1,
            "event_type": "category_created",
            "data": {
                "id": 5,
                "name": "Plastic",
                "color": "#FF6B6B",
                "unit": "kg"
            },
            "created_at": "2024-01-15 10:30:45"
        }
    ],
    "timestamp": 1705318245
}
```

### GET /api/waste-categories/server-time
Gets the current server time for synchronization.

**Response:**
```json
{
    "timestamp": 1705318245,
    "datetime": "2024-01-15 10:30:45"
}
```

## Event Types

The system emits three types of events:

### 1. category_created
Emitted when a new waste category is created.
```json
{
    "event_type": "category_created",
    "data": {
        "id": 5,
        "name": "Plastic",
        "color": "#FF6B6B",
        "unit": "kg"
    }
}
```

### 2. category_updated
Emitted when an existing category is modified.
```json
{
    "event_type": "category_updated",
    "data": {
        "category": {
            "id": 5,
            "name": "Plastic Waste",
            "color": "#FF6B6B",
            "unit": "kg"
        },
        "oldData": {
            "id": 5,
            "name": "Plastic",
            "color": "#FF6B6B",
            "unit": "kg"
        }
    }
}
```

### 3. category_deleted
Emitted when a category is deleted.
```json
{
    "event_type": "category_deleted",
    "data": {
        "id": 5
    }
}
```

## Example Implementation

### For Admin Dashboard (Waste Categories Management):

```html
<div id="waste-categories-container">
    <!-- Your waste categories list/table here -->
</div>

<script src="/js/waste-category-updates.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const manager = new WasteCategoryUpdateManager({
        pollInterval: 3000  // More frequent updates for admin
    });

    // Handler for when category is added
    manager.on('created', async (data) => {
        console.log('New category added:', data);
        const categories = await manager.refreshCategories();
        renderCategories(categories);
        showNotification('New category added!', 'success');
    });

    // Handler for when category is modified
    manager.on('updated', async (data) => {
        console.log('Category updated:', data);
        const categories = await manager.refreshCategories();
        renderCategories(categories);
        showNotification('Category updated!', 'info');
    });

    // Handler for when category is deleted
    manager.on('deleted', async (data) => {
        console.log('Category deleted:', data);
        const categories = await manager.refreshCategories();
        renderCategories(categories);
        showNotification('Category deleted!', 'warning');
    });

    manager.start();
});

function renderCategories(categories) {
    // Your rendering logic here
    const container = document.getElementById('waste-categories-container');
    container.innerHTML = ''; // Clear existing
    
    categories.forEach(cat => {
        const item = document.createElement('div');
        item.className = 'category-item';
        item.innerHTML = `
            <div style="background-color: ${cat.color}; width: 20px; height: 20px; border-radius: 4px;"></div>
            <span>${cat.name}</span>
            <span style="color: #999;">${cat.unit}</span>
        `;
        container.appendChild(item);
    });
}

function showNotification(message, type) {
    // Use your toast/notification system
    if (window.__createToast) {
        window.__createToast(message, type, 3000);
    }
}
</script>
```

## Performance Considerations

1. **Poll Interval**: The default poll interval is 5 seconds. Adjust based on your needs:
   - Faster (1-2s): More responsive but more server load
   - Slower (10-30s): Less responsive but lower server load

2. **Event Cleanup**: The system automatically cleans up events older than 24 hours. You can adjust this in `WasteCategoryEventService::clearOldEvents()`.

3. **Database Indexes**: The events table includes an index on `created_at` for efficient queries.

## Troubleshooting

### Events not being received
1. Check that the events table exists: `SELECT * FROM waste_category_events;`
2. Verify the routes are configured correctly
3. Check browser console for CORS errors
4. Ensure polling is started: `updateManager.start()`

### High CPU/Memory Usage
1. Increase the poll interval
2. Reduce the limit parameter in polling requests
3. Clean up old events more frequently

### Events appearing after a long delay
1. Reduce the poll interval
2. Check server-side database performance

## Cleanup and Maintenance

To clean up old events manually:
```sql
-- PostgreSQL
DELETE FROM waste_category_events WHERE created_at < NOW() - INTERVAL '7 days';

-- MySQL
DELETE FROM waste_category_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

## Advanced Usage

### Manual Event Triggering
```javascript
// Force refresh all categories
const categories = await updateManager.refreshCategories();

// Check server time
const response = await fetch('/api/waste-categories/server-time');
const time = await response.json();
console.log('Server time:', time.datetime);
```

### Custom Event Handler with Data Persistence
```javascript
manager.on('created', (data) => {
    // Store event in localStorage for later reference
    const stored = JSON.parse(localStorage.getItem('categoryEvents') || '[]');
    stored.push({
        type: 'created',
        data: data,
        timestamp: new Date().toISOString()
    });
    localStorage.setItem('categoryEvents', JSON.stringify(stored));
});
```

## Future Enhancements

Potential improvements for this system:
- WebSocket support for true real-time updates (no polling)
- Event batching to reduce API calls
- Offline event queuing
- Automatic reconnection on network failure
- Audit logging for category changes
- User activity tracking (who made the change)
