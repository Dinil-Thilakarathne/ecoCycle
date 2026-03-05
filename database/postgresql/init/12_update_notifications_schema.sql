-- Alter notifications table to auto-generate UUIDs for the id column
ALTER TABLE notifications ALTER COLUMN id SET DEFAULT gen_random_uuid();
