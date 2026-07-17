CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT DEFAULT NULL,
  `role_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_name` VARCHAR(100) DEFAULT 'TriX',
  `logo` VARCHAR(255) DEFAULT NULL,
  `favicon` VARCHAR(255) DEFAULT NULL,
  `timezone` VARCHAR(50) DEFAULT 'UTC',
  `maintenance_mode` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `device` VARCHAR(255) DEFAULT NULL,
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `logout_time` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

-- Insert default roles
INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Super Admin'),
(2, 'Admin'),
(3, 'Manager'),
(4, 'Super Agent'),
(5, 'Agent'),
(6, 'User') ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Insert a test user for testing login (Super Admin with username T1234, password 1234)
INSERT INTO `users` (`role_id`, `name`, `username`, `password`)
VALUES (1, 'Test Admin', 'T1234', '$2y$10$N677MJd8C1fC75p.GbXvkODassFNC1H9vUgIv2ITEdZIjZ5uLtwe2')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- Insert default settings
INSERT INTO `settings` (`site_name`, `timezone`, `maintenance_mode`)
VALUES ('TriX', 'UTC', 0)
ON DUPLICATE KEY UPDATE `site_name`=VALUES(`site_name`);
