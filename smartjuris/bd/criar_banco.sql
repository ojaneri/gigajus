CREATE DATABASE smartjuris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'smartjuris_user'@'localhost' IDENTIFIED BY 'SecurePassword2025';  -- Replace with a secure password in production

GRANT ALL PRIVILEGES ON smartjuris.* TO 'smartjuris_user'@'localhost';

FLUSH PRIVILEGES;