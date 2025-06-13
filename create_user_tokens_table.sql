-- Create user_tokens table for "Remember Me" functionality
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario)
);

-- Add index for faster token lookups
CREATE INDEX IF NOT EXISTS idx_user_tokens_token ON user_tokens(token);