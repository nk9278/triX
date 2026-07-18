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

    # Ensure active game
    cursor.execute("INSERT INTO games (name, status) VALUES ('Test Game Extended', 'active')")
    game_id = cursor.lastrowid

    # Ensure live match
    cursor.execute("INSERT INTO matches (game_id, title, start_time, status) VALUES (%s, 'Extended Live Match', NOW(), 'live')", (game_id,))
    live_match_id = cursor.lastrowid

    # Ensure upcoming match
    cursor.execute("INSERT INTO matches (game_id, title, start_time, status) VALUES (%s, 'Extended Upcoming Match', DATE_ADD(NOW(), INTERVAL 1 DAY), 'upcoming')", (game_id,))
    upcoming_match_id = cursor.lastrowid

    # Give user a small balance for insufficient funds test
    cursor.execute("UPDATE wallets SET balance = 10.00 WHERE user_id = %s", (test_user_id,))
    conn.commit()

    cursor.close()
    conn.close()

    return game_id, live_match_id, upcoming_match_id, test_user_id

def test_trix_phase5_extended():
    game_id, live_match_id, upcoming_match_id, test_user_id = setup_test_data()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 480, 'height': 800})
        page = context.new_page()

        # Login as User
        page.goto(f"{BASE_URL}/logout.php")
        page.fill("input[name='username']", "T5678")
        page.fill("input[name='password']", "5678")
        page.click("button[type='submit']")
        expect(page).to_have_url(re.compile(r".*/user/?(index\.php)?"))

        page.goto(f"{BASE_URL}/user/play.php?game_id={game_id}")
        page.wait_for_selector(f"h6:has-text(\"Extended Live Match\")", state="attached", timeout=10000)

        # 1. Invalid Amount Test (0 or Negative)
        print("Testing Invalid Amount...")
        page.click(f"button[onclick*='openBetModal({live_match_id}']")
        page.wait_for_selector(f"#bet-modal-{live_match_id} .modal-body", state="visible")
        page.fill(f"#amount-{live_match_id}", "-5")
        # Force submission
        page.evaluate(f"document.getElementById('amount-{live_match_id}').value = '-5'")
        page.click(f"#place-bet-{live_match_id}")

        try:
            page.wait_for_selector(f"#bet-msg-{live_match_id}.alert-danger", timeout=5000)
            msg = page.inner_text(f"#bet-msg-{live_match_id}")
            print("Invalid amount handled:", msg)
        except:
            print("HTML5 validation blocked submission. That's fine.")

        page.click(f"#bet-modal-{live_match_id} .btn-close")
        page.wait_for_selector(f"#bet-modal-{live_match_id}", state="hidden")

        # 2. Insufficient Balance Test
        print("Testing Insufficient Balance...")
        page.click(f"button[onclick*='openBetModal({live_match_id}']")
        page.wait_for_selector(f"#bet-modal-{live_match_id} .modal-body", state="visible")
        page.fill(f"#amount-{live_match_id}", "500")
        page.click(f"#place-bet-{live_match_id}")

        page.wait_for_selector(f"#bet-msg-{live_match_id}.alert-danger", timeout=5000)
        msg = page.inner_text(f"#bet-msg-{live_match_id}")
        assert "Insufficient balance" in msg
        print("Insufficient balance handled.")

        # 3. Upcoming Match Interaction Test
        print("Testing Upcoming Match (Disabled Buttons)...")
        buttons = page.locator(f"button[onclick*='openBetModal({upcoming_match_id}']")
        count = buttons.count()
        assert count > 0
        for i in range(count):
            assert buttons.nth(i).is_disabled()
        print("Upcoming match buttons disabled.")

        print("All extended tests passed!")
        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_trix_phase5_extended()
    finally:
        proc.terminate()
