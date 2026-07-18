CREATE TABLE IF NOT EXISTS settlements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  settlement_id VARCHAR(50) NOT NULL UNIQUE,
  match_id INT NOT NULL,
  processed_by INT NOT NULL,
  total_bets INT DEFAULT 0,
  total_payout DECIMAL(15, 2) DEFAULT 0.00,
  total_refund DECIMAL(15, 2) DEFAULT 0.00,
  processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settlement_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  settlement_id VARCHAR(50) NOT NULL,
  bet_id INT NOT NULL,
  user_id INT NOT NULL,
  result ENUM('won', 'lost', 'cancelled', 'refunded') NOT NULL,
  bet_amount DECIMAL(15, 2) NOT NULL,
  payout_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (settlement_id) REFERENCES settlements(settlement_id) ON DELETE CASCADE,
  FOREIGN KEY (bet_id) REFERENCES bets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE bets ADD COLUMN winning_amount DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE bets ADD COLUMN settled_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE bets ADD COLUMN settled_by INT DEFAULT NULL;
ALTER TABLE bets ADD FOREIGN KEY (settled_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE wallet_transactions ADD COLUMN transaction_type VARCHAR(50) DEFAULT 'Coin Transfer';
ALTER TABLE wallet_transactions ADD COLUMN match_id INT DEFAULT NULL;
ALTER TABLE wallet_transactions ADD COLUMN bet_id INT DEFAULT NULL;
ALTER TABLE wallet_transactions ADD FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL;
ALTER TABLE wallet_transactions ADD FOREIGN KEY (bet_id) REFERENCES bets(id) ON DELETE SET NULL;

-- Make sure matches has a winning_team column
ALTER TABLE matches ADD COLUMN winning_team VARCHAR(255) DEFAULT NULL;
ALTER TABLE matches ADD COLUMN is_settled TINYINT(1) DEFAULT 0;
