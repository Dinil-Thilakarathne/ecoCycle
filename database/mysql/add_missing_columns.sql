-- Add missing columns for weight calculation feature (MySQL version)
-- This migration adds the necessary columns for the collector weight saving functionality

-- 1. Add price_per_unit to waste_categories if not exists
ALTER TABLE waste_categories
ADD COLUMN price_per_unit DECIMAL(12,2) DEFAULT 0.00 AFTER default_minimum_bid;

-- 2. Add weight and amount to pickup_request_wastes if not exists  
ALTER TABLE pickup_request_wastes
ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL AFTER quantity,
ADD COLUMN amount DECIMAL(12,2) DEFAULT NULL AFTER weight;

-- 3. Add price column to pickup_requests if not exists
ALTER TABLE pickup_requests
ADD COLUMN price DECIMAL(12,2) DEFAULT NULL AFTER weight;

-- 4. Update waste_categories with sample prices (per kg)
-- Plastic: Rs 10 per kg
-- Paper: Rs 5 per kg
-- Glass: Rs 8 per kg
-- Metal: Rs 20 per kg
-- Cardboard: Rs 3 per kg

UPDATE waste_categories SET price_per_unit = 10.00 WHERE name = 'Plastic';
UPDATE waste_categories SET price_per_unit = 5.00 WHERE name = 'Paper';
UPDATE waste_categories SET price_per_unit = 8.00 WHERE name = 'Glass';
UPDATE waste_categories SET price_per_unit = 20.00 WHERE name = 'Metal';
UPDATE waste_categories SET price_per_unit = 3.00 WHERE name = 'Cardboard';

-- 5. Create indexes for new columns
CREATE INDEX idx_prw_weight ON pickup_request_wastes (weight);
CREATE INDEX idx_prw_amount ON pickup_request_wastes (amount);
CREATE INDEX idx_pickup_price ON pickup_requests (price);
