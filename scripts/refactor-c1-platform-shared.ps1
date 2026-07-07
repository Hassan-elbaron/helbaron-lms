# STEP 5B - Chunk C1: move App\Shared -> App\Platform\Shared  (ONLY this; nothing else)
# Source: docs/refactor/07A_BACKEND_CONTEXT_DRY_RUN.md (chunk C1)
# Host-side: move folder + rewrite every reference. Verification: via docker compose exec.
# Run from repo root:  powershell -ExecutionPolicy Bypass -File scripts/refactor-c1-platform-shared.ps1
# Commit/stash WIP first. Does NOT touch Identity/Notifications/any Domain.

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
$api  = Join-Path $repo "apps\api"
$utf8 = New-Object System.Text.UTF8Encoding($false)
Set-Location $repo

Write-Host "== C1.1  Move app/Shared -> app/Platform/Shared =="
$src = Join-Path $api "app\Shared"
$dst = Join-Path $api "app\Platform\Shared"
if (-not (Test-Path -LiteralPath $src)) {
  if (Test-Path -LiteralPath $dst) { Write-Host "already moved; continuing to reference rewrite." }
  else { throw "app/Shared not found and app/Platform/Shared missing - aborting." }
} else {
  [System.IO.Directory]::CreateDirectory((Join-Path $api "app\Platform")) | Out-Null
  Move-Item -LiteralPath $src -Destination $dst -Force
  Write-Host "moved: app/Shared -> app/Platform/Shared"
}

Write-Host "== C1.2  Rewrite references across apps/api (*.php) =="
# Two literal passes: double-backslash form (string literals in config/etc.) first, then single (code).
$files = Get-ChildItem -LiteralPath $api -Recurse -File -Include *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\|\\storage\\|\\bootstrap\\cache\\|\\node_modules\\' }

$changed = 0
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName                      # long-path (>260) safe prefix
  try { $t = [System.IO.File]::ReadAllText($lp) }
  catch { Write-Host ("  SKIP (unreadable): " + $f.FullName) -ForegroundColor Yellow; continue }
  $o = $t
  $t = $t.Replace('App\\Shared', 'App\\Platform\\Shared')   # e.g. 'App\\Shared\\Support\\...' in strings
  $t = $t.Replace('App\Shared',  'App\Platform\Shared')     # namespace / use / FQCN in code
  if ($t -ne $o) {
    [System.IO.File]::WriteAllText($lp, $t, $utf8)
    $changed++
    Write-Host ("  updated: " + $f.FullName.Substring($api.Length + 1))
  }
}
Write-Host "files updated: $changed"

Write-Host "== C1.3  Stage =="
git add -A | Out-Null

Write-Host "== C1.4  Assert zero remaining App\Shared references (excluding App\Platform\Shared) =="
$remainList = @()
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $c = [System.IO.File]::ReadAllText($lp) } catch { continue }
  if ($c.Contains('App\Shared') -or $c.Contains('App\\Shared')) { $remainList += $f.FullName }
}
if ($remainList.Count -gt 0) {
  Write-Host "!! Remaining App\Shared references found:" -ForegroundColor Red
  $remainList | ForEach-Object { Write-Host ("   " + $_) }
} else {
  Write-Host "OK: no remaining App\Shared references."
}

Write-Host "`n== C1.5  Verify (Docker) - outputs below =="
$ErrorActionPreference = "Continue"   # docker/native tools write progress to stderr; do not treat as fatal
Write-Host "-- ensuring services up --"
docker compose up -d 2>&1 | Out-Null
Write-Host "-- composer dump-autoload --"
docker compose exec -T api composer dump-autoload 2>&1
Write-Host "-- php artisan optimize:clear --"
docker compose exec -T api php artisan optimize:clear 2>&1
Write-Host "-- php artisan config:clear --"
docker compose exec -T api php artisan config:clear 2>&1
Write-Host "-- php artisan route:list (tail) --"
docker compose exec -T api php artisan route:list 2>&1 | Select-Object -Last 8
Write-Host "-- php artisan test --"
docker compose exec -T api php artisan test 2>&1

Write-Host "`n== C1 DONE. If tests are green and no App\Shared remain: commit and STOP (do not start C2). =="
Write-Host "   git commit -m 'refactor(backend): C1 move App\\Shared -> App\\Platform\\Shared'"
