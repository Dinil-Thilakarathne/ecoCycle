-- Create waste_category_events table for tracking real-time updates

-- MySQL version
CREATE TABLE IF NOT EXISTS waste_category_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at DESC)
);
