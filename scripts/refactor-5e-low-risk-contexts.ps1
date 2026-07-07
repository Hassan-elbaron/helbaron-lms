# STEP 5E - Low-risk contexts: move Learning, Commerce, Analytics  (Domains -> Contexts)
# Run ONLY after C1/C2/C3 (Platform) are confirmed green.
# AdminPanelProvider Filament discovery was already converted to a data map (no branches) - not touched here.
# Run from repo root:  powershell -ExecutionPolicy Bypass -File scripts/refactor-5e-low-risk-contexts.ps1

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
$api  = Join-Path $repo "apps\api"
$utf8 = New-Object System.Text.UTF8Encoding($false)
Set-Location $repo

$contexts = @('Learning','Commerce','Analytics')

Write-Host "== 5E.1  Move folders (Domains -> Contexts) =="
[System.IO.Directory]::CreateDirectory((Join-Path $api "app\Contexts")) | Out-Null
foreach ($c in $contexts) {
  $src = Join-Path $api "app\Domains\$c"
  $dst = Join-Path $api "app\Contexts\$c"
  if (-not (Test-Path -LiteralPath $src)) {
    if (Test-Path -LiteralPath $dst) { Write-Host "  $c already moved; skipping." }
    else { throw "app/Domains/$c not found and app/Contexts/$c missing - aborting." }
  } else {
    Move-Item -LiteralPath $src -Destination $dst -Force
    Write-Host "  moved: app/Domains/$c -> app/Contexts/$c"
  }
}

Write-Host "== 5E.2  Rewrite references across apps/api (*.php) =="
$files = Get-ChildItem -LiteralPath $api -Recurse -File -Include *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\|\\storage\\|\\bootstrap\\cache\\|\\node_modules\\' }
$changed = 0
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $t = [System.IO.File]::ReadAllText($lp) }
  catch { Write-Host ("  SKIP (unreadable): " + $f.FullName) -ForegroundColor Yellow; continue }
  $o = $t
  foreach ($c in $contexts) {
    $t = $t.Replace('App\\Domains\\' + $c, 'App\\Contexts\\' + $c)   # string-literal form
    $t = $t.Replace('App\Domains\' + $c,   'App\Contexts\' + $c)     # code form
  }
  if ($t -ne $o) {
    [System.IO.File]::WriteAllText($lp, $t, $utf8)
    $changed++
    Write-Host ("  updated: " + $f.FullName.Substring($api.Length + 1))
  }
}
Write-Host "files updated: $changed"

Write-Host "== 5E.3  Stage =="
git add -A | Out-Null

Write-Host "== 5E.4  Assert zero remaining App\Domains\{Learning,Commerce,Analytics} =="
$remainList = @()
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $cc = [System.IO.File]::ReadAllText($lp) } catch { continue }
  foreach ($c in $contexts) {
    if ($cc.Contains('App\Domains\' + $c) -or $cc.Contains('App\\Domains\\' + $c)) { $remainList += ($f.FullName + "  [$c]"); break }
  }
}
if ($remainList.Count -gt 0) {
  Write-Host "!! Remaining references:" -ForegroundColor Red
  $remainList | ForEach-Object { Write-Host ("   " + $_) }
} else {
  Write-Host "OK: no remaining App\Domains\{Learning,Commerce,Analytics} references."
}

Write-Host "`n== 5E.5  Verify (Docker) - outputs below (rendered as plain text) =="
$ErrorActionPreference = "Continue"
docker compose up -d 2>&1 | ForEach-Object { "$_" } | Out-Null
Write-Host "-- composer dump-autoload --"
docker compose exec -T api composer dump-autoload 2>&1 | ForEach-Object { "$_" }
Write-Host "-- php artisan optimize:clear --"
docker compose exec -T api php artisan optimize:clear 2>&1 | ForEach-Object { "$_" }
Write-Host "-- php artisan config:clear --"
docker compose exec -T api php artisan config:clear 2>&1 | ForEach-Object { "$_" }
Write-Host "-- php artisan route:list (tail) --"
docker compose exec -T api php artisan route:list 2>&1 | ForEach-Object { "$_" } | Select-Object -Last 12
Write-Host "-- php artisan test --"
docker compose exec -T api php artisan test 2>&1 | ForEach-Object { "$_" }

Write-Host "`n== 5E DONE. If green + no remaining refs: commit and STOP. =="
Write-Host "   git commit -m 'refactor(backend): 5E move Learning/Commerce/Analytics -> Contexts (+ Filament discovery map)'"
Write-Host "   Confirm /admin still shows Enrollment, Product/Coupon/Order/Contract, Dashboard/Report/Export resources."
