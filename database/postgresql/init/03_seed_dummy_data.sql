-- PostgreSQL seed script to import dummy data
-- This script assumes the schema created by 01_create_tables.sql is already applied.
-- It maps legacy string IDs (e.g., C001, VH001) into numeric PKs for tables that use SERIAL ids.

-- Temporary mapping tables
DROP TABLE IF EXISTS temp_vehicle_map;
CREATE TEMPORARY TABLE temp_vehicle_map (legacy_id VARCHAR(64) PRIMARY KEY, new_id INT);

DROP TABLE IF EXISTS temp_user_map;
CREATE TEMPORARY TABLE temp_user_map (legacy_id VARCHAR(64) PRIMARY KEY, new_id INT, user_type VARCHAR(32));

-- 1) Waste categories
INSERT INTO waste_categories (name, unit, created_at)
VALUES
  ('Plastic','kg', CURRENT_TIMESTAMP),
  ('Paper','kg', CURRENT_TIMESTAMP),
  ('Glass','kg', CURRENT_TIMESTAMP),
  ('Metal','kg', CURRENT_TIMESTAMP),
  ('Cardboard','kg', CURRENT_TIMESTAMP)
ON CONFLICT (name) DO NOTHING;

-- 2) Vehicles (insert and track legacy -> new id)
-- Vehicles data from config/dummy.php
INSERT INTO vehicles (plate_number, type, capacity, status, last_maintenance, next_maintenance, notes, created_at)
VALUES
  ('ABC-1234','Pickup Truck',2000,'available','2025-08-01','2025-11-01', '{"legacy_id":"VH001"}', CURRENT_TIMESTAMP),
  ('XYZ-5678','Large Truck',5000,'in-use','2025-07-15','2025-10-15', '{"legacy_id":"VH002"}', CURRENT_TIMESTAMP),
  ('DEF-9012','Pickup Truck',2000,'maintenance','2025-08-10','2025-11-10', '{"legacy_id":"VH003"}', CURRENT_TIMESTAMP),
  ('GHI-3456','Small Truck',3000,'available','2025-06-20','2025-09-20', '{"legacy_id":"VH004"}', CURRENT_TIMESTAMP),
  ('JKL-7890','Small Truck',3000,'in-use','2025-08-05','2025-11-05', '{"legacy_id":"VH005"}', CURRENT_TIMESTAMP)
ON CONFLICT (plate_number) DO UPDATE SET
  type = EXCLUDED.type,
  capacity = EXCLUDED.capacity,
  status = EXCLUDED.status,
  last_maintenance = EXCLUDED.last_maintenance,
  next_maintenance = EXCLUDED.next_maintenance,
  notes = EXCLUDED.notes;

-- Populate temp_vehicle_map with mappings of legacy -> inserted id by matching plate_number
INSERT INTO temp_vehicle_map (legacy_id, new_id)
SELECT notes::json->>'legacy_id' AS legacy, id
FROM vehicles
WHERE notes::json->>'legacy_id' IS NOT NULL;

-- 3) Roles (ensure roles exist - should already be seeded by 01_create_tables.sql)
INSERT INTO roles (name, label)
VALUES
  ('admin','Administrator'),
  ('manager','Manager'),
  ('collector','Collector'),
  ('company','Company'),
  ('customer','Customer')
ON CONFLICT (name) DO NOTHING;

-- 4) Users: customers
-- Insert customers and record mapping legacy id -> new numeric id in temp_user_map
INSERT INTO users (role_id, type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, total_pickups, total_earnings, created_at, metadata)
VALUES
  ((SELECT id FROM roles WHERE name='customer' LIMIT 1), 'customer','Alice Johnson','alice@email.com','+1234567890','123 Green St, Eco City','Alice Johnson','7000112233','Eco Bank','Eco City Branch','active',15,12550.00, CURRENT_TIMESTAMP, '{"legacy_id":"C001"}'),
  ((SELECT id FROM roles WHERE name='customer' LIMIT 1), 'customer','Bob Smith','bob@email.com','+1234567891','456 Recycle Ave, Green Town','Bob Smith','7000112234','Eco Bank','Green Town Branch','active',8,6725.00, CURRENT_TIMESTAMP, '{"legacy_id":"C002"}'),
  ((SELECT id FROM roles WHERE name='customer' LIMIT 1), 'customer','Carol Davis','carol@email.com','+1234567892','789 Eco Blvd, Sustainable City','Carol Davis','7000112235','Eco Bank','Sustainable City Branch','suspended',22,18075.00, CURRENT_TIMESTAMP, '{"legacy_id":"C003"}'),
  ((SELECT id FROM roles WHERE name='customer' LIMIT 1), 'customer','David Wilson','david@email.com','+1234567893','321 Green Ave, Eco Valley','David Wilson','7000112236','Eco Bank','Eco Valley Branch','pending',0,0.00, CURRENT_TIMESTAMP, '{"legacy_id":"C004"}')
ON CONFLICT (email) DO UPDATE SET
  name = EXCLUDED.name,
  phone = EXCLUDED.phone,
  address = EXCLUDED.address;

-- Populate temp_user_map for these customers
INSERT INTO temp_user_map (legacy_id, new_id, user_type)
SELECT u.metadata::json->>'legacy_id' AS legacy, u.id, u.type
FROM users u
WHERE u.type = 'customer' AND u.metadata::json->>'legacy_id' IS NOT NULL;

-- 5) Companies
INSERT INTO users (role_id, type, name, email, phone, bank_account_name, bank_account_number, bank_name, bank_branch, status, total_bids, total_purchases, created_at, metadata)
VALUES
  ((SELECT id FROM roles WHERE name='company' LIMIT 1), 'company','GreenTech Co.','contact@greentech.com','+1234567892','GreenTech Co.','910000111','National Bank','Eco City HQ','active',45,32, CURRENT_TIMESTAMP, '{"legacy_id":"CO001"}'),
  ((SELECT id FROM roles WHERE name='company' LIMIT 1), 'company','EcoRecycle Ltd.','info@ecorecycle.com','+1234567893','EcoRecycle Ltd.','910000112','National Bank','Green Town Branch','pending',12,0, CURRENT_TIMESTAMP, '{"legacy_id":"CO002"}'),
  ((SELECT id FROM roles WHERE name='company' LIMIT 1), 'company','WasteWorks Inc.','admin@wasteworks.com','+1234567894','WasteWorks Inc.','910000113','National Bank','Sustainable City Branch','active',28,19, CURRENT_TIMESTAMP, '{"legacy_id":"CO003"}'),
  ((SELECT id FROM roles WHERE name='company' LIMIT 1), 'company','RecyclePro Solutions','hello@recyclepro.com','+1234567895','RecyclePro Solutions','910000114','National Bank','Eco Valley Branch','suspended',15,8, CURRENT_TIMESTAMP, '{"legacy_id":"CO004"}')
ON CONFLICT (email) DO UPDATE SET
  name = EXCLUDED.name,
  phone = EXCLUDED.phone;

INSERT INTO temp_user_map (legacy_id, new_id, user_type)
SELECT u.metadata::json->>'legacy_id', u.id, u.type
FROM users u
WHERE u.type = 'company' AND u.metadata::json->>'legacy_id' IS NOT NULL;

-- 6) Collectors (link vehicle_id via temp_vehicle_map)
WITH collector_data AS (
  SELECT 'COL001' AS legacy_id, 'Mike Wilson' AS name, 'mike@company.com' AS email, '+1234567894' AS phone, 
         'VH001' AS vehicleId, 'active' AS status, 6 AS todayPickups, 
         '7000998877' AS bankAccountNumber, 'Eco Bank' AS bankName, 'Central Branch' AS bankBranch
  UNION ALL
  SELECT 'COL002','Sarah Brown','sarah@company.com','+1234567895','VH002','offline',0,
         '7000998878','Eco Bank','Downtown Branch'
  UNION ALL
  SELECT 'COL003','Tom Garcia','tom@company.com','+1234567896','VH003','active',4,
         '7000998879','Eco Bank','Uptown Branch'
  UNION ALL
  SELECT 'COL004','Lisa Martinez','lisa@company.com','+1234567897','VH004','pending',0,
         '7000998880','Eco Bank','Harbor Branch'
)
INSERT INTO users (type, name, email, phone, bank_account_name, bank_account_number, bank_name, bank_branch, vehicle_id, status, total_pickups, created_at, metadata)
SELECT 
  'collector',
  c.name,
  c.email,
  c.phone,
  c.name,
  c.bankAccountNumber,
  c.bankName,
  c.bankBranch,
  tv.new_id AS vehicle_id,
  c.status,
  c.todayPickups,
  CURRENT_TIMESTAMP,
  ('{"legacy_id":"' || c.legacy_id || '"}')::jsonb
FROM collector_data c
LEFT JOIN temp_vehicle_map tv ON tv.legacy_id = c.vehicleId
ON CONFLICT (email) DO UPDATE SET
  name = EXCLUDED.name,
  phone = EXCLUDED.phone;

INSERT INTO temp_user_map (legacy_id, new_id, user_type)
SELECT u.metadata::json->>'legacy_id', u.id, u.type
FROM users u
WHERE u.type = 'collector' AND u.metadata::json->>'legacy_id' IS NOT NULL;

-- 7) Payments
INSERT INTO payments (id, txn_id, type, amount, recipient_name, status, date, created_at)
VALUES
  ('PAY001', 'PAY001', 'payout', 12550.00, 'Alice Johnson', 'completed', '2025-08-28', CURRENT_TIMESTAMP),
  ('PAY002', 'PAY002', 'payment', 45000.00, 'GreenTech Co.', 'pending', '2025-08-28', CURRENT_TIMESTAMP),
  ('PAY003', 'PAY003', 'payout', 6725.00, 'Bob Smith', 'completed', '2025-08-27', CURRENT_TIMESTAMP),
  ('PAY004', 'PAY004', 'payment', 32000.00, 'EcoWaste Solutions', 'completed', '2025-08-27', CURRENT_TIMESTAMP),
  ('PAY005', 'PAY005', 'payment', 27575.00, 'RecycleCorp Ltd.', 'completed', '2025-08-26', CURRENT_TIMESTAMP),
  ('PAY006', 'PAY006', 'payout', 8930.00, 'Charlie Davis', 'pending', '2025-08-26', CURRENT_TIMESTAMP)
ON CONFLICT (id) DO NOTHING;

-- Optionally link payments.recipient_id when a user with same name exists
UPDATE payments p
SET recipient_id = u.id
FROM users u
WHERE p.recipient_name = u.name 
  AND p.recipient_id IS NULL;

-- 8) Bidding rounds
INSERT INTO bidding_rounds (id, lot_id, waste_category_id, quantity, unit, current_highest_bid, leading_company_id, status, end_time, created_at)
VALUES
  ('BR001','LOT001',(SELECT id FROM waste_categories WHERE name='Plastic' LIMIT 1),500,'kg',250000.00, (SELECT new_id FROM temp_user_map WHERE legacy_id='CO001' LIMIT 1), 'active', CURRENT_TIMESTAMP + INTERVAL '2 hours', CURRENT_TIMESTAMP),
  ('BR002','LOT002',(SELECT id FROM waste_categories WHERE name='Cardboard' LIMIT 1),1200,'kg',180000.00, (SELECT new_id FROM temp_user_map WHERE legacy_id='CO002' LIMIT 1), 'active', CURRENT_TIMESTAMP + INTERVAL '4 hours', CURRENT_TIMESTAMP),
  ('BR003','LOT003',(SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1),300,'kg',450000.00, NULL, 'completed', CURRENT_TIMESTAMP - INTERVAL '1 hour', CURRENT_TIMESTAMP),
  ('BR004','LOT004',(SELECT id FROM waste_categories WHERE name='Glass' LIMIT 1),800,'kg',320000.00, NULL, 'active', CURRENT_TIMESTAMP + INTERVAL '6 hours', CURRENT_TIMESTAMP),
  ('BR005','LOT005',(SELECT id FROM waste_categories WHERE name='Paper' LIMIT 1),150,'kg',600000.00, NULL, 'completed', CURRENT_TIMESTAMP - INTERVAL '2 hours', CURRENT_TIMESTAMP)
ON CONFLICT (id) DO NOTHING;

-- 9) Pickup requests and pickup_request_wastes
INSERT INTO pickup_requests (id, customer_id, address, time_slot, status, collector_id, collector_name, created_at)
VALUES
  ('PR001', (SELECT new_id FROM temp_user_map WHERE legacy_id='C001' LIMIT 1), '123 Green St, Eco City', '09:00-11:00', 'pending', NULL, NULL, CURRENT_TIMESTAMP),
  ('PR002', (SELECT new_id FROM temp_user_map WHERE legacy_id='C002' LIMIT 1), '456 Recycle Ave, Green Town', '11:00-13:00', 'assigned', (SELECT new_id FROM temp_user_map WHERE legacy_id='COL001' LIMIT 1), 'Mike Wilson', CURRENT_TIMESTAMP),
  ('PR003', (SELECT new_id FROM temp_user_map WHERE legacy_id='C003' LIMIT 1), '789 Eco Blvd, Sustainable City', '14:00-16:00', 'pending', NULL, NULL, CURRENT_TIMESTAMP),
  ('PR004', (SELECT new_id FROM temp_user_map WHERE legacy_id='C004' LIMIT 1), '321 Sustainability Lane, Green Valley', '16:00-18:00', 'completed', (SELECT new_id FROM temp_user_map WHERE legacy_id='COL002' LIMIT 1), 'Sarah Brown', CURRENT_TIMESTAMP)
ON CONFLICT (id) DO NOTHING;

-- pickup_request_wastes: map names to waste_category ids
INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, quantity, unit)
VALUES
  ('PR001', (SELECT id FROM waste_categories WHERE name='Plastic' LIMIT 1), NULL, 'kg'),
  ('PR001', (SELECT id FROM waste_categories WHERE name='Paper' LIMIT 1), NULL, 'kg'),
  ('PR002', (SELECT id FROM waste_categories WHERE name='Glass' LIMIT 1), NULL, 'kg'),
  ('PR002', (SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1), NULL, 'kg'),
  ('PR003', (SELECT id FROM waste_categories WHERE name='Paper' LIMIT 1), NULL, 'kg'),
  ('PR003', (SELECT id FROM waste_categories WHERE name='Cardboard' LIMIT 1), NULL, 'kg'),
  ('PR004', (SELECT id FROM waste_categories WHERE name='Plastic' LIMIT 1), NULL, 'kg'),
  ('PR004', (SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1), NULL, 'kg')
ON CONFLICT (pickup_id, waste_category_id) DO NOTHING;

-- 10) Analytics aggregates
INSERT INTO analytics_aggregates (key, value, computed_at)
VALUES ('time_slots', '["09:00-11:00","11:00-13:00","14:00-16:00","16:00-18:00"]', CURRENT_TIMESTAMP)
ON CONFLICT (key) DO UPDATE SET 
  value = EXCLUDED.value, 
  computed_at = EXCLUDED.computed_at;

-- Cleanup: temp tables are dropped automatically at end of session

-- End of seed
