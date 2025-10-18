-- Demo seed data for company dashboard views
-- Run with: mysql -u <user> -p <database> < database/seeds/seed_company_dashboard_demo.sql
-- This script resets core tables and inserts a focused data set for the company dashboards.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE bids;
TRUNCATE TABLE bidding_rounds;
TRUNCATE TABLE payments;
TRUNCATE TABLE notifications;
TRUNCATE TABLE pickup_request_wastes;
TRUNCATE TABLE pickup_requests;
TRUNCATE TABLE waste_categories;
TRUNCATE TABLE users;
TRUNCATE TABLE vehicles;

SET FOREIGN_KEY_CHECKS = 1;

-- Ensure roles exist and capture the company role id for seeded companies
INSERT IGNORE INTO roles (name, label) VALUES
  ('admin', 'Administrator'),
  ('manager', 'Manager'),
  ('collector', 'Collector'),
  ('company', 'Company'),
  ('customer', 'Customer');

SET @role_company = (SELECT id FROM roles WHERE name = 'company' LIMIT 1);

START TRANSACTION;

-- Vehicles for collectors (not heavily used on company dashboard but keeps references intact)
INSERT INTO vehicles (plate_number, type, capacity, status, last_maintenance, next_maintenance, notes, created_at)
VALUES
  ('MC-4521', 'Medium Truck', 3200, 'available', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_ADD(NOW(), INTERVAL 70 DAY), 'Primary collection vehicle', NOW()),
  ('LC-8820', 'Light Van', 1500, 'in-use', DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_ADD(NOW(), INTERVAL 40 DAY), 'Backup van for urban pickups', NOW());

-- Waste categories visible across the dashboard widgets
INSERT INTO waste_categories (name, unit, default_minimum_bid, color, created_at)
VALUES
  ('Plastic', 'kg', 120.00, '#1B9AAA', NOW()),
  ('Metal', 'kg', 200.00, '#C3423F', NOW()),
  ('Glass', 'kg', 90.00, '#3A7CA5', NOW()),
  ('Paper', 'kg', 60.00, '#F5B700', NOW());

SET @cat_plastic = (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1);
SET @cat_metal = (SELECT id FROM waste_categories WHERE name = 'Metal' LIMIT 1);
SET @cat_glass = (SELECT id FROM waste_categories WHERE name = 'Glass' LIMIT 1);
SET @cat_paper = (SELECT id FROM waste_categories WHERE name = 'Paper' LIMIT 1);

-- Demo companies (BrightCycle is the primary dashboard user)
INSERT INTO users (type, role_id, name, email, phone, address, profile_image_path, password_hash, status, total_bids, total_purchases, metadata, created_at)
VALUES (
  'company',
  @role_company,
  'BrightCycle Industries',
  'brightcycle@example.com',
  '+94 77 123 4567',
  '54 River Way, Neo City',
  'assets/img/demo/company-brightcycle.png',
  'password',
  'active',
  18,
  12,
  JSON_OBJECT(
    'companyType', 'Materials Recovery Facility',
    'registrationNumber', 'BC-REG-2025-01',
    'description', 'BrightCycle specializes in high-volume plastic and metal recycling for manufacturing partners.',
    'website', 'https://brightcycle.example.com',
    'address', '54 River Way, Neo City',
    'waste_types', JSON_ARRAY('Plastic', 'Metal', 'Cardboard'),
    'verification', JSON_OBJECT('status', 'verified', 'lastAudit', '2025-07-11'),
    'bank_details', JSON_OBJECT('accountName', 'BrightCycle Industries', 'accountNumber', '947301234', 'bank', 'Eco Bank', 'swift', 'ECOCYCLELK'),
    'preferred_payment_method', 'bank_transfer'
  ),
  DATE_SUB(NOW(), INTERVAL 120 DAY)
);
SET @company_brightcycle_id = LAST_INSERT_ID();

INSERT INTO users (type, role_id, name, email, phone, address, password_hash, status, total_bids, total_purchases, metadata, created_at)
VALUES (
  'company',
  @role_company,
  'RenewAll Manufacturing',
  'procurement@renewall.example.com',
  '+94 77 987 6543',
  '81 Circular Avenue, Port Sterling',
  'password',
  'active',
  11,
  7,
  JSON_OBJECT(
    'companyType', 'Advanced Materials',
    'registrationNumber', 'RA-552-2024',
    'waste_types', JSON_ARRAY('Glass', 'Paper'),
    'preferred_payment_method', 'credit_card'
  ),
  DATE_SUB(NOW(), INTERVAL 150 DAY)
);
SET @company_renewall_id = LAST_INSERT_ID();

INSERT INTO users (type, role_id, name, email, phone, address, password_hash, status, total_bids, total_purchases, metadata, created_at)
VALUES (
  'company',
  @role_company,
  'NorthLoop Circular',
  'hello@northloop.example.com',
  '+94 11 445 8899',
  '22 Horizon Park, Green District',
  'password',
  'active',
  9,
  5,
  JSON_OBJECT(
    'companyType', 'Industrial Partner',
    'waste_types', JSON_ARRAY('Paper', 'Glass'),
    'preferred_payment_method', 'bank_transfer'
  ),
  DATE_SUB(NOW(), INTERVAL 200 DAY)
);
SET @company_northloop_id = LAST_INSERT_ID();

-- Bidding rounds spanning the last six months
INSERT INTO bidding_rounds (id, lot_id, waste_category_id, quantity, unit, starting_bid, current_highest_bid, leading_company_id, status, end_time, notes, created_at, updated_at)
VALUES
  ('BR-202510-PL-01', 'LOT-PL-202510', @cat_plastic, 1200, 'kg', 180000.00, 240000.00, @company_brightcycle_id, 'active', DATE_ADD(NOW(), INTERVAL 5 DAY), 'High-grade PET plastics collected from metro region.', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('BR-202509-GL-01', 'LOT-GL-202509', @cat_glass, 950, 'kg', 95000.00, 128000.00, @company_renewall_id, 'active', DATE_ADD(NOW(), INTERVAL 2 DAY), 'Mixed color glass cullet ready for processing.', DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('BR-202508-MT-01', 'LOT-MT-202508', @cat_metal, 850, 'kg', 250000.00, 315000.00, @company_brightcycle_id, 'completed', DATE_SUB(NOW(), INTERVAL 40 DAY), 'Sorted aluminum offcuts from industrial partners.', DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY)),
  ('BR-202507-PA-01', 'LOT-PA-202507', @cat_paper, 1400, 'kg', 70000.00, 90000.00, @company_northloop_id, 'completed', DATE_SUB(NOW(), INTERVAL 75 DAY), 'Baled office paper suitable for pulping.', DATE_SUB(NOW(), INTERVAL 95 DAY), DATE_SUB(NOW(), INTERVAL 75 DAY)),
  ('BR-202506-PL-02', 'LOT-PL-202506', @cat_plastic, 600, 'kg', 90000.00, 180000.00, @company_brightcycle_id, 'completed', DATE_SUB(NOW(), INTERVAL 105 DAY), 'Post-consumer LDPE film bundled for resale.', DATE_SUB(NOW(), INTERVAL 130 DAY), DATE_SUB(NOW(), INTERVAL 105 DAY)),
  ('BR-202505-MT-02', 'LOT-MT-202505', @cat_metal, 500, 'kg', 160000.00, 210000.00, @company_brightcycle_id, 'completed', DATE_SUB(NOW(), INTERVAL 135 DAY), 'Stainless steel scrap from manufacturing line.', DATE_SUB(NOW(), INTERVAL 160 DAY), DATE_SUB(NOW(), INTERVAL 135 DAY));

-- Bid history (BrightCycle highlighted, with competitors for context)
INSERT INTO bids (bidding_round_id, company_id, amount, is_winner, created_at)
VALUES
  ('BR-202510-PL-01', @company_renewall_id, 220000.00, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
  ('BR-202510-PL-01', @company_brightcycle_id, 240000.00, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('BR-202509-GL-01', @company_brightcycle_id, 122000.00, 0, DATE_SUB(NOW(), INTERVAL 25 DAY)),
  ('BR-202509-GL-01', @company_renewall_id, 128000.00, 0, DATE_SUB(NOW(), INTERVAL 24 DAY)),
  ('BR-202508-MT-01', @company_renewall_id, 300000.00, 0, DATE_SUB(NOW(), INTERVAL 58 DAY)),
  ('BR-202508-MT-01', @company_brightcycle_id, 315000.00, 1, DATE_SUB(NOW(), INTERVAL 55 DAY)),
  ('BR-202507-PA-01', @company_brightcycle_id, 88000.00, 0, DATE_SUB(NOW(), INTERVAL 82 DAY)),
  ('BR-202507-PA-01', @company_northloop_id, 90000.00, 1, DATE_SUB(NOW(), INTERVAL 80 DAY)),
  ('BR-202506-PL-02', @company_brightcycle_id, 180000.00, 1, DATE_SUB(NOW(), INTERVAL 108 DAY)),
  ('BR-202506-PL-02', @company_northloop_id, 175000.00, 0, DATE_SUB(NOW(), INTERVAL 110 DAY)),
  ('BR-202505-MT-02', @company_brightcycle_id, 210000.00, 1, DATE_SUB(NOW(), INTERVAL 135 DAY)),
  ('BR-202505-MT-02', @company_renewall_id, 198000.00, 0, DATE_SUB(NOW(), INTERVAL 136 DAY));

-- Payments reflecting pending and completed invoices for each company
INSERT INTO payments (id, txn_id, type, amount, recipient_id, recipient_name, date, status, created_at)
VALUES
  ('INV-202510-BC-01', 'TXN-BC-202510-01', 'payment', 240000.00, @company_brightcycle_id, 'BrightCycle Industries', DATE_SUB(NOW(), INTERVAL 1 DAY), 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('INV-202508-BC-01', 'TXN-BC-202508-01', 'payment', 315000.00, @company_brightcycle_id, 'BrightCycle Industries', DATE_SUB(NOW(), INTERVAL 38 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 38 DAY)),
  ('INV-202506-BC-01', 'TXN-BC-202506-01', 'payment', 180000.00, @company_brightcycle_id, 'BrightCycle Industries', DATE_SUB(NOW(), INTERVAL 100 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 100 DAY)),
  ('INV-202509-RA-01', 'TXN-RA-202509-01', 'payment', 128000.00, @company_renewall_id, 'RenewAll Manufacturing', DATE_SUB(NOW(), INTERVAL 24 DAY), 'pending', DATE_SUB(NOW(), INTERVAL 24 DAY)),
  ('INV-202507-NL-01', 'TXN-NL-202507-01', 'payment', 90000.00, @company_northloop_id, 'NorthLoop Circular', DATE_SUB(NOW(), INTERVAL 79 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 79 DAY));

-- Notifications targeting company recipients
INSERT INTO notifications (id, type, title, message, recipients, recipient_group, status, sent_at, created_by, created_at)
VALUES
  ('NTF-20251018-01', 'info', 'Schedule Reminder', 'Pickup schedule for LOT-PL-202510 is awaiting confirmation. Please review logistics.', JSON_ARRAY(CAST(@company_brightcycle_id AS CHAR)), NULL, 'sent', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('NTF-20251018-02', 'alert', 'Invoice Ready', 'Your invoice INV-202510-BC-01 is now available for download and payment.', JSON_ARRAY(CAST(@company_brightcycle_id AS CHAR), CAST(@company_renewall_id AS CHAR)), NULL, 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('NTF-20251018-03', 'announcement', 'Sustainability Webinar', 'Join our November session on optimizing recycled material supply chains.', NULL, 'company', 'pending', NULL, NULL, NOW());

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- End of demo seed
