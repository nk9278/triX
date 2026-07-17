SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

DELETE FROM activity_logs;
DELETE FROM wallet_transactions;
DELETE FROM login_logs;
DELETE FROM wallets;
DELETE FROM users WHERE id > 1;

INSERT INTO wallets (user_id, balance, total_received, total_sent) VALUES (1, 0.00, 0.00, 0.00);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
