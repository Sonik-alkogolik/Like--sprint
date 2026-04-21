from __future__ import annotations

import argparse
import re
import time
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


def login(page: Page, base_url: str, email: str, password: str, role: str) -> None:
    page.goto(f"{base_url}/login", wait_until="domcontentloaded")
    page.get_by_label("Email").fill(email)
    page.get_by_label("Пароль").fill(password)
    page.get_by_role("button", name="Войти").click()
    expected = {
        "performer": f"{base_url}/performer/home",
        "advertiser": f"{base_url}/advertiser/home",
        "admin": f"{base_url}/admin/moderation",
    }[role]
    page.wait_for_url(expected)


def logout(page: Page, base_url: str) -> None:
    page.get_by_role("button", name="Выход").click()
    page.wait_for_url(f"{base_url}/login")


def row_task_id_by_title(page: Page, title: str) -> int:
    row = page.locator(".session", has_text=title).first
    text = row.inner_text()
    match = re.search(r"#(\d+)", text)
    if not match:
        raise AssertionError(f"Cannot parse task id for {title}")
    return int(match.group(1))


def run(base_url: str, delay_ms: int, headless: bool) -> None:
    unique = uuid.uuid4().hex[:7]
    password = "StrongPass123!"
    advertiser_email = f"adv6_{unique}@example.com"
    admin_email = f"admin6_{unique}@example.com"
    performer_email = f"perf6_{unique}@example.com"
    task_title = f"Notify {unique}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless, slow_mo=delay_ms)
        page = browser.new_page()

        register(page, base_url, f"Advertiser {unique}", advertiser_email, password, "advertiser")
        page.get_by_role("link", name="Финансы").click()
        page.wait_for_url(f"{base_url}/finance")
        page.locator("input[type='number']").first.fill("20")
        page.locator("[data-testid='deposit-btn']").click()
        page.get_by_text("Тестовое пополнение проведено").wait_for(timeout=10000)

        page.get_by_role("link", name="Мои задания").click()
        page.wait_for_url(f"{base_url}/advertiser/tasks")
        page.get_by_label("Заголовок").fill(task_title)
        page.get_by_label("Инструкция").fill("Проверка уведомлений")
        page.get_by_label("Start URL").fill("https://example.com/notify")
        page.locator("[data-testid='create-task-btn']").click()
        page.get_by_text("Задание создано").wait_for(timeout=10000)
        task_id = row_task_id_by_title(page, task_title)
        page.locator(".session", has_text=task_title).locator("button", has_text="На модерацию").click()
        logout(page, base_url)

        register(page, base_url, f"Admin {unique}", admin_email, password, "admin")
        page.locator(".session", has_text=task_title).locator("button", has_text="Принять").click()
        page.locator("text=Очередь пустая").wait_for(timeout=10000)
        logout(page, base_url)

        login(page, base_url, advertiser_email, password, "advertiser")
        page.get_by_role("link", name="Мои задания").click()
        page.wait_for_url(f"{base_url}/advertiser/tasks")
        page.locator(".session", has_text=task_title).locator("button", has_text="Запустить").click()
        logout(page, base_url)

        register(page, base_url, f"Performer {unique}", performer_email, password, "performer")
        page.get_by_role("link", name="Доступные задания").click()
        page.wait_for_url(f"{base_url}/performer/tasks")
        page.locator(".session", has_text=task_title).locator("button", has_text="Начать выполнение").click()
        page.wait_for_url(re.compile(f"{base_url}/performer/assignments/\\d+"))
        assignment_id = int(page.url.rstrip("/").split("/")[-1])
        page.get_by_label("Текст отчёта").fill("Уведомления этап 6")
        page.locator("[data-testid='submit-report-btn']").click()
        page.get_by_text("Спасибо, ваш отчёт принят").wait_for(timeout=10000)
        logout(page, base_url)

        login(page, base_url, advertiser_email, password, "advertiser")
        page.get_by_role("link", name=re.compile("Уведомления")).click()
        page.wait_for_url(f"{base_url}/notifications")
        page.locator(".session", has_text="Новый отчёт на проверку").wait_for(timeout=10000)

        page.goto(f"{base_url}/advertiser/tasks/{task_id}/reports")
        page.locator(".session", has_text="Уведомления этап 6").locator("button", has_text="Вернуть на доработку").click()
        page.get_by_text("Отчётов на проверке нет").wait_for(timeout=10000)
        logout(page, base_url)

        login(page, base_url, performer_email, password, "performer")
        page.get_by_role("link", name=re.compile("Уведомления")).click()
        page.wait_for_url(f"{base_url}/notifications")
        page.locator(".session", has_text="Отчёт возвращён на доработку").wait_for(timeout=10000)

        page.goto(f"{base_url}/performer/assignments/{assignment_id}")
        page.get_by_label("Текст отчёта").fill("Уведомления этап 6 (v2)")
        page.locator("[data-testid='submit-report-btn']").click()
        page.get_by_text("Спасибо, ваш отчёт принят").wait_for(timeout=10000)
        logout(page, base_url)

        login(page, base_url, advertiser_email, password, "advertiser")
        page.goto(f"{base_url}/advertiser/tasks/{task_id}/reports")
        page.locator(".session", has_text="Уведомления этап 6 (v2)").locator("button", has_text="Принять").click()
        page.get_by_text("Отчётов на проверке нет").wait_for(timeout=10000)
        logout(page, base_url)

        login(page, base_url, performer_email, password, "performer")
        page.get_by_role("link", name=re.compile("Уведомления")).click()
        page.wait_for_url(f"{base_url}/notifications")
        page.locator(".session", has_text="Отчёт подтверждён").wait_for(timeout=10000)
        logout(page, base_url)

        login(page, base_url, admin_email, password, "admin")
        page.get_by_role("link", name=re.compile("Уведомления")).click()
        page.wait_for_url(f"{base_url}/notifications")
        page.locator("[data-testid='dispatch-notification-queue-btn']").click()
        page.locator("text=Processed:").wait_for(timeout=10000)

        browser.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--delay-ms", type=int, default=300)
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    run(args.base_url, args.delay_ms, args.headless)
    time.sleep(0.2)
    print("E2E stage6 notifications simulation: PASS")


if __name__ == "__main__":
    main()
