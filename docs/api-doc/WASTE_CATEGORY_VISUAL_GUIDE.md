# 🎓 Waste Category Management API - Visual Guide

## API Request Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│                   CLIENT REQUEST                                │
│                                                                  │
│  POST /api/waste-categories                                     │
│  Headers: Content-Type: application/json                        │
│           X-CSRF-Token: xxxxx                                   │
│  Body: { name, description, basePrice }                         │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│              MIDDLEWARE CHAIN (Authentication)                  │
│                                                                  │
│  1. AuthMiddleware                                              │
│     └─ Check: Session exists?                                  │
│        ├─ Yes → Continue                                       │
│        └─ No  → Return 401 Unauthorized                        │
│                                                                  │
│  2. AdminOnly Middleware                                        │
│     └─ Check: User role == 'admin'?                            │
│        ├─ Yes → Continue                                       │
│        └─ No  → Return 403 Forbidden                           │
│                                                                  │
│  3. CSRF Middleware                                             │
│     └─ Check: X-CSRF-Token matches session?                    │
│        ├─ Yes → Continue                                       │
│        └─ No  → Return 403 Forbidden                           │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│           ROUTE HANDLER (WasteManagementController)             │
│                                                                  │
│  1. Parse JSON request body                                     │
│     └─ Extract: name, description, basePrice                   │
│                                                                  │
│  2. Validate Input                                              │
│     ├─ name: Required, 1-100 chars                             │
│     ├─ description: Required, 10-500 chars                     │
│     ├─ basePrice: Required, > 0                                │
│     └─ All valid? → Continue                                   │
│        └─ Invalid? → Return 422 + error details                │
│                                                                  │
│  3. Try to Create in Database                                   │
│     ├─ Call: WasteCategory::create($data)                      │
│     ├─ Set: created_at, updated_at timestamps                  │
│     └─ Get: newly created record with ID                       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│               DATABASE LAYER (Model)                            │
│                                                                  │
│  INSERT INTO waste_categories (                                 │
│    name, description, basePrice,                               │
│    created_at, updated_at                                      │
│  ) VALUES (?, ?, ?, NOW(), NOW())                              │
│                                                                  │
│  ✓ Record inserted successfully                                │
│  └─ Return: New record object                                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│              FORMAT RESPONSE (JSON)                             │
│                                                                  │
│  {                                                              │
│    "message": "Category created",                              │
│    "data": {                                                    │
│      "id": 1,                                                   │
│      "name": "Plastic",                                         │
│      "description": "...",                                      │
│      "basePrice": 50.00,                                        │
│      "created_at": "2025-11-29 14:22:00",                       │
│      "updated_at": "2025-11-29 14:22:00"                        │
│    }                                                            │
│  }                                                              │
│                                                                  │
│  Status Code: 201 Created                                       │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│            RESPONSE SENT TO CLIENT                              │
│                                                                  │
│  HTTP/1.1 201 Created                                           │
│  Content-Type: application/json                                 │
│  Set-Cookie: session=xxxxx                                     │
│                                                                  │
│  { "message": "...", "data": { ... } }                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## Endpoints Overview Diagram

```
                    WASTE CATEGORY API
                           |
                ┌──────────┼──────────┐
                |          |          |
             GET ALL      CREATE      |
            (index)       (store)     |
                |          |          |
                |          |          |
    /api/waste-categories (POST, GET)
                |          |          |
                └──────────┼──────────┘
                           |
                ┌──────────┴──────────┬──────────┐
                |                    |          |
              GET             UPDATE  |       DELETE
            (show)          (update) |       (destroy)
                |                    |          |
    /api/waste-categories/{id}
              (GET, PUT, DELETE)
                |
                |
    /api/waste-categories/pricing
                |
              GET
            (pricing)

AUTHENTICATION REQUIRED: ✅ ALL ENDPOINTS
ADMIN ROLE REQUIRED:     ✅ ALL ENDPOINTS
CSRF TOKEN REQUIRED:     ✅ POST, PUT, DELETE
```

---

## Data Flow - Create Category

```
User Interface
     ↓
[Form with name, description, basePrice]
     ↓
JavaScript/Frontend sends POST request
     ↓
HTTP Request (with session cookie + CSRF token)
     ↓
┌─────────────────────────────────────┐
│  ECO CYCLE SERVER                   │
│  ┌─────────────────────────────────┐│
│  │ Router                          ││
│  │ POST /api/waste-categories      ││
│  │  ↓ Match route                  ││
│  │ Call WasteManagementController  ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Middleware                      ││
│  │ 1. Auth ✓                       ││
│  │ 2. Admin ✓                      ││
│  │ 3. CSRF ✓                       ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Controller::store()             ││
│  │ 1. Parse JSON ✓                 ││
│  │ 2. Validate ✓                   ││
│  │ 3. Create ✓                     ││
│  │ 4. Return 201 ✓                 ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Database                        ││
│  │ INSERT waste_categories         ││
│  │ RETURNING id, ...               ││
│  └─────────────────────────────────┘│
└─────────────────────────────────────┘
     ↓
JSON Response: { message, data }
     ↓
Frontend processes success
     ↓
UI updated with new category
```

---

## Error Handling Flow

```
┌─────────────────────────────────────────────────────┐
│            ERROR DETECTION POINT                    │
└─────────────────────────────────────────────────────┘
                     ↓
         ┌───────────┴───────────┐
         |                       |
    VALIDATION ERROR        DATABASE ERROR
         |                       |
         ↓                       ↓
    ┌────────────┐         ┌──────────────┐
    │ 422        │         │ 500          │
    │ Unproc.    │         │ Server Error │
    │            │         │              │
    │ errors: {  │         │ detail:      │
    │  field:    │         │  "DB failed" │
    │  "message" │         │              │
    │ }          │         └──────────────┘
    └────────────┘

ROUTE/AUTHORIZATION ERROR
        ↓
    ┌────────────┐
    │ 401/403    │
    │ Unauthorized
    │ Forbidden  │
    └────────────┘

RESOURCE NOT FOUND
        ↓
    ┌────────────┐
    │ 404        │
    │ Not Found  │
    └────────────┘
```

---

## Validation Rules Diagram

```
                    INPUT DATA
                        ↓
        ┌───────────────┬───────────────┐
        |               |               |
     NAME          DESCRIPTION      BASEPI
        |               |               |
        ↓               ↓               ↓
    ┌───────┐    ┌─────────────┐  ┌─────────┐
    │ Empty?│    │ Empty?      │  │ <= 0?   │
    │ No→OK │    │ No→OK       │  │ No→OK   │
    │ Yes↓  │    │ Yes↓        │  │ Yes↓    │
    │ ERROR │    │ ERROR       │  │ ERROR   │
    └───────┘    └─────────────┘  └─────────┘
        ↓               ↓               ↓
    ┌───────────────────────────────────────┐
    │  ANY ERROR?                           │
    │  YES → Return 422 + error details    │
    │  NO  → Continue to CREATE            │
    └───────────────────────────────────────┘
```

---

## Request-Response Sequence

```
REQUEST → RESPONSE SEQUENCE

1. CREATE
   ─────────────────────────────────────────────
   POST /api/waste-categories
   {name, description, basePrice}
   ───→ 201 Created
   ←─── {message, data}

2. LIST
   ─────────────────────────────────────────────
   GET /api/waste-categories
   ───→ 200 OK
   ←─── {data: [...]}

3. GET
   ─────────────────────────────────────────────
   GET /api/waste-categories/1
   ───→ 200 OK
   ←─── {data: {...}}

4. UPDATE
   ─────────────────────────────────────────────
   PUT /api/waste-categories/1
   {basePrice: 55}
   ───→ 200 OK
   ←─── {message: "Updated"}

5. DELETE
   ─────────────────────────────────────────────
   DELETE /api/waste-categories/1
   ───→ 200 OK
   ←─── {message: "Deleted"}

6. PRICING
   ─────────────────────────────────────────────
   GET /api/waste-categories/pricing
   ───→ 200 OK
   ←─── {data: [{...pricing_tiers}]}
```

---

## Authentication Flow

```
┌──────────────────────────────────────────────────┐
│             FIRST TIME (Authentication)          │
│                                                  │
│  1. User submits login credentials               │
│  2. Server validates credentials                 │
│  3. Server creates session                       │
│  4. Session stored in database/memory            │
│  5. Session cookie sent to browser               │
│                                                  │
│     Response includes:                           │
│     Set-Cookie: session_id=xxxxx; Path=/; ...   │
└──────────────────────────────────────────────────┘
                     ↓
┌──────────────────────────────────────────────────┐
│          SUBSEQUENT REQUESTS (Authenticated)     │
│                                                  │
│  Browser automatically includes cookie:          │
│  Cookie: session_id=xxxxx                        │
│                                                  │
│  Server checks:                                  │
│  1. Session exists? YES → Continue              │
│                    NO  → 401 Unauthorized       │
│                                                  │
│  2. User role check? Admin? YES → Allow         │
│                              NO  → 403          │
│                                                  │
│  3. CSRF token valid? YES → Allow               │
│                       NO  → 403                 │
│                                                  │
│  Request proceeds to handler                     │
└──────────────────────────────────────────────────┘
```

---

## Database Schema Relationship

```
┌─────────────────────────────────────────────────────┐
│          WASTE_CATEGORIES TABLE                     │
│                                                     │
│  id (PK)  ──→ Auto increment, Primary Key         │
│  name     ──→ VARCHAR(100), UNIQUE, NOT NULL      │
│  description ──→ TEXT, NOT NULL                   │
│  basePrice ──→ DECIMAL(10,2), NOT NULL            │
│  category_icon ──→ VARCHAR(10), NULLABLE          │
│  hazardous ──→ BOOLEAN DEFAULT false              │
│  created_at ──→ TIMESTAMP DEFAULT NOW()           │
│  updated_at ──→ TIMESTAMP DEFAULT NOW()           │
│                                                     │
│  Indexes:                                           │
│  - idx_name (on name column)                       │
│  - idx_hazardous (on hazardous column)             │
│                                                     │
│  Relations:                                         │
│  ↓ Referenced by BIDDING_ROUNDS.waste_category_id  │
│  ↓ Referenced by PICKUP_REQUESTS.waste_category_id │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## File Organization

```
ecoCycle/
│
├── src/
│   ├── Controllers/
│   │   └── Api/
│   │       └── WasteManagementController.php ◄─ HANDLER
│   │
│   └── Models/
│       └── WasteCategory.php ◄─ DATABASE LAYER
│
├── config/
│   └── routes.php ◄─ ROUTING (lines 390-417)
│
├── database/
│   └── postgresql/
│       └── init/
│           └── waste_categories_table.sql ◄─ SCHEMA
│
├── docs/
│   └── api-doc/
│       ├── API_DOCUMENTATION.md ◄─ FULL DOCS (UPDATED)
│       ├── WASTE_CATEGORY_API_SUMMARY.md ◄─ OVERVIEW
│       ├── WASTE_CATEGORY_QUICK_REFERENCE.md ◄─ QUICK REF
│       ├── WASTE_CATEGORY_IMPLEMENTATION.md ◄─ ARCHITECTURE
│       └── DOCUMENTATION_INDEX.md ◄─ NAVIGATION
│
└── verify-waste-api.sh ◄─ VERIFICATION SCRIPT
```

---

## Response Status Code Diagram

```
                    API REQUEST
                        ↓
            ┌───────────────────────┐
            │   Middleware Checks   │
            └───────────────────────┘
                   ↓ ↓ ↓
        ┌──────────┼─┼─┼──────────┐
        |          | | |          |
        ↓          ↓ ↓ ↓          ↓
    Not Auth   Not Admin CSRF    Success
       401       403   403       ↓
                                Handler
                                ↓
                        ┌───────────────┐
                        │  Validation?  │
                        └───────────────┘
                          ↓       ↓
                        PASS    FAIL
                        ↓       ↓
                    Handler   422 Error
                      ↓
                ┌─────────────────┐
                │ Database Op     │
                └─────────────────┘
                      ↓    ↓
                   Success Error
                      ↓    ↓
                   201/200 500
```

---

## Success Criteria Checklist

```
✅ All endpoints defined in routes.php
✅ Controller implements all methods
✅ Model handles database operations
✅ Authentication middleware applied
✅ Admin role check applied
✅ CSRF protection on mutations
✅ Input validation implemented
✅ Error handling in place
✅ Consistent response format
✅ Timestamps tracked (created_at, updated_at)
✅ API documentation complete
✅ Quick reference guide created
✅ Implementation guide created
✅ cURL examples provided
✅ Postman collection template included
✅ Verification script created
✅ Database schema defined
✅ Security best practices followed
✅ Ready for production deployment
```

---

**Visual Guide Complete! 📊**

This diagram set provides a complete picture of:
- Request lifecycle
- Endpoint structure
- Error handling
- Authentication flow
- Data validation
- Response codes
- File organization

Reference these whenever you need a quick visual understanding of how the API works!
