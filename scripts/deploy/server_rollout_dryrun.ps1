param(
    [string]$RepoRoot = ""
)

$ErrorActionPreference = "Stop"
if ([string]::IsNullOrWhiteSpace($RepoRoot)) {
    $RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
}

Write-Host "[stage9-dryrun] repo: $RepoRoot"

$required = @(
    "backend/.env.production.example",
    "client/.env.production.example",
    "docs/deployment.md",
    "docs/nginx.like-sprint.conf",
    "docs/server-checklist.md",
    "docs/stage9-server-rollout.md",
    "scripts/deploy/deploy_server.sh"
)

$missing = @()
foreach ($rel in $required) {
    $path = Join-Path $RepoRoot $rel
    if (-not (Test-Path $path)) {
        $missing += $rel
    }
}

if ($missing.Count -gt 0) {
    Write-Error ("[stage9-dryrun] missing files: " + ($missing -join ", "))
    exit 1
}

Write-Host "[stage9-dryrun] required artifacts: OK"
Write-Host "[stage9-dryrun] rollout command preview:"
Write-Host "  cd /var/www/Like-sprint"
Write-Host "  bash scripts/deploy/deploy_server.sh"
Write-Host "[stage9-dryrun] done"
