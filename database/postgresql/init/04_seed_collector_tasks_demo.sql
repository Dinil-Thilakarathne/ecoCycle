-- Collector dashboard demo seed for PostgreSQL
--
-- Loads a demo collector with a few assigned pickup requests so the
-- collector dashboard/task UI shows realistic data. Safe to run multiple
-- times thanks to ON CONFLICT usage.

-- Ensure core reference data exists
INSERT INTO roles (name, label) 
VALUES ('collector', 'Collector')
ON CONFLICT (name) DO NOTHING;

INSERT INTO waste_categories (name, unit, created_at)
VALUES
  ('Plastic', 'kg', CURRENT_TIMESTAMP),
  ('Paper', 'kg', CURRENT_TIMESTAMP),
  ('Glass', 'kg', CURRENT_TIMESTAMP),
  ('Metal', 'kg', CURRENT_TIMESTAMP)
ON CONFLICT (name) DO UPDATE SET unit = EXCLUDED.unit;

-- Insert/update demo collector
WITH collector_insert AS (
  INSERT INTO users (type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, password_hash, total_pickups, created_at, updated_at)
  VALUES
    ('collector', 'Demo Collector', 'collector@ecocycle.com', '+94 71 000 0000', '42 Green Route, Eco City', 'Demo Collector', '776543210987', 'Eco Bank', 'Eco City Branch', 'active', 'password', 12, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
  ON CONFLICT (email) DO UPDATE SET
    name = EXCLUDED.name,
    phone = EXCLUDED.phone,
    address = EXCLUDED.address,
    bank_account_name = EXCLUDED.bank_account_name,
    bank_account_number = EXCLUDED.bank_account_number,
    bank_name = EXCLUDED.bank_name,
    bank_branch = EXCLUDED.bank_branch,
    status = EXCLUDED.status,
    total_pickups = EXCLUDED.total_pickups,
    updated_at = CURRENT_TIMESTAMP
  RETURNING id, name
)
SELECT id AS collector_id, name AS collector_name 
INTO TEMPORARY TABLE collector_info
FROM collector_insert;

-- If collector already existed, get their info
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM collector_info) THEN
    INSERT INTO collector_info
    SELECT id, name FROM users WHERE email = 'collector@ecocycle.com' LIMIT 1;
  END IF;
END $$;

-- Demo customers
INSERT INTO users (type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, password_hash, created_at, updated_at)
VALUES
  ('customer', 'Hasini Perera', 'customer1@ecocycle.com', '+94 77 100 2003', '15 Lake Road, Kandy', 'Hasini Perera', '701122334455', 'National Bank', 'Kandy Branch', 'active', 'password', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  ('customer', 'Ishara Silva', 'customer2@ecocycle.com', '+94 71 555 8899', '78 Palm Grove, Colombo 03', 'Ishara Silva', '702233445566', 'National Bank', 'Colombo 03 Branch', 'active', 'password', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  ('customer', 'Ruwani Fernando', 'customer3@ecocycle.com', '+94 75 440 2211', '9 Flower Avenue, Galle', 'Ruwani Fernando', '703344556677', 'National Bank', 'Galle Branch', 'active', 'password', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (email) DO UPDATE SET
  name = EXCLUDED.name,
  phone = EXCLUDED.phone,
  address = EXCLUDED.address,
  bank_account_name = EXCLUDED.bank_account_name,
  bank_account_number = EXCLUDED.bank_account_number,
  bank_name = EXCLUDED.bank_name,
  bank_branch = EXCLUDED.bank_branch,
  status = EXCLUDED.status,
  updated_at = CURRENT_TIMESTAMP;

-- Delete existing demo pickup requests to avoid duplicates
DELETE FROM pickup_request_wastes WHERE pickup_id IN ('PRCOL001', 'PRCOL002', 'PRCOL003');
DELETE FROM pickup_requests WHERE id IN ('PRCOL001', 'PRCOL002', 'PRCOL003');

-- Pickup requests assigned to the demo collector
INSERT INTO pickup_requests (id, customer_id, address, time_slot, status, collector_id, collector_name, scheduled_at, created_at, updated_at)
SELECT 
  'PRCOL001',
  (SELECT id FROM users WHERE email = 'customer1@ecocycle.com' LIMIT 1),
  '15 Lake Road, Kandy',
  '09:00-11:00',
  'assigned',
  ci.collector_id,
  ci.collector_name,
  CURRENT_DATE + INTERVAL '9 hours',
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
FROM collector_info ci
UNION ALL
SELECT 
  'PRCOL002',
  (SELECT id FROM users WHERE email = 'customer2@ecocycle.com' LIMIT 1),
  '78 Palm Grove, Colombo 03',
  '11:00-13:00',
  'in_progress',
  ci.collector_id,
  ci.collector_name,
  CURRENT_DATE + INTERVAL '11 hours',
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
FROM collector_info ci
UNION ALL
SELECT 
  'PRCOL003',
  (SELECT id FROM users WHERE email = 'customer3@ecocycle.com' LIMIT 1),
  '9 Flower Avenue, Galle',
  '14:00-16:00',
  'completed',
  ci.collector_id,
  ci.collector_name,
  CURRENT_DATE + INTERVAL '14 hours',
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
FROM collector_info ci;

-- Waste category breakdowns for the pickups
INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, quantity, unit)
VALUES
  ('PRCOL001', (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1), 15, 'kg'),
  ('PRCOL001', (SELECT id FROM waste_categories WHERE name = 'Paper' LIMIT 1), 6, 'kg'),
  ('PRCOL002', (SELECT id FROM waste_categories WHERE name = 'Glass' LIMIT 1), 20, 'kg'),
  ('PRCOL002', (SELECT id FROM waste_categories WHERE name = 'Metal' LIMIT 1), 10, 'kg'),
  ('PRCOL003', (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1), 8, 'kg')
ON CONFLICT (pickup_id, waste_category_id) DO UPDATE SET
  quantity = EXCLUDED.quantity,
  unit = EXCLUDED.unit;

-- Cleanup temporary table
DROP TABLE IF EXISTS collector_info;
