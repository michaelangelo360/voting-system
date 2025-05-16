-- Database schema for the voting system
CREATE DATABASE voting_system;

USE voting_system;

-- Admin users
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_super_admin BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    expired BOOLEAN DEFAULT 0,
    cost DECIMAL(10, 2) NOT NULL,
    owner VARCHAR(255) NOT NULL,
    owner_password VARCHAR(255) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    organizer_id INT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Nominees
CREATE TABLE nominees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    organizer_id INT NOT NULL,
    votes INT DEFAULT 0,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (organizer_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Vote records
CREATE TABLE vote_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nominee_id INT NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(255),
    vote_count INT NOT NULL,
    transaction_reference VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nominee_id) REFERENCES nominees(id) ON DELETE CASCADE
);

-- Payment references
CREATE TABLE payment_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(255) NOT NULL UNIQUE,
    nominee_code VARCHAR(20) NOT NULL,
    msisdn VARCHAR(20) NOT NULL,
    votes INT NOT NULL,
    nominee_id INT NOT NULL,
    status BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nominee_id) REFERENCES nominees(id) ON DELETE CASCADE
);

-- USSD Sessions
CREATE TABLE ussd_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    msisdn VARCHAR(20) NOT NULL,
    network VARCHAR(50),
    level INT DEFAULT 1,
    nominee_id INT,
    votes INT,
    reference VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nominee_id) REFERENCES nominees(id) ON DELETE SET NULL
);