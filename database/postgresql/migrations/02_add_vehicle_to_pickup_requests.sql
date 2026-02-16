-- Migration: Add vehicle_id to pickup_requests table
-- This allows tracking which vehicle is assigned to each pickup request

-- Add vehicle_id column to pickup_requests table
ALTER TABLE pickup_requests 
ADD COLUMN IF NOT EXISTS vehicle_id INT DEFAULT NULL;

-- Create index for vehicle_id
CREATE INDEX IF NOT EXISTS idx_pickup_vehicle ON pickup_requests (vehicle_id);

-- Add foreign key constraint
ALTER TABLE pickup_requests 
ADD CONSTRAINT fk_pickup_vehicle 
FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Add comment to explain the column
COMMENT ON COLUMN pickup_requests.vehicle_id IS 'Vehicle assigned to this pickup request. Status managed automatically based on pickup request lifecycle.';
