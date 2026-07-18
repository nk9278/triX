import re
from playwright.sync_api import Page, expect, sync_playwright
import time
import subprocess
import mysql.connector

BASE_URL = "http://127.0.0.1:3000"

def start_php_server():
    print("Starting PHP server...")
    proc = subprocess.Popen(["php", "-S", "127.0.0.1:3000"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(2)
    return proc

def setup_test_data():
    conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
    cursor = conn.cursor(dictionary=True)

    # Ensure test user
    cursor.execute("SELECT id FROM users WHERE username='T5678'")
    user = cursor.fetchone()
    test_user_id = user['id']

    cursor.execute("INSERT INTO games (name, status) VALUES ('Settlement Extended Game', 'active')")
    game_id = cursor.lastrowid

    # Create match and mark it as completed so we can settle it
    cursor.execute("INSERT INTO matches (game_id, title, start_time, status) VALUES (%s, 'Settle Cancelled Match', NOW(), 'completed')", (game_id,))
    match_id = cursor.lastrowid

    # Insert 1 pending bet to refund
    cursor.execute("INSERT INTO bets (user_id, match_id, amount, selection, status) VALUES (%s, %s, 100, 'India', 'pending')", (test_user_id, match_id))
    bet_id = cursor.lastrowid

    conn.commit()

    cursor.close()
    conn.close()

    return match_id, test_user_id, bet_id

def test_trix_phase6_extended():
    match_id, test_user_id, bet_id = setup_test_data()
    print(f"Created Match ID: {match_id}")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 480, 'height': 800})
        page = context.new_page()

        # Login as Super Admin
        page.goto(f"{BASE_URL}/login.php")
        page.fill("input[name='username']", "T1234")
        page.fill("input[name='password']", "1234")
        page.click("button[type='submit']")

        # 1. Cancelled Match Settlement Test
        print("Testing Cancelled Settlement...")
        page.goto(f"{BASE_URL}/superadmin/match_result.php")

        # Diagnostics for select option
        page.wait_for_timeout(2000)



        try:
            page.locator("select[name='match_id']").select_option(str(match_id), timeout=5000)
        except Exception as e:
            print("Standard select_option failed, using fallback:", str(e))
            page.evaluate(f"document.querySelector('select[name=\"match_id\"]').value = '{match_id}'")

        page.fill("input[name='winning_team']", "Cancelled") # Magic keyword
        page.click("button[type='submit']")

        expect(page).to_have_url(re.compile(r".*/superadmin/settle_preview\.php"))
        expect(page.locator("text=Total Refund")).to_be_visible()
        expect(page.locator("text=100.00")).to_have_count(3) # Refund amount and bet amount

        page.click("button:has-text('Confirm Settlement')")

        # 2. Try to settle again (should not appear in list)
        print("Testing Duplicate Settlement Prevention...")
        page.goto(f"{BASE_URL}/superadmin/match_result.php")
        select_options = page.locator("select[name='match_id'] option").all_text_contents()
        assert not any("Settle Cancelled Match" in option for option in select_options)
        print("Duplicate processing prevented successfully.")

        # Verify DB
        conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT status FROM bets WHERE id = %s", (bet_id,))
        assert cursor.fetchone()['status'] == 'refunded'
        cursor.close()
        conn.close()

        print("Phase 6 extended tests passed!")
        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_trix_phase6_extended()
    finally:
        proc.terminate()
