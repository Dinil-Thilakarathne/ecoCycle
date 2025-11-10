# Postman Setup Checklist for ecoCycle API

**Goal:** Complete API testing setup in under 10 minutes ⚡

---

## ✅ Pre-Setup Requirements

- [ ] **Postman installed** (Desktop or Web)
- [ ] **XAMPP running** (Apache + PostgreSQL)
- [ ] **Database seeded** with demo data
- [ ] **ecoCycle project** cloned/downloaded

---

## 🚀 Quick Setup (5 Minutes)

### Step 1: Import Collection (2 min)

1. **Open Postman**
2. Click **Import** button (top-left corner)
3. **Drag & drop** or select file:
   ```
   /Applications/XAMPP/xamppfiles/htdocs/ecoCycle/postman_collection.json
   ```
4. Click **Import**
5. **Verify:** You should see "ecoCycle API Collection" in left sidebar

**Expected Result:**

```
✅ ecoCycle API Collection
   ├── Authentication (5 requests)
   ├── Admin - Vehicles (5 requests)
   ├── Admin - Bidding Rounds (6 requests)
   ├── Customer - Pickup Requests (4 requests)
   ├── Collector - Pickup Status (2 requests)
   ├── Company - Bids (3 requests)
   └── Development & Debug (4 requests)
```

---

### Step 2: Create Environment (2 min)

1. Click **Environments** in left sidebar
2. Click **+** (Create New Environment)
3. **Name:** `ecoCycle - Local`
4. **Add these variables:**

| Variable     | Initial Value      | Type    |
| ------------ | ------------------ | ------- |
| `base_url`   | `http://localhost` | default |
| `vehicle_id` | _(leave empty)_    | default |
| `round_id`   | _(leave empty)_    | default |
| `pickup_id`  | _(leave empty)_    | default |
| `bid_id`     | _(leave empty)_    | default |

5. Click **Save** (⌘ + S / Ctrl + S)
6. **Select environment** from dropdown (top-right)

**Expected Result:**

```
✅ "ecoCycle - Local" selected in top-right dropdown
✅ {{base_url}} will resolve to http://localhost
```

---

### Step 3: Configure Settings (1 min)

1. Click **⚙️ Settings** (top-right)
2. **General** tab → Enable these:
   - ✅ **Automatically follow redirects**
   - ✅ **Send cookies with requests**
   - ✅ **Retain headers on clicking on links**
3. **Disable** (for local testing only):
   - ❌ **SSL certificate verification**
4. Click **Save**

---

### Step 4: First Test Request (30 sec)

1. Go to: **Authentication → Login - Admin**
2. Click **Send** button
3. **Expected Response:**
   ```json
   {
     "success": true,
     "message": "Login successful",
     "user": {
       "role": "admin",
       "email": "admin@ecocycle.com"
     }
   }
   ```
4. Check **Tests** tab (should show ✅ green checks)
5. Check **Console** (⌘⌥C / Ctrl+Alt+C):
   ```
   ✅ Session ID saved: abc123...
   ```

**If successful:**

```
🎉 Setup Complete! You're ready to test all APIs!
```

**If failed:**

- ❌ Connection refused → [Start XAMPP](#troubleshooting-xampp-not-running)
- ❌ 404 Not Found → [Check routes](#troubleshooting-404-errors)
- ❌ Database error → [Seed database](#troubleshooting-database-not-seeded)

---

## 🧪 Verification Tests (3 Minutes)

Run these to confirm everything works:

### Test 1: Authentication ✅

```
1. Authentication → Login - Admin → Send
Expected: 200 OK, session saved

2. Authentication → Login - Customer → Send
Expected: 200 OK, customer role

3. Authentication → Logout → Send
Expected: 200 OK, session cleared
```

### Test 2: Admin APIs ✅

```
Login as Admin first, then:

1. Admin - Vehicles → List All Vehicles → Send
Expected: 200 OK, vehicles array

2. Admin - Vehicles → Create Vehicle → Send
Expected: 201 Created, vehicle_id saved

3. Admin - Vehicles → Get Vehicle Details → Send
Expected: 200 OK, uses {{vehicle_id}}

4. Admin - Vehicles → Delete Vehicle → Send
Expected: 200 OK
```

### Test 3: Customer APIs ✅

```
Login as Customer first, then:

1. Customer - Pickup Requests → List My Pickups → Send
Expected: 200 OK, data array

2. Customer - Pickup Requests → Create Pickup → Send
Expected: 201 Created, pickup_id saved
```

### Test 4: Company APIs ✅

```
1. Login as Admin → Create Bidding Round
2. Logout → Login as Company
3. Company - Bids → Place Bid → Send
Expected: 201 Created, bid_id saved
```

### Test 5: Debug Endpoints ✅

```
1. Development & Debug → System Health Check → Send
Expected: 200 OK, system status

2. Development & Debug → Database Ping → Send
Expected: 200 OK, database connected
```

---

## 📊 Testing Dashboard

After setup, create a testing dashboard:

### Collection Runner

1. Click **Collections** → **ecoCycle API Collection**
2. Click **Run** button
3. **Select folder** to test (e.g., "Authentication")
4. **Set delay:** 500ms between requests
5. Click **Run ecoCycle API Collection**

**Expected Results:**

```
✅ 5/5 tests passed
✅ 0 failures
✅ Average response time: < 200ms
```

---

## 🔧 Advanced Configuration

### A. Add Collection Variables

For values used across all requests:

1. Click **ecoCycle API Collection**
2. Go to **Variables** tab
3. Add:

| Variable      | Value  | Scope      |
| ------------- | ------ | ---------- |
| `api_version` | `v1`   | Collection |
| `timeout`     | `5000` | Collection |

### B. Add Pre-request Script

**Collection level:**

```javascript
// Auto-refresh expired sessions
const lastLogin = pm.environment.get("last_login_time");
const now = new Date().getTime();
const sessionTimeout = 30 * 60 * 1000; // 30 minutes

if (lastLogin && now - lastLogin > sessionTimeout) {
  console.log("🔄 Session expired, please login again");
  pm.environment.unset("session_id");
}
```

### C. Add Global Tests

**Collection level:**

```javascript
// Response validation
pm.test("Response is JSON", function () {
  pm.response.to.be.json;
});

pm.test("No server errors (5xx)", function () {
  pm.response.to.not.be.serverError;
});

// Performance check
if (pm.response.responseTime > 1000) {
  console.warn("⚠️ Slow response:", pm.response.responseTime, "ms");
}
```

---

## 🐛 Troubleshooting

### Issue: XAMPP Not Running

**Symptoms:**

```
Error: connect ECONNREFUSED 127.0.0.1:80
```

**Fix:**

```bash
# Start XAMPP
sudo /Applications/XAMPP/xamppfiles/xampp start

# Or start Apache only
sudo /Applications/XAMPP/xamppfiles/xampp startapache

# Verify
curl http://localhost
```

**Check:**

- ✅ XAMPP Control Panel shows Apache = Green
- ✅ Browser at `http://localhost` shows page

---

### Issue: 404 Errors

**Symptoms:**

```json
{
  "error": "Not Found",
  "message": "Route not found"
}
```

**Fix:**

1. **Check .htaccess** exists:

   ```bash
   ls -la /Applications/XAMPP/xamppfiles/htdocs/ecoCycle/public/.htaccess
   ```

2. **Verify Apache mod_rewrite** enabled:

   ```bash
   # Edit httpd.conf
   nano /Applications/XAMPP/xamppfiles/etc/httpd.conf

   # Uncomment this line:
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

3. **Restart Apache**:

   ```bash
   sudo /Applications/XAMPP/xamppfiles/xampp restartapache
   ```

4. **Test routes**:
   ```
   GET {{base_url}}/routes/list
   Should list all available routes
   ```

---

### Issue: Database Not Seeded

**Symptoms:**

```json
{
  "error": "No users found"
}
```

**Fix:**

```bash
# Seed database
cd /Applications/XAMPP/xamppfiles/htdocs/ecoCycle
php scripts/seed_db.php

# Verify
curl http://localhost/debug/db/users
```

---

### Issue: Session Not Persisting

**Symptoms:** Must login for every request

**Fix:**

**Option 1: Enable Cookies**

```
Settings → General →
✅ Enable "Store cookies for current session"
```

**Option 2: Manual Cookie Header**

```
1. Login → Copy PHPSESSID from Cookies tab
2. Add to all requests:
   Headers → Cookie: PHPSESSID=<value>
```

**Option 3: Use Interceptor**

```
1. Install Postman Interceptor extension
2. Enable in Postman: Settings → Interceptor → ON
```

---

### Issue: Variables Not Working

**Symptoms:** `{{vehicle_id}}` shows as literal text

**Fix:**

1. **Select environment:**

   ```
   Top-right dropdown → "ecoCycle - Local"
   ```

2. **Check variable scope:**

   ```
   Variables should be in environment, not collection
   ```

3. **Re-run test script:**

   ```
   Tests tab should have:
   pm.environment.set('vehicle_id', jsonData.vehicle.id);
   ```

4. **Manual set:**
   ```
   Environments → ecoCycle - Local →
   vehicle_id = <paste-value>
   ```

---

## 🎯 Testing Workflows

### Workflow 1: Full CRUD Test (Vehicles)

**Time:** 2 minutes

1. ✅ Login as Admin
2. ✅ List All Vehicles
3. ✅ Create Vehicle (saves ID)
4. ✅ Get Vehicle Details (uses saved ID)
5. ✅ Update Vehicle (uses saved ID)
6. ✅ Delete Vehicle (uses saved ID)
7. ✅ Verify Deleted (should return 404)

**Run automatically:**

```
Collections → Admin - Vehicles → Run
```

---

### Workflow 2: Pickup Request Lifecycle

**Time:** 3 minutes

1. ✅ Login as Customer
2. ✅ Create Pickup Request
3. ✅ List My Pickups
4. ✅ Logout → Login as Admin
5. ✅ Assign Collector to Pickup
6. ✅ Logout → Login as Collector
7. ✅ Update Status to "in progress"
8. ✅ Update Status to "completed"

---

### Workflow 3: Bidding Process

**Time:** 3 minutes

1. ✅ Login as Admin
2. ✅ Create Bidding Round
3. ✅ Logout → Login as Company
4. ✅ Place Bid on Round
5. ✅ Update Bid (higher amount)
6. ✅ Logout → Login as Admin
7. ✅ Approve Bidding Round

---

## 📚 Next Steps

### 1. Create Test Suites

**Regression Test Suite:**

- All critical paths
- Run before each deployment

**Smoke Test Suite:**

- Quick health checks
- Run after each server restart

**Load Test Suite:**

- Multiple iterations
- Performance benchmarking

### 2. Set Up Monitors

**Postman Monitors** (scheduled tests):

```
1. Collection → Monitors → Create Monitor
2. Schedule: Every hour
3. Region: Closest to server
4. Alerts: Email on failure
```

### 3. Integrate with CI/CD

**Newman (CLI runner):**

```bash
# Install
npm install -g newman

# Run collection
newman run postman_collection.json \
  -e environment.json \
  --reporters cli,json

# In GitHub Actions
- name: Run API Tests
  run: newman run postman_collection.json
```

### 4. Document Custom Tests

**Create test documentation:**

- Test scenarios
- Expected results
- Known issues
- Performance benchmarks

---

## 🎓 Learning Resources

### Postman Documentation

- **Official Docs:** https://learning.postman.com/
- **Testing Scripts:** https://learning.postman.com/docs/writing-scripts/test-scripts/
- **Variables:** https://learning.postman.com/docs/sending-requests/variables/

### Video Tutorials

- **Postman Beginner's Course:** https://www.youtube.com/watch?v=VywxIQ2ZXw4
- **API Testing with Postman:** https://www.youtube.com/watch?v=juldrxDrSH0

### ecoCycle Specific

- **API Documentation:** [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
- **Testing Guide:** [POSTMAN_TESTING_GUIDE.md](./POSTMAN_TESTING_GUIDE.md)
- **Framework Docs:** [FRAMEWORK_DOCUMENTATION.md](../FRAMEWORK_DOCUMENTATION.md)

---

## ✅ Final Checklist

Before starting development:

- [ ] ✅ Postman collection imported
- [ ] ✅ Environment created and selected
- [ ] ✅ Settings configured (cookies, SSL)
- [ ] ✅ XAMPP server running
- [ ] ✅ Database seeded with demo data
- [ ] ✅ Login test successful
- [ ] ✅ Admin APIs working
- [ ] ✅ Customer APIs working
- [ ] ✅ Company APIs working
- [ ] ✅ Debug endpoints accessible
- [ ] ✅ Variables auto-populating
- [ ] ✅ Collection runner tested
- [ ] ✅ Team members have access

---

## 🆘 Need Help?

**If you're stuck:**

1. **Check console:** ⌘⌥C / Ctrl+Alt+C
2. **Review server logs:** `storage/logs/`
3. **Test with cURL:** Compare with Postman results
4. **Ask the team:** Share collection and environment

**Support Channels:**

- GitHub Issues: https://github.com/Dinil-Thilakarathne/ecoCycle/issues
- Email: support@ecocycle.com
- Documentation: `/docs` folder

---

**Happy Testing! 🚀**

**Setup Time:** ~10 minutes  
**Confidence Level:** 💯  
**Ready for Development:** ✅
