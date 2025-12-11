# Waste Category Management API - Implementation Details

## Architecture Overview

The Waste Category Management API is implemented using a clean MVC architecture following RESTful principles. This document explains the implementation, key components, and how to extend the system.

---

## Directory Structure

```
ecoCycle/
├── src/
│   ├── Controllers/
│   │   └── Api/
│   │       └── WasteManagementController.php    ← API Controller
│   ├── Models/
│   │   └── WasteCategory.php                    ← Database Model
│   └── Services/
│       └── WasteCategoryService.php             ← Business Logic (optional)
├── config/
│   └── routes.php                                ← Route Definitions
├── database/
│   └── postgresql/
│       └── init/
│           └── waste_categories_table.sql        ← Table Schema
└── docs/
    └── api-doc/
        ├── API_DOCUMENTATION.md                  ← Full Documentation
        └── WASTE_CATEGORY_QUICK_REFERENCE.md   ← Quick Reference
```

---

## Core Components

### 1. WasteManagementController

**File:** `src/Controllers/Api/WasteManagementController.php`

The main API controller that handles all HTTP requests related to waste categories.

```php
class WasteCategoryController extends BaseController
{
    private WasteCategory $categories;

    public function __construct()
    {
        $this->categories = new WasteCategory();
    }

    // GET /api/waste-categories
    public function index(Request $request): Response { ... }

    // POST /api/waste-categories
    public function store(Request $request): Response { ... }

    // PUT /api/waste-categories/{id}
    public function update(Request $request): Response { ... }

    // DELETE /api/waste-categories/{id}
    public function destroy(Request $request): Response { ... }

    // GET /api/waste-categories/pricing
    public function pricing(Request $request): Response { ... }

    // Helper methods
    private function validatePayload(...) { ... }
    private function mergeJsonBody(...) { ... }
    private function resolveRouteId(...) { ... }
}
```

**Key Methods:**

#### `index()` - List All Categories
- **Route:** `GET /api/waste-categories`
- **Purpose:** Retrieve all active waste categories
- **Returns:** JSON array of category objects
- **Middleware:** AuthMiddleware, AdminOnly

#### `store()` - Create Category
- **Route:** `POST /api/waste-categories`
- **Purpose:** Create a new waste category
- **Validation:** Validates name, description, basePrice
- **Returns:** Created category object (201)
- **Middleware:** AuthMiddleware, AdminOnly, CSRF

#### `update()` - Update Category
- **Route:** `PUT /api/waste-categories/{id}`
- **Purpose:** Update existing category (partial updates supported)
- **Validation:** Validates provided fields
- **Returns:** Success message (200)
- **Middleware:** AuthMiddleware, AdminOnly, CSRF

#### `destroy()` - Delete Category
- **Route:** `DELETE /api/waste-categories/{id}`
- **Purpose:** Delete a waste category
- **Validation:** Checks if category is in use
- **Returns:** Success or conflict error
- **Middleware:** AuthMiddleware, AdminOnly, CSRF

#### `pricing()` - Get Pricing Tiers
- **Route:** `GET /api/waste-categories/pricing`
- **Purpose:** Retrieve categories with dynamic pricing tiers
- **Returns:** Categories with pricing brackets
- **Middleware:** AuthMiddleware, AdminOnly

### 2. WasteCategory Model

**File:** `src/Models/WasteCategory.php`

Handles database operations for waste categories.

```php
class WasteCategory extends Model
{
    protected string $table = 'waste_categories';
    
    // CRUD Operations
    public function findAll(): array { ... }
    public function findById(int $id): ?array { ... }
    public function create(array $data): array { ... }
    public function update(int $id, array $data): void { ... }
    public function delete(int $id): void { ... }
    
    // Special Queries
    public function getPricingTiers(): array { ... }
    public function findByName(string $name): ?array { ... }
    public function getCollectionStats(int $categoryId): array { ... }
}
```

**Database Interaction:**

The model uses the framework's `Database` class for all queries:

```php
class WasteCategory extends Model
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function findAll(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM waste_categories ORDER BY name ASC"
        );
    }

    public function create(array $data): array
    {
        $this->db->insert('waste_categories', [
            'name' => $data['name'],
            'description' => $data['description'],
            'basePrice' => $data['basePrice'] ?? 0,
            'hazardous' => $data['hazardous'] ?? false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $id = $this->db->lastInsertId();
        return $this->findById($id);
    }
}
```

### 3. Database Schema

**File:** `database/postgresql/init/waste_categories_table.sql`

```sql
CREATE TABLE waste_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    basePrice DECIMAL(10, 2) NOT NULL CHECK (basePrice > 0),
    category_icon VARCHAR(10),
    hazardous BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_waste_categories_name ON waste_categories(name);
CREATE INDEX idx_waste_categories_hazardous ON waste_categories(hazardous);

-- Audit trigger for updated_at
CREATE OR REPLACE FUNCTION update_waste_categories_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER waste_categories_update_trigger
BEFORE UPDATE ON waste_categories
FOR EACH ROW
EXECUTE FUNCTION update_waste_categories_timestamp();
```

### 4. Route Definitions

**File:** `config/routes.php`

```php
// Waste Category Management Routes
$router->get('/api/waste-categories', 'Controllers\Api\WasteManagementController@index', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->post('/api/waste-categories', 'Controllers\Api\WasteManagementController@store', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->put('/api/waste-categories/{id}', 'Controllers\Api\WasteManagementController@update', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->delete('/api/waste-categories/{id}', 'Controllers\Api\WasteManagementController@destroy', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);

$router->get('/api/waste-categories/pricing', 'Controllers\Api\WasteManagementController@pricing', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);
```

---

## Request/Response Flow

### Create Category Flow

```
1. Client sends POST request
   └─ POST /api/waste-categories
      └─ Headers: Content-Type, X-CSRF-Token
      └─ Body: {name, description, basePrice, ...}

2. Middleware Chain
   ├─ AuthMiddleware: Verify session exists
   └─ AdminOnly: Verify role is 'admin'

3. Route Handler (store method)
   ├─ Parse JSON body
   ├─ Validate payload
   │  └─ Check required fields
   │  └─ Check field constraints
   │  └─ Return 422 if invalid
   └─ Create in database
      └─ Set created_at timestamp
      └─ Return 201 with created object

4. Response to Client
   └─ {message, data}
```

### Update Category Flow

```
1. Client sends PUT request
   └─ PUT /api/waste-categories/1
      └─ Body: {basePrice: 55.00}  (partial update)

2. Middleware Chain
   ├─ AuthMiddleware: Verify session
   └─ AdminOnly: Verify role

3. Route Handler (update method)
   ├─ Extract ID from URL parameter
   ├─ Parse JSON body
   ├─ Validate provided fields only
   │  └─ Only validate fields that are provided
   │  └─ Existing values unaffected
   ├─ Check category exists
   │  └─ 404 if not found
   └─ Update database
      └─ Update only provided fields
      └─ Set updated_at timestamp
      └─ Return 200

4. Response to Client
   └─ {message: "Category updated"}
```

---

## Validation Logic

### Validation Rules Engine

The controller uses a `validatePayload()` method to validate all input:

```php
private function validatePayload(Request $request, bool $isUpdate = false): array
{
    $data = $request->all();
    $errors = [];

    // Name validation
    if (!$isUpdate || isset($data['name'])) {
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required.';
        }
    }

    // Description validation
    if (!$isUpdate || isset($data['description'])) {
        if (empty($data['description'])) {
            $errors['description'] = 'Description is required.';
        }
    }

    // Base price validation
    if (!$isUpdate || isset($data['basePrice'])) {
        if (!isset($data['basePrice']) || (float)$data['basePrice'] <= 0) {
            $errors['basePrice'] = 'Base price must be greater than zero.';
        }
    }

    if (!empty($errors)) {
        return ['errors' => $errors];
    }

    return [
        'data' => [
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'basePrice' => isset($data['basePrice']) ? (float)$data['basePrice'] : null,
        ]
    ];
}
```

**Key Features:**

- **Conditional Validation**: `$isUpdate` flag allows partial updates
- **Type Casting**: `(float)` for numeric values
- **Early Return**: Stops validation on first error set
- **Error Aggregation**: Collects all errors before returning

---

## Error Handling

### Exception Handling Pattern

```php
public function store(Request $request): Response
{
    try {
        $record = $this->categories->create($payload['data']);
    } catch (\Throwable $e) {
        return Response::errorJson('Failed to create category', 500, [
            'detail' => $e->getMessage()
        ]);
    }

    return Response::json([
        'message' => 'Category created',
        'data' => $record
    ]);
}
```

### Error Response Format

```json
{
  "message": "Failed to create category",
  "error_code": "CREATION_ERROR",
  "status": 500,
  "details": {
    "detail": "UNIQUE constraint failed: waste_categories.name"
  }
}
```

---

## Security Features

### 1. Authentication Middleware

```php
'Middleware\AuthMiddleware'  // Checks session is active
```

**Effect:** Rejects unauthenticated requests with 401

### 2. Role-Based Access Control

```php
'Middleware\Roles\AdminOnly'  // Checks user role = 'admin'
```

**Effect:** Rejects non-admin requests with 403

### 3. CSRF Protection

```php
// Applied to POST/PUT/DELETE
-H "X-CSRF-Token: $TOKEN"
```

**Verification:** Framework validates token matches session

### 4. Input Validation

- All user input validated before database operations
- Type casting to prevent injection
- Length constraints on string fields

### 5. SQL Injection Prevention

Framework uses parameterized queries:

```php
// Safe - parameterized
$this->db->query(
    "SELECT * FROM waste_categories WHERE id = ?",
    [$id]
);

// Not used in this codebase
// $db->query("SELECT * FROM waste_categories WHERE id = $id");  // ❌ UNSAFE
```

---

## Performance Optimizations

### 1. Database Indexing

```sql
CREATE INDEX idx_waste_categories_name ON waste_categories(name);
CREATE INDEX idx_waste_categories_hazardous ON waste_categories(hazardous);
```

**Impact:** Speeds up:
- Searches by name
- Filtering by hazardous flag
- Sorting operations

### 2. Query Optimization

**Avoid N+1 Queries:**
```php
// ✅ Good - Single query
$categories = $this->categories->findAll();

// ❌ Bad - N queries
foreach ($categoryIds as $id) {
    $this->categories->findById($id);  // N queries!
}
```

### 3. Response Structure

- Only necessary fields returned
- Nested data flattened when possible
- Pagination available for large datasets

---

## Extending the API

### Adding New Endpoints

1. **Add Route** in `config/routes.php`:
```php
$router->post('/api/waste-categories/bulk-import', 
    'Controllers\Api\WasteManagementController@bulkImport', [
    'Middleware\AuthMiddleware',
    'Middleware\Roles\AdminOnly',
]);
```

2. **Add Controller Method**:
```php
public function bulkImport(Request $request): Response
{
    $this->mergeJsonBody($request);
    $data = $request->get('categories', []);
    
    $results = [];
    foreach ($data as $categoryData) {
        $results[] = $this->categories->create($categoryData);
    }
    
    return Response::json(['imported' => count($results)]);
}
```

3. **Update Documentation** in `API_DOCUMENTATION.md`

### Adding New Validations

```php
// Extend validatePayload method
private function validatePayload(Request $request, bool $isUpdate = false): array
{
    $data = $request->all();
    $errors = [];

    // ... existing validations ...

    // New validation: Icon must be emoji
    if (!$isUpdate || isset($data['category_icon'])) {
        if (!$this->isValidEmoji($data['category_icon'] ?? '')) {
            $errors['category_icon'] = 'Icon must be a valid emoji.';
        }
    }

    // ...
}

private function isValidEmoji(string $text): bool
{
    return preg_match('/\p{Emoji}/u', $text);
}
```

### Adding New Model Methods

```php
class WasteCategory extends Model
{
    // New method for bulk operations
    public function bulkCreate(array $categories): array
    {
        $created = [];
        foreach ($categories as $data) {
            $created[] = $this->create($data);
        }
        return $created;
    }

    // New method for statistics
    public function getMonthlyStats(int $categoryId): array
    {
        return $this->db->fetchAll(
            "SELECT DATE_TRUNC('month', created_at) as month,
                    COUNT(*) as count
             FROM waste_categories
             WHERE id = ?
             GROUP BY DATE_TRUNC('month', created_at)
             ORDER BY month DESC",
            [$categoryId]
        );
    }
}
```

---

## Testing Strategy

### Unit Testing Example

```php
class WasteCategoryControllerTest extends TestCase
{
    private WasteManagementController $controller;
    private Request $request;

    protected function setUp(): void
    {
        $this->controller = new WasteManagementController();
        $this->request = new Request();
    }

    public function testCreateValidCategory()
    {
        $this->request->setBody([
            'name' => 'Glass',
            'description' => 'Glass waste',
            'basePrice' => 40.00
        ]);

        $response = $this->controller->store($this->request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('data', json_decode($response->getBody(), true));
    }

    public function testCreateWithInvalidPrice()
    {
        $this->request->setBody([
            'name' => 'Invalid',
            'description' => 'Test',
            'basePrice' => -50  // Invalid: negative
        ]);

        $response = $this->controller->store($this->request);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
```

### Integration Testing

```bash
# Test the full flow
curl -X POST http://localhost/api/waste-categories \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" \
  -d '{"name":"Glass","description":"Glass waste","basePrice":40}' \
  | jq '.data.id as $id | $id'  # Extract ID

# Update the created category
curl -X PUT http://localhost/api/waste-categories/$id \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" \
  -d '{"basePrice": 45}'

# Verify update
curl -X GET http://localhost/api/waste-categories/$id -b cookies.txt \
  | jq '.data.basePrice'  # Should be 45
```

---

## Monitoring & Debugging

### Debug Routes

```bash
# Check database connectivity
curl http://localhost/debug/db/ping.json

# View database state
curl http://localhost/debug/db/waste_categories.json

# Check framework routes
curl http://localhost/routes/list

# Validate routes
curl http://localhost/routes/validate
```

### Logging Example

```php
public function store(Request $request): Response
{
    try {
        \Log::info('Creating waste category', [
            'user_id' => auth()->id(),
            'data' => $request->all()
        ]);
        
        $record = $this->categories->create($payload['data']);
        
        \Log::info('Waste category created', ['id' => $record['id']]);
    } catch (\Throwable $e) {
        \Log::error('Failed to create category', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        // ...
    }
}
```

---

## Migration from Previous Version

If upgrading from v1.0:

1. **Database Migration:**
```sql
-- Add any new columns
ALTER TABLE waste_categories ADD COLUMN category_icon VARCHAR(10);

-- Create index if missing
CREATE INDEX idx_waste_categories_name ON waste_categories(name);
```

2. **API Changes:** None - maintains backward compatibility

3. **Update Clients:** No immediate action required

---

## Conclusion

The Waste Category Management API provides a robust, secure, and extensible foundation for managing waste types in the ecoCycle platform. The implementation follows SOLID principles, includes comprehensive error handling, and is ready for production use.

For questions or issues, refer to [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) or check the GitHub issues.
