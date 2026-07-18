# TriX API Integration

## Live Cricket API Synchronization
TriX supports integrating real-time match data using a configurable adapter pattern located in `includes/api_service.php`.

- **Settings:** Configure your provider (e.g., CricAPI, SportMonks), Base URL, API Key, and Sync Interval from the Super Admin dashboard -> Settings -> API Settings.
- **Fetching:** Use the `superadmin/import_matches.php` tool to manually fetch and map upstream match IDs (`provider_match_id`) into the local `matches` table.
- **Live Sync:** Endpoints like `api/sync_live.php` mock the behavior of real-time polling to update `matches` statuses automatically.
- **Betting Controls:** Automated betting locks apply when a match transitions to "completed".

## Architecture
The API endpoints are strictly secured and require session authentication. Do not expose `place_bet.php` to external unauthenticated sources.
