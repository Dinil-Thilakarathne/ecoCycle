-- Add weight column to pickup_requests table
ALTER TABLE pickup_requests
ADD COLUMN IF NOT EXISTS weight DECIMAL(10,2) DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_pickup_weight ON pickup_requests (weight);
