-- PostgreSQL Schema for ecoCycle
-- Converted from MySQL schema

-- Roles
CREATE TABLE IF NOT EXISTS roles (
  id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  label VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles (create before users so users.vehicle_id FK can reference it)
CREATE TABLE IF NOT EXISTS vehicles (
  id SERIAL PRIMARY KEY,
  plate_number VARCHAR(32) NOT NULL UNIQUE,
  type VARCHAR(64) DEFAULT NULL,
  capacity INT DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'available',
  last_maintenance DATE DEFAULT NULL,
  next_maintenance DATE DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Create ENUM type for user types
CREATE TYPE user_type AS ENUM ('customer', 'company', 'collector', 'admin');

-- Users table (customers/companies/collectors/admins)
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  type user_type NOT NULL DEFAULT 'customer',
  name VARCHAR(255) DEFAULT NULL,
  username VARCHAR(100) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  nic VARCHAR(20) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  bank_account_name VARCHAR(255) DEFAULT NULL,
  bank_account_number VARCHAR(100) DEFAULT NULL,
  bank_name VARCHAR(150) DEFAULT NULL,
  bank_branch VARCHAR(150) DEFAULT NULL,
  profile_image_path VARCHAR(255) DEFAULT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  role_id INT DEFAULT NULL,
  vehicle_id INT DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'active',
  total_pickups INT DEFAULT 0,
  total_earnings DECIMAL(12,2) DEFAULT 0.00,
  total_bids INT DEFAULT 0,
  total_purchases INT DEFAULT 0,
  metadata JSONB DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT users_email_unique UNIQUE (email)
);

-- Create indexes for users table
CREATE INDEX idx_users_type ON users (type);
CREATE INDEX idx_users_status ON users (status);
CREATE INDEX idx_users_role ON users (role_id);
CREATE INDEX idx_users_vehicle ON users (vehicle_id);

-- Add foreign key constraints for users table
ALTER TABLE users ADD CONSTRAINT fk_users_roles 
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE users ADD CONSTRAINT fk_users_vehicles 
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Waste categories
CREATE TABLE IF NOT EXISTS waste_categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(128) NOT NULL UNIQUE,
  color VARCHAR(32) DEFAULT NULL,
  default_minimum_bid DECIMAL(12,2) DEFAULT NULL,
  unit VARCHAR(16) DEFAULT 'kg',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Pickup requests
CREATE TABLE IF NOT EXISTS pickup_requests (
  id VARCHAR(64) PRIMARY KEY,
  customer_id INT NOT NULL,
  address TEXT DEFAULT NULL,
  time_slot VARCHAR(64) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'pending',
  collector_id INT DEFAULT NULL,
  collector_name VARCHAR(255) DEFAULT NULL,
  scheduled_at TIMESTAMP DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Create indexes for pickup_requests table
CREATE INDEX idx_pickup_status ON pickup_requests (status);
CREATE INDEX idx_pickup_time_slot ON pickup_requests (time_slot);
CREATE INDEX idx_pickup_customer ON pickup_requests (customer_id);
CREATE INDEX idx_pickup_collector ON pickup_requests (collector_id);

-- Add foreign key constraints for pickup_requests table
ALTER TABLE pickup_requests ADD CONSTRAINT fk_pickup_customer 
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE pickup_requests ADD CONSTRAINT fk_pickup_collector 
  FOREIGN KEY (collector_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Pivot: pickup_request_wastes
CREATE TABLE IF NOT EXISTS pickup_request_wastes (
  id SERIAL PRIMARY KEY,
  pickup_id VARCHAR(64) NOT NULL,
  waste_category_id INT NOT NULL,
  quantity DECIMAL(10,2) DEFAULT NULL,
  unit VARCHAR(16) DEFAULT NULL
);

-- Create indexes for pickup_request_wastes table
CREATE INDEX idx_prw_pickup ON pickup_request_wastes (pickup_id);
CREATE INDEX idx_prw_category ON pickup_request_wastes (waste_category_id);

-- Add foreign key constraints for pickup_request_wastes table
ALTER TABLE pickup_request_wastes ADD CONSTRAINT fk_prw_pickup 
  FOREIGN KEY (pickup_id) REFERENCES pickup_requests(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE pickup_request_wastes ADD CONSTRAINT fk_prw_category 
  FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Bidding rounds / lots
CREATE TABLE IF NOT EXISTS bidding_rounds (
  id VARCHAR(64) PRIMARY KEY,
  lot_id VARCHAR(64) DEFAULT NULL UNIQUE,
  waste_category_id INT DEFAULT NULL,
  quantity DECIMAL(12,2) DEFAULT NULL,
  unit VARCHAR(16) DEFAULT 'kg',
  starting_bid DECIMAL(12,2) DEFAULT 0.00,
  current_highest_bid DECIMAL(12,2) DEFAULT 0.00,
  leading_company_id INT DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'active',
  end_time TIMESTAMP DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Create indexes for bidding_rounds table
CREATE INDEX idx_bidding_status ON bidding_rounds (status);
CREATE INDEX idx_bidding_end ON bidding_rounds (end_time);
CREATE INDEX idx_bidding_category ON bidding_rounds (waste_category_id);

-- Add foreign key constraints for bidding_rounds table
ALTER TABLE bidding_rounds ADD CONSTRAINT fk_bidding_category 
  FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE bidding_rounds ADD CONSTRAINT fk_bidding_leader 
  FOREIGN KEY (leading_company_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Bids history
CREATE TABLE IF NOT EXISTS bids (
  id BIGSERIAL PRIMARY KEY,
  bidding_round_id VARCHAR(64) NOT NULL,
  company_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  is_winner BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for bids table
CREATE INDEX idx_bids_round ON bids (bidding_round_id);
CREATE INDEX idx_bids_company ON bids (company_id);

-- Add foreign key constraints for bids table
ALTER TABLE bids ADD CONSTRAINT fk_bids_round 
  FOREIGN KEY (bidding_round_id) REFERENCES bidding_rounds(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE bids ADD CONSTRAINT fk_bids_company 
  FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Payments / transactions
CREATE TABLE IF NOT EXISTS payments (
  id VARCHAR(64) PRIMARY KEY,
  txn_id VARCHAR(128) DEFAULT NULL,
  type VARCHAR(32) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  recipient_id INT DEFAULT NULL,
  recipient_name VARCHAR(255) DEFAULT NULL,
  date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(32) DEFAULT 'pending',
  gateway_response JSONB DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Create indexes for payments table
CREATE INDEX idx_payments_type ON payments (type);
CREATE INDEX idx_payments_status ON payments (status);

-- Add foreign key constraints for payments table
ALTER TABLE payments ADD CONSTRAINT fk_payments_recipient 
  FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
  id VARCHAR(64) PRIMARY KEY,
  type VARCHAR(64) DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  recipients JSONB DEFAULT NULL,
  recipient_group VARCHAR(64) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'pending',
  sent_at TIMESTAMP DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Collector ratings (customer supplied reviews for collectors)
CREATE TABLE IF NOT EXISTS collector_ratings (
  id SERIAL PRIMARY KEY,
  customer_id INT NOT NULL,
  collector_id INT DEFAULT NULL,
  collector_name VARCHAR(255) NOT NULL,
  rating INT NOT NULL,
  description TEXT DEFAULT NULL,
  address TEXT DEFAULT NULL,
  rating_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_cr_customer ON collector_ratings (customer_id);
CREATE INDEX idx_cr_collector ON collector_ratings (collector_id);

ALTER TABLE collector_ratings ADD CONSTRAINT fk_cr_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE collector_ratings ADD CONSTRAINT fk_cr_collector FOREIGN KEY (collector_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Create indexes for notifications table
CREATE INDEX idx_notifications_type ON notifications (type);
CREATE INDEX idx_notifications_status ON notifications (status);

-- Add foreign key constraints for notifications table
ALTER TABLE notifications ADD CONSTRAINT fk_notifications_creator 
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- System alerts (config)
CREATE TABLE IF NOT EXISTS system_alerts (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'inactive',
  settings JSONB DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Optional analytics cache table
CREATE TABLE IF NOT EXISTS analytics_aggregates (
  key VARCHAR(128) PRIMARY KEY,
  value JSONB DEFAULT NULL,
  computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Small seeds for roles
INSERT INTO roles (name, label) VALUES
  ('admin', 'Administrator'),
  ('manager', 'Manager'),
  ('collector', 'Collector'),
  ('company', 'Company'),
  ('customer', 'Customer')
ON CONFLICT (name) DO NOTHING;

-- End of schema