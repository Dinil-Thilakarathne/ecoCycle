-- Add missing columns for weight calculation feature
-- This migration adds the necessary columns for the collector weight saving functionality

-- 1. Add price_per_unit to waste_categories
ALTER TABLE waste_categories
ADD COLUMN IF NOT EXISTS price_per_unit DECIMAL(12,2) DEFAULT 0.00;

-- 2. Add weight and amount to pickup_request_wastes
ALTER TABLE pickup_request_wastes
ADD COLUMN IF NOT EXISTS weight DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS amount DECIMAL(12,2) DEFAULT NULL;

-- 3. Add price column to pickup_requests
ALTER TABLE pickup_requests
ADD COLUMN IF NOT EXISTS price DECIMAL(12,2) DEFAULT NULL;

-- 4. Update waste_categories with sample prices (per kg)
-- Plastic: Rs 10 per kg
-- Paper: Rs 5 per kg
-- Glass: Rs 8 per kg
-- Metal: Rs 20 per kg
-- Cardboard: Rs 3 per kg

UPDATE waste_categories SET price_per_unit = 10.00 WHERE name = 'Plastic' AND price_per_unit = 0.00;
UPDATE waste_categories SET price_per_unit = 5.00 WHERE name = 'Paper' AND price_per_unit = 0.00;
UPDATE waste_categories SET price_per_unit = 8.00 WHERE name = 'Glass' AND price_per_unit = 0.00;
UPDATE waste_categories SET price_per_unit = 20.00 WHERE name = 'Metal' AND price_per_unit = 0.00;
UPDATE waste_categories SET price_per_unit = 3.00 WHERE name = 'Cardboard' AND price_per_unit = 0.00;
