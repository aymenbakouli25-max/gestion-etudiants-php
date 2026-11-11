$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
Set-Location $projectPath

if (-not (Test-Path (Join-Path $projectPath '.git'))) {
    throw "Git repository not found: $projectPath"
}

$status = git status --porcelain
if (-not $status) {
    exit 0
}

$timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm'
git add --all
git commit -m "Auto-sync: $timestamp"
git push origin main
