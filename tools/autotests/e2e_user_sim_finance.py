from __future__ import annotations

import argparse
import time
import uuid

from playwright.sync_api import sync_playwright


def pause(ms: int) -> None:
    time.sleep(ms / 1000)


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:8]
    email = f"finance_{unique}@example.com"
    password = "StrongPass123!"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        page.goto(f"{base_url}/register", wait_until="domcontentloaded")
        pause(delay_ms)
        page.get_by_label("Имя").fill(f"Finance {unique}")
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
        page.wait_for_url(f"{base_url}/performer/home")

        page.get_by_role("link", name="Финансы").click()
        page.wait_for_url(f"{base_url}/finance")
        page.locator("[data-testid='wallet-available']").wait_for(timeout=10000)

        before = float(page.locator("[data-testid='wallet-available']").inner_text())
        page.locator("input[type='number']").first.fill("12.50")
        pause(delay_ms)
        page.locator("[data-testid='deposit-btn']").click()
        page.get_by_text("Тестовое пополнение проведено").wait_for(timeout=10000)
        page.wait_for_function(
            """(before) => {
                const el = document.querySelector('[data-testid="wallet-available"]');
                if (!el) return false;
                const val = parseFloat(el.textContent || '0');
                return Number.isFinite(val) && val > before;
            }""",
            arg=before,
            timeout=10000,
        )
        after_deposit = float(page.locator("[data-testid='wallet-available']").inner_text())

        if after_deposit <= before:
            raise AssertionError("Deposit did not increase available balance")

        page.locator("input[type='number']").nth(1).fill("5.00")
        pause(delay_ms)
        page.get_by_label("Реквизиты карты").fill("4111111111111111")
        pause(delay_ms)
        page.locator("[data-testid='withdraw-btn']").click()
        page.get_by_text("Заявка на вывод создана").wait_for(timeout=10000)

        page.wait_for_function(
            """() => {
                const el = document.querySelector('[data-testid="wallet-hold"]');
                if (!el) return false;
                const val = parseFloat(el.textContent || '0');
                return Number.isFinite(val) && val >= 5;
            }""",
            timeout=10000,
        )
        hold = float(page.locator("[data-testid='wallet-hold']").inner_text())
        if hold < 5:
            raise AssertionError("Withdrawal hold was not applied")

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=500)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    run(args.base_url, args.delay_ms, args.headless)
    print("E2E finance simulation: PASS")


if __name__ == "__main__":
    main()
