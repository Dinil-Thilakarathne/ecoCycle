-- Add missing columns for weight calculation feature
-- This migration adds the necessary columns for the collector weight saving functionality

-- 2. Add weight and amount to pickup_request_wastes
ALTER TABLE pickup_request_wastes
ADD COLUMN IF NOT EXISTS weight DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS amount DECIMAL(12,2) DEFAULT NULL;

-- 3. Add price column to pickup_requests
ALTER TABLE pickup_requests
ADD COLUMN IF NOT EXISTS price DECIMAL(12,2) DEFAULT NULL;

;
