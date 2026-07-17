CREATE TABLE IF NOT EXISTS `wallets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `balance` DECIMAL(15, 2) DEFAULT 0.00,
  `total_received` DECIMAL(15, 2) DEFAULT 0.00,
  `total_sent` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` VARCHAR(50) NOT NULL UNIQUE,
  `from_user` INT NULL,
  `to_user` INT NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `opening_balance` DECIMAL(15, 2) NOT NULL,
  `closing_balance` DECIMAL(15, 2) NOT NULL,
  `remark` TEXT,
  `status` ENUM('completed', 'failed', 'pending') DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`from_user`) REFERENCES `users`(`id`),
  FOREIGN KEY (`to_user`) REFERENCES `users`(`id`)
);

-- Initialize wallets for existing users
INSERT IGNORE INTO `wallets` (`user_id`, `balance`) SELECT `id`, 0.00 FROM `users`;
