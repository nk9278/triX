# TriX Database Schema

The database consists of the following key tables:

- **users:** Core user records supporting hierarchical relationships (`parent_id`) and roles (`role_id`).
- **roles:** RBAC roles definition.
- **wallets:** Virtual coin balances for every user.
- **wallet_transactions:** Full double-entry ledger for coin generation, transfers, bets, and payouts.
- **games:** Top-level categories (e.g., Cricket).
- **matches:** Specific events users can bet on.
- **bets:** Individual user wagers on matches.
- **settlements:** Super Admin confirmed result blocks detailing total payouts and refunds.
- **settlement_details:** Individual breakdown of every bet settled inside a `settlement`.
- **activity_logs:** Audit trail for key system events.
- **login_logs:** Tracks user session beginnings and ends.
- **settings / api_settings:** Global platform and third-party configuration details.

Important Note: Always use transactions with `FOR UPDATE` row locks when altering `wallet` balances and `bets` to avoid race conditions.
