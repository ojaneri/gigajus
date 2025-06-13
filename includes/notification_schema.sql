-- Notification System Database Schema

CREATE TABLE email_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INT NOT NULL DEFAULT 465,
    smtp_user VARCHAR(255) NOT NULL,
    smtp_pass VARBINARY(255) NOT NULL COMMENT 'Encrypted with AES-256',
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    encryption_key VARCHAR(64) NOT NULL COMMENT 'Key version identifier'
);

CREATE TABLE notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    send_email BOOLEAN NOT NULL DEFAULT 1,
    send_whatsapp BOOLEAN NOT NULL DEFAULT 1,
    whatsapp_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario)
);