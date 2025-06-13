-- Add processed_by and processed_at columns to notifications table if they don't exist
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_by INT AFTER processada;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_at DATETIME AFTER processed_by;

-- Add foreign key constraint if it doesn't exist
ALTER TABLE notifications 
ADD CONSTRAINT IF NOT EXISTS fk_notifications_processed_by
FOREIGN KEY (processed_by) REFERENCES users(id);