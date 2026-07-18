CREATE TABLE IF NOT EXISTS `games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `start_time` DATETIME NOT NULL,
  `status` ENUM('upcoming', 'live', 'completed') DEFAULT 'upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `bets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `match_id` INT NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `selection` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'won', 'lost', 'refunded') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE
);
