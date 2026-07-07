# STEP 5C - Chunk C2: move App\Domains\Identity -> App\Platform\Identity  (ONLY this)
# Run ONLY after C1 (App\Shared) is confirmed green (php artisan test).
# Source: docs/refactor/07A_BACKEND_CONTEXT_DRY_RUN.md (chunk C2)
# Run from repo root:  powershell -ExecutionPolicy Bypass -File scripts/refactor-c2-platform-identity.ps1

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
$api  = Join-Path $repo "apps\api"
$utf8 = New-Object System.Text.UTF8Encoding($false)
Set-Location $repo

Write-Host "== C2.1  Move app/Domains/Identity -> app/Platform/Identity =="
$src = Join-Path $api "app\Domains\Identity"
$dst = Join-Path $api "app\Platform\Identity"
if (-not (Test-Path -LiteralPath $src)) {
  if (Test-Path -LiteralPath $dst) { Write-Host "already moved; continuing to reference rewrite." }
  else { throw "app/Domains/Identity not found and app/Platform/Identity missing - aborting." }
} else {
  [System.IO.Directory]::CreateDirectory((Join-Path $api "app\Platform")) | Out-Null
  Move-Item -LiteralPath $src -Destination $dst -Force
  Write-Host "moved: app/Domains/Identity -> app/Platform/Identity"
}

Write-Host "== C2.2  Rewrite references across apps/api (*.php) =="
$files = Get-ChildItem -LiteralPath $api -Recurse -File -Include *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\|\\storage\\|\\bootstrap\\cache\\|\\node_modules\\' }
$changed = 0
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $t = [System.IO.File]::ReadAllText($lp) }
  catch { Write-Host ("  SKIP (unreadable): " + $f.FullName) -ForegroundColor Yellow; continue }
  $o = $t
  $t = $t.Replace('App\\Domains\\Identity', 'App\\Platform\\Identity')   # string-literal form (configs)
  $t = $t.Replace('App\Domains\Identity',   'App\Platform\Identity')     # namespace / use / FQCN
  if ($t -ne $o) {
    [System.IO.File]::WriteAllText($lp, $t, $utf8)
    $changed++
    Write-Host ("  updated: " + $f.FullName.Substring($api.Length + 1))
  }
}
Write-Host "files updated: $changed"

Write-Host "== C2.3  Filament: redirect Identity resource discovery to Platform =="
# The AdminPanelProvider discovery loop builds paths/namespaces from strings ("Domains/{domain}"),
# which the blanket rewrite does NOT touch. Inject an explicit Platform branch for Identity so
# UserResource is still discovered after the move.
$adm = Join-Path $api "app\Providers\AdminPanelProvider.php"
if (Test-Path -LiteralPath $adm) {
  $a = [System.IO.File]::ReadAllText("\\?\" + $adm)
  $anchor = 'foreach (self::DOMAINS as $domain) {'
  $branch = 'foreach (self::DOMAINS as $domain) {' + "`n" +
            '            if ($domain === ''Identity'') {' + "`n" +
            '                $panel->discoverResources(' + "`n" +
            '                    in: app_path(''Platform/Identity/Filament/Resources''),' + "`n" +
            '                    for: ''App\Platform\Identity\Filament\Resources'',' + "`n" +
            '                );' + "`n" +
            '                continue;' + "`n" +
            '            }'
  if ($a.Contains($anchor) -and -not $a.Contains("=== 'Identity'")) {
    $a = $a.Replace($anchor, $branch)
    [System.IO.File]::WriteAllText("\\?\" + $adm, $a, $utf8)
    Write-Host "patched AdminPanelProvider (Identity discovery -> Platform/Identity)."
  } elseif ($a.Contains("=== 'Identity'")) {
    Write-Host "AdminPanelProvider already patched; skipping."
  } else {
    Write-Host "!! anchor not found in AdminPanelProvider - patch manually (see 07C report R1)." -ForegroundColor Yellow
  }
}

Write-Host "== C2.4  Stage =="
git add -A | Out-Null

Write-Host "== C2.5  Assert zero remaining App\Domains\Identity references =="
$remainList = @()
foreach ($f in $files) {
  $lp = "\\?\" + $f.FullName
  try { $c = [System.IO.File]::ReadAllText($lp) } catch { continue }
  if ($c.Contains('App\Domains\Identity') -or $c.Contains('App\\Domains\\Identity')) { $remainList += $f.FullName }
}
if ($remainList.Count -gt 0) {
  Write-Host "!! Remaining App\Domains\Identity references:" -ForegroundColor Red
  $remainList | ForEach-Object { Write-Host ("   " + $_) }
} else {
  Write-Host "OK: no remaining App\Domains\Identity references."
}

Write-Host "`n== C2.6  Verify (Docker) - outputs below =="
$ErrorActionPreference = "Continue"   # docker/native tools write progress to stderr; not fatal
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

Write-Host "`n== C2 DONE. If green + no remaining refs: commit and STOP (do not start C3). =="
Write-Host "   git commit -m 'refactor(backend): C2 move App\Domains\Identity -> App\Platform\Identity'"
Write-Host "   Manually confirm: /admin login works, Users resource visible, API auth works."
