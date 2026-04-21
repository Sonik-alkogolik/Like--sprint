param(
    [string]$BaseUrl = "http://127.0.0.1:8000",
    [string]$FrontendUrl = "http://127.0.0.1:5173"
)

$ErrorActionPreference = "Stop"

function Assert-Http200 {
    param(
        [string]$Url,
        [string]$Label
    )
    Write-Host "[stage10-smoke] $Label -> $Url"
    $resp = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 20
    if ($resp.StatusCode -ne 200) {
        throw "$Label failed: HTTP $($resp.StatusCode)"
    }
}

Assert-Http200 -Url "$BaseUrl/api/health" -Label "API health"
Assert-Http200 -Url "$FrontendUrl" -Label "Frontend root"

Write-Host "[stage10-smoke] PASS"
