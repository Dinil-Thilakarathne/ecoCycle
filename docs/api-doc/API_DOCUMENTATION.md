rou# ecoCycle API Documentation

**Version:** 1.0.0  
**Last Updated:** October 24, 2025  
**Base URL:** `http://localhost` (Development) | `https://your-domain.com` (Production)

---

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Error Handling](#error-handling)
5. [API Endpoints](#api-endpoints)
   - [Authentication APIs](#authentication-apis)
   - [Admin APIs](#admin-apis)
   - [Customer APIs](#customer-apis)
   - [Collector APIs](#collector-apis)
   - [Company APIs](#company-apis)

- [Payment APIs](#payment-apis)

6. [Testing Guide](#testing-guide)
7. [Future Development](#future-development)

---

## Introduction

The ecoCycle API is a RESTful API that provides endpoints for managing waste collection, bidding, and recycling operations. This documentation covers all available endpoints, request/response formats, and usage examples.

### Key Features

- 🔐 **Role-based Access Control**: Admin, Customer, Collector, Company
- 🔒 **CSRF Protection**: Enabled for state-changing operations
- 📊 **JSON Responses**: Consistent response structure
- ⚡ **RESTful Design**: Standard HTTP methods (GET, POST, PUT, DELETE)

### Tech Stack

- **Backend Framework**: Custom PHP Framework (ecoCycle)
- **Database**: PostgreSQL
- **Authentication**: Session-based with role middleware
- **Response Format**: JSON

---

## Authentication

### Session-Based Authentication

All API requests (except public endpoints) require authentication via session cookies.

**Login Required Headers:**

```http
Cookie: PHPSESSID=your_session_id
Content-Type: application/json
```

**CSRF Protection (for POST/PUT/DELETE):**

```http
X-CSRF-Token: your_csrf_token
```

### User Roles

| Role        | Description          | Access Level                    |
| ----------- | -------------------- | ------------------------------- |
| `admin`     | System administrator | Full access to all resources    |
| `customer`  | Household user       | Manage own pickup requests      |
| `collector` | Waste collector      | Update assigned pickup statuses |
| `company`   | Recycling company    | Place and manage bids           |

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": "Validation error message"
  }
}
```

### HTTP Status Codes

| Code | Meaning                                  |
| ---- | ---------------------------------------- |
| 200  | OK - Request successful                  |
| 201  | Created - Resource created successfully  |
| 400  | Bad Request - Invalid request parameters |
| 401  | Unauthorized - Authentication required   |
| 403  | Forbidden - Insufficient permissions     |
| 404  | Not Found - Resource not found           |
| 422  | Unprocessable Entity - Validation failed |
| 500  | Internal Server Error - Server error     |

---

## Error Handling

### Common Error Scenarios

**1. Validation Error (422)**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "quantity": "Quantity must be greater than zero.",
    "endTime": "End time must be in the future."
  }
}
```

**2. Authentication Error (401)**

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**3. Authorization Error (403)**

```json
{
  "success": false,
  "message": "Forbidden"
}
```

---

## API Endpoints

---

## Authentication APIs

### 1. Login

**Endpoint:** `POST /login`  
**Authentication:** Not required  
**Role:** Public

**Description:** Authenticate user and create session.

**Request Body:**

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "role": "customer"
  },
  "redirect": "/customer"
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123"
  }'
```

---

### 2. Register

**Endpoint:** `POST /register`  
**Authentication:** Not required  
**Role:** Public

**Description:** Register a new user account.

**Request Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "customer",
  "phone": "+94771234567",
  "address": "123 Main St, Colombo"
}
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Registration successful",
  "user": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```

---

### 3. Logout

**Endpoint:** `POST /logout`  
**Authentication:** Required  
**Role:** Any authenticated user

**Description:** End user session.

**Success Response (200):**

```json
{
  "success": true,
  "message": "Logged out successfully",
  "redirect": "/login"
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/logout \
  -H "Cookie: PHPSESSID=your_session_id"
```

---

## Admin APIs

### 1. List All Vehicles

**Endpoint:** `GET /api/vehicles`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Retrieve all vehicles in the system.

**Success Response (200):**

```json
{
  "vehicles": [
    {
      "id": 1,
      "plate_number": "ABC-1234",
      "type": "Large Truck",
      "capacity": 5000,
      "status": "available",
      "last_maintenance": "2025-10-01",
      "next_maintenance": "2026-01-01",
      "created_at": "2025-09-01 10:00:00"
    }
  ]
}
```

**cURL Example:**

```bash
curl -X GET http://localhost/api/vehicles \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"
```

**Testing with Postman:**

1. Set method to `GET`
2. URL: `http://localhost/api/vehicles`
3. Add Cookie header with your session ID
4. Send request

---

### 2. Get Vehicle Details

**Endpoint:** `GET /api/vehicles/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Retrieve details of a specific vehicle.

**URL Parameters:**

- `id` (integer, required) - Vehicle ID

**Success Response (200):**

```json
{
  "vehicle": {
    "id": 1,
    "plate_number": "ABC-1234",
    "type": "Large Truck",
    "capacity": 5000,
    "status": "available",
    "last_maintenance": "2025-10-01",
    "next_maintenance": "2026-01-01",
    "created_at": "2025-09-01 10:00:00",
    "updated_at": "2025-10-15 14:30:00"
  }
}
```

**cURL Example:**

```bash
curl -X GET http://localhost/api/vehicles/1 \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"
```

---

### 3. Create Vehicle

**Endpoint:** `POST /api/vehicles`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Register a new vehicle in the system.

**Request Body:**

```json
{
  "plateNumber": "DEF-5678",
  "type": "Pickup Truck",
  "lastMaintenance": "2025-10-20",
  "nextMaintenance": "2026-01-20"
}
```

**Field Specifications:**

- `plateNumber` (string, required) - Format: ABC-1234 (3 letters, hyphen, 4 digits)
- `type` (string, required) - Options: "Pickup Truck" (2000kg), "Small Truck" (3000kg), "Large Truck" (5000kg)
- `lastMaintenance` (date, optional) - Format: YYYY-MM-DD (cannot be future)
- `nextMaintenance` (date, optional) - Format: YYYY-MM-DD (cannot be past)

**Success Response (201):**

```json
{
  "message": "Vehicle created",
  "vehicle": {
    "id": 5,
    "plate_number": "DEF-5678",
    "type": "Pickup Truck",
    "capacity": 2000,
    "status": "available",
    "last_maintenance": "2025-10-20",
    "next_maintenance": "2026-01-20",
    "created_at": "2025-10-24 12:00:00"
  }
}
```

**Validation Errors:**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "plateNumber": "Plate number must follow the format ABC-1234.",
    "type": "Vehicle type is invalid.",
    "lastMaintenance": "Last maintenance date cannot be in the future."
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/vehicles \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "plateNumber": "DEF-5678",
    "type": "Pickup Truck",
    "lastMaintenance": "2025-10-20",
    "nextMaintenance": "2026-01-20"
  }'
```

---

### 4. Update Vehicle

**Endpoint:** `PUT /api/vehicles/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Update vehicle information.

**URL Parameters:**

- `id` (integer, required) - Vehicle ID

**Request Body (partial update supported):**

```json
{
  "status": "maintenance",
  "nextMaintenance": "2026-02-01"
}
```

**Valid Statuses:**

- `available` - Ready for assignments
- `in-use` - Currently assigned
- `maintenance` - Under maintenance
- `removed` - Decommissioned

**Success Response (200):**

```json
{
  "message": "Vehicle updated",
  "vehicle": {
    "id": 1,
    "plate_number": "ABC-1234",
    "type": "Large Truck",
    "capacity": 5000,
    "status": "maintenance",
    "next_maintenance": "2026-02-01",
    "updated_at": "2025-10-24 15:30:00"
  }
}
```

**cURL Example:**

```bash
curl -X PUT http://localhost/api/vehicles/1 \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "maintenance",
    "nextMaintenance": "2026-02-01"
  }'
```

---

### 5. Delete Vehicle

**Endpoint:** `DELETE /api/vehicles/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Permanently delete a vehicle from the system.

**URL Parameters:**

- `id` (integer, required) - Vehicle ID

**Success Response (200):**

```json
{
  "message": "Vehicle deleted",
  "vehicle": {
    "id": 5,
    "plate_number": "DEF-5678",
    "type": "Pickup Truck"
  }
}
```

**cURL Example:**

```bash
curl -X DELETE http://localhost/api/vehicles/5 \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"
```

---

### 6. Create Bidding Round

**Endpoint:** `POST /api/bidding/rounds`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Create a new bidding round for waste lots.

**Request Body:**

```json
{
  "lotId": "LOT-2025-001",
  "wasteCategory": "Plastic",
  "quantity": 500.5,
  "unit": "kg",
  "startingBid": 1000.0,
  "endTime": "2025-10-30 18:00:00"
}
```

**Field Specifications:**

- `lotId` (string, optional) - Auto-generated if not provided; max 64 chars
- `wasteCategory` (string, required) - Waste category name or use `wasteCategoryId`
- `wasteCategoryId` (integer, optional) - Direct category ID
- `quantity` (number, required) - Must be > 0
- `unit` (string, optional) - Default: "kg"; Options: kg, tons, tonnes, lb
- `startingBid` (number, required) - Minimum bid amount; >= 0
- `endTime` (datetime, required) - Format: YYYY-MM-DD HH:MM:SS; must be future

**Success Response (201):**

```json
{
  "success": true,
  "message": "Bidding round created successfully",
  "round": {
    "id": "uuid-here",
    "lot_id": "LOT-2025-001",
    "waste_category_id": 3,
    "wasteCategory": "Plastic",
    "quantity": 500.5,
    "unit": "kg",
    "starting_bid": 1000.0,
    "current_highest_bid": 0.0,
    "status": "active",
    "end_time": "2025-10-30 18:00:00",
    "created_at": "2025-10-24 10:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/bidding/rounds \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "wasteCategory": "Plastic",
    "quantity": 500.5,
    "unit": "kg",
    "startingBid": 1000,
    "endTime": "2025-10-30 18:00:00"
  }'
```

---

### 7. Get Bidding Round Details

**Endpoint:** `GET /api/bidding/rounds/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Retrieve details of a specific bidding round.

**URL Parameters:**

- `id` (string, required) - Bidding round UUID

**Success Response (200):**

```json
{
  "success": true,
  "round": {
    "id": "uuid-here",
    "lot_id": "LOT-2025-001",
    "wasteCategory": "Plastic",
    "quantity": 500.5,
    "unit": "kg",
    "starting_bid": 1000.0,
    "current_highest_bid": 1250.0,
    "leading_company_id": 10,
    "leading_company_name": "Green Recyclers Ltd",
    "status": "active",
    "end_time": "2025-10-30 18:00:00",
    "bids_count": 5
  }
}
```

**cURL Example:**

```bash
curl -X GET http://localhost/api/bidding/rounds/uuid-here \
  -H "Cookie: PHPSESSID=your_session_id"
```

---

### 8. Update Bidding Round

**Endpoint:** `PUT /api/bidding/rounds/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Update an active bidding round (only if no bids placed).

**URL Parameters:**

- `id` (string, required) - Bidding round UUID

**Request Body (only these fields allowed):**

```json
{
  "quantity": 600.0,
  "startingBid": 1200.0,
  "endTime": "2025-10-31 18:00:00"
}
```

**Restrictions:**

- Can only update `active` rounds
- Cannot update if bids have been placed
- Cannot update if leading company exists

**Success Response (200):**

```json
{
  "success": true,
  "message": "Bidding round updated",
  "round": {
    "id": "uuid-here",
    "quantity": 600.0,
    "starting_bid": 1200.0,
    "end_time": "2025-10-31 18:00:00",
    "updated_at": "2025-10-24 11:00:00"
  }
}
```

**Error Response (422):**

```json
{
  "success": false,
  "message": "Cannot edit bidding round: bids already placed or a leading company exists"
}
```

**cURL Example:**

```bash
curl -X PUT http://localhost/api/bidding/rounds/uuid-here \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 600,
    "endTime": "2025-10-31 18:00:00"
  }'
```

---

### 9. Cancel Bidding Round

**Endpoint:** `DELETE /api/bidding/rounds/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Cancel an active bidding round.

**URL Parameters:**

- `id` (string, required) - Bidding round UUID

**Request Body:**

```json
{
  "reason": "Insufficient waste collected"
}
```

**Restrictions:**

- Can only cancel `active` rounds
- Cannot cancel if bids have been placed
- Cannot cancel if leading company exists

**Success Response (200):**

```json
{
  "success": true,
  "message": "Bidding round cancelled",
  "round": {
    "id": "uuid-here",
    "status": "cancelled",
    "cancellation_reason": "Insufficient waste collected",
    "cancelled_at": "2025-10-24 12:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X DELETE http://localhost/api/bidding/rounds/uuid-here \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Insufficient waste collected"
  }'
```

---

### 10. Approve Bidding Round

**Endpoint:** `POST /api/bidding/approve`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Approve a bidding round and assign winning company.

**Request Body:**

```json
{
  "biddingId": "uuid-here",
  "companyId": 10
}
```

**Field Specifications:**

- `biddingId` (string, required) - Bidding round UUID
- `companyId` (integer, optional) - Winning company ID; if omitted, uses leading bidder

**Success Response (200):**

```json
{
  "success": true,
  "message": "Bidding round approved",
  "round": {
    "id": "uuid-here",
    "status": "approved",
    "winning_company_id": 10,
    "winning_company_name": "Green Recyclers Ltd",
    "final_bid_amount": 1500.0,
    "approved_at": "2025-10-24 13:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/bidding/approve \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "biddingId": "uuid-here",
    "companyId": 10
  }'
```

---

### 11. Reject Bidding Round

**Endpoint:** `POST /api/bidding/reject`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Reject a bidding round with reason.

**Request Body:**

```json
{
  "biddingId": "uuid-here",
  "reason": "Bids below expected value"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Bidding round rejected",
  "round": {
    "id": "uuid-here",
    "status": "rejected",
    "rejection_reason": "Bids below expected value",
    "rejected_at": "2025-10-24 14:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/bidding/reject \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "biddingId": "uuid-here",
    "reason": "Bids below expected value"
  }'
```

---

### 12. Update Pickup Request (Admin)

**Endpoint:** `PUT /api/pickup-requests/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Assign collector and update pickup request details.

**URL Parameters:**

- `id` (string, required) - Pickup request ID

**Request Body:**

```json
{
  "collectorId": 5,
  "status": "assigned",
  "scheduledAt": "2025-10-25 09:00:00",
  "timeSlot": "morning",
  "address": "Updated address"
}
```

**Valid Statuses:**

- `pending` - Awaiting assignment
- `assigned` - Collector assigned
- `in_progress` - Collection in progress
- `completed` - Collection completed
- `cancelled` - Request cancelled
- `confirmed` - Customer confirmed

**Success Response (200):**

```json
{
  "message": "Pickup request updated",
  "pickup": {
    "id": "pickup-uuid",
    "customer_id": 3,
    "collector_id": 5,
    "collector_name": "John Collector",
    "status": "assigned",
    "scheduled_at": "2025-10-25 09:00:00",
    "time_slot": "morning",
    "address": "Updated address",
    "updated_at": "2025-10-24 15:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X PUT http://localhost/api/pickup-requests/pickup-uuid \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "collectorId": 5,
    "status": "assigned",
    "scheduledAt": "2025-10-25 09:00:00"
  }'
```

---

## Customer APIs

### 1. List My Pickup Requests

**Endpoint:** `GET /api/customer/pickup-requests`  
**Authentication:** Required  
**Role:** Customer only

**Description:** Get all pickup requests for the authenticated customer.

**Query Parameters:**

- `status` (string, optional) - Filter by status (pending, assigned, completed, etc.)

**Success Response (200):**

```json
{
  "data": [
    {
      "id": "pickup-uuid-1",
      "customer_id": 3,
      "status": "pending",
      "address": "123 Main St, Colombo",
      "time_slot": "morning",
      "scheduled_at": "2025-10-25 09:00:00",
      "waste_categories": [
        {
          "id": 1,
          "name": "Plastic",
          "quantity": 5.5,
          "unit": "kg"
        }
      ],
      "created_at": "2025-10-24 10:00:00"
    }
  ]
}
```

**cURL Example:**

```bash
curl -X GET "http://localhost/api/customer/pickup-requests?status=pending" \
  -H "Cookie: PHPSESSID=your_session_id"
```

---

### 2. Create Pickup Request

**Endpoint:** `POST /api/customer/pickup-requests`  
**Authentication:** Required  
**Role:** Customer only

**Description:** Schedule a new waste pickup.

**Request Body:**

```json
{
  "address": "123 Main St, Colombo",
  "timeSlot": "morning",
  "scheduledAt": "2025-10-26 09:00:00",
  "wasteCategories": [
    {
      "id": 1,
      "quantity": 5.5,
      "unit": "kg"
    },
    {
      "id": 2,
      "quantity": 10,
      "unit": "kg"
    }
  ]
}
```

**Field Specifications:**

- `address` (string, required) - Pickup location
- `timeSlot` (string, required) - Preferred time (morning, afternoon, evening)
- `scheduledAt` (datetime, optional) - Specific pickup date/time
- `wasteCategories` (array, required) - List of waste types and quantities
  - `id` (integer, required) - Waste category ID
  - `quantity` (number, optional) - Amount of waste
  - `unit` (string, optional) - Unit of measurement

**Success Response (201):**

```json
{
  "message": "Pickup request created",
  "data": {
    "id": "pickup-uuid",
    "customer_id": 3,
    "address": "123 Main St, Colombo",
    "time_slot": "morning",
    "scheduled_at": "2025-10-26 09:00:00",
    "status": "pending",
    "waste_categories": [
      {
        "id": 1,
        "name": "Plastic",
        "quantity": 5.5,
        "unit": "kg"
      }
    ],
    "created_at": "2025-10-24 16:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/customer/pickup-requests \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "X-CSRF-Token: your_csrf_token" \
  -H "Content-Type: application/json" \
  -d '{
    "address": "123 Main St, Colombo",
    "timeSlot": "morning",
    "scheduledAt": "2025-10-26 09:00:00",
    "wasteCategories": [
      {"id": 1, "quantity": 5.5, "unit": "kg"}
    ]
  }'
```

---

### 3. Update Pickup Request

**Endpoint:** `PUT /api/customer/pickup-requests/{id}`  
**Authentication:** Required  
**Role:** Customer only

**Description:** Update own pickup request (before collection).

**URL Parameters:**

- `id` (string, required) - Pickup request ID

**Request Body (partial update):**

```json
{
  "address": "Updated address",
  "timeSlot": "afternoon",
  "scheduledAt": "2025-10-27 14:00:00"
}
```

**Restrictions:**

- Can only update own requests
- Cannot update completed or cancelled requests

**Success Response (200):**

```json
{
  "message": "Pickup request updated",
  "data": {
    "id": "pickup-uuid",
    "address": "Updated address",
    "time_slot": "afternoon",
    "scheduled_at": "2025-10-27 14:00:00",
    "updated_at": "2025-10-24 17:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X PUT http://localhost/api/customer/pickup-requests/pickup-uuid \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "X-CSRF-Token: your_csrf_token" \
  -H "Content-Type: application/json" \
  -d '{
    "timeSlot": "afternoon"
  }'
```

---

### 4. Cancel Pickup Request

**Endpoint:** `DELETE /api/customer/pickup-requests/{id}`  
**Authentication:** Required  
**Role:** Customer only

**Description:** Cancel own pickup request.

**URL Parameters:**

- `id` (string, required) - Pickup request ID

**Restrictions:**

- Can only cancel own requests
- Cannot cancel if already completed

**Success Response (200):**

```json
{
  "message": "Pickup request cancelled",
  "data": {
    "id": "pickup-uuid",
    "status": "cancelled",
    "cancelled_at": "2025-10-24 18:00:00"
  }
}
```

**cURL Example:**

```bash
curl -X DELETE http://localhost/api/customer/pickup-requests/pickup-uuid \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "X-CSRF-Token: your_csrf_token"
```

---

## Collector APIs

### 1. Update Pickup Status

**Endpoint:** `PUT /api/collector/pickup-requests/{id}/status`  
**Authentication:** Required  
**Role:** Collector only

**Description:** Update status of assigned pickup request.

**URL Parameters:**

- `id` (string, required) - Pickup request ID

**Request Body:**

```json
{
  "status": "in progress"
}
```

**Valid Status Transitions:**

- `assigned` → `in progress`
- `in progress` → `completed`

**Restrictions:**

- Can only update own assigned pickups
- Must follow status progression

**Success Response (200):**

```json
{
  "message": "Pickup status updated",
  "data": {
    "id": "pickup-uuid",
    "collector_id": 5,
    "status": "in_progress",
    "updated_at": "2025-10-24 19:00:00"
  }
}
```

**Error Response (422):**

```json
{
  "success": false,
  "message": "Status transition not allowed",
  "errors": {
    "status": "Cannot transition from assigned to completed."
  }
}
```

**cURL Example:**

```bash
curl -X PUT http://localhost/api/collector/pickup-requests/pickup-uuid/status \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "X-CSRF-Token: your_csrf_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in progress"
  }'
```

---

## Company APIs

### 1. Place Bid

**Endpoint:** `POST /api/company/bids`  
**Authentication:** Required  
**Role:** Company only

**Description:** Place a bid on an active waste lot.

**Request Body:**

```json
{
  "roundId": "bidding-round-uuid",
  "lotId": "LOT-2025-001",
  "wasteType": "Plastic",
  "bidPerUnit": 25.5,
  "wasteAmount": 500
}
```

**Field Specifications:**

- `roundId` (string, required) - Bidding round UUID (or use `lotId`)
- `lotId` (string, optional) - Alternative to roundId
- `wasteType` (string, optional) - Waste category name
- `bidPerUnit` (number, required) - Bid amount per unit; must meet minimum
- `wasteAmount` (number, required) - Quantity to bid for; cannot exceed lot quantity

**Minimum Bid Requirements (configurable):**

- Plastic: Rs. 20.00/kg
- Paper: Rs. 15.00/kg
- Glass: Rs. 10.00/kg
- Metal: Rs. 30.00/kg

**Success Response (201):**

```json
{
  "success": true,
  "message": "Bid placed successfully.",
  "bid": {
    "id": 15,
    "round_id": "bidding-round-uuid",
    "company_id": 10,
    "company_name": "Green Recyclers Ltd",
    "bid_amount": 12750.0,
    "bid_per_unit": 25.5,
    "waste_amount": 500,
    "created_at": "2025-10-24 20:00:00"
  },
  "round": {
    "id": "bidding-round-uuid",
    "current_highest_bid": 12750.0,
    "leading_company_id": 10
  },
  "lot": {
    "id": "bidding-round-uuid",
    "lotId": "LOT-2025-001",
    "category": "Plastic",
    "quantity": 500,
    "currentHighestBid": 12750.0
  }
}
```

**Validation Errors:**

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "bidPerUnit": "Bid must be at least Rs 20.00 for Plastic.",
    "wasteAmount": "Cannot bid for more than available lot quantity (500)."
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/company/bids \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "roundId": "bidding-round-uuid",
    "bidPerUnit": 25.50,
    "wasteAmount": 500
  }'
```

---

### 2. Update Bid

**Endpoint:** `PUT /api/company/bids/{id}`  
**Authentication:** Required  
**Role:** Company only

**Description:** Update own bid (increase amount).

**URL Parameters:**

- `id` (integer, required) - Bid ID

**Request Body:**

```json
{
  "bidPerUnit": 27.0,
  "wasteAmount": 500
}
```

**Restrictions:**

- Can only update own bids
- Bidding round must still be active
- Cannot decrease bid amount

**Success Response (200):**

```json
{
  "success": true,
  "message": "Bid updated successfully.",
  "bid": {
    "id": 15,
    "bid_amount": 13500.0,
    "bid_per_unit": 27.0,
    "updated_at": "2025-10-24 21:00:00"
  },
  "round": {
    "current_highest_bid": 13500.0
  }
}
```

**cURL Example:**

```bash
curl -X PUT http://localhost/api/company/bids/15 \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "bidPerUnit": 27.00,
    "wasteAmount": 500
  }'
```

---

### 3. Delete Bid

**Endpoint:** `DELETE /api/company/bids/{id}`  
**Authentication:** Required  
**Role:** Company only

**Description:** Withdraw own bid.

**URL Parameters:**

- `id` (integer, required) - Bid ID

**Restrictions:**

- Can only delete own bids
- Bidding round must still be active

**Success Response (200):**

```json
{
  "success": true,
  "message": "Bid deleted successfully.",
  "roundId": "bidding-round-uuid",
  "round": {
    "id": "bidding-round-uuid",
    "current_highest_bid": 12000.0
  }
}
```

**cURL Example:**

```bash
curl -X DELETE http://localhost/api/company/bids/15 \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"
```

---

## Testing Guide

### Prerequisites

1. **Running Server**: Ensure your development server is running
2. **Database**: PostgreSQL database properly seeded
3. **Authentication**: Valid user accounts for each role

### Testing Tools

#### 1. cURL (Command Line)

**Login and Get Session:**

```bash
# Login
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "email": "admin@ecocycle.com",
    "password": "admin123"
  }'

# Use session for subsequent requests
curl -X GET http://localhost/api/vehicles \
  -b cookies.txt
```

#### 2. Postman

**Setup:**

1. Import collection (create from this documentation)
2. Set environment variables:
   - `BASE_URL`: `http://localhost`
   - `SESSION_ID`: Your session cookie value

**Test Flow:**

1. **Login**: POST `/login` → Save session cookie
2. **Create Resource**: POST `/api/vehicles`
3. **List Resources**: GET `/api/vehicles`
4. **Update Resource**: PUT `/api/vehicles/1`
5. **Delete Resource**: DELETE `/api/vehicles/1`

**Postman Collection Example:**

```json
{
  "info": {
    "name": "ecoCycle API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Authentication",
      "item": [
        {
          "name": "Login",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Content-Type",
                "value": "application/json"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"email\": \"admin@ecocycle.com\",\n  \"password\": \"admin123\"\n}"
            },
            "url": {
              "raw": "{{BASE_URL}}/login",
              "host": ["{{BASE_URL}}"],
              "path": ["login"]
            }
          }
        }
      ]
    }
  ]
}
```

#### 3. HTTPie (User-Friendly CLI)

```bash
# Install
brew install httpie  # macOS
pip install httpie   # Python

# Usage
http POST localhost/login email=admin@ecocycle.com password=admin123
http --session=admin GET localhost/api/vehicles
```

#### 4. JavaScript/Fetch

```javascript
// Login
const login = async () => {
  const response = await fetch("http://localhost/login", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    credentials: "include", // Important for cookies
    body: JSON.stringify({
      email: "admin@ecocycle.com",
      password: "admin123",
    }),
  });
  return response.json();
};

// Create vehicle
const createVehicle = async () => {
  const response = await fetch("http://localhost/api/vehicles", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    credentials: "include",
    body: JSON.stringify({
      plateNumber: "XYZ-9876",
      type: "Large Truck",
    }),
  });
  return response.json();
};
```

### Test Scenarios

#### Scenario 1: Complete Pickup Workflow

```bash
# 1. Customer creates pickup request
curl -X POST http://localhost/api/customer/pickup-requests \
  -b customer-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "address": "456 Test St",
    "timeSlot": "morning",
    "wasteCategories": [{"id": 1, "quantity": 10}]
  }'

# 2. Admin assigns collector
curl -X PUT http://localhost/api/pickup-requests/pickup-uuid \
  -b admin-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "collectorId": 5,
    "status": "assigned"
  }'

# 3. Collector updates to in-progress
curl -X PUT http://localhost/api/collector/pickup-requests/pickup-uuid/status \
  -b collector-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"status": "in progress"}'

# 4. Collector completes pickup
curl -X PUT http://localhost/api/collector/pickup-requests/pickup-uuid/status \
  -b collector-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"status": "completed"}'
```

#### Scenario 2: Bidding Workflow

```bash
# 1. Admin creates bidding round
curl -X POST http://localhost/api/bidding/rounds \
  -b admin-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "wasteCategory": "Plastic",
    "quantity": 1000,
    "startingBid": 20000,
    "endTime": "2025-10-30 18:00:00"
  }'

# 2. Company A places bid
curl -X POST http://localhost/api/company/bids \
  -b company-a-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "roundId": "round-uuid",
    "bidPerUnit": 22,
    "wasteAmount": 1000
  }'

# 3. Company B places higher bid
curl -X POST http://localhost/api/company/bids \
  -b company-b-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "roundId": "round-uuid",
    "bidPerUnit": 25,
    "wasteAmount": 1000
  }'

# 4. Admin approves round
curl -X POST http://localhost/api/bidding/approve \
  -b admin-cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "biddingId": "round-uuid",
    "companyId": 11
  }'
```

### Common Issues & Solutions

**Issue 1: 401 Unauthorized**

- **Cause**: Missing or expired session
- **Solution**: Login again and ensure cookies are sent

**Issue 2: 403 Forbidden**

- **Cause**: Insufficient permissions
- **Solution**: Use account with correct role

**Issue 3: 422 Validation Error**

- **Cause**: Invalid input data
- **Solution**: Check error response for field-specific messages

**Issue 4: CSRF Token Error**

- **Cause**: Missing CSRF token for POST/PUT/DELETE
- **Solution**: Include `X-CSRF-Token` header

---

## Payment APIs

### 1. Record Payment (Admin)

**Endpoint:** `POST /api/payments`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Create a manual ledger entry for company payments or customer payouts. This is typically used by finance teams after an offline transfer is confirmed.

**Request Body:**

```json
{
  "recipientId": 42,
  "amount": 15000,
  "type": "payout",
  "status": "completed",
  "txnId": "TXN-2025-1101",
  "date": "2025-11-01 10:15:00",
  "gatewayResponse": {
    "method": "bank_transfer",
    "reference": "UB1234567"
  }
}
```

**Field Specs:**

| Field             | Type     | Required | Notes                                                                |
| ----------------- | -------- | -------- | -------------------------------------------------------------------- |
| `recipientId`     | integer  | ✅       | User receiving funds/owing invoice                                   |
| `amount`          | decimal  | ✅       | Must be > 0 (auto rounded to 2 decimals)                             |
| `type`            | string   | ✅       | `payment`, `payout`, or `refund` (default `payout`)                  |
| `status`          | string   | ✅       | `pending`, `processing`, `completed`, `failed` (default `completed`) |
| `txnId`           | string   | ❌       | External transaction reference                                       |
| `date`            | datetime | ❌       | Defaults to current timestamp                                        |
| `gatewayResponse` | object   | ❌       | Stored as JSON for auditing                                          |

**Success Response (201):**

```json
{
  "message": "Payment recorded",
  "data": {
    "id": "PAY-8F3ACD12",
    "txnId": "TXN-2025-1101",
    "type": "payout",
    "amount": 15000,
    "recipient": "John Collector",
    "recipientName": "John Collector",
    "recipientId": 42,
    "status": "completed",
    "date": "2025-11-01 10:15:00",
    "gatewayResponse": {
      "method": "bank_transfer",
      "reference": "UB1234567"
    }
  }
}
```

**cURL Example:**

```bash
curl -X POST http://localhost/api/payments \\
  -b admin-cookies.txt \\
  -H "Content-Type: application/json" \\
  -H "X-CSRF-Token: $(csrf_token)" \\
  -d '{
    "recipientId": 42,
    "amount": 15000,
    "type": "payout",
    "status": "completed"
  }'
```

---

### 2. Get Payment Details (Admin)

**Endpoint:** `GET /api/payments/{id}`  
**Authentication:** Required  
**Role:** Admin only

**Description:** Retrieve a single payment/payout entry by id. Useful for reconciliations and support tickets.

**Success Response (200):**

```json
{
  "data": {
    "id": "PAY-8F3ACD12",
    "txnId": "TXN-2025-1101",
    "type": "payout",
    "amount": 15000,
    "recipientId": 42,
    "recipient": "John Collector",
    "status": "completed",
    "date": "2025-11-01 10:15:00",
    "gatewayResponse": {
      "method": "bank_transfer",
      "reference": "UB1234567"
    }
  }
}
```

**Errors:**

- `400` – Missing payment id
- `404` – Record not found

---

### 3. List Customer Payments

**Endpoint:** `GET /api/customer/payments`  
**Authentication:** Required  
**Role:** Customer only

**Description:** Customers can review all payouts processed to their account. Results are sorted by newest first (max 50 records).

**Query Parameters:**

| Param    | Type   | Description                                                      |
| -------- | ------ | ---------------------------------------------------------------- |
| `status` | string | Optional filter (`pending`, `processing`, `completed`, `failed`) |

**Success Response (200):**

```json
{
  "data": [
    {
      "id": "PAY-12ABEF45",
      "type": "payout",
      "amount": 7500,
      "status": "completed",
      "date": "2025-10-28 14:00:00"
    }
  ]
}
```

---

### 4. List Company Invoices

**Endpoint:** `GET /api/company/invoices`  
**Authentication:** Required  
**Role:** Company only

**Description:** Companies can track pending or completed invoices owed to ecoCycle. Supports optional status filter and returns up to 50 latest items.

**Query Parameters:** identical to customer endpoint.

**Success Response (200):**

```json
{
  "data": [
    {
      "id": "PAY-44CDEE11",
      "type": "payment",
      "amount": 32000,
      "status": "pending",
      "date": "2025-11-02 09:30:00"
    }
  ]
}
```

---

## Future Development

### Planned Features (Phase 2)

#### 1. Enhanced APIs

**Notification APIs**

```
GET    /api/notifications         - List user notifications
PUT    /api/notifications/{id}    - Mark as read
POST   /api/notifications/subscribe - Push notification subscription
DELETE /api/notifications/{id}    - Delete notification
```

**Analytics & Reporting APIs**

```
GET    /api/analytics/dashboard   - Role-specific analytics
GET    /api/reports/waste-collection - Collection reports
GET    /api/reports/bidding       - Bidding analytics
GET    /api/reports/revenue       - Revenue reports
POST   /api/reports/export        - Export data (CSV/PDF)
```

**Real-time Features**

```
WS     /ws/bidding/{roundId}      - WebSocket for live bidding
WS     /ws/notifications          - Real-time notifications
GET    /api/tracking/{pickupId}   - Real-time pickup tracking
```

#### 2. Advanced Vehicle Management

```
POST   /api/vehicles/{id}/maintenance - Schedule maintenance
GET    /api/vehicles/available    - Get available vehicles by date
POST   /api/vehicles/{id}/assign  - Assign to collector
GET    /api/vehicles/{id}/history - Vehicle usage history
```

#### 3. Waste Category Management

```
GET    /api/waste-categories      - List all categories
POST   /api/waste-categories      - Create category (admin)
PUT    /api/waste-categories/{id} - Update category
DELETE /api/waste-categories/{id} - Delete category
GET    /api/waste-categories/pricing - Get pricing tiers
```

#### 4. User Management APIs

```
GET    /api/users                 - List users (admin)
GET    /api/users/{id}            - Get user details
PUT    /api/users/{id}            - Update user
DELETE /api/users/{id}            - Deactivate user
POST   /api/users/{id}/restore    - Restore user
GET    /api/users/{id}/activity   - User activity log
```

#### 5. Advanced Search & Filters

```
GET    /api/search?q=...&type=... - Global search
GET    /api/bidding/rounds?status=active&category=plastic
GET    /api/pickup-requests?date_from=...&date_to=...
GET    /api/vehicles?status=available&type=truck
```

#### 6. Batch Operations

```
POST   /api/batch/pickup-requests - Bulk create pickups
PUT    /api/batch/vehicles        - Bulk update vehicles
DELETE /api/batch/bids            - Bulk delete bids
POST   /api/batch/notifications   - Send bulk notifications
```

#### 7. Integration APIs

**Third-Party Integrations**

```
POST   /api/integrations/payment-gateway - Payment gateway webhook
POST   /api/integrations/sms      - SMS notification webhook
POST   /api/integrations/email    - Email service webhook
GET    /api/integrations/maps     - Map service integration
```

**Mobile App APIs**

```
POST   /api/mobile/register-device - Register mobile device
GET    /api/mobile/sync           - Sync offline data
POST   /api/mobile/location       - Update collector location
GET    /api/mobile/config         - Get mobile app config
```

#### 8. Data Export & Import

```
POST   /api/export/pickups        - Export pickup data
POST   /api/export/bidding        - Export bidding data
POST   /api/import/customers      - Import customer data (CSV)
POST   /api/import/waste-categories - Import categories
```

### Technical Enhancements

#### 1. API Versioning

```
/api/v1/vehicles
/api/v2/vehicles
```

#### 2. Rate Limiting

```php
'throttle:60,1' // 60 requests per minute
'throttle:api'  // API-specific limits
```

#### 3. API Documentation Tools

- **Swagger/OpenAPI**: Auto-generated documentation
- **API Blueprint**: Markdown-based API docs
- **Postman Collections**: Automated collection generation

#### 4. GraphQL Support

```graphql
query {
  vehicles(status: "available") {
    id
    plateNumber
    type
    capacity
  }
}
```

#### 5. Webhook System

```
POST   /api/webhooks             - Register webhook
GET    /api/webhooks             - List webhooks
DELETE /api/webhooks/{id}        - Delete webhook
```

**Events:**

- `pickup.created`
- `pickup.completed`
- `bid.placed`
- `bidding.approved`
- `payment.processed`

### Security Enhancements

1. **OAuth 2.0 / API Keys**: Token-based authentication
2. **2FA Support**: Two-factor authentication
3. **IP Whitelisting**: Restrict API access by IP
4. **Request Signing**: HMAC signature verification
5. **Audit Logging**: Detailed API usage logs

### Performance Optimizations

1. **Caching Layer**: Redis/Memcached
2. **Database Indexing**: Optimize queries
3. **Pagination**: Cursor-based pagination
4. **Response Compression**: GZIP compression
5. **CDN Integration**: Static asset delivery

### Development Roadmap

**Q1 2026:**

- ✅ Payment Processing APIs
- ✅ Enhanced Notification System
- ✅ Basic Analytics APIs

**Q2 2026:**

- ✅ Real-time Bidding (WebSockets)
- ✅ Mobile App APIs
- ✅ Advanced Reporting

**Q3 2026:**

- ✅ GraphQL Support
- ✅ Third-party Integrations
- ✅ Webhook System

**Q4 2026:**

- ✅ API v2 Release
- ✅ Performance Optimizations
- ✅ Advanced Security Features

---

## Support & Contributing

### Getting Help

- **Documentation**: [Framework Documentation](../FRAMEWORK_DOCUMENTATION.md)
- **Issues**: [GitHub Issues](https://github.com/Dinil-Thilakarathne/ecoCycle/issues)
- **Email**: support@ecocycle.com

### Contributing to API

1. Fork the repository
2. Create feature branch
3. Add/update API endpoints
4. Update this documentation
5. Write tests
6. Submit pull request

### API Design Guidelines

1. **RESTful Principles**: Use appropriate HTTP methods
2. **Consistent Naming**: Use camelCase for JSON, snake_case for DB
3. **Versioning**: Plan for backward compatibility
4. **Error Messages**: Provide clear, actionable errors
5. **Documentation**: Keep this doc updated with changes

---

## Changelog

### Version 1.0.0 (October 24, 2025)

- ✅ Initial API documentation
- ✅ Authentication endpoints
- ✅ Admin APIs (Vehicles, Bidding, Pickups)
- ✅ Customer APIs (Pickup Requests)
- ✅ Collector APIs (Status Updates)
- ✅ Company APIs (Bidding)
- ✅ Testing guide
- ✅ Future development roadmap

---

**End of Documentation**

For the latest updates, visit: [GitHub Repository](https://github.com/Dinil-Thilakarathne/ecoCycle)
