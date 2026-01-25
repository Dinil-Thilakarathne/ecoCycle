# 🗄️ Database Setup & Weight Feature Guide

## Current Setup

You're running **PostgreSQL in Docker** with DBeaver as your database client.

### Configuration
```
Database Type: PostgreSQL
Connection: Docker container (ecocycle-db)
Host: localhost
Port: 5432
Database: eco_cycle
Username: ecocycle_user
Password: ecocycle_dev_password
```

---

## ✅ Quick Setup Checklist

### 1️⃣ Verify Docker is Running

```bash
# Check if containers are up
docker-compose -f docker-compose.dev.yml ps

# Expected output:
# NAME               STATUS
# ecocycle-app       Up
# ecocycle-db        Up
```

If not running:
```bash
docker-compose -f docker-compose.dev.yml up -d
```

### 2️⃣ Verify .env Configuration

Your `.env` file should have:
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=eco_cycle
DB_USERNAME=ecocycle_user
DB_PASSWORD=ecocycle_dev_password
```

### 3️⃣ Apply Database Migration

Run the setup script to add missing columns:

```bash
# Windows (from project root)
php scripts/setup_database.php

# Or via Docker
docker-compose exec app php scripts/setup_database.php
```

### 4️⃣ Verify in DBeaver

1. Open DBeaver
2. Connect to `ecocycle-db` (if not already connected)
3. Expand `eco_cycle` database
4. Check these tables have the required columns:

#### waste_categories
- ✅ id
- ✅ name  
- ✅ **price_per_unit** ← NEW (should be added)

#### pickup_request_wastes
- ✅ id
- ✅ pickup_id
- ✅ waste_category_id
- ✅ **weight** ← NEW (should be added)
- ✅ **amount** ← NEW (should be added)

#### pickup_requests
- ✅ id
- ✅ customer_id
- ✅ **price** ← NEW (should be added)

---

## 🔄 How Weight Calculation Works

### Data Flow

1. **Collector enters weight** in the UI (Measured Weight field)
   
2. **POST to API**: `/api/collector/pickup-requests/{id}/weight`
   ```json
   {
     "weight": 12.50  // kg
   }
   ```

3. **Backend calculation** (in `IncomeWaste::saveWeightAndCalculateSingle`):
   ```
   amount = weight × price_per_unit
   ```

4. **Updates database**:
   - `pickup_requests.weight` = 12.50 kg
   - `pickup_requests.price` = calculated amount
   - `pickup_request_wastes.weight` = 12.50 kg
   - `pickup_request_wastes.amount` = calculated amount

5. **Returns to UI**: Shows calculated price to collector

### Example Calculation

If waste category is **Plastic** (price_per_unit = Rs 10/kg):
- Collector enters: 12.50 kg
- Calculation: 12.50 × 10 = **Rs 125.00**
- Displayed: "₹125.00"

---

## 📊 Waste Category Prices (Configured)

| Category | Price/kg | Database |
|----------|----------|----------|
| Plastic | Rs 10 | waste_categories |
| Paper | Rs 5 | waste_categories |
| Glass | Rs 8 | waste_categories |
| Metal | Rs 20 | waste_categories |
| Cardboard | Rs 3 | waste_categories |

To change prices:
1. Open DBeaver
2. Edit `waste_categories` table
3. Update `price_per_unit` values
4. Changes apply immediately

---

## 🧪 Testing Weight Feature

### 1. Manual Test in DBeaver

```sql
-- View waste category prices
SELECT id, name, price_per_unit FROM waste_categories;

-- Check a pickup's waste types
SELECT prw.*, wc.price_per_unit
FROM pickup_request_wastes prw
JOIN waste_categories wc ON wc.id = prw.waste_category_id
WHERE prw.pickup_id = 'PR001'
LIMIT 3;
```

### 2. Test via Web UI

1. Go to Collector Dashboard → My Assigned Pickups
2. Click "View Details" on a pickup
3. Enter weight in "Measured Weight (kg)" field
4. Click "Enter"
5. Should see calculated price displayed

### 3. Verify in DBeaver

```sql
-- Check the saved weight and price
SELECT id, weight, price, status FROM pickup_requests WHERE id = 'PR001';

-- Check pickup_request_wastes details
SELECT id, pickup_id, weight, amount FROM pickup_request_wastes WHERE pickup_id = 'PR001';
```

---

## 🐛 Troubleshooting

### Problem: "Failed to save weight"

**Check 1: Database connection**
```bash
php scripts/setup_database.php
```

**Check 2: Missing columns**
- Verify columns exist in DBeaver (see Verification section above)
- Run migration script if needed

**Check 3: Price_per_unit not set**
```sql
SELECT * FROM waste_categories WHERE price_per_unit = 0 OR price_per_unit IS NULL;
```
If any found, update them:
```sql
UPDATE waste_categories SET price_per_unit = 10 WHERE name = 'Plastic';
```

### Problem: Docker not running

```bash
# Start Docker containers
docker-compose -f docker-compose.dev.yml up -d

# Check status
docker-compose logs -f db
```

### Problem: Wrong connection credentials

Edit `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=eco_cycle
DB_USERNAME=ecocycle_user
DB_PASSWORD=ecocycle_dev_password
```

Then restart app:
```bash
docker-compose -f docker-compose.dev.yml restart app
```

---

## 📝 Database Schema Changes Made

### waste_categories
```sql
ALTER TABLE waste_categories 
ADD COLUMN price_per_unit DECIMAL(12,2) DEFAULT 0.00;
```

### pickup_request_wastes  
```sql
ALTER TABLE pickup_request_wastes
ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN amount DECIMAL(12,2) DEFAULT NULL;
```

### pickup_requests
```sql
ALTER TABLE pickup_requests
ADD COLUMN price DECIMAL(12,2) DEFAULT NULL;
```

---

## ✨ What's Ready

- ✅ Database schema updated with all required columns
- ✅ .env configured for PostgreSQL Docker
- ✅ Weight calculation logic implemented
- ✅ API endpoint fixed (early exit removed)
- ✅ JavaScript error handling improved
- ✅ Price per unit configured for all waste categories

## 🎯 Next Steps

1. **Verify setup**: Run `php scripts/setup_database.php`
2. **Check DBeaver**: Confirm all columns are present
3. **Test feature**: Try entering weight in collector dashboard
4. **Monitor logs**: Check for any errors

---

**Need Help?** Check:
- `docs/database-doc/DOCKER_SETUP_WITH_DBEAVER.md` - Docker setup
- `docs/api-doc/API_DOCUMENTATION.md` - API details
- Browser console - Frontend errors
- Docker logs: `docker-compose logs app`
