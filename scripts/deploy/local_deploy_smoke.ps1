param(
    [switch]$DryRun = $true
)

$ErrorActionPreference = "Stop"
$root = Resolve-Path (Join-Path $PSScriptRoot "..\..")
$backend = Join-Path $root "backend"
$client = Join-Path $root "client"
$php = "C:\OSPanel\modules\PHP-8.3\PHP\php.exe"

Write-Host "[stage8-smoke] root: $root"
if ($DryRun) {
    Write-Host "[stage8-smoke] DRY RUN enabled"
}

function Run-Cmd {
    param(
        [string]$Name,
        [string]$WorkDir,
        [string]$Exe,
        [string[]]$CommandArgs
    )

    Write-Host "[stage8-smoke] $Name"
    if ($DryRun) {
        Write-Host "  -> $Exe $($CommandArgs -join ' ')"
        return
    }

    Push-Location $WorkDir
    try {
        & $Exe @CommandArgs
        if ($LASTEXITCODE -ne 0) {
            throw "Command failed with exit code ${LASTEXITCODE}: $Exe $($CommandArgs -join ' ')"
        }
    } finally {
        Pop-Location
    }
}

Run-Cmd -Name "Backend route smoke" -WorkDir $backend -Exe $php -CommandArgs @("artisan", "route:list")
Run-Cmd -Name "Frontend build" -WorkDir $client -Exe "npm.cmd" -CommandArgs @("run", "build")
Run-Cmd -Name "Python e2e syntax check" -WorkDir $root -Exe "python" -CommandArgs @("-m", "py_compile", "tools\autotests\e2e_user_sim_stage7_admin_antifraud.py")

Write-Host "[stage8-smoke] done"
