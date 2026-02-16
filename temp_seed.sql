-- Update waste categories with prices
UPDATE waste_categories SET price_per_unit = 10.00 WHERE name = 'Plastic';
UPDATE waste_categories SET price_per_unit = 5.00 WHERE name = 'Paper';
UPDATE waste_categories SET price_per_unit = 8.00 WHERE name = 'Glass';
UPDATE waste_categories SET price_per_unit = 20.00 WHERE name = 'Metal';
UPDATE waste_categories SET price_per_unit = 3.00 WHERE name = 'Cardboard';

-- Update existing waste records with weight and amount
UPDATE pickup_request_wastes prw
SET 
    weight = COALESCE(prw.weight, prw.quantity),
    amount = COALESCE(prw.amount, prw.quantity * (
        SELECT COALESCE(price_per_unit, 10.00) 
        FROM waste_categories 
        WHERE id = prw.waste_category_id
    ))
WHERE prw.weight IS NULL OR prw.amount IS NULL;

-- Get collector IDs for seeding
DO $$
DECLARE
    collector_id INT;
    customer_ids INT[];
    customer_id INT;
    rating_val INT;
    descriptions TEXT[] := ARRAY[
        'Excellent service! Very professional and punctual.',
        'Good collector, arrived on time and handled waste carefully.',
        'Decent service but could be more friendly.',
        'Average service, nothing special.',
        'Great job! Very satisfied with the collection process.'
    ];
BEGIN
    -- Get first collector
    SELECT id INTO collector_id FROM users WHERE type = 'collector' LIMIT 1;
    
    IF collector_id IS NOT NULL THEN
        -- Get up to 5 customers
        SELECT ARRAY_AGG(id) INTO customer_ids 
        FROM (SELECT id FROM users WHERE type = 'customer' LIMIT 5) sub;
        
        -- Insert ratings for each customer if they don't  exist
        FOR i IN 1..array_length(customer_ids, 1) LOOP
            customer_id := customer_ids[i];
            rating_val := 6 - i; -- 5, 4, 3, 2, 1
            
            INSERT INTO collector_ratings 
            (customer_id, collector_id, collector_name, rating, description, rating_date, created_at) 
            SELECT 
                customer_id,
                collector_id,
                (SELECT name FROM users WHERE id = collector_id),
                rating_val,
                descriptions[i],
                CURRENT_DATE - (i || ' days')::INTERVAL,
                NOW()
            WHERE NOT EXISTS (
                SELECT 1 FROM collector_ratings 
                WHERE collector_id = collector_id AND customer_id = customer_id
            );
        END LOOP;
    END IF;
END $$;

-- Show results
SELECT 'Waste Records Updated:' as status, COUNT(*) as count FROM pickup_request_wastes WHERE weight IS NOT NULL
UNION ALL
SELECT 'Collector Ratings:' as status, COUNT(*) as count FROM collector_ratings;
