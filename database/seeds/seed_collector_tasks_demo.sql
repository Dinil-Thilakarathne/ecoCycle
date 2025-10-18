-- Collector dashboard demo seed
--
-- Loads a demo collector with a few assigned pickup requests so the
-- collector dashboard/task UI shows realistic data. Safe to run multiple
-- times thanks to INSERT ... ON DUPLICATE KEY UPDATE usage.
--
-- Usage:
--   mysql -u <user> -p <database> < database/seeds/seed_collector_tasks_demo.sql

SET FOREIGN_KEY_CHECKS = 0;
SET @now := NOW();

-- Ensure core reference data exists
INSERT IGNORE INTO roles (name, label) VALUES
  ('collector', 'Collector');

INSERT INTO waste_categories (name, unit, created_at)
VALUES
  ('Plastic', 'kg', @now),
  ('Paper', 'kg', @now),
  ('Glass', 'kg', @now),
  ('Metal', 'kg', @now)
ON DUPLICATE KEY UPDATE unit = VALUES(unit);

INSERT INTO users (type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, password_hash, total_pickups, created_at, updated_at)
VALUES
  ('collector', 'Demo Collector', 'collector@ecocycle.com', '+94 71 000 0000', '42 Green Route, Eco City', 'Demo Collector', '776543210987', 'Eco Bank', 'Eco City Branch', 'active', 'password', 12, @now, @now)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  phone = VALUES(phone),
  address = VALUES(address),
  bank_account_name = VALUES(bank_account_name),
  bank_account_number = VALUES(bank_account_number),
  bank_name = VALUES(bank_name),
  bank_branch = VALUES(bank_branch),
  status = VALUES(status),
  total_pickups = VALUES(total_pickups),
  updated_at = @now,
  id = LAST_INSERT_ID(id);

SET @collector_id := LAST_INSERT_ID();
SET @collector_name := (SELECT name FROM users WHERE id = @collector_id LIMIT 1);

-- Demo customers
INSERT INTO users (type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, password_hash, created_at, updated_at)
VALUES
  ('customer', 'Hasini Perera', 'customer1@ecocycle.com', '+94 77 100 2003', '15 Lake Road, Kandy', 'Hasini Perera', '701122334455', 'National Bank', 'Kandy Branch', 'active', 'password', @now, @now)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  phone = VALUES(phone),
  address = VALUES(address),
  bank_account_name = VALUES(bank_account_name),
  bank_account_number = VALUES(bank_account_number),
  bank_name = VALUES(bank_name),
  bank_branch = VALUES(bank_branch),
  status = VALUES(status),
  updated_at = @now,
  id = LAST_INSERT_ID(id);
SET @customer1_id := LAST_INSERT_ID();

INSERT INTO users (type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, password_hash, created_at, updated_at)
VALUES
  ('customer', 'Ishara Silva', 'customer2@ecocycle.com', '+94 71 555 8899', '78 Palm Grove, Colombo 03', 'Ishara Silva', '702233445566', 'National Bank', 'Colombo 03 Branch', 'active', 'password', @now, @now)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  phone = VALUES(phone),
  address = VALUES(address),
  bank_account_name = VALUES(bank_account_name),
  bank_account_number = VALUES(bank_account_number),
  bank_name = VALUES(bank_name),
  bank_branch = VALUES(bank_branch),
  status = VALUES(status),
  updated_at = @now,
  id = LAST_INSERT_ID(id);
SET @customer2_id := LAST_INSERT_ID();

INSERT INTO users (type, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, status, password_hash, created_at, updated_at)
VALUES
  ('customer', 'Ruwani Fernando', 'customer3@ecocycle.com', '+94 75 440 2211', '9 Flower Avenue, Galle', 'Ruwani Fernando', '703344556677', 'National Bank', 'Galle Branch', 'active', 'password', @now, @now)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  phone = VALUES(phone),
  address = VALUES(address),
  bank_account_name = VALUES(bank_account_name),
  bank_account_number = VALUES(bank_account_number),
  bank_name = VALUES(bank_name),
  bank_branch = VALUES(bank_branch),
  status = VALUES(status),
  updated_at = @now,
  id = LAST_INSERT_ID(id);
SET @customer3_id := LAST_INSERT_ID();

-- Pickup requests assigned to the demo collector
DELETE FROM pickup_request_wastes WHERE pickup_id IN ('PRCOL001', 'PRCOL002', 'PRCOL003');
DELETE FROM pickup_requests WHERE id IN ('PRCOL001', 'PRCOL002', 'PRCOL003');

INSERT INTO pickup_requests (id, customer_id, address, time_slot, status, collector_id, collector_name, scheduled_at, created_at, updated_at)
VALUES
  ('PRCOL001', @customer1_id, '15 Lake Road, Kandy', '09:00-11:00', 'assigned', @collector_id, @collector_name, DATE_ADD(CURDATE(), INTERVAL 9 HOUR), @now, @now),
  ('PRCOL002', @customer2_id, '78 Palm Grove, Colombo 03', '11:00-13:00', 'in_progress', @collector_id, @collector_name, DATE_ADD(CURDATE(), INTERVAL 11 HOUR), @now, @now),
  ('PRCOL003', @customer3_id, '9 Flower Avenue, Galle', '14:00-16:00', 'completed', @collector_id, @collector_name, DATE_ADD(CURDATE(), INTERVAL 14 HOUR), @now, @now);

-- Waste category breakdowns for the pickups
INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, quantity, unit)
VALUES
  ('PRCOL001', (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1), 15, 'kg'),
  ('PRCOL001', (SELECT id FROM waste_categories WHERE name = 'Paper' LIMIT 1), 6, 'kg'),
  ('PRCOL002', (SELECT id FROM waste_categories WHERE name = 'Glass' LIMIT 1), 20, 'kg'),
  ('PRCOL002', (SELECT id FROM waste_categories WHERE name = 'Metal' LIMIT 1), 10, 'kg'),
  ('PRCOL003', (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1), 8, 'kg')
ON DUPLICATE KEY UPDATE
  quantity = VALUES(quantity),
  unit = VALUES(unit);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
