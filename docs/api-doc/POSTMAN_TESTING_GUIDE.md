# Postman Testing Guide for ecoCycle API

**Version:** 1.0.0  
**Last Updated:** October 24, 2025  
**Prerequisites:** Postman Desktop App or Postman Web

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Initial Setup](#initial-setup)
3. [Environment Configuration](#environment-configuration)
4. [Authentication Setup](#authentication-setup)
5. [Testing Workflows](#testing-workflows)
6. [Advanced Features](#advanced-features)
7. [Common Issues & Solutions](#common-issues--solutions)
8. [Best Practices](#best-practices)

---

## Quick Start

### Step 1: Import the Collection

1. **Open Postman**
2. Click **Import** button (top-left)
3. Select **File** tab
4. Navigate to: `/Applications/XAMPP/xamppfiles/htdocs/ecoCycle/postman_collection.json`
5. Click **Import**

### Step 2: Create Environment

1. Click **Environments** in left sidebar
2. Click **+** (Create Environment)
3. Name it: `ecoCycle - Local`
4. Add variables (see [Environment Configuration](#environment-configuration))
5. Click **Save**

### Step 3: Start Testing

1. Select `ecoCycle - Local` environment from dropdown (top-right)
2. Go to **Authentication** → **Login - Admin**
3. Click **Send**
4. You're ready to test! 🚀

---

## Initial Setup

### 1. Install Postman

**Download:**

- Desktop App: https://www.postman.com/downloads/
- Web Version: https://web.postman.com/

**Recommended:** Desktop app for better cookie/session management.

### 2. Import the Collection

```bash
# Method 1: Direct Import
1. File → Import → Upload Files
2. Select: postman_collection.json
3. Click Import

# Method 2: From GitHub
1. File → Import → Link
2. Paste repository raw file URL
3. Click Continue → Import
```

### 3. Verify Import

After import, you should see:

- ✅ **ecoCycle API Collection** (root folder)
- ✅ **6 folders** (Authentication, Admin APIs, Customer APIs, etc.)
- ✅ **25+ requests** total

---

## Environment Configuration

### Create Local Environment

**Click Environments → + Create Environment**

**Environment Name:** `ecoCycle - Local`

**Variables to Add:**

| Variable Name | Initial Value      | Current Value      | Type    |
| ------------- | ------------------ | ------------------ | ------- |
| `base_url`    | `http://localhost` | `http://localhost` | default |
| `vehicle_id`  | _(leave empty)_    | _(auto-populated)_ | default |
| `round_id`    | _(leave empty)_    | _(auto-populated)_ | default |
| `pickup_id`   | _(leave empty)_    | _(auto-populated)_ | default |
| `bid_id`      | _(leave empty)_    | _(auto-populated)_ | default |
| `csrf_token`  | _(leave empty)_    | _(auto-populated)_ | default |
| `session_id`  | _(leave empty)_    | _(auto-populated)_ | default |

**Screenshot:**

```
┌─────────────────────────────────────────────────┐
│ Environment: ecoCycle - Local                   │
├──────────────┬──────────────────┬───────────────┤
│ Variable     │ Initial Value    │ Current Value │
├──────────────┼──────────────────┼───────────────┤
│ base_url     │ http://localhost │ ...           │
│ vehicle_id   │                  │               │
│ round_id     │                  │               │
│ pickup_id    │                  │               │
│ bid_id       │                  │               │
└──────────────┴──────────────────┴───────────────┘
```

### Create Production Environment

**Environment Name:** `ecoCycle - Production`

| Variable Name | Initial Value             | Type    |
| ------------- | ------------------------- | ------- |
| `base_url`    | `https://your-domain.com` | default |

---

## Authentication Setup

### Understanding Session-Based Auth

ecoCycle uses **session cookies** for authentication. After login:

- ✅ Postman stores cookies automatically
- ✅ Subsequent requests use the session
- ✅ No need for manual token management

### Method 1: Automatic Cookie Management (Recommended)

**1. Enable Cookie Handling:**

```
Settings (⚙️) → General →
✅ Enable "Automatically follow redirects"
✅ Enable "Send cookies with requests"
✅ Enable "Store cookies for current session"
```

**2. Login Request:**

```
POST {{base_url}}/login
Body:
{
  "email": "admin@ecocycle.com",
  "password": "admin123"
}
```

**3. View Cookies:**

- Click **Cookies** (below Send button)
- You should see: `PHPSESSID=<session-value>`

**4. Test Authenticated Request:**

```
GET {{base_url}}/api/vehicles
```

Cookies are sent automatically! ✨

### Method 2: Manual Session Management

If automatic cookies don't work:

**1. Extract Session from Login:**

Add to **Login** request → **Tests** tab:

```javascript
// Extract session cookie
var cookies = pm.cookies.get("PHPSESSID");
if (cookies) {
  pm.environment.set("session_id", cookies);
  console.log("Session ID saved:", cookies);
}

// Extract CSRF token if available
var jsonData = pm.response.json();
if (jsonData.csrf_token) {
  pm.environment.set("csrf_token", jsonData.csrf_token);
}
```

**2. Add to Authenticated Requests:**

**Headers** tab:

```
Cookie: PHPSESSID={{session_id}}
```

### Method 3: Using Pre-request Scripts

**Collection-level Pre-request Script:**

1. Click on **ecoCycle API Collection** (root)
2. Go to **Pre-request Script** tab
3. Add:

```javascript
// Auto-refresh session if expired
const sessionId = pm.environment.get("session_id");
if (!sessionId) {
  console.log("⚠️ No session found. Please login first.");
}
```

---

## Testing Workflows

### Workflow 1: Admin Vehicle Management

**Goal:** Create, update, and delete a vehicle.

**Steps:**

1. **Login as Admin**

   ```
   POST {{base_url}}/login
   Body: { "email": "admin@ecocycle.com", "password": "admin123" }
   Expected: ✅ 200 OK, cookies set
   ```

2. **List All Vehicles**

   ```
   GET {{base_url}}/api/vehicles
   Expected: ✅ 200 OK, vehicles array
   Note: Vehicle IDs auto-saved to {{vehicle_id}}
   ```

3. **Create New Vehicle**

   ```
   POST {{base_url}}/api/vehicles
   Body:
   {
     "plateNumber": "TEST-1234",
     "type": "Large Truck",
     "lastMaintenance": "2025-10-20",
     "nextMaintenance": "2026-01-20"
   }
   Expected: ✅ 201 Created
   Note: New vehicle_id saved automatically
   ```

4. **Get Vehicle Details**

   ```
   GET {{base_url}}/api/vehicles/{{vehicle_id}}
   Expected: ✅ 200 OK, vehicle details
   ```

5. **Update Vehicle**

   ```
   PUT {{base_url}}/api/vehicles/{{vehicle_id}}
   Body:
   {
     "status": "maintenance"
   }
   Expected: ✅ 200 OK
   ```

6. **Delete Vehicle**
   ```
   DELETE {{base_url}}/api/vehicles/{{vehicle_id}}
   Expected: ✅ 200 OK
   ```

**Test Results:**

- ✅ All CRUD operations working
- ✅ Auto-populate variables working
- ✅ Session persisted across requests

---

### Workflow 2: Complete Pickup Process

**Goal:** Customer creates pickup → Admin assigns → Collector completes

**Step 1: Login as Customer**

```
POST {{base_url}}/login
Body: { "email": "customer@ecocycle.com", "password": "customer123" }
```

**Step 2: Create Pickup Request**

```
POST {{base_url}}/api/customer/pickup-requests
Body:
{
  "address": "123 Test Street, Colombo",
  "timeSlot": "morning",
  "scheduledAt": "2025-10-26 09:00:00",
  "wasteCategories": [
    { "id": 1, "quantity": 10, "unit": "kg" }
  ]
}
Expected: ✅ 201 Created
Note: pickup_id saved to environment
```

**Step 3: Customer Views Their Pickups**

```
GET {{base_url}}/api/customer/pickup-requests
Expected: ✅ 200 OK, array with new pickup
```

**Step 4: Switch to Admin**

```
POST {{base_url}}/logout
POST {{base_url}}/login
Body: { "email": "admin@ecocycle.com", "password": "admin123" }
```

**Step 5: Admin Assigns Collector**

```
PUT {{base_url}}/api/pickup-requests/{{pickup_id}}
Body:
{
  "collectorId": 5,
  "status": "assigned",
  "scheduledAt": "2025-10-26 09:00:00"
}
Expected: ✅ 200 OK
```

**Step 6: Switch to Collector**

```
POST {{base_url}}/logout
POST {{base_url}}/login
Body: { "email": "collector@ecocycle.com", "password": "collector123" }
```

**Step 7: Collector Starts Pickup**

```
PUT {{base_url}}/api/collector/pickup-requests/{{pickup_id}}/status
Body: { "status": "in progress" }
Expected: ✅ 200 OK
```

**Step 8: Collector Completes Pickup**

```
PUT {{base_url}}/api/collector/pickup-requests/{{pickup_id}}/status
Body: { "status": "completed" }
Expected: ✅ 200 OK
```

---

### Workflow 3: Bidding Process

**Goal:** Admin creates round → Company bids → Admin approves

**Step 1: Login as Admin**

```
POST {{base_url}}/login
Body: { "email": "admin@ecocycle.com", "password": "admin123" }
```

**Step 2: Create Bidding Round**

```
POST {{base_url}}/api/bidding/rounds
Body:
{
  "wasteCategory": "Plastic",
  "quantity": 1000,
  "unit": "kg",
  "startingBid": 20000,
  "endTime": "2025-10-30 18:00:00"
}
Expected: ✅ 201 Created
Note: round_id saved automatically
```

**Step 3: Get Round Details**

```
GET {{base_url}}/api/bidding/rounds/{{round_id}}
Expected: ✅ 200 OK, round details
```

**Step 4: Switch to Company**

```
POST {{base_url}}/logout
POST {{base_url}}/login
Body: { "email": "company@ecocycle.com", "password": "company123" }
```

**Step 5: Company Places Bid**

```
POST {{base_url}}/api/company/bids
Body:
{
  "roundId": "{{round_id}}",
  "bidPerUnit": 22,
  "wasteAmount": 1000
}
Expected: ✅ 201 Created
Note: bid_id saved automatically
```

**Step 6: Company Updates Bid (Higher)**

```
PUT {{base_url}}/api/company/bids/{{bid_id}}
Body:
{
  "bidPerUnit": 25,
  "wasteAmount": 1000
}
Expected: ✅ 200 OK
```

**Step 7: Switch Back to Admin**

```
POST {{base_url}}/logout
POST {{base_url}}/login
Body: { "email": "admin@ecocycle.com", "password": "admin123" }
```

**Step 8: Admin Approves Round**

```
POST {{base_url}}/api/bidding/approve
Body:
{
  "biddingId": "{{round_id}}",
  "companyId": 10
}
Expected: ✅ 200 OK
```

---

## Advanced Features

### 1. Collection-Level Tests

Add to **Collection → Tests** tab:

```javascript
// Global test for all requests
pm.test("Response time is less than 2000ms", function () {
  pm.expect(pm.response.responseTime).to.be.below(2000);
});

pm.test("Response has proper content-type", function () {
  pm.response.to.have.header("Content-Type");
});

// Log response for debugging
console.log("Response:", pm.response.json());
```

### 2. Request-Level Tests

Example for **Create Vehicle** request:

```javascript
// Status code check
pm.test("Status code is 201", function () {
  pm.response.to.have.status(201);
});

// Response structure validation
pm.test("Response has vehicle object", function () {
  var jsonData = pm.response.json();
  pm.expect(jsonData).to.have.property("vehicle");
  pm.expect(jsonData.vehicle).to.have.property("id");
  pm.expect(jsonData.vehicle).to.have.property("plate_number");
});

// Save vehicle ID for later use
var jsonData = pm.response.json();
if (jsonData.vehicle && jsonData.vehicle.id) {
  pm.environment.set("vehicle_id", jsonData.vehicle.id);
  console.log("✅ Vehicle ID saved:", jsonData.vehicle.id);
}

// Validate plate number format
pm.test("Plate number has correct format", function () {
  var plateNumber = jsonData.vehicle.plate_number;
  pm.expect(plateNumber).to.match(/^[A-Z]{3}-\d{4}$/);
});
```

### 3. Pre-request Scripts

**Auto-generate Dynamic Data:**

```javascript
// Generate random plate number
const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const randomLetters = Array.from(
  { length: 3 },
  () => letters[Math.floor(Math.random() * letters.length)]
).join("");
const randomNumbers = Math.floor(1000 + Math.random() * 9000);
pm.environment.set("random_plate", `${randomLetters}-${randomNumbers}`);

// Generate future date for endTime
const futureDate = new Date();
futureDate.setDate(futureDate.getDate() + 7);
pm.environment.set(
  "future_date",
  futureDate.toISOString().slice(0, 19).replace("T", " ")
);

// Log for debugging
console.log("Generated plate:", pm.environment.get("random_plate"));
console.log("Future date:", pm.environment.get("future_date"));
```

**Then use in request body:**

```json
{
  "plateNumber": "{{random_plate}}",
  "endTime": "{{future_date}}"
}
```

### 4. Collection Runner

**Run All Tests Automatically:**

1. **Click Collection** → **Run**
2. **Select Requests** to run (or select all)
3. **Choose Environment**: ecoCycle - Local
4. **Set Iterations**: 1 (or more for load testing)
5. **Add Delay**: 500ms between requests
6. **Click Run**

**Suggested Test Order:**

1. Authentication → Login - Admin
2. Admin - Vehicles → List All Vehicles
3. Admin - Vehicles → Create Vehicle
4. Admin - Vehicles → Update Vehicle
5. Admin - Bidding → Create Round
6. Authentication → Login - Company
7. Company - Bids → Place Bid
8. Development → Health Check

### 5. Mock Servers

**Create Mock Server for Frontend Development:**

1. **Click Collection** → **Mocks**
2. **Create Mock Server**
3. **Name**: ecoCycle Mock API
4. **Save**
5. **Copy Mock URL**: `https://xxxxx.mock.pstmn.io`

Use in frontend:

```javascript
const API_URL =
  process.env.NODE_ENV === "production"
    ? "https://api.ecocycle.com"
    : "https://xxxxx.mock.pstmn.io";
```

### 6. Documentation Generation

**Generate API Docs from Collection:**

1. **Click Collection** → **View Documentation**
2. **Click Publish**
3. **Customize** styling and descriptions
4. **Publish** to get shareable URL

Example: `https://documenter.getpostman.com/view/xxxxx`

---

## Common Issues & Solutions

### Issue 1: 401 Unauthorized Error

**Symptoms:**

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Solutions:**

**A. Check Session Cookie**

```
1. Click Cookies button (below Send)
2. Verify PHPSESSID exists
3. If missing, login again
```

**B. Enable Cookie Jar**

```
Settings → General →
✅ Enable "Automatically store cookies"
✅ Enable "Send cookies with requests"
```

**C. Manual Cookie Header**

```
Headers tab:
Cookie: PHPSESSID={{session_id}}
```

**D. Clear Old Cookies**

```
1. Cookies → Manage Cookies
2. Delete all localhost cookies
3. Login again
```

---

### Issue 2: 403 Forbidden Error

**Symptoms:**

```json
{
  "success": false,
  "message": "Forbidden"
}
```

**Cause:** Wrong user role for endpoint.

**Solution:**

```
1. Check API documentation for required role
2. Logout current user
3. Login with correct role:
   - Admin: admin@ecocycle.com
   - Customer: customer@ecocycle.com
   - Company: company@ecocycle.com
   - Collector: collector@ecocycle.com
```

---

### Issue 3: CSRF Token Error

**Symptoms:**

```json
{
  "success": false,
  "message": "CSRF token mismatch"
}
```

**Solution:**

**Method 1: Disable CSRF for Testing**

In `src/Middleware/CsrfMiddleware.php`:

```php
// Temporarily disable for API testing
if ($request->getPathInfo() === '/api/test') {
    return $next($request);
}
```

**Method 2: Extract CSRF Token**

Add to Login Tests:

```javascript
var jsonData = pm.response.json();
if (jsonData.csrf_token) {
  pm.environment.set("csrf_token", jsonData.csrf_token);
}
```

Add to Request Headers:

```
X-CSRF-Token: {{csrf_token}}
```

---

### Issue 4: Variables Not Auto-Populating

**Symptoms:** `{{vehicle_id}}` shows as literal text in URL.

**Solutions:**

**A. Select Correct Environment**

```
Top-right dropdown → Select "ecoCycle - Local"
```

**B. Check Test Scripts**

```javascript
// Add to Tests tab of Create Vehicle:
pm.test("Save vehicle ID", function () {
  var jsonData = pm.response.json();
  pm.environment.set("vehicle_id", jsonData.vehicle.id);
});
```

**C. Manual Variable Set**

```
1. Go to Environments
2. Click "ecoCycle - Local"
3. Manually paste value in Current Value column
```

---

### Issue 5: Connection Refused

**Symptoms:**

```
Error: connect ECONNREFUSED 127.0.0.1:80
```

**Solutions:**

**A. Start XAMPP Server**

```bash
# Start Apache
sudo /Applications/XAMPP/xamppfiles/xampp startapache

# Or use XAMPP Control Panel
```

**B. Verify Server Running**

```bash
curl http://localhost
# Should return HTML or JSON response
```

**C. Check Port Configuration**

```
If using different port (e.g., 8080):
Environment → base_url → http://localhost:8080
```

---

### Issue 6: SSL Certificate Error

**Symptoms:**

```
Error: self signed certificate
```

**Solution:**

```
Settings → General →
❌ Disable "SSL certificate verification"
(Only for local development!)
```

---

## Best Practices

### 1. Organize Collections

**Use Folders:**

```
ecoCycle API Collection/
├── Authentication/
├── Admin - Vehicles/
├── Admin - Bidding/
├── Customer - Pickups/
├── Collector - Status/
├── Company - Bids/
└── Development & Debug/
```

### 2. Naming Conventions

**Good Names:**

- ✅ `List All Vehicles`
- ✅ `Create Pickup Request`
- ✅ `Update Bid - Increase Amount`

**Bad Names:**

- ❌ `Test 1`
- ❌ `API Call`
- ❌ `Request`

### 3. Add Descriptions

**Request Description Example:**

```
Description:
Creates a new vehicle in the system with maintenance schedule.

Requirements:
- Admin role required
- Plate number format: ABC-1234
- Valid vehicle type (Pickup Truck, Small Truck, Large Truck)

Returns:
- 201 Created with vehicle object
- Auto-saves vehicle_id to environment
```

### 4. Use Environments

**Create Multiple Environments:**

- `ecoCycle - Local` (http://localhost)
- `ecoCycle - Staging` (https://staging.ecocycle.com)
- `ecoCycle - Production` (https://api.ecocycle.com)

### 5. Version Control

**Export Collection Regularly:**

```
Collection → Export → Collection v2.1 → Save
Commit to Git: postman_collection.json
```

### 6. Test Coverage

**Ensure Tests For:**

- ✅ Status codes
- ✅ Response structure
- ✅ Required fields present
- ✅ Data types correct
- ✅ Business logic validation

### 7. Documentation

**Keep Updated:**

```
1. Update postman_collection.json
2. Update API_DOCUMENTATION.md
3. Update POSTMAN_TESTING_GUIDE.md
4. Commit all changes together
```

---

## Quick Reference

### Essential Keyboard Shortcuts

| Action            | Shortcut (Mac) | Shortcut (Windows) |
| ----------------- | -------------- | ------------------ |
| Send Request      | `⌘ + Enter`    | `Ctrl + Enter`     |
| Save Request      | `⌘ + S`        | `Ctrl + S`         |
| Search Collection | `⌘ + F`        | `Ctrl + F`         |
| New Request       | `⌘ + N`        | `Ctrl + N`         |
| Open Console      | `⌘ + ⌥ + C`    | `Ctrl + Alt + C`   |
| Format JSON       | `⌘ + B`        | `Ctrl + B`         |

### Test Credentials

| Role      | Email                  | Password     |
| --------- | ---------------------- | ------------ |
| Admin     | admin@ecocycle.com     | admin123     |
| Customer  | customer@ecocycle.com  | customer123  |
| Collector | collector@ecocycle.com | collector123 |
| Company   | company@ecocycle.com   | company123   |

### Useful Console Commands

```javascript
// View all environment variables
console.log(pm.environment.toObject());

// View specific variable
console.log("Vehicle ID:", pm.environment.get("vehicle_id"));

// View response
console.log("Response:", pm.response.json());

// View headers
console.log("Headers:", pm.response.headers);

// View cookies
pm.cookies.jar().getAll(pm.request.url, (error, cookies) => {
  console.log("Cookies:", cookies);
});
```

---

## Next Steps

1. ✅ Import collection to Postman
2. ✅ Create local environment
3. ✅ Start XAMPP server
4. ✅ Seed database with demo data
5. ✅ Run Authentication → Login - Admin
6. ✅ Test all workflows
7. ✅ Set up automated testing
8. ✅ Share collection with team

---

## Additional Resources

- **API Documentation:** [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
- **Framework Docs:** [FRAMEWORK_DOCUMENTATION.md](../FRAMEWORK_DOCUMENTATION.md)
- **Postman Learning:** https://learning.postman.com/
- **Repository:** https://github.com/Dinil-Thilakarathne/ecoCycle

---

**Happy Testing! 🚀**

If you encounter any issues not covered here, please:

1. Check the console (⌘ + ⌥ + C)
2. Review server logs
3. Open an issue on GitHub
4. Contact: support@ecocycle.com
