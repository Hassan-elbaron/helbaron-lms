#Requires -Version 5
# ============================================================
# HElbaron - apply everything (Steps 16 to 24) in one run.
# Run from the project root (same folder as docker-compose.yml):
#     powershell -ExecutionPolicy Bypass -File .\apply-all.ps1
# Requirements: Docker Desktop running + Node.js installed.
# (API, PHP, Postgres, Redis all run inside Docker - no PHP needed on Windows.)
# ============================================================

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

function Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }

# 0) Check Docker is running
Step "0/8  Checking Docker"
docker version | Out-Null
if ($LASTEXITCODE -ne 0) { throw "Docker is not running. Open Docker Desktop and retry." }

# 1) Start containers (Postgres + Redis + API)
Step "1/8  Starting containers (postgres + redis + api) - first run builds the image, may take minutes"
docker compose up -d --build

# 2) Wait for the API to be ready
Step "2/8  Waiting for the API to be ready"
$ready = $false
$prev = $ErrorActionPreference; $ErrorActionPreference = 'SilentlyContinue'
for ($i = 0; $i -lt 40; $i++) {
    docker compose exec -T api php artisan --version *> $null
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Start-Sleep -Seconds 3
}
$ErrorActionPreference = $prev
if (-not $ready) { throw "API not ready. Check: docker compose logs api" }

# 3) Backend dependencies (Composer inside the container)
Step "3/8  composer install"
docker compose exec -T api composer install --no-interaction --prefer-dist

# 4) Migrations + demo data (creates admin@helbaron.local)
Step "4/8  migrate --seed (creates a demo admin account)"
docker compose exec -T api php artisan migrate --seed --force

# 5) Filament assets + storage link + clear caches
Step "5/8  filament:assets + storage:link + optimize:clear"
docker compose exec -T api php artisan storage:link
docker compose exec -T api php artisan filament:assets
docker compose exec -T api php artisan optimize:clear

# 6) Backend tests
Step "6/8  Backend tests (php artisan test)"
docker compose exec -T api php artisan test

# 7) Frontend: env + deps + checks
Step "7/8  Setting up apps/web (env + install + typecheck + tests)"
Set-Location -Path (Join-Path $PSScriptRoot 'apps\web')
if (-not (Test-Path '.env.local')) {
    'NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1' | Out-File -Encoding ascii '.env.local'
    Write-Host "Created apps\web\.env.local"
}
npm install
# Clean stale Next.js generated types (avoids false TS errors from old .next/types)
if (Test-Path '.next') { Remove-Item -Recurse -Force '.next' }
npm run typecheck
npm run test

# 8) Start the frontend (stays running - press Ctrl+C to stop)
Step "8/8  Starting the frontend"
Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host " Done. URLs:" -ForegroundColor Green
Write-Host "  Frontend (Next.js): http://localhost:3000" -ForegroundColor Green
Write-Host "  API:                http://localhost:8000/api/v1" -ForegroundColor Green
Write-Host "  Admin panel:        http://localhost:8000/admin" -ForegroundColor Green
Write-Host "  Admin login:        admin@helbaron.local  /  password" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""
npm run dev
