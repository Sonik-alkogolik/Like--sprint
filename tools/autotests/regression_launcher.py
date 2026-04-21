from __future__ import annotations

import argparse
import subprocess
import sys
import time
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
AUTOTESTS_DIR = ROOT / "tools" / "autotests"
REPORTS_DIR = ROOT / "tools" / "autotest-reports"


@dataclass
class TestCase:
    script: str
    label: str
    supports_browser_flags: bool = True


FULL_BROWSER_SUITE = [
    TestCase("e2e_user_sim_auth.py", "Auth"),
    TestCase("e2e_user_sim_performer.py", "Performer"),
    TestCase("e2e_user_sim_advertiser.py", "Advertiser"),
    TestCase("e2e_user_sim_finance.py", "Finance"),
    TestCase("e2e_user_sim_stage4_tasks.py", "Stage4 Tasks"),
    TestCase("e2e_user_sim_stage5_assignments.py", "Stage5 Assignments"),
    TestCase("e2e_user_sim_stage6_notifications.py", "Stage6 Notifications"),
    TestCase("e2e_user_sim_stage7_admin_antifraud.py", "Stage7 Antifraud"),
    TestCase("e2e_user_sim_stage7_blacklist.py", "Stage7 Blacklist"),
    TestCase("e2e_user_sim_stage7_admin_overview.py", "Stage7 Overview"),
]

POST_DEPLOY_SUITE = [
    TestCase("post_deploy_api_smoke.py", "Post-deploy API smoke", supports_browser_flags=False),
]

SUITES = {
    "full_browser": FULL_BROWSER_SUITE,
    "post_deploy": POST_DEPLOY_SUITE,
}


def build_command(case: TestCase, delay_ms: int, headless: bool, base_url: str) -> list[str]:
    script_path = str(AUTOTESTS_DIR / case.script)
    cmd = [sys.executable, script_path]

    if case.supports_browser_flags:
        cmd.extend(["--delay-ms", str(delay_ms), "--base-url", base_url])
        if headless:
            cmd.append("--headless")
    else:
        cmd.extend(["--base-url", base_url.replace(":5173", ":8000") if ":5173" in base_url else base_url])

    return cmd


def run_suite(name: str, delay_ms: int, headless: bool, base_url: str) -> tuple[int, str]:
    cases = SUITES[name]
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    started = time.time()

    lines: list[str] = [
        f"# Regression Report: {name}",
        "",
        f"- Started: {datetime.now().isoformat(timespec='seconds')}",
        f"- Base URL: {base_url}",
        f"- Headless: {headless}",
        f"- Delay ms: {delay_ms}",
        "",
        "## Results",
    ]

    fail_count = 0
    for idx, case in enumerate(cases, start=1):
        cmd = build_command(case, delay_ms, headless, base_url)
        case_start = time.time()
        proc = subprocess.run(cmd, cwd=str(ROOT), capture_output=True, text=True)
        duration = time.time() - case_start
        ok = proc.returncode == 0
        if not ok:
            fail_count += 1

        lines.append(f"{idx}. {'PASS' if ok else 'FAIL'} - {case.label} (`{case.script}`) [{duration:.1f}s]")
        if proc.stdout.strip():
            lines.append("```text")
            lines.append(proc.stdout.strip())
            lines.append("```")
        if proc.stderr.strip():
            lines.append("```text")
            lines.append(proc.stderr.strip())
            lines.append("```")

    total = time.time() - started
    lines.extend(
        [
            "",
            "## Summary",
            f"- Total tests: {len(cases)}",
            f"- Failed: {fail_count}",
            f"- Duration: {total:.1f}s",
        ]
    )

    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    report = REPORTS_DIR / f"regression_{name}_{stamp}.md"
    report.write_text("\n".join(lines), encoding="utf-8")
    return fail_count, str(report)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--suite", choices=sorted(SUITES.keys()), default="full_browser")
    parser.add_argument("--delay-ms", type=int, default=120)
    parser.add_argument("--headless", action="store_true")
    parser.add_argument("--base-url", default="http://127.0.0.1:5173")
    parser.add_argument("--list", action="store_true")
    args = parser.parse_args()

    if args.list:
        for suite_name, cases in SUITES.items():
            print(f"{suite_name}:")
            for case in cases:
                print(f"  - {case.script}")
        return

    fail_count, report = run_suite(
        name=args.suite,
        delay_ms=args.delay_ms,
        headless=args.headless,
        base_url=args.base_url,
    )
    if fail_count == 0:
        print(f"Regression suite {args.suite}: PASS")
        print(f"Report: {report}")
        return

    print(f"Regression suite {args.suite}: FAIL ({fail_count} failed)")
    print(f"Report: {report}")
    raise SystemExit(1)


if __name__ == "__main__":
    main()
