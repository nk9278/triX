# TriX Gaming Platform

TriX is a complete, white-label, mobile-first gaming platform built on Core PHP and MySQL. It features hierarchical user management, a virtual coin wallet system, match management, and a full-fledged bet settlement engine with API synchronization capabilities.

## Features

- **Role Hierarchy:** Super Admin -> Admin -> Manager -> Super Agent -> Agent -> User.
- **Virtual Coin System:** Built-in virtual wallets; Super Admin generates coins that flow down the hierarchy.
- **Match Management:** Demo engine and Live Cricket API ready.
- **Betting Engine:** Real-time betting on live matches with automated wallet deductions and payouts.
- **Reporting & Settings:** Configurable payout multipliers, API integrations, and comprehensive reports.

## Installation

1. **Requirements:** PHP 8+, MySQL/MariaDB, PDO extension.
2. **Database:** Import `database.sql`, `update_db.sql`, `update_db_phase4.sql`, `update_db_phase5.sql`, `update_db_phase6.sql`, and `update_db_phase7.sql` in order to your MySQL instance.
3. **Configuration:** Copy `config/config.php` and configure your `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`.
4. **Deploy:** Upload files to your shared hosting or start a local server using `php -S localhost:3000`.

## Default Login
- **Super Admin Username:** `T1234`
- **Password:** `1234`

*(Make sure to change the password immediately after logging in)*
