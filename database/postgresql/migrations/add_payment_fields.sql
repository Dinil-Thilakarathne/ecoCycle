-- Migration: Add notes and bidding_round_id to payments table
-- This allows invoices to be linked directly to bidding rounds and display custom descriptions

ALTER TABLE payments 
ADD COLUMN IF NOT EXISTS notes VARCHAR(255),
ADD COLUMN IF NOT EXISTS bidding_round_id VARCHAR(50);
