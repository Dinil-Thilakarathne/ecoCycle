# NIC Field Addition Documentation

## Overview

The NIC (National Identity Card) field has been added to the `users` table to store national identity card numbers for users in the ecoCycle system.

## Changes Made

### 1. Database Schema Updates

#### PostgreSQL Schema

**File:** `database/postgresql/init/01_create_tables.sql`

Added `nic` field to the users table:

```sql
CREATE TABLE IF NOT EXISTS users (
  ...
  phone VARCHAR(50) DEFAULT NULL,
  nic VARCHAR(20) DEFAULT NULL,  -- NEW FIELD
  address TEXT DEFAULT NULL,
  ...
);
```

#### MySQL Schema

**File:** `database/create_tables.sql`

Added `nic` field to the users table:

```sql
CREATE TABLE IF NOT EXISTS `users` (
  ...
  `phone` VARCHAR(50) DEFAULT NULL,
  `nic` VARCHAR(20) DEFAULT NULL,  -- NEW FIELD
  `address` TEXT DEFAULT NULL,
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Migration Scripts

#### PostgreSQL Migration

**File:** `database/postgresql/init/02_add_nic_to_users.sql`

For existing PostgreSQL databases:

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS nic VARCHAR(20) DEFAULT NULL;
COMMENT ON COLUMN users.nic IS 'National Identity Card number';
```

#### MySQL Migration

**File:** `database/schema/004_add_nic_to_users.sql`

For existing MySQL databases:

```sql
ALTER TABLE `users` ADD COLUMN `nic` VARCHAR(20) DEFAULT NULL AFTER `phone`;
ALTER TABLE `users` MODIFY COLUMN `nic` VARCHAR(20) DEFAULT NULL COMMENT 'National Identity Card number';
```

### 3. Model Updates

**File:** `src/Models/User.php`

The User model already includes:

- NIC field in table creation
- `nicExists()` method to check for duplicate NICs
- NIC is included in all user queries

```php
public function nicExists(string $nic, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM users WHERE nic = ?';
    $params = [$nic];

    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $row = $this->db->fetch($sql, $params);
    return (bool) $row;
}
```

## Field Specifications

| Property        | Value                                    |
| --------------- | ---------------------------------------- |
| **Column Name** | `nic`                                    |
| **Data Type**   | VARCHAR(20)                              |
| **Nullable**    | YES                                      |
| **Default**     | NULL                                     |
| **Position**    | After `phone` field                      |
| **Index**       | Optional (create if needed for searches) |

## Usage Examples

### 1. Creating a User with NIC

```php
use Models\User;

$userModel = new User();

$userId = $userModel->createUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '0771234567',
    'nic' => '199512345678',  // NIC field
    'type' => 'customer',
    'password' => 'secure_password'
]);
```

### 2. Validating NIC Before Registration

```php
use Models\User;

$userModel = new User();
$nic = '199512345678';

if ($userModel->nicExists($nic)) {
    echo "This NIC is already registered!";
} else {
    // Proceed with registration
}
```

### 3. Updating User NIC

```php
use Models\User;

$userModel = new User();

$userModel->updateUser($userId, [
    'nic' => '199512345678'
]);
```

### 4. Retrieving User with NIC

```php
use Models\User;

$userModel = new User();
$user = $userModel->findById($userId);

echo "User NIC: " . ($user['nic'] ?? 'Not provided');
```

## Validation Recommendations

When implementing forms that collect NIC data, consider adding validation:

### PHP Validation Example

```php
function validateNIC(string $nic): bool
{
    // Sri Lankan NIC formats:
    // Old format: 9 digits + V (e.g., 123456789V)
    // New format: 12 digits (e.g., 199512345678)

    $oldFormat = '/^[0-9]{9}[VvXx]$/';
    $newFormat = '/^[0-9]{12}$/';

    return preg_match($oldFormat, $nic) || preg_match($newFormat, $nic);
}

// Usage in validation
$nic = $_POST['nic'] ?? '';

if (!validateNIC($nic)) {
    $errors[] = 'Invalid NIC format';
}

// Check for duplicates
$userModel = new User();
if ($userModel->nicExists($nic)) {
    $errors[] = 'This NIC is already registered';
}
```

### HTML Form Example

```html
<div class="form-group">
  <label for="nic">National Identity Card (NIC)</label>
  <input
    type="text"
    id="nic"
    name="nic"
    class="form-control"
    placeholder="123456789V or 199512345678"
    maxlength="20"
    pattern="[0-9]{9}[VvXx]|[0-9]{12}"
    title="Enter valid NIC (9 digits + V or 12 digits)"
  />
  <small class="form-text text-muted">
    Old format: 9 digits + V (e.g., 123456789V)<br />
    New format: 12 digits (e.g., 199512345678)
  </small>
</div>
```

## Migration Instructions

### For New Installations

If you're setting up a fresh database:

**Using Docker (Recommended):**

```bash
# The NIC field is already included in the schema
docker-compose down -v
docker-compose up -d
```

**Manual Setup:**

```bash
# PostgreSQL
psql -U postgres -d eco_cycle < database/postgresql/init/01_create_tables.sql

# MySQL
mysql -u root -p eco_cycle < database/create_tables.sql
```

### For Existing Databases

If you have an existing database that needs the NIC field:

**PostgreSQL:**

```bash
docker-compose exec -T db psql -U postgres -d eco_cycle < database/postgresql/init/02_add_nic_to_users.sql
```

Or manually:

```sql
-- Connect to PostgreSQL
psql -U postgres -d eco_cycle

-- Run the migration
ALTER TABLE users ADD COLUMN IF NOT EXISTS nic VARCHAR(20) DEFAULT NULL;
COMMENT ON COLUMN users.nic IS 'National Identity Card number';
```

**MySQL:**

```bash
mysql -u root -p eco_cycle < database/schema/004_add_nic_to_users.sql
```

Or manually:

```sql
-- Connect to MySQL
mysql -u root -p eco_cycle

-- Run the migration
ALTER TABLE `users` ADD COLUMN `nic` VARCHAR(20) DEFAULT NULL AFTER `phone`;
```

## Testing

### Verify Field Exists

**PostgreSQL:**

```sql
-- Check column exists
SELECT column_name, data_type, character_maximum_length, is_nullable
FROM information_schema.columns
WHERE table_name = 'users' AND column_name = 'nic';
```

**MySQL:**

```sql
-- Check column exists
DESCRIBE users;
-- or
SHOW COLUMNS FROM users WHERE Field = 'nic';
```

### Test Data Insertion

```sql
-- PostgreSQL/MySQL
INSERT INTO users (name, email, phone, nic, type, password_hash)
VALUES ('Test User', 'test@example.com', '0771234567', '199512345678', 'customer', 'hashed_password');

-- Verify
SELECT id, name, email, nic FROM users WHERE email = 'test@example.com';
```

### Test Duplicate Check

```php
// In your PHP code
$userModel = new User();

// Test with existing NIC
$exists = $userModel->nicExists('199512345678');
echo $exists ? 'NIC exists' : 'NIC available';
```

## Security Considerations

1. **Data Privacy**: NIC is sensitive personal information

   - Don't display full NIC in public views
   - Consider masking: `1995****5678` or `****5678`
   - Implement proper access controls

2. **Validation**: Always validate NIC format

   - Client-side validation for UX
   - Server-side validation for security

3. **Encryption**: For sensitive deployments, consider:

   - Encrypting NIC data at rest
   - Using database-level encryption
   - Implementing application-level encryption

4. **Audit Trail**: Consider logging NIC changes
   - Who updated the NIC
   - When it was updated
   - Previous value (encrypted logs)

## API Usage

If you have API endpoints for user management:

### Registration Endpoint

```php
// POST /api/users/register
$data = json_decode(file_get_contents('php://input'), true);

$userModel = new User();

// Validate NIC
if (!empty($data['nic'])) {
    if (!validateNIC($data['nic'])) {
        return response()->json(['error' => 'Invalid NIC format'], 400);
    }

    if ($userModel->nicExists($data['nic'])) {
        return response()->json(['error' => 'NIC already registered'], 409);
    }
}

// Create user
$userId = $userModel->createUser([
    'name' => $data['name'],
    'email' => $data['email'],
    'phone' => $data['phone'],
    'nic' => $data['nic'] ?? null,
    'type' => 'customer',
    'password' => $data['password']
]);
```

### Profile Update Endpoint

```php
// PUT /api/users/{id}
$userId = (int) $params['id'];
$data = json_decode(file_get_contents('php://input'), true);

$userModel = new User();

if (!empty($data['nic'])) {
    // Validate format
    if (!validateNIC($data['nic'])) {
        return response()->json(['error' => 'Invalid NIC format'], 400);
    }

    // Check for duplicates (excluding current user)
    if ($userModel->nicExists($data['nic'], $userId)) {
        return response()->json(['error' => 'NIC already in use'], 409);
    }
}

$userModel->updateUser($userId, ['nic' => $data['nic']]);
```

## Troubleshooting

### Issue: Column doesn't exist after migration

**Solution:**

```bash
# Verify migration ran successfully
# PostgreSQL
docker-compose exec db psql -U postgres -d eco_cycle -c "\d users"

# MySQL
docker-compose exec db mysql -u root -p eco_cycle -e "DESCRIBE users;"

# Re-run migration if needed
```

### Issue: NIC validation not working

**Solution:**

- Check validation regex patterns
- Ensure NIC format matches Sri Lankan standards
- Test with both old (9+V) and new (12 digit) formats

### Issue: Duplicate NIC error not showing

**Solution:**

- Verify `nicExists()` method is being called
- Check database query is correct
- Ensure proper error handling in forms

## Future Enhancements

Consider implementing:

1. **NIC Verification Service**: Integration with government database
2. **OCR Support**: Scan NIC card and auto-fill
3. **Format Conversion**: Auto-convert old to new format
4. **Validation Rules**: Age verification from NIC
5. **Privacy Controls**: User consent for NIC storage

---

**Last Updated:** October 23, 2025  
**Version:** 1.0  
**Related Files:**

- `database/postgresql/init/01_create_tables.sql`
- `database/postgresql/init/02_add_nic_to_users.sql`
- `database/create_tables.sql`
- `database/schema/004_add_nic_to_users.sql`
- `src/Models/User.php`
