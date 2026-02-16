-- Add markup_percentage column to waste_categories table
ALTER TABLE waste_categories ADD COLUMN IF NOT EXISTS markup_percentage DECIMAL(5,2) DEFAULT 0.00;
