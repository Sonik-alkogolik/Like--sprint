from __future__ import annotations

import argparse
import re
import uuid

from playwright.sync_api import Page, sync_playwright


def register(page: Page, base_url: str, name: str, email: str, password: str, role: str) -> None:
    page.goto(f"{base_url}/register", wait_until="domcontentloaded")
    page.get_by_label("Имя").fill(name)
    page.get_by_label("Email").fill(email)
    page.locator("select").first.select_option(role)
    page.get_by_label("Пароль").fill(password)
    page.get_by_label("Подтверждение пароля").fill(password)
    page.get_by_role("button", name="Создать аккаунт").click()
    expected = {
        "performer": f"{base_url}/performer/home",
        "advertiser": f"{base_url}/advertiser/home",
        "admin": f"{base_url}/admin/moderation",
    }[role]
    page.wait_for_url(expected)


def ensure_number_card(page: Page, testid: str) -> None:
    card = page.locator(f"[data-testid='{testid}']")
    card.wait_for(timeout=10000)
    text = card.inner_text()
    if not re.search(r"\d+", text):
        raise AssertionError(f"Overview card has no numeric value: {testid}, text={text}")


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:7]
    password = "StrongPass123!"
    admin_email = f"admin_overview_{unique}@example.com"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        register(page, base_url, f"Admin {unique}", admin_email, password, "admin")
        page.locator("[data-testid='admin-overview']").wait_for(timeout=10000)

        ensure_number_card(page, "overview-users-total")
        ensure_number_card(page, "overview-users-blocked")
        ensure_number_card(page, "overview-tasks-active")
        ensure_number_card(page, "overview-disputes-open")
        ensure_number_card(page, "overview-blacklist-active")
        ensure_number_card(page, "overview-notify-pending")

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=180)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    run(args.base_url, args.delay_ms, args.headless)
    print("E2E stage7 admin overview simulation: PASS")


if __name__ == "__main__":
    main()
