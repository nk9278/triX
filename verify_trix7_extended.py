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

def test_trix_phase7_extended():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 480, 'height': 800})
        page = context.new_page()

        # Login as Super Admin
        page.goto(f"{BASE_URL}/login.php")
        page.fill("input[name='username']", "T1234")
        page.fill("input[name='password']", "1234")
        page.click("button[type='submit']")

        # Duplicate Import Protection
        conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM games LIMIT 1")
        game = cursor.fetchone()
        game_id = game['id']
        cursor.close()
        conn.close()

        page.goto(f"{BASE_URL}/superadmin/import_matches.php")
        page.select_option("select[name='type']", "completed")
        page.select_option("select[name='game_id']", str(game_id))
        page.click("button:has-text('Fetch & Import Matches')")

        # Run second time, should say 0 imported due to duplicate prevention
        page.select_option("select[name='type']", "completed")
        page.select_option("select[name='game_id']", str(game_id))
        page.click("button:has-text('Fetch & Import Matches')")
        expect(page.locator("text=Successfully imported 0 matches")).to_be_visible()

        print("Phase 7 extended verification passed.")
        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_trix_phase7_extended()
    finally:
        proc.terminate()
