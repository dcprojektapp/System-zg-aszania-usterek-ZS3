CREATE DATABASE IF NOT EXISTS szkola_usterki CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE szkola_usterki;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reporter_name VARCHAR(100) NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('nowe', 'w_trakcie', 'naprawione', 'rozwiazane') DEFAULT 'nowe',
    is_archived TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
