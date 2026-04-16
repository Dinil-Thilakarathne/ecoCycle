-- Feedback & Ratings Table for ecoCycle Analytics
-- For tracking collector feedback and ratings from customers

CREATE TABLE IF NOT EXISTS collector_feedback (
    id SERIAL PRIMARY KEY,
    collector_id INT NOT NULL,
    customer_id INT,
    pickup_request_id VARCHAR(255),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    feedback TEXT,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'archived', 'flagged')),
    feedback_type VARCHAR(50) DEFAULT 'general' CHECK (feedback_type IN ('general', 'complaint', 'praise', 'issue')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collector_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (pickup_request_id) REFERENCES pickup_requests(id) ON DELETE SET NULL
);

-- Index for faster queries
CREATE INDEX idx_collector_feedback_collector_id ON collector_feedback(collector_id);
CREATE INDEX idx_collector_feedback_rating ON collector_feedback(rating);
CREATE INDEX idx_collector_feedback_status ON collector_feedback(status);
CREATE INDEX idx_collector_feedback_created_at ON collector_feedback(created_at);

-- Trigger for updated_at
CREATE OR REPLACE FUNCTION update_collector_feedback_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER collector_feedback_update_trigger
BEFORE UPDATE ON collector_feedback
FOR EACH ROW
EXECUTE FUNCTION update_collector_feedback_timestamp();
