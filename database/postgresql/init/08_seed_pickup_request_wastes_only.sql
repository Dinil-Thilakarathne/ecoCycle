-- Seed ONLY pickup_request_wastes table
-- This script safely inserts waste items for existing pickup requests

-- 9) Pickup requests and pickup_request_wastes
-- Note: We assume PR001, PR002, etc. already exist in pickup_requests from previous seeds.
-- If they don't exist, these inserts will fail due to foreign key constraints.

INSERT INTO pickup_request_wastes (pickup_id, waste_category_id, quantity, unit)
VALUES
  -- Items for PR001
  ('PR001', (SELECT id FROM waste_categories WHERE name='Plastic' LIMIT 1), NULL, 'kg'),
  ('PR001', (SELECT id FROM waste_categories WHERE name='Paper' LIMIT 1), NULL, 'kg'),
  
  -- Items for PR002
  ('PR002', (SELECT id FROM waste_categories WHERE name='Glass' LIMIT 1), NULL, 'kg'),
  ('PR002', (SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1), NULL, 'kg'),
  
  -- Items for PR003
  ('PR003', (SELECT id FROM waste_categories WHERE name='Paper' LIMIT 1), NULL, 'kg'),
  ('PR003', (SELECT id FROM waste_categories WHERE name='Cardboard' LIMIT 1), NULL, 'kg'),
  
  -- Items for PR004
  ('PR004', (SELECT id FROM waste_categories WHERE name='Plastic' LIMIT 1), NULL, 'kg'),
  ('PR004', (SELECT id FROM waste_categories WHERE name='Metal' LIMIT 1), NULL, 'kg')
ON CONFLICT DO NOTHING;
