# Waste Category Management API - Quick Reference

## Overview

The Waste Category Management API provides CRUD operations for waste categories in the ecoCycle platform. These categories are fundamental to the bidding and waste collection workflow.

---

## Endpoints Summary

| Method | Endpoint                          | Action           | Role   | CSRF |
| ------ | --------------------------------- | ---------------- | ------ | ---- |
| GET    | `/api/waste-categories`           | List categories  | Admin  | ❌   |
| POST   | `/api/waste-categories`           | Create category  | Admin  | ✅   |
| GET    | `/api/waste-categories/{id}`      | Get details      | Admin  | ❌   |
| PUT    | `/api/waste-categories/{id}`      | Update category  | Admin  | ✅   |
| DELETE | `/api/waste-categories/{id}`      | Delete category  | Admin  | ✅   |
| GET    | `/api/waste-categories/pricing`   | View pricing     | Admin  | ❌   |

---

## HTTP Status Codes

| Code | Meaning              | Example Scenario                        |
| ---- | -------------------- | --------------------------------------- |
| 200  | OK - Success         | Category retrieved/updated successfully |
| 201  | Created              | New category created                    |
| 400  | Bad Request          | Missing required field (id)             |
| 401  | Unauthorized         | Not authenticated                       |
| 403  | Forbidden            | Insufficient permissions (not Admin)    |
| 404  | Not Found            | Category ID doesn't exist               |
| 409  | Conflict             | Category referenced by active rounds    |
| 422  | Validation Failed    | Invalid input data (e.g., price <= 0)  |
| 500  | Server Error         | Database error or unhandled exception   |

---

## Request/Response Structure

### Standard Success Response

```json
{
  "message": "Operation successful",
  "data": {
    "id": 1,
    "name": "Plastic",
    "description": "...",
    "basePrice": 50.00,
    ...
  }
}
```

### Standard Error Response

```json
{
  "message": "Error message",
  "errors": {
    "field_name": "Specific error detail"
  }
}
```

### Headers Required

- **Content-Type**: `application/json` (for POST/PUT)
- **X-CSRF-Token**: Required for POST/PUT/DELETE requests
- **Cookie**: Session cookie for authentication

---

## Common Use Cases

### 1. Initialize Waste Categories

```bash
# Create standard waste categories on first setup
categories=(
  '{"name":"Plastic","description":"Plastic waste","basePrice":50}'
  '{"name":"Organic","description":"Organic waste","basePrice":30}'
  '{"name":"Electronic","description":"E-waste","basePrice":120}'
)

for cat in "${categories[@]}"; do
  curl -X POST http://localhost/api/waste-categories \
    -b cookies.txt \
    -H "Content-Type: application/json" \
    -H "X-CSRF-Token: $TOKEN" \
    -d "$cat"
done
```

### 2. Update Pricing Strategy

```bash
# Adjust base price for a category
curl -X PUT http://localhost/api/waste-categories/1 \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" \
  -d '{"basePrice": 55.00}'
```

### 3. Check Available Categories & Pricing

```bash
# Get all categories with pricing tiers
curl -X GET "http://localhost/api/waste-categories/pricing?include_stats=true" \
  -b cookies.txt
```

### 4. Create Bidding Round (requires category)

```bash
# After creating a waste category, use it in a bidding round
curl -X POST http://localhost/api/bidding/rounds \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" \
  -d '{
    "wasteCategory": "Plastic",
    "quantity": 500,
    "targetBiddingDate": "2025-12-15 14:00:00"
  }'
```

---

## Field Definitions

### Waste Category Object

| Field            | Type    | Editable | Notes                                      |
| ---------------- | ------- | -------- | ------------------------------------------ |
| `id`             | integer | ❌       | Auto-generated unique ID                   |
| `name`           | string  | ✅       | 1-100 chars, unique                        |
| `description`    | string  | ✅       | 10-500 chars, descriptive                  |
| `basePrice`      | decimal | ✅       | > 0, primary pricing reference             |
| `category_icon`  | string  | ✅       | Emoji or icon (optional)                   |
| `hazardous`      | boolean | ✅       | Safety flag for special waste types        |
| `created_at`     | datetime| ❌       | Auto-set on creation                       |
| `updated_at`     | datetime| ❌       | Auto-updated on changes                    |

---

## Validation Rules

### Name Field
- **Required**: Yes
- **Type**: String
- **Length**: 1-100 characters
- **Constraint**: Must be unique across all categories
- **Error**: "Name is required."

### Description Field
- **Required**: Yes
- **Type**: String
- **Length**: 10-500 characters
- **Constraint**: Descriptive text required
- **Error**: "Description is required."

### Base Price Field
- **Required**: Yes
- **Type**: Decimal (float)
- **Constraint**: Must be > 0
- **Max Decimals**: 2
- **Error**: "Base price must be greater than zero."

### Hazardous Field
- **Required**: No
- **Type**: Boolean
- **Default**: false
- **Notes**: Marks special handling required

---

## Error Codes Reference

### Validation Errors (422)

```json
{
  "message": "Validation failed",
  "errors": {
    "name": "Name is required.",
    "description": "Description is required.",
    "basePrice": "Base price must be greater than zero."
  }
}
```

### Category Not Found (404)

```json
{
  "message": "Category not found",
  "error": "Invalid category ID"
}
```

### Category In Use (409)

```json
{
  "message": "Cannot delete category",
  "error": "Category is referenced by 5 active bidding rounds. Archive or update them first."
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthorized",
  "error": "Please log in first"
}
```

### Forbidden (403)

```json
{
  "message": "Forbidden",
  "error": "Only admins can access this resource"
}
```

---

## Pricing Tiers Structure

When using the `/api/waste-categories/pricing` endpoint, categories include dynamic pricing:

```json
{
  "id": 1,
  "name": "Plastic",
  "basePrice": 50.00,
  "pricing_tiers": [
    {
      "min_kg": 0,
      "max_kg": 100,
      "price_per_kg": 50.00,
      "discount_percent": 0
    },
    {
      "min_kg": 100,
      "max_kg": 500,
      "price_per_kg": 47.50,
      "discount_percent": 5
    },
    {
      "min_kg": 500,
      "max_kg": null,
      "price_per_kg": 45.00,
      "discount_percent": 10
    }
  ]
}
```

**Tier Calculation Logic:**
- Base price applies for 0-100 kg
- 5% discount for 100-500 kg
- 10% discount for 500+ kg
- `max_kg: null` indicates unlimited upper bound

---

## Authentication & Security

### Session-Based Authentication

All endpoints require an active session:

```bash
# 1. Login first
curl -X POST http://localhost/api/auth/login \
  -c cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secret"}'

# 2. Use cookies in subsequent requests
curl -X GET http://localhost/api/waste-categories \
  -b cookies.txt
```

### CSRF Protection

POST/PUT/DELETE requests require CSRF token:

```bash
# Extract token from session/form
TOKEN=$(grep -oP 'csrf_token="\K[^"]+' <<< "$HTML")

# Include in request header
curl -X POST http://localhost/api/waste-categories \
  -b cookies.txt \
  -H "X-CSRF-Token: $TOKEN" \
  -d '...'
```

### Role-Based Access

Only Admin role can access these endpoints:

```
Admin    ✅ Can create, read, update, delete categories
Customer ❌ Cannot access
Collector❌ Cannot access
Company  ❌ Cannot access
```

---

## Testing Workflow

### 1. Setup Test Environment

```bash
# Start fresh session
curl -X POST http://localhost/dev/login/admin \
  -c cookies.txt

# Get CSRF token (from dashboard page or existing request)
CSRF_TOKEN="get-from-request-header"
```

### 2. Create Test Category

```bash
curl -X POST http://localhost/api/waste-categories \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d '{
    "name": "Test Material",
    "description": "Testing waste category API",
    "basePrice": 60.00,
    "hazardous": false
  }' | jq '.'
```

### 3. Verify Creation

```bash
# List all categories
curl -X GET http://localhost/api/waste-categories -b cookies.txt | jq '.'

# Get specific category
curl -X GET http://localhost/api/waste-categories/1 -b cookies.txt | jq '.'
```

### 4. Update Category

```bash
curl -X PUT http://localhost/api/waste-categories/1 \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d '{"basePrice": 65.00}' | jq '.'
```

### 5. Check Pricing

```bash
curl -X GET "http://localhost/api/waste-categories/pricing?include_stats=true" \
  -b cookies.txt | jq '.'
```

### 6. Cleanup

```bash
curl -X DELETE http://localhost/api/waste-categories/1 \
  -b cookies.txt \
  -H "X-CSRF-Token: $CSRF_TOKEN"
```

---

## Postman Collection Template

Import this into Postman for easy testing:

```json
{
  "info": {
    "name": "Waste Category API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "List Categories",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/waste-categories"
      }
    },
    {
      "name": "Create Category",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/api/waste-categories",
        "body": {
          "mode": "raw",
          "raw": "{\"name\":\"New Category\",\"description\":\"...\",\"basePrice\":50}"
        },
        "header": [{"key":"X-CSRF-Token","value":"{{csrf_token}}"}]
      }
    },
    {
      "name": "Update Category",
      "request": {
        "method": "PUT",
        "url": "{{base_url}}/api/waste-categories/{{category_id}}",
        "body": {"mode":"raw","raw":"{\"basePrice\":55}"},
        "header": [{"key":"X-CSRF-Token","value":"{{csrf_token}}"}]
      }
    },
    {
      "name": "Get Pricing",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/waste-categories/pricing?include_stats=true"
      }
    }
  ]
}
```

---

## Troubleshooting

| Issue                    | Cause                              | Solution                           |
| ------------------------ | ---------------------------------- | ---------------------------------- |
| 401 Unauthorized         | Not logged in or session expired   | Login again, check cookies         |
| 403 Forbidden            | User is not Admin                  | Use admin account                  |
| 422 Validation Error     | Invalid field values               | Check field requirements above     |
| 404 Not Found            | Invalid category ID                | Verify ID exists with GET request  |
| 409 Conflict             | Category in use by bidding rounds  | Archive related bidding rounds     |
| CSRF Token Error         | Missing or invalid token           | Include X-CSRF-Token header        |
| Empty response           | Database connection issue          | Check `/debug/db/ping.json`        |

---

## Best Practices

1. **Category Naming**: Use clear, standardized names (e.g., "Plastic", "Organic Waste", "Electronic Waste")
2. **Pricing Strategy**: Base prices should reflect market rates and collection costs
3. **Hazardous Flag**: Always mark special waste types (e-waste, hazardous) for proper handling
4. **Documentation**: Keep descriptions updated for clarity
5. **Immutability**: Once bidding rounds are created, avoid deleting categories
6. **Audit Trail**: Use `created_at` and `updated_at` timestamps for tracking changes

---

## Integration Points

Waste Categories are used by:

- **Bidding Rounds API** (`/api/bidding/rounds`) - Create rounds per category
- **Pickup Requests** - Customers specify waste categories
- **Pricing Engine** - Dynamic pricing calculations
- **Analytics** - Collection statistics per category
- **Reports** - Category-specific waste collection reports

---

## Version History

| Version | Date       | Changes                              |
| ------- | ---------- | ------------------------------------ |
| 1.0     | 2025-11-29 | Initial waste category API release   |

---

**For full documentation**, see [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
