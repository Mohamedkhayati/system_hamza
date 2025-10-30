-- gym_management.sql
-- Full schema for gym management (members + subscriptions history + categories)

DROP DATABASE IF EXISTS gym_management;
CREATE DATABASE gym_management CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gym_management;

-- Members table
CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  email VARCHAR(150),
  dob DATE NULL,
  photo VARCHAR(255) DEFAULT NULL,
  category ENUM('karate', 'fitness') DEFAULT 'fitness',
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Current subscription table (one row per active subscription)
CREATE TABLE subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  plan_name VARCHAR(100),
  price DECIMAL(10,2),
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Archive of all subscriptions / changes for each member:
CREATE TABLE subscription_archive (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  start_date DATE,
  end_date DATE,
  plan_name VARCHAR(100),
  price DECIMAL(10,2),
  archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  note VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Simple users table for dashboard access (optional)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,
  password_hash VARCHAR(255),
  role VARCHAR(50) DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample indexes
CREATE INDEX idx_members_category ON members(category);
CREATE INDEX idx_subscriptions_member ON subscriptions(member_id);
CREATE INDEX idx_archive_member ON subscription_archive(member_id);

-- Example sample data (optional)
INSERT INTO members (first_name, last_name, phone, email, category) VALUES
('Amina', 'Ben Ali', '21612345678', 'amina@example.com', 'fitness'),
('Hamza', 'Khayati', '21698765432', 'hamza@example.com', 'karate');

INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, price) VALUES
(1, '2025-01-01', '2025-06-30', '6-month fitness', 180.00),
(2, '2025-02-15', '2025-05-15', '3-month karate', 120.00);

-- Put current subscriptions also in archive (initial snapshot)
INSERT INTO subscription_archive (member_id, start_date, end_date, plan_name, price, note)
SELECT member_id, start_date, end_date, plan_name, price, 'initial import'
FROM subscriptions;
ALTER TABLE members
  ADD COLUMN category ENUM('karate', 'fitness') DEFAULT 'fitness',
  ADD COLUMN updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS subscription_archive (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  start_date DATE,
  end_date DATE,
  plan_name VARCHAR(100),
  price DECIMAL(10,2),
  archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  note VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

