# ecoCycle API Quick Reference

**Quick access guide for developers**

## Base URL

```
Development: http://localhost
Production: https://your-domain.com
```

## Authentication

All endpoints require session authentication (except login/register)

---

## Endpoint Summary

### 🔐 Authentication

| Method | Endpoint    | Role   | Description       |
| ------ | ----------- | ------ | ----------------- |
| POST   | `/login`    | Public | User login        |
| POST   | `/register` | Public | User registration |
| POST   | `/logout`   | Any    | User logout       |

### 👨‍💼 Admin APIs

#### Vehicles

| Method | Endpoint             | Description         |
| ------ | -------------------- | ------------------- |
| GET    | `/api/vehicles`      | List all vehicles   |
| GET    | `/api/vehicles/{id}` | Get vehicle details |
| POST   | `/api/vehicles`      | Create vehicle      |
| PUT    | `/api/vehicles/{id}` | Update vehicle      |
| DELETE | `/api/vehicles/{id}` | Delete vehicle      |

#### Bidding Rounds

| Method | Endpoint                   | Description          |
| ------ | -------------------------- | -------------------- |
| POST   | `/api/bidding/rounds`      | Create bidding round |
| GET    | `/api/bidding/rounds/{id}` | Get round details    |
| PUT    | `/api/bidding/rounds/{id}` | Update round         |
| DELETE | `/api/bidding/rounds/{id}` | Cancel round         |
| POST   | `/api/bidding/approve`     | Approve round        |
| POST   | `/api/bidding/reject`      | Reject round         |

#### Pickup Management

| Method | Endpoint                    | Description                     |
| ------ | --------------------------- | ------------------------------- |
| PUT    | `/api/pickup-requests/{id}` | Assign collector, update status |

### 🏠 Customer APIs

| Method | Endpoint                             | Description           |
| ------ | ------------------------------------ | --------------------- |
| GET    | `/api/customer/pickup-requests`      | List my pickups       |
| POST   | `/api/customer/pickup-requests`      | Create pickup request |
| PUT    | `/api/customer/pickup-requests/{id}` | Update my pickup      |
| DELETE | `/api/customer/pickup-requests/{id}` | Cancel my pickup      |

### 🚛 Collector APIs

| Method | Endpoint                                     | Description          |
| ------ | -------------------------------------------- | -------------------- |
| PUT    | `/api/collector/pickup-requests/{id}/status` | Update pickup status |

### 🏭 Company APIs

| Method | Endpoint                 | Description |
| ------ | ------------------------ | ----------- |
| POST   | `/api/company/bids`      | Place bid   |
| PUT    | `/api/company/bids/{id}` | Update bid  |
| DELETE | `/api/company/bids/{id}` | Delete bid  |

### 💸 Payment APIs

| Method | Endpoint                 | Role     | Description                  |
| ------ | ------------------------ | -------- | ---------------------------- |
| POST   | `/api/payments`          | Admin    | Record manual payment/payout |
| GET    | `/api/payments/{id}`     | Admin    | Fetch payment details        |
| GET    | `/api/customer/payments` | Customer | List customer payouts        |
| GET    | `/api/company/invoices`  | Company  | List company invoices        |

---

## Common Request Examples

### Login

```bash
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'
```

### Create Vehicle (Admin)

```bash
curl -X POST http://localhost/api/vehicles \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "plateNumber":"ABC-1234",
    "type":"Large Truck",
    "lastMaintenance":"2025-10-20",
    "nextMaintenance":"2026-01-20"
  }'
```

### Create Pickup (Customer)

```bash
curl -X POST http://localhost/api/customer/pickup-requests \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "address":"123 Main St",
    "timeSlot":"morning",
    "wasteCategories":[{"id":1,"quantity":10}]
  }'
```

### Place Bid (Company)

```bash
curl -X POST http://localhost/api/company/bids \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "roundId":"uuid-here",
    "bidPerUnit":25.50,
    "wasteAmount":500
  }'
```

---

## Response Codes

| Code | Meaning           |
| ---- | ----------------- |
| 200  | Success           |
| 201  | Created           |
| 400  | Bad Request       |
| 401  | Unauthorized      |
| 403  | Forbidden         |
| 404  | Not Found         |
| 422  | Validation Failed |
| 500  | Server Error      |

---

## Field Validation

### Vehicle Types & Capacity

- `Pickup Truck` → 2000 kg
- `Small Truck` → 3000 kg
- `Large Truck` → 5000 kg

### Plate Number Format

- Pattern: `ABC-1234`
- 3 uppercase letters + hyphen + 4 digits

### Vehicle Status

- `available`, `in-use`, `maintenance`, `removed`

### Pickup Status

- `pending`, `assigned`, `in_progress`, `completed`, `cancelled`

### Bidding Status

- `active`, `completed`, `approved`, `rejected`, `cancelled`

### Time Slots

- `morning`, `afternoon`, `evening`

---

## Quick Testing Script

```bash
#!/bin/bash

# Login as admin
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'

# List vehicles
curl -X GET http://localhost/api/vehicles \
  -b cookies.txt

# Create vehicle
curl -X POST http://localhost/api/vehicles \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "plateNumber":"XYZ-9876",
    "type":"Large Truck"
  }'

# Create bidding round
curl -X POST http://localhost/api/bidding/rounds \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{
    "wasteCategory":"Plastic",
    "quantity":1000,
    "startingBid":20000,
    "endTime":"2025-10-30 18:00:00"
  }'
```

---

## Development Endpoints

### Debug Routes

| Endpoint                  | Description           |
| ------------------------- | --------------------- |
| GET `/test`               | System health check   |
| GET `/routes/list`        | List all routes       |
| GET `/routes/validate`    | Validate routes       |
| GET `/diagnostic`         | System diagnostic     |
| GET `/debug/db/ping.json` | Database connectivity |
| GET `/debug/db/users`     | List users            |

### Dev Login (Development Only)

```
GET /dev/login/admin
GET /dev/login/customer
GET /dev/login/collector
GET /dev/login/company
```

---

For full documentation, see [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
