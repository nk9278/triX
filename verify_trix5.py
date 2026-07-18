import re
from playwright.sync_api import Page, expect, sync_playwright
import time
import subprocess
import os

BASE_URL = "http://127.0.0.1:3000"

def start_php_server():
    print("Starting PHP server...")
    proc = subprocess.Popen(["php", "-S", "127.0.0.1:3000"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(2)
    return proc

def test_trix_phase5():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 480, 'height': 800}) # Mobile first UI
        page = context.new_page()

        # Step 1: Login as Super Admin
        print("Logging in as Super Admin...")
        page.goto(f"{BASE_URL}/login.php")
        page.fill("input[name='username']", "T1234")
        page.fill("input[name='password']", "1234")
        page.click("button[type='submit']")

        # Super admin index may or may not redirect, sometimes it just goes to /superadmin/
        expect(page).to_have_url(re.compile(r".*/superadmin/?(index\.php)?"))

        # Step 2: Create a Game
        print("Creating a Game...")
        page.goto(f"{BASE_URL}/superadmin/games.php")
        # Ensure locator works for the <a> tag
        page.locator("a", has_text="Add Game").click()
        expect(page).to_have_url(re.compile(r".*/superadmin/game_add\.php"))

        game_name = f"Cricket {int(time.time())}"
        page.fill("input[name='name']", game_name)
        page.click("button[type='submit']")
        expect(page).to_have_url(re.compile(r".*/superadmin/games\.php"))
        expect(page.locator(f"text={game_name}")).to_be_visible()

        # Grab the game ID from the table
        row = page.locator("tr", has_text=game_name)
        game_id = row.locator("td").first.inner_text().strip()
        print(f"Game created with ID: {game_id}")

        # Step 3: Create a Match
        print("Creating a Match...")
        page.goto(f"{BASE_URL}/superadmin/match_add.php")
        match_title = f"India vs Australia {int(time.time())}"
        page.select_option("select[name='game_id']", value=game_id)
        page.fill("input[name='title']", match_title)

        import datetime
        now = datetime.datetime.now()
        start_time_str = now.strftime("%Y-%m-%dT%H:%M")
        page.fill("input[name='start_time']", start_time_str)
        page.select_option("select[name='status']", value="live")
        page.click("button[type='submit']")

        expect(page).to_have_url(re.compile(r".*/superadmin/matches\.php.*"))
        expect(page.locator(f"text={match_title}")).to_be_visible()

        row = page.locator("tr", has_text=match_title)
        match_id = row.locator("td").first.inner_text().strip()
        print(f"Match created with ID: {match_id}")

        # Ensure test user exists and has funds.
        print("Ensuring a test user exists and has funds...")
        import mysql.connector
        try:
            conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT id FROM users WHERE username='T5678'")
            user = cursor.fetchone()
            if not user:
                import bcrypt
                pw = bcrypt.hashpw(b"5678", bcrypt.gensalt()).decode('utf-8')
                cursor.execute("INSERT INTO users (role_id, name, username, password) VALUES (6, 'Test User', 'T5678', %s)", (pw,))
                conn.commit()
                test_user_id = cursor.lastrowid
                cursor.execute("INSERT INTO wallets (user_id, balance) VALUES (%s, 1000.00)", (test_user_id,))
                conn.commit()
            else:
                test_user_id = user['id']
                cursor.execute("UPDATE wallets SET balance = 1000.00 WHERE user_id = %s", (test_user_id,))
                conn.commit()
            cursor.close()
            conn.close()
        except Exception as e:
            print("DB error:", e)

        # Step 4: Login as User
        print("Logging in as User...")
        page.goto(f"{BASE_URL}/logout.php")
        page.fill("input[name='username']", "T5678")
        page.fill("input[name='password']", "5678")
        page.click("button[type='submit']")
        expect(page).to_have_url(re.compile(r".*/user/?(index\.php)?"))

        # Step 5: Play Screen and Bet Placement
        print("Going to play screen...")
        page.goto(f"{BASE_URL}/user/play.php?game_id={game_id}")

        page.wait_for_selector(f"h6:has-text(\"{match_title}\")", timeout=10000)

        print("Opening bet modal...")
        page.click(f"button[onclick*='openBetModal({match_id}']")

        modal_selector = f"#bet-modal-{match_id}"
        page.wait_for_selector(f"{modal_selector} .modal-body", state="visible")

        print("Placing bet...")
        amount_input = f"#amount-{match_id}"
        page.fill(amount_input, "50")

        submit_btn = f"#place-bet-{match_id}"
        page.click(submit_btn)

        msg_div = f"#bet-msg-{match_id}"
        page.wait_for_selector(f"{msg_div}.alert-success", timeout=10000)
        print("Bet placed successfully.")

        # Step 6: Verify Bet History
        print("Verifying Bet History...")
        page.goto(f"{BASE_URL}/user/bet_history.php")
        expect(page.locator("td", has_text="50.00")).to_have_count(1, timeout=5000) if page.locator("td", has_text="50.00").count() == 1 else expect(page.locator("td", has_text="50.00").first).to_be_visible()

        # Step 7: Verify Wallet Deduction / Transaction
        print("Verifying Wallet Transaction in DB...")
        try:
            conn = mysql.connector.connect(host="localhost", user="root", password="", database="trix_db")
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM wallet_transactions WHERE from_user = %s ORDER BY id DESC LIMIT 1", (test_user_id,))
            tx = cursor.fetchone()
            assert tx is not None
            assert float(tx['amount']) == 50.0
            print("Wallet transaction verified.")

            cursor.execute("SELECT * FROM activity_logs WHERE user_id = %s AND action = 'Bet Placed' ORDER BY id DESC LIMIT 1", (test_user_id,))
            log = cursor.fetchone()
            assert log is not None
            print("Activity log verified.")

            cursor.close()
            conn.close()
        except Exception as e:
            print("DB check error:", e)
            raise e

        print("All core tests passed!")
        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_trix_phase5()
    finally:
        proc.terminate()
