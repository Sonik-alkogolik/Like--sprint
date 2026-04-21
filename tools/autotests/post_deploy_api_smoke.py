from __future__ import annotations

import argparse
import json
import urllib.error
import urllib.request
import uuid


def request_json(method: str, url: str, payload: dict | None = None, token: str | None = None) -> tuple[int, dict]:
    body = None
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    if payload is not None:
        body = json.dumps(payload).encode("utf-8")

    req = urllib.request.Request(url=url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            status = resp.getcode()
            content = resp.read().decode("utf-8") or "{}"
            return status, json.loads(content)
    except urllib.error.HTTPError as e:
        content = e.read().decode("utf-8") or "{}"
        try:
            data = json.loads(content)
        except json.JSONDecodeError:
            data = {"raw": content}
        return e.code, data


def assert_ok(status: int, data: dict, label: str, expected_status: int = 200) -> None:
    if status != expected_status:
        raise AssertionError(f"{label} failed: expected {expected_status}, got {status}, payload={data}")


def run(base_url: str) -> None:
    unique = uuid.uuid4().hex[:8]
    email = f"smoke_{unique}@example.com"
    password = "StrongPass123!"

    status, data = request_json("GET", f"{base_url}/api/health")
    assert_ok(status, data, "health")
    if data.get("status") != "ok":
        raise AssertionError(f"health payload invalid: {data}")

    status, data = request_json(
        "POST",
        f"{base_url}/api/auth/register",
        {
            "name": f"Smoke {unique}",
            "email": email,
            "password": password,
            "password_confirmation": password,
            "role": "performer",
        },
    )
    assert_ok(status, data, "register", expected_status=201)
    token = data.get("token")
    if not token:
        raise AssertionError(f"register returned no token: {data}")

    status, data = request_json("GET", f"{base_url}/api/auth/me", token=token)
    assert_ok(status, data, "auth/me")

    status, data = request_json("GET", f"{base_url}/api/performer/home", token=token)
    assert_ok(status, data, "performer/home")
    if data.get("role") != "performer":
        raise AssertionError(f"performer/home payload invalid: {data}")

    status, data = request_json(
        "POST",
        f"{base_url}/api/auth/login",
        {"email": email, "password": password},
    )
    assert_ok(status, data, "login")


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://127.0.0.1:8000")
    args = parser.parse_args()
    run(args.base_url.rstrip("/"))
    print("Post-deploy API smoke: PASS")


if __name__ == "__main__":
    main()
