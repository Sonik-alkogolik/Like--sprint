from __future__ import annotations

import argparse
import time
import uuid

from playwright.sync_api import Page, sync_playwright


def pause(ms: int) -> None:
    time.sleep(ms / 1000)


def register(page: Page, base_url: str, name: str, email: str, password: str, role: str) -> None:
    page.goto(f"{base_url}/register", wait_until="domcontentloaded")
    page.get_by_label("Имя").fill(name)
    page.get_by_label("Email").fill(email)
    page.locator("select").first.select_option(role)
    page.get_by_label("Пароль").fill(password)
    page.get_by_label("Подтверждение пароля").fill(password)
    page.get_by_role("button", name="Создать аккаунт").click()
    if role == "advertiser":
        page.wait_for_url(f"{base_url}/advertiser/home")
    elif role == "admin":
        page.wait_for_url(f"{base_url}/admin/moderation")
    else:
        page.wait_for_url(f"{base_url}/performer/home")


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:7]
    password = "StrongPass123!"
    advertiser_email = f"adv_{unique}@example.com"
    admin_email = f"admin_{unique}@example.com"
    performer_email = f"perf_{unique}@example.com"
    task_title = f"Task {unique}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        # Advertiser creates task and sends to moderation
        register(page, base_url, f"Adv {unique}", advertiser_email, password, "advertiser")
        page.get_by_role("link", name="Финансы").click()
        page.wait_for_url(f"{base_url}/finance")
        page.locator("input[type='number']").first.fill("10")
        page.locator("[data-testid='deposit-btn']").click()
        page.get_by_text("Тестовое пополнение проведено").wait_for(timeout=10000)

        page.get_by_role("link", name="Мои задания").click()
        page.wait_for_url(f"{base_url}/advertiser/tasks")
        page.get_by_label("Заголовок").fill(task_title)
        page.get_by_label("Инструкция").fill("Сделать действие и отправить отчёт")
        page.get_by_label("Start URL").fill("https://example.com")
        page.locator("[data-testid='create-task-btn']").click()
        page.get_by_text("Задание создано").wait_for(timeout=10000)

        row = page.locator(".session", has_text=task_title).first
        row.locator("button", has_text="На модерацию").click()
        row.locator("text=moderation: pending").wait_for(timeout=10000)

        page.get_by_role("button", name="Выход").click()
        page.wait_for_url(f"{base_url}/login")

        # Admin approves task
        register(page, base_url, f"Admin {unique}", admin_email, password, "admin")
        row_admin = page.locator(".session", has_text=task_title).first
        row_admin.locator("button", has_text="Принять").click()
        row_admin.wait_for(state="hidden", timeout=10000)

        page.get_by_role("button", name="Выход").click()
        page.wait_for_url(f"{base_url}/login")

        # Advertiser launches task
        page.get_by_label("Email").fill(advertiser_email)
        page.get_by_label("Пароль").fill(password)
        page.get_by_role("button", name="Войти").click()
        page.wait_for_url(f"{base_url}/advertiser/home")
        page.get_by_role("link", name="Мои задания").click()
        page.wait_for_url(f"{base_url}/advertiser/tasks")

        row_after = page.locator(".session", has_text=task_title).first
        row_after.locator("button", has_text="Запустить").click()
        row_after.locator("text=status: active").wait_for(timeout=10000)

        page.get_by_role("button", name="Выход").click()
        page.wait_for_url(f"{base_url}/login")

        # Performer sees task in feed
        register(page, base_url, f"Performer {unique}", performer_email, password, "performer")
        page.get_by_role("link", name="Доступные задания").click()
        page.wait_for_url(f"{base_url}/performer/tasks")
        page.locator(".session", has_text=task_title).first.wait_for(timeout=10000)

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=400)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    run(args.base_url, args.delay_ms, args.headless)
    print("E2E stage4 task flow simulation: PASS")


if __name__ == "__main__":
    main()