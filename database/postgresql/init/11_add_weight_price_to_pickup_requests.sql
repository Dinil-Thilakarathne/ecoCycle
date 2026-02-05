-- Add weight and price columns to pickup_requests table
-- These columns store the total weight and calculated price for completed pickups

-- Add weight column (total weight in kg)
ALTER TABLE pickup_requests 
ADD COLUMN IF NOT EXISTS weight DECIMAL(10, 2) DEFAULT NULL;

-- Add price column (total calculated price)
ALTER TABLE pickup_requests 
ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) DEFAULT NULL;

-- Add comments for documentation
COMMENT ON COLUMN pickup_requests.weight IS 'Total weight of all waste categories in kg (sum of pickup_request_wastes.weight)';
COMMENT ON COLUMN pickup_requests.price IS 'Total calculated price based on waste category rates (sum of pickup_request_wastes.amount)';

-- Optional: Add index for performance if querying by weight/price
CREATE INDEX IF NOT EXISTS idx_pickup_requests_weight ON pickup_requests(weight) WHERE weight IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_pickup_requests_price ON pickup_requests(price) WHERE price IS NOT NULL;
