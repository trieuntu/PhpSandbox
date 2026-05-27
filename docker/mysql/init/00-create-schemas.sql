-- Initialize databases and sample data for PHP Sandbox Learning System

CREATE DATABASE IF NOT EXISTS `phpsandbox`;
CREATE DATABASE IF NOT EXISTS `sandbox_shared`;

-- Populate sandbox_shared with student practice tables
USE sandbox_shared;

CREATE TABLE IF NOT EXISTS products (
    id    INT PRIMARY KEY AUTO_INCREMENT,
    name  VARCHAR(255)   NOT NULL,
    price DECIMAL(10,2)  NOT NULL,
    stock INT            NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS customers (
    id    INT PRIMARY KEY AUTO_INCREMENT,
    name  VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    city  VARCHAR(100) NOT NULL
);

INSERT INTO products (name, price, stock) VALUES
    ('Laptop',   999.99, 50),
    ('Mouse',     29.99, 200),
    ('Keyboard',  79.99, 150)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO customers (name, email, city) VALUES
    ('Alice',   'alice@example.com',   'HCM'),
    ('Bob',     'bob@example.com',     'HN'),
    ('Charlie', 'charlie@example.com', 'DN')
ON DUPLICATE KEY UPDATE name = VALUES(name);
