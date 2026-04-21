# autotests

Place smoke, functional, e2e, and regression tests here.

## Regression launcher

Run full browser regression:

```bash
python tools/autotests/regression_launcher.py --suite full_browser --delay-ms 120
```

Run post-deploy API smoke suite:

```bash
python tools/autotests/regression_launcher.py --suite post_deploy
```

Reports are saved to `tools/autotest-reports/`.
