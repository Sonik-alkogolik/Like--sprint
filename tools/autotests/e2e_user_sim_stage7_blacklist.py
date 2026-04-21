from __future__ import annotations

import argparse
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


def logout(page: Page, base_url: str) -> None:
    page.get_by_role("button", name="Выход").click()
    page.wait_for_url(f"{base_url}/login")


def login_expect_blocked(page: Page, base_url: str, email: str, password: str) -> None:
    page.goto(f"{base_url}/login", wait_until="domcontentloaded")
    page.get_by_label("Email").fill(email)
    page.get_by_label("Пароль").fill(password)
    page.get_by_role("button", name="Войти").click()
    page.get_by_text("Access denied by blacklist").wait_for(timeout=10000)


def register_expect_blocked(page: Page, base_url: str, email: str, password: str) -> None:
    page.goto(f"{base_url}/register", wait_until="domcontentloaded")
    page.get_by_label("Имя").fill("Blocked User")
    page.get_by_label("Email").fill(email)
    page.locator("select").first.select_option("performer")
    page.get_by_label("Пароль").fill(password)
    page.get_by_label("Подтверждение пароля").fill(password)
    page.get_by_role("button", name="Создать аккаунт").click()
    page.get_by_text("Registration is blocked by blacklist").wait_for(timeout=10000)


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:7]
    password = "StrongPass123!"
    admin_email = f"admin_bl_{unique}@example.com"
    performer_email = f"perf_bl_{unique}@example.com"
    blocked_register_email = f"new_bl_{unique}@example.com"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        register(page, base_url, f"Performer {unique}", performer_email, password, "performer")
        logout(page, base_url)

        register(page, base_url, f"Admin {unique}", admin_email, password, "admin")
        page.locator("[data-testid='blacklist-type']").select_option("email")
        page.locator("[data-testid='blacklist-value']").fill(performer_email)
        page.locator("[data-testid='blacklist-note']").fill("Block existing performer login")
        page.locator("[data-testid='add-blacklist-btn']").click()

        page.locator("[data-testid='blacklist-value']").fill(blocked_register_email)
        page.locator("[data-testid='blacklist-note']").fill("Block new registration")
        page.locator("[data-testid='add-blacklist-btn']").click()

        page.locator("[data-testid^='blacklist-row-']", has_text=performer_email).first.wait_for(timeout=10000)
        page.locator("[data-testid^='blacklist-row-']", has_text=blocked_register_email).first.wait_for(timeout=10000)
        logout(page, base_url)

        login_expect_blocked(page, base_url, performer_email, password)
        register_expect_blocked(page, base_url, blocked_register_email, password)

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=220)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    run(args.base_url, args.delay_ms, args.headless)
    print("E2E stage7 blacklist simulation: PASS")


if __name__ == "__main__":
    main()
