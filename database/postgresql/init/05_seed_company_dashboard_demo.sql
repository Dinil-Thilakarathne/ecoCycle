-- Demo seed data for company dashboard views - PostgreSQL version
-- This script resets core tables and inserts a focused data set for the company dashboards.

-- Note: TRUNCATE requires special handling in PostgreSQL
-- We'll use DELETE instead for better compatibility
DELETE FROM bids;
DELETE FROM bidding_rounds;
DELETE FROM payments WHERE type = 'payment'; -- Keep user payouts, remove company payments
DELETE FROM notifications WHERE recipient_group = 'company';
DELETE FROM pickup_request_wastes;
DELETE FROM pickup_requests;
-- Don't delete waste_categories or users as they may be needed by other seeds

-- Ensure roles exist and get the company role id
INSERT INTO roles (name, label) VALUES
  ('admin', 'Administrator'),
  ('manager', 'Manager'),
  ('collector', 'Collector'),
  ('company', 'Company'),
  ('customer', 'Customer')
ON CONFLICT (name) DO NOTHING;

-- Vehicles for collectors (not heavily used on company dashboard but keeps references intact)
INSERT INTO vehicles (plate_number, type, capacity, status, last_maintenance, next_maintenance, notes, created_at)
VALUES
  ('MC-4521', 'Medium Truck', 3200, 'available', CURRENT_DATE - INTERVAL '20 days', CURRENT_DATE + INTERVAL '70 days', 'Primary collection vehicle', CURRENT_TIMESTAMP),
  ('LC-8820', 'Light Van', 1500, 'in-use', CURRENT_DATE - INTERVAL '45 days', CURRENT_DATE + INTERVAL '40 days', 'Backup van for urban pickups', CURRENT_TIMESTAMP)
ON CONFLICT (plate_number) DO UPDATE SET
  type = EXCLUDED.type,
  capacity = EXCLUDED.capacity,
  status = EXCLUDED.status;

-- Waste categories visible across the dashboard widgets
INSERT INTO waste_categories (name, unit, default_minimum_bid, color, created_at)
VALUES
  ('Plastic', 'kg', 120.00, '#1B9AAA', CURRENT_TIMESTAMP),
  ('Metal', 'kg', 200.00, '#C3423F', CURRENT_TIMESTAMP),
  ('Glass', 'kg', 90.00, '#3A7CA5', CURRENT_TIMESTAMP),
  ('Paper', 'kg', 60.00, '#F5B700', CURRENT_TIMESTAMP)
ON CONFLICT (name) DO UPDATE SET
  unit = EXCLUDED.unit,
  default_minimum_bid = EXCLUDED.default_minimum_bid,
  color = EXCLUDED.color;

-- Demo companies (BrightCycle is the primary dashboard user)
INSERT INTO users (type, role_id, name, email, phone, address, bank_account_name, bank_account_number, bank_name, bank_branch, profile_image_path, password_hash, status, total_bids, total_purchases, metadata, created_at)
VALUES (
  'company',
  (SELECT id FROM roles WHERE name = 'company' LIMIT 1),
  'BrightCycle Industries',
  'brightcycle@example.com',
  '+94 77 123 4567',
  '54 River Way, Neo City',
  'BrightCycle Industries',
  '947301234',
  'Eco Bank',
  'Neo City Branch',
  'assets/img/demo/company-brightcycle.png',
  'password',
  'active',
  18,
  12,
  jsonb_build_object(
    'companyType', 'Materials Recovery Facility',
    'registrationNumber', 'BC-REG-2025-01',
    'description', 'BrightCycle specializes in high-volume plastic and metal recycling for manufacturing partners.',
    'website', 'https://brightcycle.example.com',
    'address', '54 River Way, Neo City',
    'waste_types', jsonb_build_array('Plastic', 'Metal', 'Cardboard'),
    'verification', jsonb_build_object('status', 'verified', 'lastAudit', '2025-07-11'),
    'bank_details', jsonb_build_object('accountName', 'BrightCycle Industries', 'accountNumber', '947301234', 'bank', 'Eco Bank', 'swift', 'ECOCYCLELK'),
    'preferred_payment_method', 'bank_transfer'
  ),
  CURRENT_TIMESTAMP - INTERVAL '120 days'
),
(
  'company',
  (SELECT id FROM roles WHERE name = 'company' LIMIT 1),
  'RenewAll Manufacturing',
  'procurement@renewall.example.com',
  '+94 77 987 6543',
  '81 Circular Avenue, Port Sterling',
  'RenewAll Manufacturing',
  '935002211',
  'First Capital Bank',
  'Port Sterling Branch',
  NULL,
  'password',
  'active',
  11,
  7,
  jsonb_build_object(
    'companyType', 'Advanced Materials',
    'registrationNumber', 'RA-552-2024',
    'waste_types', jsonb_build_array('Glass', 'Paper'),
    'preferred_payment_method', 'credit_card'
  ),
  CURRENT_TIMESTAMP - INTERVAL '150 days'
),
(
  'company',
  (SELECT id FROM roles WHERE name = 'company' LIMIT 1),
  'NorthLoop Circular',
  'hello@northloop.example.com',
  '+94 11 445 8899',
  '22 Horizon Park, Green District',
  'NorthLoop Circular',
  '944785210',
  'Eco Bank',
  'Green District Branch',
  NULL,
  'password',
  'active',
  9,
  5,
  jsonb_build_object(
    'companyType', 'Industrial Partner',
    'waste_types', jsonb_build_array('Paper', 'Glass'),
    'preferred_payment_method', 'bank_transfer'
  ),
  CURRENT_TIMESTAMP - INTERVAL '200 days'
)
ON CONFLICT (email) DO UPDATE SET
  name = EXCLUDED.name,
  phone = EXCLUDED.phone,
  status = EXCLUDED.status,
  total_bids = EXCLUDED.total_bids,
  total_purchases = EXCLUDED.total_purchases,
  metadata = EXCLUDED.metadata;

-- Bidding rounds spanning the last six months
INSERT INTO bidding_rounds (id, lot_id, waste_category_id, quantity, unit, starting_bid, current_highest_bid, leading_company_id, status, end_time, notes, created_at, updated_at)
VALUES
  ('BR-202510-PL-01', 'LOT-PL-202510', (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1), 1200, 'kg', 180000.00, 240000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'active', CURRENT_TIMESTAMP + INTERVAL '5 days', 'High-grade PET plastics collected from metro region.', CURRENT_TIMESTAMP - INTERVAL '10 days', CURRENT_TIMESTAMP - INTERVAL '2 days'),
  ('BR-202509-GL-01', 'LOT-GL-202509', (SELECT id FROM waste_categories WHERE name = 'Glass' LIMIT 1), 950, 'kg', 95000.00, 128000.00, (SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1), 'active', CURRENT_TIMESTAMP + INTERVAL '2 days', 'Mixed color glass cullet ready for processing.', CURRENT_TIMESTAMP - INTERVAL '35 days', CURRENT_TIMESTAMP - INTERVAL '1 day'),
  ('BR-202508-MT-01', 'LOT-MT-202508', (SELECT id FROM waste_categories WHERE name = 'Metal' LIMIT 1), 850, 'kg', 250000.00, 315000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'completed', CURRENT_TIMESTAMP - INTERVAL '40 days', 'Sorted aluminum offcuts from industrial partners.', CURRENT_TIMESTAMP - INTERVAL '70 days', CURRENT_TIMESTAMP - INTERVAL '40 days'),
  ('BR-202507-PA-01', 'LOT-PA-202507', (SELECT id FROM waste_categories WHERE name = 'Paper' LIMIT 1), 1400, 'kg', 70000.00, 90000.00, (SELECT id FROM users WHERE email = 'hello@northloop.example.com' LIMIT 1), 'completed', CURRENT_TIMESTAMP - INTERVAL '75 days', 'Baled office paper suitable for pulping.', CURRENT_TIMESTAMP - INTERVAL '95 days', CURRENT_TIMESTAMP - INTERVAL '75 days'),
  ('BR-202506-PL-02', 'LOT-PL-202506', (SELECT id FROM waste_categories WHERE name = 'Plastic' LIMIT 1), 600, 'kg', 90000.00, 180000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'completed', CURRENT_TIMESTAMP - INTERVAL '105 days', 'Post-consumer LDPE film bundled for resale.', CURRENT_TIMESTAMP - INTERVAL '130 days', CURRENT_TIMESTAMP - INTERVAL '105 days'),
  ('BR-202505-MT-02', 'LOT-MT-202505', (SELECT id FROM waste_categories WHERE name = 'Metal' LIMIT 1), 500, 'kg', 160000.00, 210000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'completed', CURRENT_TIMESTAMP - INTERVAL '135 days', 'Stainless steel scrap from manufacturing line.', CURRENT_TIMESTAMP - INTERVAL '160 days', CURRENT_TIMESTAMP - INTERVAL '135 days')
ON CONFLICT (id) DO UPDATE SET
  current_highest_bid = EXCLUDED.current_highest_bid,
  leading_company_id = EXCLUDED.leading_company_id,
  status = EXCLUDED.status,
  updated_at = EXCLUDED.updated_at;

-- Bid history (BrightCycle highlighted, with competitors for context)
INSERT INTO bids (bidding_round_id, company_id, amount, is_winner, created_at)
VALUES
  ('BR-202510-PL-01', (SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1), 220000.00, false, CURRENT_TIMESTAMP - INTERVAL '4 days'),
  ('BR-202510-PL-01', (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 240000.00, false, CURRENT_TIMESTAMP - INTERVAL '2 days'),
  ('BR-202509-GL-01', (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 122000.00, false, CURRENT_TIMESTAMP - INTERVAL '25 days'),
  ('BR-202509-GL-01', (SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1), 128000.00, false, CURRENT_TIMESTAMP - INTERVAL '24 days'),
  ('BR-202508-MT-01', (SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1), 300000.00, false, CURRENT_TIMESTAMP - INTERVAL '58 days'),
  ('BR-202508-MT-01', (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 315000.00, true, CURRENT_TIMESTAMP - INTERVAL '55 days'),
  ('BR-202507-PA-01', (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 88000.00, false, CURRENT_TIMESTAMP - INTERVAL '82 days'),
  ('BR-202507-PA-01', (SELECT id FROM users WHERE email = 'hello@northloop.example.com' LIMIT 1), 90000.00, true, CURRENT_TIMESTAMP - INTERVAL '80 days'),
  ('BR-202506-PL-02', (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 180000.00, true, CURRENT_TIMESTAMP - INTERVAL '108 days'),
  ('BR-202506-PL-02', (SELECT id FROM users WHERE email = 'hello@northloop.example.com' LIMIT 1), 175000.00, false, CURRENT_TIMESTAMP - INTERVAL '110 days'),
  ('BR-202505-MT-02', (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 210000.00, true, CURRENT_TIMESTAMP - INTERVAL '135 days'),
  ('BR-202505-MT-02', (SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1), 198000.00, false, CURRENT_TIMESTAMP - INTERVAL '136 days');

-- Payments reflecting pending and completed invoices for each company
INSERT INTO payments (id, txn_id, type, amount, recipient_id, recipient_name, date, status, created_at)
VALUES
  ('INV-202510-BC-01', 'TXN-BC-202510-01', 'payment', 240000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'BrightCycle Industries', CURRENT_DATE - INTERVAL '1 day', 'pending', CURRENT_TIMESTAMP - INTERVAL '1 day'),
  ('INV-202508-BC-01', 'TXN-BC-202508-01', 'payment', 315000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'BrightCycle Industries', CURRENT_DATE - INTERVAL '38 days', 'completed', CURRENT_TIMESTAMP - INTERVAL '38 days'),
  ('INV-202506-BC-01', 'TXN-BC-202506-01', 'payment', 180000.00, (SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1), 'BrightCycle Industries', CURRENT_DATE - INTERVAL '100 days', 'completed', CURRENT_TIMESTAMP - INTERVAL '100 days'),
  ('INV-202509-RA-01', 'TXN-RA-202509-01', 'payment', 128000.00, (SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1), 'RenewAll Manufacturing', CURRENT_DATE - INTERVAL '24 days', 'pending', CURRENT_TIMESTAMP - INTERVAL '24 days'),
  ('INV-202507-NL-01', 'TXN-NL-202507-01', 'payment', 90000.00, (SELECT id FROM users WHERE email = 'hello@northloop.example.com' LIMIT 1), 'NorthLoop Circular', CURRENT_DATE - INTERVAL '79 days', 'completed', CURRENT_TIMESTAMP - INTERVAL '79 days')
ON CONFLICT (id) DO UPDATE SET
  status = EXCLUDED.status,
  amount = EXCLUDED.amount;

-- Notifications targeting company recipients
INSERT INTO notifications (id, type, title, message, recipients, recipient_group, status, sent_at, created_by, created_at)
VALUES
  ('NTF-20251018-01', 'info', 'Schedule Reminder', 'Pickup schedule for LOT-PL-202510 is awaiting confirmation. Please review logistics.', jsonb_build_array(CAST((SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1) AS TEXT)), NULL, 'sent', CURRENT_TIMESTAMP - INTERVAL '2 days', NULL, CURRENT_TIMESTAMP - INTERVAL '2 days'),
  ('NTF-20251018-02', 'alert', 'Invoice Ready', 'Your invoice INV-202510-BC-01 is now available for download and payment.', jsonb_build_array(
    CAST((SELECT id FROM users WHERE email = 'brightcycle@example.com' LIMIT 1) AS TEXT),
    CAST((SELECT id FROM users WHERE email = 'procurement@renewall.example.com' LIMIT 1) AS TEXT)
  ), NULL, 'sent', CURRENT_TIMESTAMP - INTERVAL '1 day', NULL, CURRENT_TIMESTAMP - INTERVAL '1 day'),
  ('NTF-20251018-03', 'announcement', 'Sustainability Webinar', 'Join our November session on optimizing recycled material supply chains.', NULL, 'company', 'pending', NULL, NULL, CURRENT_TIMESTAMP)
ON CONFLICT (id) DO UPDATE SET
  status = EXCLUDED.status,
  message = EXCLUDED.message;

-- End of demo seed
