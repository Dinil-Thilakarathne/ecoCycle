# Real-Time Waste Category Updates - Reference Card

## 🚀 3-Step Setup

```bash
# 1. Database
mysql -u user -p db < database/mysql/create_waste_category_events.sql

# 2. Routes (Edit config/routes/Api.php)
Route::get('/waste-categories/updates', 'Api\\WasteCategoryUpdatesController@getUpdates');
Route::get('/waste-categories/server-time', 'Api\\WasteCategoryUpdatesController@getServerTime');

# 3. Scripts (Edit src/Views/layouts/app.php)
<script src="/js/waste-category-updates.js"></script>
```

## 📚 Documentation Files

| File | Read Time | Purpose |
|------|-----------|---------|
| QUICK_START.md | 5 min | Setup guide |
| REAL_TIME_UPDATES.md | 15 min | Full reference |
| VISUAL_GUIDE.md | 10 min | Examples |
| SUMMARY.md | 10 min | Architecture |
| DEMO.html | 2 min | See it work |

## 🎯 Event Types

```javascript
manager.on('created', (data) => {
    // New category added
    console.log(data.name); // e.g., "Plastic"
});

manager.on('updated', (data) => {
    // Category modified
    console.log(data.category.name);
});

manager.on('deleted', (data) => {
    // Category removed
    console.log(data.id);
});
```

## ⚙️ Configuration

```javascript
const manager = new WasteCategoryUpdateManager({
    pollInterval: 5000  // milliseconds
});

manager.on('created', myHandler);
manager.start();     // Begin polling
manager.stop();      // Stop polling
```

## 🔌 API Endpoints

### Polling
```
GET /api/waste-categories/updates?since=1234567890&limit=50
Response: { events: [...], timestamp: 1234567890 }
```

### Server Time
```
GET /api/waste-categories/server-time
Response: { timestamp: 1234567890, datetime: "2024-01-15 10:30:45" }
```

## 📊 Database

```sql
-- View events
SELECT * FROM waste_category_events ORDER BY created_at DESC;

-- Count by type
SELECT event_type, COUNT(*) FROM waste_category_events GROUP BY event_type;

-- Delete old events (>7 days)
DELETE FROM waste_category_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

## 🧪 Quick Test

```bash
# 1. Create category via admin UI
# 2. Check console for: [WasteCategoryUpdates] Received 1 events
# 3. Verify in customer UI: category appears automatically

# Or test API directly:
curl http://localhost/api/waste-categories/updates
```

## 🐛 Debugging

```javascript
// Enable detailed logging
const manager = new WasteCategoryUpdateManager();
manager.on('created', (d) => console.log('✅ Created:', d));
manager.on('updated', (d) => console.log('📝 Updated:', d));
manager.on('deleted', (d) => console.log('❌ Deleted:', d));
manager.on('refreshed', (d) => console.log('🔄 Refreshed:', d));
```

## 📁 File Locations

```
New Backend:
  src/Services/WasteCategoryEventService.php
  src/Controllers/Api/WasteCategoryUpdatesController.php

New Frontend:
  public/js/waste-category-updates.js
  public/js/waste-category-bidding-integration.js
  public/js/waste-category-pickup-integration.js

Database:
  database/mysql/create_waste_category_events.sql
  database/postgresql/create_waste_category_events.sql

Documentation:
  docs/WASTE_CATEGORY_UPDATES_*.md
  WASTE_CATEGORY_REAL_TIME_README.md
```

## ✅ Verification

```
✓ Events table created
✓ Routes added
✓ Scripts included
✓ No JavaScript errors in console
✓ [WasteCategoryUpdates] Polling started message appears
✓ Category creation triggers UI update
✓ No page refresh needed
```

## 💡 Common Customizations

### Change Poll Interval to 10 seconds
```javascript
new WasteCategoryUpdateManager({ pollInterval: 10000 })
```

### Refresh Categories Manually
```javascript
const categories = await manager.refreshCategories();
```

### Force UI Refresh
```javascript
manager.on('created', async (data) => {
    const categories = await manager.refreshCategories();
    renderMyUI(categories);
});
```

## 🎓 Learning Path

1. **Start**: WASTE_CATEGORY_UPDATES_QUICK_START.md
2. **Setup**: Follow 3-step setup above
3. **Test**: Create category and verify
4. **Learn**: Read WASTE_CATEGORY_REAL_TIME_UPDATES.md
5. **Advanced**: Check WASTE_CATEGORY_UPDATES_VISUAL_GUIDE.md

## 📋 Integration Checklist

### For Admin Dashboard
```html
<script src="/js/waste-category-updates.js"></script>
<script src="/js/waste-category-bidding-integration.js"></script>
<!-- Auto-refreshes waste category dropdown -->
```

### For Customer Pickup Form
```html
<script src="/js/waste-category-updates.js"></script>
<script src="/js/waste-category-pickup-integration.js"></script>
<!-- Auto-refreshes waste category checkboxes -->
```

### For Custom Pages
```html
<script src="/js/waste-category-updates.js"></script>
<script>
const manager = new WasteCategoryUpdateManager();
manager.on('created', myCustomHandler);
manager.start();
</script>
```

## 🚨 Troubleshooting

| Problem | Solution |
|---------|----------|
| **Not updating** | Check routes added, verify table exists |
| **High CPU** | Increase pollInterval (5000 → 10000) |
| **No events** | Check JS file included, console for errors |
| **CORS error** | Ensure /api/ endpoints accessible |
| **Slow** | Add database index (already done) |

## 🎉 You're All Set!

- ✅ System is production-ready
- ✅ All documentation provided
- ✅ Examples included
- ✅ Demo available

**Questions?** See the full documentation in `docs/` folder

**Happy Coding!** 🚀
