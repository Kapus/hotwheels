CREATE DATABASE IF NOT EXISTS hotwheels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotwheels;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    series VARCHAR(255) NOT NULL,
    collector_number VARCHAR(50) DEFAULT NULL,
    is_treasure_hunt TINYINT(1) NOT NULL DEFAULT 0,
    is_super_treasure TINYINT(1) NOT NULL DEFAULT 0,
    image_url VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_car_name_year (name, year),
    UNIQUE KEY uq_year_collector (year, collector_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    `condition` ENUM('mint', 'loose', 'custom') NOT NULL DEFAULT 'mint',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_car (user_id, car_id),
    CONSTRAINT fk_user_cars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_cars_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user INT NOT NULL,
    to_user INT NOT NULL,
    car_id INT NOT NULL,
    type ENUM('buy', 'trade') NOT NULL DEFAULT 'buy',
    message TEXT,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_offers_from FOREIGN KEY (from_user) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_offers_to FOREIGN KEY (to_user) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_offers_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
