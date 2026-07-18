# TriX Gaming Platform - Installation Guide

## 1. Requirements
- PHP 8.0 or newer
- MySQL 5.7+ or MariaDB 10.3+
- PDO PHP Extension
- OpenSSL PHP Extension

## 2. Server Setup
- The application is built for standard shared hosting or VPS environments.
- Ensure the root directory of your web server points to the project folder.

## 3. Database Setup
- Create a new MySQL database.
- Import the following SQL files in this exact order to build the schema:
  1. `database.sql`
  2. `update_db.sql`
  3. `update_db_phase4.sql`
  4. `update_db_phase5.sql`
  5. `update_db_phase6.sql`
  6. `update_db_phase7.sql`

## 4. Configuration
- Open `config/config.php`.
- Change the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` variables to match your database.
- Update the `APP_ENV` to `production` if deploying live to turn off error reporting.

## 5. First Login
- Navigate to your installation URL (e.g., `http://yourdomain.com/login.php`).
- Log in using the default Super Admin credentials:
  - **Username:** `T1234`
  - **Password:** `1234`
- **IMPORTANT:** Immediately change your password by navigating to Profile -> Change Password.

## 6. API Configuration (Optional)
- For live scores and automatic match updates, navigate to `Settings -> API Settings`.
- Select your provider and input your API keys. Ensure the "Status" is set to "Active".
- Set up a cron job to curl the `/api/sync_live.php` endpoint periodically to fetch live updates in the background.
