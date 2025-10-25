-- ============================================================================
-- PostgreSQL Seed Script for Main Tables
-- ============================================================================
-- Purpose: Seed essential data into roles, waste_categories, and analytics_aggregates
-- Tables: roles, waste_categories, analytics_aggregates
-- Created: 2025-10-25
-- ============================================================================

-- Start transaction for atomic seeding
BEGIN;

-- ============================================================================
-- 1. ROLES TABLE
-- ============================================================================
-- Insert core user roles with descriptions
-- These are the fundamental roles used throughout the application
-- ============================================================================

INSERT INTO roles (name, label, created_at) VALUES
  ('admin', 'Administrator', CURRENT_TIMESTAMP),
  ('manager', 'Manager', CURRENT_TIMESTAMP),
  ('collector', 'Waste Collector', CURRENT_TIMESTAMP),
  ('company', 'Recycling Company', CURRENT_TIMESTAMP),
  ('customer', 'Customer', CURRENT_TIMESTAMP)
ON CONFLICT (name) DO UPDATE SET
  label = EXCLUDED.label,
  created_at = EXCLUDED.created_at;

-- ============================================================================
-- 2. WASTE CATEGORIES TABLE
-- ============================================================================
-- Insert the 5 fixed waste categories with minimum bid values
-- Minimum bid values are in Sri Lankan Rupees (Rs) per kg
-- ============================================================================

INSERT INTO waste_categories (name, color, default_minimum_bid, unit, created_at, updated_at) VALUES
  -- Plastic - Most common recyclable with moderate value
  ('Plastic', '#3B82F6', 350.00, 'kg', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  
  -- Paper - Lower value recyclable
  ('Paper', '#10B981', 200.00, 'kg', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  
  -- Glass - Moderate value, fragile material
  ('Glass', '#06B6D4', 400.00, 'kg', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  
  -- Metal - High value recyclable (includes aluminum, steel, copper)
  ('Metal', '#F59E0B', 1500.00, 'kg', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  
  -- Cardboard - Similar to paper but bulkier
  ('Cardboard', '#8B5CF6', 150.00, 'kg', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (name) DO UPDATE SET
  color = EXCLUDED.color,
  default_minimum_bid = EXCLUDED.default_minimum_bid,
  unit = EXCLUDED.unit,
  updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- 3. ANALYTICS AGGREGATES TABLE
-- ============================================================================
-- Initialize analytics cache with timestamp markers and empty data structures
-- This table is used for caching computed analytics to improve performance
-- ============================================================================

INSERT INTO analytics_aggregates (key, value, computed_at) VALUES
  -- System initialization timestamp
  ('system.initialized_at', 
   json_build_object(
     'timestamp', CURRENT_TIMESTAMP,
     'version', '1.0.0',
     'database', 'postgresql'
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- Last analytics computation timestamp
  ('analytics.last_computed', 
   json_build_object(
     'timestamp', CURRENT_TIMESTAMP,
     'status', 'initialized'
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- Dashboard statistics cache (empty initial state)
  ('dashboard.stats', 
   json_build_object(
     'total_users', 0,
     'active_customers', 0,
     'active_companies', 0,
     'active_collectors', 0,
     'pending_pickups', 0,
     'completed_pickups', 0,
     'active_bidding_rounds', 0,
     'completed_bidding_rounds', 0,
     'total_revenue', 0,
     'last_updated', CURRENT_TIMESTAMP
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- Waste category statistics cache (empty initial state)
  ('waste_categories.stats', 
   json_build_object(
     'Plastic', json_build_object('total_collected', 0, 'total_bids', 0, 'avg_price', 0),
     'Paper', json_build_object('total_collected', 0, 'total_bids', 0, 'avg_price', 0),
     'Glass', json_build_object('total_collected', 0, 'total_bids', 0, 'avg_price', 0),
     'Metal', json_build_object('total_collected', 0, 'total_bids', 0, 'avg_price', 0),
     'Cardboard', json_build_object('total_collected', 0, 'total_bids', 0, 'avg_price', 0),
     'last_updated', CURRENT_TIMESTAMP
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- Monthly revenue tracking (current month)
  ('revenue.monthly', 
   json_build_object(
     'year', EXTRACT(YEAR FROM CURRENT_TIMESTAMP),
     'month', EXTRACT(MONTH FROM CURRENT_TIMESTAMP),
     'total', 0,
     'pickups', 0,
     'bidding_rounds', 0,
     'last_updated', CURRENT_TIMESTAMP
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- System health metrics
  ('system.health', 
   json_build_object(
     'status', 'healthy',
     'uptime_days', 0,
     'active_sessions', 0,
     'last_backup', NULL,
     'database_size_mb', 0,
     'last_checked', CURRENT_TIMESTAMP
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- Popular waste categories cache
  ('waste_categories.popular', 
   json_build_array()::jsonb, 
   CURRENT_TIMESTAMP),

  -- Recent activity feed cache (empty)
  ('activity.recent', 
   json_build_array()::jsonb, 
   CURRENT_TIMESTAMP),

  -- Collector performance metrics (empty)
  ('collectors.performance', 
   json_build_object(
     'metrics', json_build_array(),
     'last_updated', CURRENT_TIMESTAMP
   )::jsonb, 
   CURRENT_TIMESTAMP),

  -- Company bidding statistics (empty)
  ('companies.bidding_stats', 
   json_build_object(
     'metrics', json_build_array(),
     'last_updated', CURRENT_TIMESTAMP
   )::jsonb, 
   CURRENT_TIMESTAMP)
ON CONFLICT (key) DO UPDATE SET
  value = EXCLUDED.value,
  computed_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Uncomment these queries to verify the seeded data
-- ============================================================================

-- SELECT * FROM roles ORDER BY id;
-- SELECT * FROM waste_categories ORDER BY id;
-- SELECT * FROM analytics_aggregates ORDER BY key;

-- Count verification
-- SELECT 
--   (SELECT COUNT(*) FROM roles) AS roles_count,
--   (SELECT COUNT(*) FROM waste_categories) AS waste_categories_count,
--   (SELECT COUNT(*) FROM analytics_aggregates) AS analytics_aggregates_count;

-- ============================================================================
-- Commit transaction
-- ============================================================================
COMMIT;

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================
DO $$
BEGIN
  RAISE NOTICE '✅ Main tables seeded successfully!';
  RAISE NOTICE '   - % roles inserted', (SELECT COUNT(*) FROM roles);
  RAISE NOTICE '   - % waste categories inserted', (SELECT COUNT(*) FROM waste_categories);
  RAISE NOTICE '   - % analytics aggregates initialized', (SELECT COUNT(*) FROM analytics_aggregates);
END $$;
