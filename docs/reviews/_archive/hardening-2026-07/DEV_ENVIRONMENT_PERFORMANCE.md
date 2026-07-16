# Dev Environment Performance — Diagnosis & Fixes

**Date:** 2026-07-16 · **Scope:** local development speed on Windows + Docker. Evidence from the repo's actual files.

## The build itself is NOT slow (measured)
| Command | Where it runs | Measured time |
|---|---|---|
| `npm run build` (Next.js) | **host** | ~10–30 s |
| `npm test` (Vitest, 114 tests) | host | ~24 s |
| Full CI (7 jobs, 2 Docker images + Trivy) | GitHub runners | 6 m 14 s |

These are healthy. If dev feels "very very slow," it is the **Laravel API dev container**, not the build.

## Root cause (from `docker-compose.yml` + host path)
1. **The repo is on `D:\` (Windows NTFS)**, not inside the WSL2 Linux filesystem — the slowest possible source for a Docker bind mount.
2. **The API service bind-mounts all of `apps/api` including `vendor/`**: `docker-compose.yml:47` `./apps/api:/var/www/html`. Laravel's Composer autoloader reads **thousands of tiny `vendor/` files per request**; over a `D:\`→Linux bind mount every read crosses the filesystem translation layer. This is the #1 dev-runtime slowdown (page loads, `php artisan`, in-container tests).
   - `.dockerignore` excludes `vendor` from the build *context* (correct) but the runtime **bind mount re-overlays the slow host `vendor/`** regardless.
3. **Next.js is already outside Docker for dev** (not in compose) — already optimal; no change needed there.
4. Postgres + Redis already use **named volumes** — fast; no issue.

## Fix #1 — biggest win (host action): move the repo into WSL2
Bind mounts from *inside* the WSL2 ext4 filesystem are near-native speed. Expected **50–80%** faster.
```bash
# In a WSL2 (Ubuntu) shell:
mkdir -p ~/projects && cd ~/projects
git clone <repo-url> corelms      # or: cp -r /mnt/d/Claude_Files/.../corelms .
cd corelms && docker compose up
```
Edit via `\\wsl$\Ubuntu\home\<user>\projects\corelms` or VS Code "WSL" remote. Requires Docker Desktop → Settings → Resources → WSL Integration enabled for your distro.

## Fix #2 — applied in this repo (keeps `vendor/` off NTFS even on `D:\`)
`docker-compose.yml` now mounts the hot generated paths on fast **named volumes** instead of the bind mount:
```yaml
- api-vendor:/var/www/html/vendor
- api-bootstrap-cache:/var/www/html/bootstrap/cache
- api-storage-framework:/var/www/html/storage/framework
```
**Required one-time migration after pulling this change** (the named volumes start empty):
```powershell
docker compose down
docker compose build api
docker compose up -d
docker compose exec api composer install      # populates the api-vendor volume
docker compose exec api php artisan migrate    # if needed
```
Notes:
- Your **host `vendor/` is untouched** — keep running `composer install` on the host too if your IDE needs it for autocomplete. The container now uses its own fast copy.
- Fully reversible: `git checkout docker-compose.yml` + `docker compose down -v` restores the old behavior.

## Fix #3 — cheap host-side wins (your list, confirmed)
- **BuildKit** (usually default in Docker Desktop): ensure `DOCKER_BUILDKIT=1` and `COMPOSE_DOCKER_CLI_BUILD=1`.
- **Don't `--build` every time**: `docker compose up` (rebuild only when a Dockerfile or `composer.json`/`package.json` changes).
- **Give Docker enough resources** via `%UserProfile%\.wslconfig`: e.g. `[wsl2]` `processors=8`, `memory=12GB` (only if the machine has it) — 2 CPU / 4 GB is too little.
- **`Dockerfile.dev` micro-opt (optional):** line 5 `ADD https://…/latest/…install-php-extensions` busts the layer cache on every build because `latest` can change. Pinning a specific release tag makes that layer cacheable. (Left as a recommendation — needs a concrete version.)

## How to quantify (so we measure, not guess)
Time these before Fix #2 (current) and after:
```powershell
Measure-Command { docker compose up -d }                          # cold start
Measure-Command { docker compose exec api php artisan test }      # in-container test suite
# hit http://localhost:8000/api/v1/health a few times and note latency
```
Post the before/after numbers and I'll confirm which fix moved the needle.

## Summary
| Fix | Impact | Where |
|---|---|---|
| Move repo into WSL2 | **Highest (50–80%)** | host |
| `vendor`/cache on named volumes | High (autoload I/O) | **applied in repo** |
| BuildKit + no needless `--build` + resources | Medium | host config |
| Pin php-extensions installer | Low (build cache) | recommendation |
