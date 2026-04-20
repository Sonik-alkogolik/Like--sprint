"""
Browser user-simulation E2E for auth/profile/session flow.

Run prerequisites:
1) Backend running on http://127.0.0.1:8000
2) Frontend running on http://127.0.0.1:5173
3) `python -m playwright install chromium` once
"""

from __future__ import annotations

import argparse
import time
import uuid

from playwright.sync_api import sync_playwright


def pause(ms: int) -> None:
    time.sleep(ms / 1000)


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:8]
    email = f"user_{unique}@example.com"
    password = "StrongPass123!"
    name = f"User {unique}"
    username = f"user_{unique}"
    wallet = f"wallet_{unique}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        # Register
        page.goto(f"{base_url}/register", wait_until="domcontentloaded")
        pause(delay_ms)
        page.get_by_label("Имя").fill(name)
        pause(delay_ms)
        page.get_by_label("Email").fill(email)
        pause(delay_ms)
        page.locator("select").first.select_option("performer")
        pause(delay_ms)
        page.get_by_label("Пароль").fill(password)
        pause(delay_ms)
        page.get_by_label("Подтверждение пароля").fill(password)
        pause(delay_ms)
        page.get_by_role("button", name="Создать аккаунт").click()
        page.wait_for_url(f"{base_url}/profile")
        pause(delay_ms)

        # Update profile
        page.get_by_label("Username").fill(username)
        pause(delay_ms)
        page.get_by_label("Display name").fill(name)
        pause(delay_ms)
        page.get_by_label("О себе").fill("E2E simulated user flow")
        pause(delay_ms)
        page.get_by_label("Кошелек для выплат").fill(wallet)
        pause(delay_ms)
        page.get_by_role("button", name="Сохранить").click()
        page.get_by_text("Профиль сохранен").wait_for(timeout=10000)
        pause(delay_ms)

        # Sessions
        page.get_by_role("link", name="Сессии").click()
        page.wait_for_url(f"{base_url}/sessions")
        page.get_by_text("Current: yes").first.wait_for(timeout=10000)
        pause(delay_ms)

        # Logout
        page.get_by_role("button", name="Выход").click()
        page.wait_for_url(f"{base_url}/login")
        pause(delay_ms)

        # Login again
        page.get_by_label("Email").fill(email)
        pause(delay_ms)
        page.get_by_label("Пароль").fill(password)
        pause(delay_ms)
        page.get_by_role("button", name="Войти").click()
        page.wait_for_url(f"{base_url}/profile")
        page.get_by_text("Роль:").wait_for(timeout=10000)

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=600)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    run(base_url=args.base_url, delay_ms=args.delay_ms, headless=args.headless)
    print("E2E user simulation: PASS")


if __name__ == "__main__":
    main()
