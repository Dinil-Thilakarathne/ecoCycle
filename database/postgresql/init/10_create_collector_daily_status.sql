-- PostgreSQL Migration: Create collector_daily_status table
-- Purpose: Track daily availability status for each collector-vehicle pair

CREATE TABLE IF NOT EXISTS collector_daily_status (
  id SERIAL PRIMARY KEY,
  collector_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  date DATE NOT NULL,
  is_available BOOLEAN DEFAULT true,
  status_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT unique_collector_date UNIQUE(collector_id, date)
);

-- Create indexes for performance
CREATE INDEX idx_cds_collector ON collector_daily_status(collector_id);
CREATE INDEX idx_cds_date ON collector_daily_status(date);
CREATE INDEX idx_cds_vehicle ON collector_daily_status(vehicle_id);
CREATE INDEX idx_cds_availability ON collector_daily_status(is_available);

-- Add foreign key constraints
ALTER TABLE collector_daily_status 
  ADD CONSTRAINT fk_cds_collector 
  FOREIGN KEY (collector_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE collector_daily_status 
  ADD CONSTRAINT fk_cds_vehicle 
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Add comment
COMMENT ON TABLE collector_daily_status IS 'Tracks daily availability status for collectors and their assigned vehicles';
