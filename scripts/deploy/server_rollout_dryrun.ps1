param(
    [string]$RepoRoot = "",
    [switch]$Strict
)

$ErrorActionPreference = "Stop"
if ([string]::IsNullOrWhiteSpace($RepoRoot)) {
    $RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
}

Write-Host "[stage9-dryrun] repo: $RepoRoot"
Write-Host "[stage9-dryrun] strict: $($Strict.IsPresent)"

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

$deployScriptPath = Join-Path $RepoRoot "scripts/deploy/deploy_server.sh"
$deployScript = Get-Content $deployScriptPath -Raw
$requiredDeployChecks = @(
    @{ Name = "git pull"; Pattern = "git\s+pull\s+origin\s+main" },
    @{ Name = "composer install"; Pattern = "install\s+--no-dev\s+--optimize-autoloader" },
    @{ Name = "artisan migrate --force"; Pattern = "artisan\s+migrate\s+--force" },
    @{ Name = "artisan optimize:clear"; Pattern = "artisan\s+optimize:clear" },
    @{ Name = "npm ci"; Pattern = "(npm|NPM_BIN).*(\s|^)ci(\s|$)" },
    @{ Name = "npm run build"; Pattern = "(npm|NPM_BIN).*(run\s+build)" },
    @{ Name = "artisan queue:restart"; Pattern = "artisan\s+queue:restart" },
    @{ Name = "api/health"; Pattern = "api/health" }
)

$missingDeploySteps = @()
foreach ($check in $requiredDeployChecks) {
    if ($deployScript -notmatch $check.Pattern) {
        $missingDeploySteps += $check.Name
    }
}

if ($missingDeploySteps.Count -gt 0) {
    $message = "[stage9-dryrun] deploy script misses required steps: " + ($missingDeploySteps -join ", ")
    if ($Strict.IsPresent) {
        Write-Error $message
        exit 1
    }
    Write-Warning $message
} else {
    Write-Host "[stage9-dryrun] deploy script steps: OK"
}

$runbookPath = Join-Path $RepoRoot "docs/stage9-server-rollout.md"
$runbook = Get-Content $runbookPath -Raw
if ($runbook -notmatch [regex]::Escape("bash scripts/deploy/deploy_server.sh")) {
    $message = "[stage9-dryrun] stage9 runbook does not reference scripts/deploy/deploy_server.sh"
    if ($Strict.IsPresent) {
        Write-Error $message
        exit 1
    }
    Write-Warning $message
} else {
    Write-Host "[stage9-dryrun] runbook deploy command: OK"
}

Write-Host "[stage9-dryrun] rollout command preview:"
Write-Host "  cd /var/www/Like-sprint"
Write-Host "  bash scripts/deploy/deploy_server.sh"
Write-Host "[stage9-dryrun] done"
