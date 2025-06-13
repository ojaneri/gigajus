-- Add teor column to notifications table if it doesn't exist
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS teor TEXT AFTER data_publicacao;