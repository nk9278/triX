import re
from playwright.sync_api import Page, expect, sync_playwright
import time
import subprocess
import mysql.connector

BASE_URL = "http://127.0.0.1:3000"

def start_php_server():
    proc = subprocess.Popen(["php", "-S", "127.0.0.1:3000"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(2)
    return proc

def test_trix_phase7():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 480, 'height': 800})
        page = context.new_page()

        # Login as Super Admin
        page.goto(f"{BASE_URL}/login.php")
        page.fill("input[name='username']", "T1234")
        page.fill("input[name='password']", "1234")
        page.click("button[type='submit']")

        # 1. API Settings
        page.goto(f"{BASE_URL}/superadmin/api_settings.php")
        page.fill("input[name='api_key']", "TEST_KEY_123")
        page.fill("input[name='api_secret']", "SECRET_456")
        page.select_option("select[name='status']", "active")
        page.click("button:has-text('Save Configuration')")
        expect(page.locator("text=API Settings saved successfully")).to_be_visible()

        page.goto(f"{BASE_URL}/superadmin/api_settings.php")
        api_key_val = page.locator("input[name='api_key']").input_value()
        api_secret_val = page.locator("input[name='api_secret']").input_value()
        assert api_key_val == "********"
        assert api_secret_val == "********"
        print("API keys encrypted and masked successfully.")

        # 2. General Settings
        page.goto(f"{BASE_URL}/superadmin/settings.php")
        page.fill("input[name='site_name']", "TriX Updated")
        page.click("button:has-text('Save Settings')")
        expect(page.locator("text=Settings updated successfully")).to_be_visible()

        # 3. Match Import
        # First ensure we have a game
        conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
        cursor = conn.cursor(dictionary=True)
        cursor.execute("INSERT INTO games (name, status) VALUES ('API Game', 'active')")
        game_id = cursor.lastrowid
        conn.commit()

        page.goto(f"{BASE_URL}/superadmin/import_matches.php")
        page.select_option("select[name='type']", "upcoming")
        page.select_option("select[name='game_id']", str(game_id))
        page.click("button:has-text('Fetch & Import Matches')")
        expect(page.locator("text=Successfully imported")).to_be_visible()

        # 4. Sync Live Mock via API Endpoint
        # Get the match ID just imported
        cursor.execute("SELECT id FROM matches WHERE provider_match_id IS NOT NULL ORDER BY id DESC LIMIT 1")
        match = cursor.fetchone()
        imported_match_id = match['id']
        cursor.close()
        conn.close()

        page.goto(f"{BASE_URL}/api/sync_live.php?match_id={imported_match_id}")
        content = page.content()
        assert "success" in content

        # 5. Reports View
        page.goto(f"{BASE_URL}/superadmin/reports.php?type=wallet")
        expect(page.locator("text=System Reports")).to_be_visible()
        expect(page.locator("text=T1234")).to_be_visible()

        print("Phase 7 core verification passed.")
        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_trix_phase7()
    finally:
        proc.terminate()
