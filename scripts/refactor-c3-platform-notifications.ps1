# STEP 5D - Chunk C3: move App\Domains\Notifications -> App\Platform\Notifications  (ONLY this)
# Run ONLY after C2 (Identity) is confirmed green.
# Source: docs/refactor/07A_BACKEND_CONTEXT_DRY_RUN.md (chunk C3)
# Run from repo root:  powershell -ExecutionPolicy Bypass -File scripts/refactor-c3-platform-notifications.ps1

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
$api  = Join-Path $repo "apps\api"
$utf8 = New-Object System.Text.UTF8Encoding($false)
Set-Location $repo

Write-Host "== C3.1  Move app/Domains/Notifications -> app/Platform/Notifications =="
$src = Join-Path $api "app\Domains\Notifications"
$dst = Join-Path $api "app\Platform\Notifications"
if (-not (Test-Path -LiteralPath $src)) {
  if (Test-Path -LiteralPath $dst) { Write-Host "already moved; continuing to reference rewrite." }
  else { throw "app/Domains/Notifications not found and app/Platform/Notifications missing - aborting." }
} else {
  [System.IO.Directory]::CreateDirectory((Join-Path $api "app\Platform")) | Out-Null
  Move-Item -LiteralPath $src -Destination $dst -Force
  Write-Host "moved: app/Domains/Notifications -> app/Platform/Notifications"
}

Write-Host "== C3.2  Rewrite references across apps/api (*.php) =="
$files = Get-ChildItem -LiteralPath $api -Recurse -File -Include *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\|\\storage\\|\\bootstrap\\cache\\|\\node_modules\\' }
$changed = 0
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $t = [System.IO.File]::ReadAllText($lp) }
  catch { Write-Host ("  SKIP (unreadable): " + $f.FullName) -ForegroundColor Yellow; continue }
  $o = $t
  $t = $t.Replace('App\\Domains\\Notifications', 'App\\Platform\\Notifications')
  $t = $t.Replace('App\Domains\Notifications',   'App\Platform\Notifications')
  if ($t -ne $o) {
    [System.IO.File]::WriteAllText($lp, $t, $utf8)
    $changed++
    Write-Host ("  updated: " + $f.FullName.Substring($api.Length + 1))
  }
}
Write-Host "files updated: $changed"

Write-Host "== C3.3  Filament: redirect Notifications resource discovery to Platform =="
$adm = Join-Path $api "app\Providers\AdminPanelProvider.php"
if (Test-Path -LiteralPath $adm) {
  $a = [System.IO.File]::ReadAllText("\\?\" + $adm)
  $anchor = 'foreach (self::DOMAINS as $domain) {'
  $branch = 'foreach (self::DOMAINS as $domain) {' + "`n" +
            '            if ($domain === ''Notifications'') {' + "`n" +
            '                $panel->discoverResources(' + "`n" +
            '                    in: app_path(''Platform/Notifications/Filament/Resources''),' + "`n" +
            '                    for: ''App\Platform\Notifications\Filament\Resources'',' + "`n" +
            '                );' + "`n" +
            '                continue;' + "`n" +
            '            }'
  if ($a.Contains($anchor) -and -not $a.Contains("=== 'Notifications'")) {
    $a = $a.Replace($anchor, $branch)
    [System.IO.File]::WriteAllText("\\?\" + $adm, $a, $utf8)
    Write-Host "patched AdminPanelProvider (Notifications discovery -> Platform/Notifications)."
  } elseif ($a.Contains("=== 'Notifications'")) {
    Write-Host "AdminPanelProvider already patched for Notifications; skipping."
  } else {
    Write-Host "!! anchor not found in AdminPanelProvider - patch manually (see 07D report R1)." -ForegroundColor Yellow
  }
}

Write-Host "== C3.4  Stage =="
git add -A | Out-Null

Write-Host "== C3.5  Assert zero remaining App\Domains\Notifications references =="
$remainList = @()
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $c = [System.IO.File]::ReadAllText($lp) } catch { continue }
  if ($c.Contains('App\Domains\Notifications') -or $c.Contains('App\\Domains\\Notifications')) { $remainList += $f.FullName }
}
if ($remainList.Count -gt 0) {
  Write-Host "!! Remaining App\Domains\Notifications references:" -ForegroundColor Red
  $remainList | ForEach-Object { Write-Host ("   " + $_) }
} else {
  Write-Host "OK: no remaining App\Domains\Notifications references."
}

Write-Host "`n== C3.6  Verify (Docker) - outputs below =="
$ErrorActionPreference = "Continue"
docker compose up -d 2>&1 | Out-Null
Write-Host "-- composer dump-autoload --"
docker compose exec -T api composer dump-autoload 2>&1
Write-Host "-- php artisan optimize:clear --"
docker compose exec -T api php artisan optimize:clear 2>&1
Write-Host "-- php artisan config:clear --"
docker compose exec -T api php artisan config:clear 2>&1
Write-Host "-- php artisan route:list (tail) --"
docker compose exec -T api php artisan route:list 2>&1 | Select-Object -Last 10
Write-Host "-- php artisan test --"
docker compose exec -T api php artisan test 2>&1

Write-Host "`n== C3 DONE. If green + no remaining refs: commit and STOP (do not start Learning). =="
Write-Host "   git commit -m 'refactor(backend): C3 move App\Domains\Notifications -> App\Platform\Notifications'"
