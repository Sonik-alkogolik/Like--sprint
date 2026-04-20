from __future__ import annotations

import argparse
import time
import uuid

from playwright.sync_api import sync_playwright


def pause(ms: int) -> None:
    time.sleep(ms / 1000)


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:8]
    email = f"advertiser_{unique}@example.com"
    password = "StrongPass123!"
    name = f"Advertiser {unique}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        page.goto(f"{base_url}/register", wait_until="domcontentloaded")
        pause(delay_ms)
        page.get_by_label("Имя").fill(name)
        page.get_by_label("Email").fill(email)
        page.locator("select").first.select_option("advertiser")
        page.get_by_label("Пароль").fill(password)
        page.get_by_label("Подтверждение пароля").fill(password)
        pause(delay_ms)
        page.get_by_role("button", name="Создать аккаунт").click()
        page.wait_for_url(f"{base_url}/advertiser/home")
        page.get_by_text("Advertiser dashboard access granted").wait_for(timeout=10000)

        # role guard check
        page.goto(f"{base_url}/performer/home")
        page.wait_for_url(f"{base_url}/profile")

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=600)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()
    run(args.base_url, args.delay_ms, args.headless)
    print("E2E advertiser simulation: PASS")


if __name__ == "__main__":
    main()

