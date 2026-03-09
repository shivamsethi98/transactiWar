-- TransactiWar Database Schema
-- Engine: InnoDB (required for transactions + row locking)

CREATE DATABASE IF NOT EXISTS transactiwar
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE transactiwar;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    balance     DECIMAL(12,2) NOT NULL DEFAULT 100.00 CHECK (balance >= 0),
    full_name   VARCHAR(100) DEFAULT NULL,
    biography   TEXT DEFAULT NULL,
    avatar_path VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Transactions
CREATE TABLE IF NOT EXISTS transactions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    amount      DECIMAL(12,2) NOT NULL CHECK (amount > 0),
    comment     VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_sender   (sender_id, created_at),
    INDEX idx_receiver (receiver_id, created_at)
) ENGINE=InnoDB;

-- Activity log
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    username    VARCHAR(50) DEFAULT NULL,
    page        VARCHAR(255) NOT NULL,
    ip_address  VARCHAR(45) NOT NULL,
    user_agent  VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_activity (user_id, created_at),
    INDEX idx_timestamp (created_at)
) ENGINE=InnoDB;

-- Login attempts (brute-force detection)
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45)  NOT NULL,
    username     VARCHAR(50)  NOT NULL,
    success      TINYINT(1)   NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time   (ip_address, attempted_at),
    INDEX idx_user_time (username, attempted_at)
) ENGINE=InnoDB;

-- Generic rate limiting
CREATE TABLE IF NOT EXISTS rate_limits (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier   VARCHAR(255) NOT NULL,
    action       VARCHAR(50)  NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lookup (identifier, action, attempted_at)
) ENGINE=InnoDB;
