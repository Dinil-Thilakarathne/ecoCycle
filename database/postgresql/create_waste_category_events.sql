-- Create waste_category_events table for tracking real-time updates

-- PostgreSQL version
CREATE TABLE IF NOT EXISTS waste_category_events (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at DESC)
);

-- Add index for better performance on queries
CREATE INDEX IF NOT EXISTS idx_waste_category_events_created_at 
ON waste_category_events(created_at DESC);
