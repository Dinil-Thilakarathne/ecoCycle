-- Seed script to import data matching config/dummy.php into the database
-- This script assumes the schema created by database/create_tables.sql is already applied.
-- It maps legacy string IDs (e.g., C001, VH001) into numeric PKs for tables that use INT ids.

SET FOREIGN_KEY_CHECKS = 0;

-- Temporary mapping tables
DROP TABLE IF EXISTS temp_vehicle_map;
CREATE TEMPORARY TABLE temp_vehicle_map (legacy_id VARCHAR(64) PRIMARY KEY, new_id INT);

DROP TABLE IF EXISTS temp_user_map;
CREATE TEMPORARY TABLE temp_user_map (legacy_id VARCHAR(64) PRIMARY KEY, new_id INT, user_type VARCHAR(32));

-- 1) Waste categories
INSERT IGNORE INTO waste_categories (name, unit, created_at)
VALUES
  ('Plastic','kg', NOW()),
  ('Paper','kg', NOW()),
  ('Glass','kg', NOW()),
  ('Metal','kg', NOW()),
  ('Cardboard','kg', NOW());

-- 2) Vehicles (insert and track legacy -> new id)
-- Vehicles data from config/dummy.php
INSERT INTO vehicles (plate_number, type, capacity, status, last_maintenance, next_maintenance, notes, created_at)
VALUES
  ('ABC-1234','Pickup Truck',2000,'available','2025-08-01','2025-11-01', JSON_OBJECT('legacy_id','VH001'), NOW()),
  ('XYZ-5678','Van',1500,'in-use','2025-07-15','2025-10-15', JSON_OBJECT('legacy_id','VH002'), NOW()),
  ('DEF-9012','Pickup Truck',2000,'maintenance','2025-08-10','2025-11-10', JSON_OBJECT('legacy_id','VH003'), NOW()),
  ('GHI-3456','Small Truck',3000,'available','2025-06-20','2025-09-20', JSON_OBJECT('legacy_id','VH004'), NOW()),
  ('JKL-7890','Van',1200,'in-use','2025-08-05','2025-11-05', JSON_OBJECT('legacy_id','VH005'), NOW());

-- Populate temp_vehicle_map with mappings of legacy -> inserted id by matching plate_number
-- (we stored legacy in notes, but plate_number is unique so we can map by plate)
INSERT INTO temp_vehicle_map (legacy_id, new_id)
SELECT JSON_UNQUOTE(JSON_EXTRACT(notes, '$.legacy_id')) AS legacy, id
FROM vehicles
WHERE JSON_EXTRACT(notes, '$.legacy_id') IS NOT NULL;

-- 3) Roles (ensure roles exist)
INSERT IGNORE INTO roles (name, label)
VALUES
  ('admin','Administrator'),
  ('manager','Manager'),
  ('collector','Collector'),
  ('company','Company'),
  ('customer','Customer');

-- 4) Users: customers
-- Insert customers and record mapping legacy id -> new numeric id in temp_user_map
INSERT INTO users (role_id, type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, total_pickups, total_earnings, created_at, metadata)
VALUES
  (2, 'customer','Alice Johnson','alice@email.com','+1234567890','123 Green St, Eco City','Alice Johnson','7000112233','Eco Bank','Eco City Branch','active',15,12550.00, NOW(), JSON_OBJECT('legacy_id','C001')),
  (2, 'customer','Bob Smith','bob@email.com','+1234567891','456 Recycle Ave, Green Town','Bob Smith','7000112234','Eco Bank','Green Town Branch','active',8,6725.00, NOW(), JSON_OBJECT('legacy_id','C002')),
  (2, 'customer','Carol Davis','carol@email.com','+1234567892','789 Eco Blvd, Sustainable City','Carol Davis','7000112235','Eco Bank','Sustainable City Branch','suspended',22,18075.00, NOW(), JSON_OBJECT('legacy_id','C003')),
  (2, 'customer','David Wilson','david@email.com','+1234567893','321 Green Ave, Eco Valley','David Wilson','7000112236','Eco Bank','Eco Valley Branch','pending',0,0.00, NOW(), JSON_OBJECT('legacy_id','C004'));

-- Populate temp_user_map for these customers
INSERT INTO temp_user_map (legacy_id, new_id, user_type)
SELECT JSON_UNQUOTE(JSON_EXTRACT(u.metadata, '$.legacy_id')) AS legacy, u.id, u.type
FROM users u
WHERE u.type = 'customer' AND JSON_EXTRACT(u.metadata, '$.legacy_id') IS NOT NULL;

-- 5) Companies
INSERT INTO users (role_id, type, name, email, phone, bank_account_name, bank_account_number, bank_name, bank_branch, status, total_bids, total_purchases, created_at, metadata)
VALUES
  (4, 'company','GreenTech Co.','contact@greentech.com','+1234567892','GreenTech Co.','910000111','National Bank','Eco City HQ','active',45,32, NOW(), JSON_OBJECT('legacy_id','CO001')),
  (4, 'company','EcoRecycle Ltd.','info@ecorecycle.com','+1234567893','EcoRecycle Ltd.','910000112','National Bank','Green Town Branch','pending',12,0, NOW(), JSON_OBJECT('legacy_id','CO002')),
  (4, 'company','WasteWorks Inc.','admin@wasteworks.com','+1234567894','WasteWorks Inc.','910000113','National Bank','Sustainable City Branch','active',28,19, NOW(), JSON_OBJECT('legacy_id','CO003')),
  (4, 'company','RecyclePro Solutions','hello@recyclepro.com','+1234567895','RecyclePro Solutions','910000114','National Bank','Eco Valley Branch','suspended',15,8, NOW(), JSON_OBJECT('legacy_id','CO004'));

INSERT INTO temp_user_map (legacy_id, new_id, user_type)
SELECT JSON_UNQUOTE(JSON_EXTRACT(u.metadata, '$.legacy_id')), u.id, u.type
FROM users u
WHERE u.type = 'company' AND JSON_EXTRACT(u.metadata, '$.legacy_id') IS NOT NULL;

-- 6) Collectors (link vehicle_id via temp_vehicle_map)
-- For collectors, find new vehicle id using temp_vehicle_map
INSERT INTO users (type, name, email, phone, bank_account_name, bank_account_number, bank_name, bank_branch, vehicle_id, status, total_pickups, created_at, metadata)
SELECT 'collector', c.name, c.email, c.phone,
       c.name,
       c.bankAccountNumber,
       c.bankName,
       c.bankBranch,
       tv.new_id AS vehicle_id,
       c.status,
       c.todayPickups,
       NOW(),
       JSON_OBJECT('legacy_id', c.id)
FROM (
  SELECT 'COL001' AS id, 'Mike Wilson' AS name, 'mike@company.com' AS email, '+1234567894' AS phone, 'VH001' AS vehicleId, 'active' AS status, 6 AS todayPickups, '7000998877' AS bankAccountNumber, 'Eco Bank' AS bankName, 'Central Branch' AS bankBranch
  UNION ALL
  SELECT 'COL002','Sarah Brown','sarah@company.com','+1234567895','VH002','offline',0,'7000998878','Eco Bank','Downtown Branch'
  UNION ALL
  SELECT 'COL003','Tom Garcia','tom@company.com','+1234567896','VH003','active',4,'7000998879','Eco Bank','Uptown Branch'
  UNION ALL
  SELECT 'COL004','Lisa Martinez','lisa@company.com','+1234567897','VH004','pending',0,'7000998880','Eco Bank','Harbor Branch'
) AS c
LEFT JOIN temp_vehicle_map tv ON tv.legacy_id = c.vehicleId;

INSERT INTO temp_user_map (legacy_id, new_id, user_type)
SELECT JSON_UNQUOTE(JSON_EXTRACT(u.metadata, '$.legacy_id')), u.id, u.type
FROM users u
WHERE u.type = 'collector' AND JSON_EXTRACT(u.metadata, '$.legacy_id') IS NOT NULL;

-- 7) Payments
INSERT INTO payments (id, txn_id, type, amount, recipient_name, status, date, created_at)
VALUES
  ('PAY001', 'PAY001', 'payout', 12550.00, 'Alice Johnson', 'completed', '2025-08-28', NOW()),
  ('PAY002', 'PAY002', 'payment', 45000.00, 'GreenTech Co.', 'pending', '2025-08-28', NOW()),
  ('PAY003', 'PAY003', 'payout', 6725.00, 'Bob Smith', 'completed', '2025-08-27', NOW()),
  ('PAY004', 'PAY004', 'payment', 32000.00, 'EcoWaste Solutions', 'completed', '2025-08-27', NOW()),
  ('PAY005', 'PAY005', 'payment', 27575.00, 'RecycleCorp Ltd.', 'completed', '2025-08-26', NOW()),
  ('PAY006', 'PAY006', 'payout', 8930.00, 'Charlie Davis', 'pending', '2025-08-26', NOW());

-- Optionally link payments.recipient_id when a user with same name exists
UPDATE payments p
LEFT JOIN users u ON u.name = p.recipient_name
SET p.recipient_id = u.id
WHERE p.recipient_id IS NULL AND u.id IS NOT NULL;

-- 8) Bidding rounds
INSERT INTO bidding_rounds (id, lot_id, waste_category_id, quantity, unit, current_highest_bid, leading_company_id, status, end_time, created_at)
VALUES
  ('BR001','LOT001',(SELECT id FROM waste_categories WHERE name='Plastic' LIMIT 1),500,'kg',250000.00, (SELECT new_id FROM temp_user_map WHERE legacy_id='CO001' LIMIT 1), 'active', DATE_ADD(NOW(), INTERVAL 2 HOUR), NOW()),
  ('BR002','LOT002',(SELECT id FROM waste_categories WHERE name='Cardboard' LIMIT 1),1200,'kg',180000.00, (SELECT new_id FROM temp_user_map WHERE legacy_id='CO002' LIMIT 1), 'active', DATE_ADD(NOW(), INTERVAL 4 HOUR), NOW()),
  ('BR003','LOT003',(SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1),300,'kg',450000.00, NULL, 'completed', DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW()),
  ('BR004','LOT004',(SELECT id FROM waste_categories WHERE name='Glass' LIMIT 1),800,'kg',320000.00, NULL, 'active', DATE_ADD(NOW(), INTERVAL 6 HOUR), NOW()),
  ('BR005','LOT005',(SELECT id FROM waste_categories WHERE name='Paper' LIMIT 1),150,'kg',600000.00, NULL, 'completed', DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW());

-- 9) Pickup requests and pickup_request_wastes
INSERT INTO pickup_requests (id, customer_id, address, time_slot, status, collector_id, collector_name, created_at)
VALUES
  ('PR001', (SELECT new_id FROM temp_user_map WHERE legacy_id='C001' LIMIT 1), '123 Green St, Eco City', '09:00-11:00', 'pending', NULL, NULL, NOW()),
  ('PR002', (SELECT new_id FROM temp_user_map WHERE legacy_id='C002' LIMIT 1), '456 Recycle Ave, Green Town', '11:00-13:00', 'assigned', (SELECT new_id FROM temp_user_map WHERE legacy_id='COL001' LIMIT 1), 'Mike Wilson', NOW()),
  ('PR003', (SELECT new_id FROM temp_user_map WHERE legacy_id='C003' LIMIT 1), '789 Eco Blvd, Sustainable City', '14:00-16:00', 'pending', NULL, NULL, NOW()),
  ('PR004', (SELECT new_id FROM temp_user_map WHERE legacy_id='C004' LIMIT 1), '321 Sustainability Lane, Green Valley', '16:00-18:00', 'completed', (SELECT new_id FROM temp_user_map WHERE legacy_id='COL002' LIMIT 1), 'Sarah Brown', NOW());

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
  ('PR004', (SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1), NULL, 'kg');

-- 10) Time slots could be kept in config; no DB table needed. But we can insert into analytics_aggregates as helper
INSERT INTO analytics_aggregates (`key`, `value`, `computed_at`)
VALUES ('time_slots', JSON_ARRAY('09:00-11:00','11:00-13:00','14:00-16:00','16:00-18:00'), NOW())
ON DUPLICATE KEY UPDATE value = VALUES(value), computed_at = VALUES(computed_at);

SET FOREIGN_KEY_CHECKS = 1;

-- Cleanup: drop temp tables will happen automatically at end of session

-- End of seed
