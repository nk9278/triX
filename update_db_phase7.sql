CREATE TABLE IF NOT EXISTS api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    api_secret VARCHAR(255) DEFAULT NULL,
    base_url VARCHAR(255) NOT NULL,
    sync_interval INT DEFAULT 60,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE matches ADD COLUMN provider_match_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE matches ADD COLUMN provider VARCHAR(50) DEFAULT NULL;
ALTER TABLE matches ADD COLUMN venue VARCHAR(255) DEFAULT NULL;
ALTER TABLE matches ADD COLUMN toss_winner VARCHAR(100) DEFAULT NULL;
ALTER TABLE matches ADD COLUMN last_sync TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE matches ADD COLUMN api_status VARCHAR(50) DEFAULT NULL;
ALTER TABLE matches ADD UNIQUE KEY unique_provider_match (provider, provider_match_id);

ALTER TABLE settings ADD COLUMN min_bet DECIMAL(15, 2) DEFAULT 10.00;
ALTER TABLE settings ADD COLUMN max_bet DECIMAL(15, 2) DEFAULT 10000.00;
ALTER TABLE settings ADD COLUMN default_bet DECIMAL(15, 2) DEFAULT 100.00;
ALTER TABLE settings ADD COLUMN default_multiplier DECIMAL(5, 2) DEFAULT 2.00;
ALTER TABLE settings ADD COLUMN default_wallet_balance DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE settings ADD COLUMN coin_name VARCHAR(50) DEFAULT 'Coins';

-- Add indexes for optimization
CREATE INDEX idx_bets_user_status ON bets(user_id, status);
CREATE INDEX idx_bets_match ON bets(match_id);
CREATE INDEX idx_matches_status ON matches(status);
CREATE INDEX idx_wallet_transactions_user ON wallet_transactions(from_user, to_user);
