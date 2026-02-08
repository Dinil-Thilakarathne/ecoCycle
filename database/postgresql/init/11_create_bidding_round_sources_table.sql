-- Create linking table to track which pickup requests contributed to each bidding round
-- This enables traceability from collected waste to bidding rounds

CREATE TABLE IF NOT EXISTS bidding_round_sources (
    id SERIAL PRIMARY KEY,
    bidding_round_id VARCHAR(64) NOT NULL,
    pickup_id VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_brs_round FOREIGN KEY (bidding_round_id) 
        REFERENCES bidding_rounds(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_brs_pickup FOREIGN KEY (pickup_id) 
        REFERENCES pickup_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT unique_round_pickup UNIQUE (bidding_round_id, pickup_id)
);

-- Create indexes for performance
CREATE INDEX idx_brs_round ON bidding_round_sources(bidding_round_id);
CREATE INDEX idx_brs_pickup ON bidding_round_sources(pickup_id);

-- Add comment for documentation
COMMENT ON TABLE bidding_round_sources IS 'Links bidding rounds to the pickup requests that provided the waste';
COMMENT ON COLUMN bidding_round_sources.bidding_round_id IS 'Reference to the bidding round';
COMMENT ON COLUMN bidding_round_sources.pickup_id IS 'Reference to the pickup request that contributed waste';
