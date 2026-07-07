# Fix: restore + relocate the 4 dynamic pages whose paths contain [public_id].
# The main migration used Test-Path which treats [ ] as wildcards, so these were skipped
# (then removed with their old groups). This restores them from git and moves them literally.
# Run from repo root:  powershell -ExecutionPolicy Bypass -File scripts/fix-bracket-pages.ps1

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
$app  = Join-Path $repo "apps\web\src\app"
Set-Location $repo

# source (original, in HEAD) -> destination (new group)
$pairs = @(
  @("apps/web/src/app/(public)/courses/[public_id]/page.tsx",        "apps/web/src/app/(marketing)/(site)/courses/[public_id]/page.tsx"),
  @("apps/web/src/app/(public)/courses/[public_id]/learn/page.tsx",  "apps/web/src/app/(learning)/(player)/learn/[public_id]/page.tsx"),
  @("apps/web/src/app/(public)/lessons/[public_id]/page.tsx",        "apps/web/src/app/(learning)/(player)/lessons/[public_id]/page.tsx"),
  @("apps/web/src/app/(org)/org/organizations/[public_id]/page.tsx", "apps/web/src/app/(organization)/org/organizations/[public_id]/page.tsx")
)

foreach ($p in $pairs) {
  $src = Join-Path $repo ($p[0] -replace '/', '\')
  $dst = Join-Path $repo ($p[1] -replace '/', '\')

  # 1) Restore the original file from HEAD (":(literal)" stops git from globbing the brackets)
  git checkout HEAD -- (":(literal)" + $p[0])

  if (-not (Test-Path -LiteralPath $src)) { Write-Host "!! could not restore $($p[0])" -ForegroundColor Red; continue }

  # 2) Ensure destination directory exists (.NET, bracket-safe)
  [System.IO.Directory]::CreateDirectory((Split-Path -Parent $dst)) | Out-Null

  # 3) Move literally
  Move-Item -LiteralPath $src -Destination $dst -Force
  Write-Host "restored + moved: $($p[0])  ->  $($p[1])"
}

# 4) Remove now-empty old group folders left behind by the restore
foreach ($g in @("(public)","(org)")) {
  $gp = Join-Path $app $g
  if (Test-Path -LiteralPath $gp) { Remove-Item -LiteralPath $gp -Recurse -Force; Write-Host "cleaned leftover: $g" }
}

git add -A | Out-Null
Write-Host "`n== Fixed. Now re-run verification: =="
Write-Host "  cd apps\web; npm run typecheck; npm test; npm run build"
