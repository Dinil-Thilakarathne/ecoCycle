-- Create view for waste inventory tracking
-- Shows collected vs committed waste for each category

CREATE OR REPLACE VIEW waste_inventory AS
SELECT 
    wc.id AS category_id,
    wc.name AS category_name,
    wc.unit,
    wc.price_per_unit,
    -- Total collected from completed pickups
    COALESCE(SUM(prw.weight), 0) AS total_collected,
    -- Total committed in active/awarded bidding rounds
    COALESCE(
        (SELECT SUM(br.quantity) 
         FROM bidding_rounds br 
         WHERE br.waste_category_id = wc.id 
         AND br.status NOT IN ('cancelled')), 
        0
    ) AS total_committed,
    -- Available = Collected - Committed
    COALESCE(SUM(prw.weight), 0) - COALESCE(
        (SELECT SUM(br.quantity) 
         FROM bidding_rounds br 
         WHERE br.waste_category_id = wc.id 
         AND br.status NOT IN ('cancelled')), 
        0
    ) AS available_quantity,
    -- Count of completed pickups
    COUNT(DISTINCT CASE WHEN pr.status = 'completed' THEN pr.id END) AS pickup_count,
    -- Total value of collected waste
    COALESCE(SUM(prw.amount), 0) AS total_value
FROM waste_categories wc
LEFT JOIN pickup_request_wastes prw ON prw.waste_category_id = wc.id
LEFT JOIN pickup_requests pr ON pr.id = prw.pickup_id AND pr.status = 'completed'
GROUP BY wc.id, wc.name, wc.unit, wc.price_per_unit
ORDER BY wc.name;

-- Add comment for documentation
COMMENT ON VIEW waste_inventory IS 'Real-time view of waste inventory showing collected, committed, and available quantities by category';
