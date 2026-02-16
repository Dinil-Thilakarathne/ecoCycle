# Real-Time Waste Category Updates - Quick Setup

## 5-Minute Setup Guide

### Step 1: Create the Events Table
Run ONE of these commands based on your database:

**PostgreSQL:**
```bash
psql -U your_user -d your_database < database/postgresql/create_waste_category_events.sql
```

**MySQL:**
```bash
mysql -u your_user -p your_database < database/mysql/create_waste_category_events.sql
```

### Step 2: Update Your Routes File
Edit `config/routes/Api.php` and add these routes after your waste-categories routes:

```php
// Real-time waste category updates
Route::get('/waste-categories/updates', 'Api\\WasteCategoryUpdatesController@getUpdates');
Route::get('/waste-categories/server-time', 'Api\\WasteCategoryUpdatesController@getServerTime');
```

### Step 3: Add Scripts to Your Layout
Edit `src/Views/layouts/app.php` and add before the closing `</body>` tag:

```html
<!-- Real-time waste category updates -->
<script src="/js/waste-category-updates.js"></script>
```

### Step 4: Enable Updates in Your Views

#### For Bidding Management (Admin):
Add to `src/Views/admin/biddingManagement.php` in the script section:

```html
<script src="/js/waste-category-updates.js"></script>
<script src="/js/waste-category-bidding-integration.js"></script>
```

#### For Pickup Form (Customer):
Add to `src/Views/customer/pickup.php` in the script section:

```html
<script src="/js/waste-category-updates.js"></script>
<script src="/js/waste-category-pickup-integration.js"></script>
```

### Step 5: Verify Installation
1. Open your browser's developer console (F12)
2. Look for messages like: `[WasteCategoryUpdates] Polling started`
3. Try creating a new waste category
4. Check console for event notifications

## Testing

### Manual Test
```javascript
// In browser console, test the API:
fetch('/api/waste-categories/updates')
    .then(r => r.json())
    .then(data => console.log(data))
```

### Check Database
```sql
SELECT * FROM waste_category_events LIMIT 10;
```

## Common Issues

### Events not showing up
- Check that the table exists: `SELECT * FROM waste_category_events;`
- Check routes are added correctly
- Check browser console for errors

### High CPU usage
- Increase `pollInterval` (5000ms = 5 seconds)
- Reduce `limit` parameter in polling

### CORS errors
- Check that `/api/` endpoints are not blocked
- Ensure CSRF token is configured correctly

## Customization

### Change Poll Interval
Edit the JavaScript initialization to poll more/less frequently:

```javascript
const manager = new WasteCategoryUpdateManager({
    pollInterval: 10000  // Change to 10 seconds
});
```

### Custom Event Handlers
```javascript
manager.on('created', (data) => {
    console.log('New category:', data);
    // Your custom logic here
});
```

## Files Added

- `src/Services/WasteCategoryEventService.php` - Event broadcasting service
- `src/Controllers/Api/WasteCategoryUpdatesController.php` - API endpoints
- `public/js/waste-category-updates.js` - Core polling manager
- `public/js/waste-category-bidding-integration.js` - Bidding page integration
- `public/js/waste-category-pickup-integration.js` - Pickup form integration
- `database/postgresql/create_waste_category_events.sql` - PostgreSQL migration
- `database/mysql/create_waste_category_events.sql` - MySQL migration

## What Happens When You Add/Update/Delete a Category?

1. **Create Category** → Event logged to DB → Manager fetches it → UI updates dropdown/checkboxes
2. **Update Category** → Event logged with old & new data → UI refreshes with new info
3. **Delete Category** → Event logged → UI removes category from all interfaces

## Performance Tips

1. **For frequently accessed pages**: Use 3000ms poll interval
2. **For admin pages**: Use 5000ms poll interval (default)
3. **For low-traffic pages**: Use 10000ms poll interval or higher

## Real-Time Updates

The system polls the server for changes. When a change is detected:
- Waste category dropdowns refresh automatically
- Waste category checkboxes update automatically
- Toast notifications show what changed
- The interface stays in sync without requiring page reload

## Support

Check the full documentation at: `docs/WASTE_CATEGORY_REAL_TIME_UPDATES.md`
