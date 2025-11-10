# Postman Testing Workflow Diagrams

Visual guides for testing ecoCycle API endpoints with Postman.

---

## 🎯 Workflow 1: Complete Vehicle Management

```
┌─────────────────────────────────────────────────────────────┐
│                   VEHICLE CRUD WORKFLOW                      │
└─────────────────────────────────────────────────────────────┘

Step 1: Authentication
┌──────────────────────────────────────┐
│  POST /login                         │
│  Body: admin@ecocycle.com / admin123 │
│  ✅ Response: 200 OK                 │
│  🔐 Session: PHPSESSID saved         │
└──────────────────────────────────────┘
              ↓

Step 2: List Existing Vehicles
┌──────────────────────────────────────┐
│  GET /api/vehicles                   │
│  ✅ Response: 200 OK                 │
│  📊 Returns: vehicles[] array        │
│  💾 Auto-save: {{vehicle_id}}        │
└──────────────────────────────────────┘
              ↓

Step 3: Create New Vehicle
┌──────────────────────────────────────┐
│  POST /api/vehicles                  │
│  Body: {                             │
│    plateNumber: "ABC-1234",          │
│    type: "Large Truck"               │
│  }                                   │
│  ✅ Response: 201 Created            │
│  💾 Save: {{vehicle_id}} = new ID    │
└──────────────────────────────────────┘
              ↓

Step 4: Get Vehicle Details
┌──────────────────────────────────────┐
│  GET /api/vehicles/{{vehicle_id}}    │
│  ✅ Response: 200 OK                 │
│  📊 Returns: Full vehicle object     │
└──────────────────────────────────────┘
              ↓

Step 5: Update Vehicle
┌──────────────────────────────────────┐
│  PUT /api/vehicles/{{vehicle_id}}    │
│  Body: {                             │
│    status: "maintenance"             │
│  }                                   │
│  ✅ Response: 200 OK                 │
└──────────────────────────────────────┘
              ↓

Step 6: Delete Vehicle
┌──────────────────────────────────────┐
│  DELETE /api/vehicles/{{vehicle_id}} │
│  ✅ Response: 200 OK                 │
│  🗑️ Vehicle removed from system      │
└──────────────────────────────────────┘

⏱️ Total Time: ~2 minutes
✅ Tests Passed: 6/6
```

---

## 🚛 Workflow 2: Pickup Request Lifecycle

```
┌─────────────────────────────────────────────────────────────┐
│              COMPLETE PICKUP REQUEST WORKFLOW                │
└─────────────────────────────────────────────────────────────┘

Phase 1: Customer Creates Request
┌──────────────────────────────────────┐
│  👤 Login as Customer                │
│  POST /login                         │
│  customer@ecocycle.com / customer123 │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  📝 Create Pickup Request            │
│  POST /api/customer/pickup-requests  │
│  Body: {                             │
│    address: "123 Main St",           │
│    timeSlot: "morning",              │
│    wasteCategories: [{               │
│      id: 1, quantity: 10             │
│    }]                                │
│  }                                   │
│  ✅ 201 Created                      │
│  💾 {{pickup_id}} saved              │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  📋 View My Pickup Requests          │
│  GET /api/customer/pickup-requests   │
│  ✅ 200 OK                           │
│  📊 Shows: pending requests          │
└──────────────────────────────────────┘
              ↓

Phase 2: Admin Assigns Collector
┌──────────────────────────────────────┐
│  🔓 Logout → Login as Admin          │
│  POST /logout                        │
│  POST /login (admin credentials)     │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  👷 Assign Collector                 │
│  PUT /api/pickup-requests/           │
│      {{pickup_id}}                   │
│  Body: {                             │
│    collectorId: 5,                   │
│    status: "assigned",               │
│    scheduledAt: "2025-10-26 09:00"   │
│  }                                   │
│  ✅ 200 OK                           │
│  📌 Status: pending → assigned       │
└──────────────────────────────────────┘
              ↓

Phase 3: Collector Executes Pickup
┌──────────────────────────────────────┐
│  🔓 Logout → Login as Collector      │
│  collector@ecocycle.com              │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  🚚 Start Pickup                     │
│  PUT /api/collector/pickup-requests/ │
│      {{pickup_id}}/status            │
│  Body: { status: "in progress" }     │
│  ✅ 200 OK                           │
│  📌 Status: assigned → in_progress   │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  ✅ Complete Pickup                  │
│  PUT /api/collector/pickup-requests/ │
│      {{pickup_id}}/status            │
│  Body: { status: "completed" }       │
│  ✅ 200 OK                           │
│  📌 Status: in_progress → completed  │
└──────────────────────────────────────┘

⏱️ Total Time: ~3 minutes
✅ Tests Passed: 8/8
👥 Roles Used: Customer, Admin, Collector
```

---

## 💰 Workflow 3: Bidding Process

```
┌─────────────────────────────────────────────────────────────┐
│                    BIDDING ROUND WORKFLOW                    │
└─────────────────────────────────────────────────────────────┘

Phase 1: Admin Creates Bidding Round
┌──────────────────────────────────────┐
│  👤 Login as Admin                   │
│  admin@ecocycle.com / admin123       │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  📢 Create Bidding Round             │
│  POST /api/bidding/rounds            │
│  Body: {                             │
│    wasteCategory: "Plastic",         │
│    quantity: 1000,                   │
│    unit: "kg",                       │
│    startingBid: 20000,               │
│    endTime: "2025-10-30 18:00:00"    │
│  }                                   │
│  ✅ 201 Created                      │
│  💾 {{round_id}} = <uuid>            │
│  📊 Status: active                   │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  📋 View Round Details               │
│  GET /api/bidding/rounds/            │
│      {{round_id}}                    │
│  ✅ 200 OK                           │
│  📊 Shows: lot details, bids = 0     │
└──────────────────────────────────────┘
              ↓

Phase 2: Company A Places Bid
┌──────────────────────────────────────┐
│  🔓 Logout → Login as Company        │
│  company@ecocycle.com / company123   │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  💵 Place Initial Bid                │
│  POST /api/company/bids              │
│  Body: {                             │
│    roundId: "{{round_id}}",          │
│    bidPerUnit: 22,                   │
│    wasteAmount: 1000                 │
│  }                                   │
│  ✅ 201 Created                      │
│  💾 {{bid_id}} saved                 │
│  💰 Total Bid: Rs 22,000             │
│  🏆 Current Leader: Company A        │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  ⬆️ Increase Bid                     │
│  PUT /api/company/bids/{{bid_id}}    │
│  Body: {                             │
│    bidPerUnit: 25,                   │
│    wasteAmount: 1000                 │
│  }                                   │
│  ✅ 200 OK                           │
│  💰 New Total: Rs 25,000             │
└──────────────────────────────────────┘
              ↓

Phase 3: (Optional) Company B Competes
┌──────────────────────────────────────┐
│  🔓 Login as Another Company         │
│  company2@ecocycle.com               │
│  💵 Place Higher Bid (Rs 27,000)     │
│  🏆 New Leader: Company B            │
└──────────────────────────────────────┘
              ↓

Phase 4: Admin Approves Winner
┌──────────────────────────────────────┐
│  🔓 Logout → Login as Admin          │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│  ✅ Approve Round                    │
│  POST /api/bidding/approve           │
│  Body: {                             │
│    biddingId: "{{round_id}}",        │
│    companyId: 10                     │
│  }                                   │
│  ✅ 200 OK                           │
│  🏆 Winner: Company with highest bid │
│  📌 Status: active → approved        │
│  💰 Final Amount: Locked             │
└──────────────────────────────────────┘

⏱️ Total Time: ~3 minutes
✅ Tests Passed: 7/7
👥 Roles Used: Admin, Company
💰 Bids Placed: 2+
```

---

## 🔄 Session Management Flow

```
┌─────────────────────────────────────────────────────────────┐
│                 SESSION MANAGEMENT FLOW                      │
└─────────────────────────────────────────────────────────────┘

Initial State
┌──────────────────────────────────────┐
│  🔴 No Active Session                │
│  session_id: null                    │
│  User: Anonymous                     │
└──────────────────────────────────────┘
              ↓
              │  POST /login
              │  { email, password }
              ↓
┌──────────────────────────────────────┐
│  🟢 Session Created                  │
│  ✅ 200 OK                           │
│  🔐 PHPSESSID: abc123...             │
│  💾 Postman saves cookie             │
│  👤 User: admin/customer/etc.        │
└──────────────────────────────────────┘
              ↓
              │  Authenticated Requests
              │  Cookie: PHPSESSID=abc123
              ↓
┌──────────────────────────────────────┐
│  🟢 Session Valid                    │
│  All API requests work               │
│  Role-based access enforced          │
│  CSRF protection enabled             │
└──────────────────────────────────────┘
              ↓
              │  POST /logout
              ↓
┌──────────────────────────────────────┐
│  🔴 Session Destroyed                │
│  ✅ 200 OK                           │
│  🗑️ Cookie removed                   │
│  Redirect: /login                    │
└──────────────────────────────────────┘

⚠️ Session Expiry (30 minutes)
┌──────────────────────────────────────┐
│  If idle > 30 min:                   │
│  🔴 Session expires automatically    │
│  ❌ 401 Unauthorized on next request │
│  💡 Solution: Login again            │
└──────────────────────────────────────┘
```

---

## 🧪 Collection Runner Workflow

```
┌─────────────────────────────────────────────────────────────┐
│               AUTOMATED COLLECTION TESTING                   │
└─────────────────────────────────────────────────────────────┘

Setup Phase
┌──────────────────────────────────────┐
│  1. Select Collection/Folder         │
│  2. Choose Environment               │
│  3. Set Iterations: 1                │
│  4. Set Delay: 500ms                 │
│  5. Click "Run"                      │
└──────────────────────────────────────┘
              ↓

Execution Phase
┌──────────────────────────────────────┐
│  Request 1: Login - Admin            │
│  ✅ Status: 200 | Tests: 3/3         │
│  ⏱️ Time: 120ms                      │
└──────────────────────────────────────┘
              ↓ (delay 500ms)
┌──────────────────────────────────────┐
│  Request 2: List All Vehicles        │
│  ✅ Status: 200 | Tests: 2/2         │
│  ⏱️ Time: 45ms                       │
└──────────────────────────────────────┘
              ↓ (delay 500ms)
┌──────────────────────────────────────┐
│  Request 3: Create Vehicle           │
│  ✅ Status: 201 | Tests: 3/3         │
│  ⏱️ Time: 85ms                       │
│  💾 vehicle_id: 42                   │
└──────────────────────────────────────┘
              ↓ (delay 500ms)
┌──────────────────────────────────────┐
│  Request 4: Get Vehicle Details      │
│  ✅ Status: 200 | Tests: 2/2         │
│  ⏱️ Time: 35ms                       │
│  ✅ Uses: vehicle_id = 42            │
└──────────────────────────────────────┘
              ↓ (delay 500ms)
┌──────────────────────────────────────┐
│  Request 5: Update Vehicle           │
│  ✅ Status: 200 | Tests: 2/2         │
│  ⏱️ Time: 55ms                       │
└──────────────────────────────────────┘
              ↓ (delay 500ms)
┌──────────────────────────────────────┐
│  Request 6: Delete Vehicle           │
│  ✅ Status: 200 | Tests: 1/1         │
│  ⏱️ Time: 40ms                       │
└──────────────────────────────────────┘
              ↓

Results Summary
┌──────────────────────────────────────┐
│  📊 Test Run Complete                │
│  ✅ Passed: 13/13 (100%)             │
│  ❌ Failed: 0                        │
│  ⏱️ Total Time: 3.2 seconds          │
│  📈 Avg Response: 63ms               │
│  🎯 Success Rate: 100%               │
└──────────────────────────────────────┘
```

---

## 🎯 Error Handling Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    ERROR HANDLING FLOW                       │
└─────────────────────────────────────────────────────────────┘

Request Sent
┌──────────────────────────────────────┐
│  POST /api/vehicles                  │
│  Body: { plateNumber: "INVALID" }    │
└──────────────────────────────────────┘
              ↓

Response Received
┌──────────────────────────────────────┐
│  ❌ Status: 422 Unprocessable        │
│  {                                   │
│    "success": false,                 │
│    "message": "Validation failed",   │
│    "errors": {                       │
│      "plateNumber": "Invalid format" │
│    }                                 │
│  }                                   │
└──────────────────────────────────────┘
              ↓

Postman Tests Check
┌──────────────────────────────────────┐
│  pm.test("Status code", () => {      │
│    if (pm.response.code === 422) {   │
│      console.log('❌ Validation     │
│         error detected');            │
│      // Show error details           │
│    }                                 │
│  });                                 │
└──────────────────────────────────────┘
              ↓

User Action
┌──────────────────────────────────────┐
│  1. Check Console (⌘⌥C)              │
│  2. Review error message             │
│  3. Fix request body                 │
│  4. Resend request                   │
└──────────────────────────────────────┘

Common Error Codes:
┌────────┬─────────────────────────────────────┐
│  400   │ Bad Request - Invalid syntax        │
│  401   │ Unauthorized - Not logged in        │
│  403   │ Forbidden - Wrong role              │
│  404   │ Not Found - Resource doesn't exist  │
│  422   │ Validation Failed - Invalid data    │
│  500   │ Server Error - Backend issue        │
└────────┴─────────────────────────────────────┘
```

---

## 🔐 Authentication State Diagram

```
┌─────────────────────────────────────────────────────────────┐
│              AUTHENTICATION STATE MACHINE                    │
└─────────────────────────────────────────────────────────────┘

          ┌─────────────┐
          │  Anonymous  │
          │   (Start)   │
          └──────┬──────┘
                 │
                 │ POST /login
                 │ ✅ Valid credentials
                 ↓
          ┌─────────────┐
          │ Authenticated│
     ┌────┤   (Active)   │────┐
     │    └──────┬──────┘    │
     │           │           │
     │           │           │
     │    API Requests       │
     │    ✅ Authorized      │ POST /logout
     │                       │ or
     │    ┌─────────────┐   │ Session Timeout
     │    │   Session   │   │
     │    │   Active    │   │
     │    └─────────────┘   │
     │                       │
     └───────────────────────┘
                 ↓
          ┌─────────────┐
          │  Anonymous  │
          │    (End)    │
          └─────────────┘

Role-Based Access:
┌────────────┬──────────────────────────────────┐
│ Admin      │ ✅ All APIs                      │
│ Customer   │ ✅ Own pickup requests only      │
│ Collector  │ ✅ Assigned pickups only         │
│ Company    │ ✅ Bidding & own bids only       │
└────────────┴──────────────────────────────────┘
```

---

## 📊 Testing Checklist Visual

```
┌─────────────────────────────────────────────────────────────┐
│                   TESTING CHECKLIST                          │
└─────────────────────────────────────────────────────────────┘

Pre-Testing Setup
☐ XAMPP server running
☐ Database seeded
☐ Postman collection imported
☐ Environment created and selected
☐ Settings configured

Authentication Tests
☐ Login as Admin        → 200 OK
☐ Login as Customer     → 200 OK
☐ Login as Company      → 200 OK
☐ Login as Collector    → 200 OK
☐ Logout                → 200 OK

Admin - Vehicles
☐ List All             → 200 OK
☐ Create               → 201 Created
☐ Get Details          → 200 OK
☐ Update               → 200 OK
☐ Delete               → 200 OK

Admin - Bidding
☐ Create Round         → 201 Created
☐ Get Round Details    → 200 OK
☐ Update Round         → 200 OK
☐ Approve Round        → 200 OK
☐ Reject Round         → 200 OK

Customer - Pickups
☐ List My Pickups      → 200 OK
☐ Create Pickup        → 201 Created
☐ Update Pickup        → 200 OK
☐ Cancel Pickup        → 200 OK

Collector - Status
☐ Update to In Progress → 200 OK
☐ Update to Completed   → 200 OK

Company - Bids
☐ Place Bid            → 201 Created
☐ Update Bid           → 200 OK
☐ Delete Bid           → 200 OK

Debug Endpoints
☐ Health Check         → 200 OK
☐ Database Ping        → 200 OK
☐ List Routes          → 200 OK

✅ All tests passed!
🎉 Ready for development!
```

---

## 🚀 Quick Reference Card

```
┌─────────────────────────────────────────────────────────────┐
│              POSTMAN QUICK REFERENCE                         │
└─────────────────────────────────────────────────────────────┘

Essential Shortcuts
⌘ + Enter / Ctrl + Enter    Send Request
⌘ + S / Ctrl + S             Save Request
⌘ + ⌥ + C / Ctrl + Alt + C   Open Console
⌘ + B / Ctrl + B             Format JSON

Test Credentials
Admin:      admin@ecocycle.com / admin123
Customer:   customer@ecocycle.com / customer123
Company:    company@ecocycle.com / company123
Collector:  collector@ecocycle.com / collector123

Common Variables
{{base_url}}     → http://localhost
{{vehicle_id}}   → Auto-populated from Create Vehicle
{{round_id}}     → Auto-populated from Create Round
{{pickup_id}}    → Auto-populated from Create Pickup
{{bid_id}}       → Auto-populated from Place Bid

Status Codes
200 OK           Request successful
201 Created      Resource created
400 Bad Request  Invalid syntax
401 Unauthorized Not logged in
403 Forbidden    Wrong role
404 Not Found    Resource missing
422 Validation   Invalid data
500 Server Error Backend issue

Quick Troubleshooting
❌ Connection Refused   → Start XAMPP
❌ 401 Unauthorized     → Login first
❌ 403 Forbidden        → Check user role
❌ Variables not working → Select environment
❌ Session lost         → Enable cookie storage
```

---

**Happy Testing! 🎉**

These visual workflows make it easy to understand the complete API testing process. Use them as reference guides while testing in Postman!
