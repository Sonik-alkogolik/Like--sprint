"""Like-sprint autotest UI."""

from __future__ import annotations

import subprocess
import sys
from datetime import datetime
from pathlib import Path

import gradio as gr


ROOT = Path(__file__).resolve().parents[1]
AUTOTESTS = ROOT / "tools" / "autotests"
REPORTS = ROOT / "tools" / "autotest-reports"

SINGLE_TESTS = [
    "e2e_user_sim_auth.py",
    "e2e_user_sim_performer.py",
    "e2e_user_sim_advertiser.py",
    "e2e_user_sim_finance.py",
    "e2e_user_sim_stage4_tasks.py",
    "e2e_user_sim_stage5_assignments.py",
    "e2e_user_sim_stage6_notifications.py",
    "e2e_user_sim_stage7_admin_antifraud.py",
    "e2e_user_sim_stage7_blacklist.py",
    "e2e_user_sim_stage7_admin_overview.py",
]


def run_single_test(script: str, delay_ms: int, headless: bool) -> str:
    cmd = [sys.executable, str(AUTOTESTS / script), "--delay-ms", str(delay_ms), "--base-url", "http://127.0.0.1:5173"]
    if headless:
        cmd.append("--headless")
    started = datetime.now().isoformat(timespec="seconds")
    proc = subprocess.run(cmd, cwd=str(ROOT), capture_output=True, text=True)
    status = "PASS" if proc.returncode == 0 else "FAIL"
    return (
        f"[{started}] {script}: {status}\n\n"
        f"STDOUT:\n{proc.stdout.strip() or '-'}\n\n"
        f"STDERR:\n{proc.stderr.strip() or '-'}"
    )


def run_full_regression(delay_ms: int, headless: bool) -> str:
    cmd = [sys.executable, str(AUTOTESTS / "regression_launcher.py"), "--suite", "full_browser", "--delay-ms", str(delay_ms)]
    if headless:
        cmd.append("--headless")
    proc = subprocess.run(cmd, cwd=str(ROOT), capture_output=True, text=True)
    status = "PASS" if proc.returncode == 0 else "FAIL"
    return f"Full regression: {status}\n\n{proc.stdout.strip()}\n\n{proc.stderr.strip()}"


def run_post_deploy_smoke(base_url: str) -> str:
    cmd = [sys.executable, str(AUTOTESTS / "post_deploy_api_smoke.py"), "--base-url", base_url]
    proc = subprocess.run(cmd, cwd=str(ROOT), capture_output=True, text=True)
    status = "PASS" if proc.returncode == 0 else "FAIL"
    return f"Post-deploy smoke: {status}\n\n{proc.stdout.strip()}\n\n{proc.stderr.strip()}"


def latest_report() -> str:
    REPORTS.mkdir(parents=True, exist_ok=True)
    reports = sorted(REPORTS.glob("regression_*.md"), key=lambda p: p.stat().st_mtime, reverse=True)
    if not reports:
        return "No regression reports yet"
    latest = reports[0]
    return f"{latest}\n\n{latest.read_text(encoding='utf-8')}"


with gr.Blocks(title="Like-sprint Autotest UI") as demo:
    gr.Markdown("# Like-sprint Autotest UI")
    with gr.Row():
        test_choice = gr.Dropdown(choices=SINGLE_TESTS, value=SINGLE_TESTS[0], label="Single E2E")
        delay = gr.Slider(minimum=50, maximum=500, step=10, value=120, label="Delay ms")
        headless = gr.Checkbox(value=False, label="Headless")
    with gr.Row():
        run_single_btn = gr.Button("Run Single E2E")
        run_reg_btn = gr.Button("Run Full Regression")
        run_post_btn = gr.Button("Run Post-deploy API Smoke")
        latest_btn = gr.Button("Open Latest Regression Report")
    base_url = gr.Textbox(value="http://127.0.0.1:8000", label="API Base URL for post-deploy smoke")
    out = gr.Textbox(label="Result", lines=24)

    run_single_btn.click(fn=run_single_test, inputs=[test_choice, delay, headless], outputs=out)
    run_reg_btn.click(fn=run_full_regression, inputs=[delay, headless], outputs=out)
    run_post_btn.click(fn=run_post_deploy_smoke, inputs=[base_url], outputs=out)
    latest_btn.click(fn=latest_report, outputs=out)


if __name__ == "__main__":
    demo.launch(server_name="127.0.0.1", server_port=7861)
