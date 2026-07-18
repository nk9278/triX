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
    if not user:
        import bcrypt
        pw = bcrypt.hashpw(b"5678", bcrypt.gensalt()).decode('utf-8')
        cursor.execute("INSERT INTO users (role_id, name, username, password) VALUES (6, 'Test User', 'T5678', %s)", (pw,))
        test_user_id = cursor.lastrowid
        cursor.execute("INSERT INTO wallets (user_id, balance) VALUES (%s, 1000.00)", (test_user_id,))
    else:
        test_user_id = user['id']
        cursor.execute("UPDATE wallets SET balance = 1000.00 WHERE user_id = %s", (test_user_id,))

    cursor.execute("INSERT INTO games (name, status) VALUES ('Settlement Test Game', 'active')")
    game_id = cursor.lastrowid

    # Create match and mark it as completed so we can settle it
    cursor.execute("INSERT INTO matches (game_id, title, start_time, status) VALUES (%s, 'Settle Match 1', NOW(), 'completed')", (game_id,))
    match_id = cursor.lastrowid

    # Insert 2 pending bets: 1 winning, 1 losing
    cursor.execute("INSERT INTO bets (user_id, match_id, amount, selection, status) VALUES (%s, %s, 100, 'India', 'pending')", (test_user_id, match_id))
    win_bet = cursor.lastrowid
    cursor.execute("INSERT INTO bets (user_id, match_id, amount, selection, status) VALUES (%s, %s, 50, 'Australia', 'pending')", (test_user_id, match_id))
    lose_bet = cursor.lastrowid

    # Deduct 150 from wallet manually for initial bet simulation
    cursor.execute("UPDATE wallets SET balance = balance - 150 WHERE user_id = %s", (test_user_id,))
    conn.commit()

    cursor.close()
    conn.close()

    return match_id, test_user_id, win_bet, lose_bet

def test_trix_phase6():
    match_id, test_user_id, win_bet, lose_bet = setup_test_data()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 480, 'height': 800})
        page = context.new_page()

        # Login as Super Admin
        page.goto(f"{BASE_URL}/login.php")
        page.fill("input[name='username']", "T1234")
        page.fill("input[name='password']", "1234")
        page.click("button[type='submit']")

        # 1. Match Result Entry
        print("Entering match result...")
        page.goto(f"{BASE_URL}/superadmin/match_result.php")

        # use fallback select
        try:
            page.locator("select[name='match_id']").select_option(str(match_id), timeout=5000)
        except Exception as e:
            page.evaluate(f"document.querySelector('select[name=\"match_id\"]').value = '{match_id}'")

        page.fill("input[name='winning_team']", "India")
        page.click("button[type='submit']")

        # 2. Settlement Preview
        expect(page).to_have_url(re.compile(r".*/superadmin/settle_preview\.php"))
        expect(page.locator("text=Total Payout")).to_be_visible()
        expect(page.locator("text=200.00").first).to_be_visible()

        # 3. Confirm Settlement
        print("Confirming settlement...")
        page.click("button:has-text('Confirm Settlement')")
        expect(page).to_have_url(re.compile(r".*/superadmin/match_result\.php\?success=.*"))

        # Verify in DB
        print("Verifying in DB...")
        conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SELECT status FROM bets WHERE id = %s", (win_bet,))
        assert cursor.fetchone()['status'] == 'won'

        cursor.execute("SELECT status FROM bets WHERE id = %s", (lose_bet,))
        assert cursor.fetchone()['status'] == 'lost'

        cursor.execute("SELECT is_settled, winning_team FROM matches WHERE id = %s", (match_id,))
        match_record = cursor.fetchone()
        assert match_record['is_settled'] == 1
        assert match_record['winning_team'] == 'India'

        cursor.close()
        conn.close()

        # Login as User
        page.goto(f"{BASE_URL}/logout.php")
        page.fill("input[name='username']", "T5678")
        page.fill("input[name='password']", "5678")
        page.click("button[type='submit']")

        # Check bet history
        print("Verifying user bet history...")
        page.goto(f"{BASE_URL}/user/bet_history.php")
        expect(page.locator("span.badge.bg-success:has-text('Won')").first).to_be_visible()
        expect(page.locator("span.badge.bg-danger:has-text('Lost')").first).to_be_visible()

        print("Phase 6 core tests passed!")
        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_trix_phase6()
    finally:
        proc.terminate()
