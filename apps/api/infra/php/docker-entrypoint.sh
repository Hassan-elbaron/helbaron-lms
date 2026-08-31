#!/bin/sh
# Container entrypoint for the API image (php-fpm, horizon, scheduler — all three share it).
#
# WHY THIS EXISTS
# The deploy script used to warm the framework caches with `docker compose run --rm api php artisan
# config:cache`. That runs in a THROWAWAY container: its filesystem is discarded on exit, and the
# api/horizon/scheduler services mount no volume over bootstrap/cache. The cache was therefore
# written somewhere no serving process could ever read it, and every production request has been
# paying to re-parse ~35 config files and rebuild the route table.
#
# Building the caches here fixes that at the only place it can be fixed correctly: inside each
# container that will actually serve, from that container's own environment. It also means the
# caches always match the code in the image — there is no window in which a cache built for one
# release is read by another.
#
# FAIL CLOSED. If the configuration cannot be compiled, the container does not start. A container
# that refuses to boot is visible in `docker ps` and to the orchestrator; a container serving traffic
# on a half-built cache is not.
set -e

# Publishes Filament's browser assets onto the volume nginx serves.
#
# They used to be COPY'd into the NGINX image at ITS build time, which put the admin panel's CSS and
# JavaScript on a different release cadence from the PHP that references them: a `composer update
# filament/filament` without an nginx rebuild shipped stale assets. Not a 404 — a subtly wrong panel,
# which is why nobody noticed. Copying them from THIS image ties them to the PHP they belong to.
#
# Deliberately NOT fatal. A missing stylesheet is a bad admin panel; a container that refuses to boot
# is a dead API. The copy failing produces a visible 404 rather than a silent staleness, which is the
# whole improvement.
publish_filament_assets() {
    target=/srv/filament-assets

    if [ ! -d "$target" ]; then
        echo "entrypoint: no ${target} volume mounted, skipping Filament assets"
        return 0
    fi

    for dir in css js fonts; do
        source="public/${dir}/filament"
        [ -d "$source" ] || continue

        # Removed first: a release that DROPS an asset must not leave the old file being served.
        rm -rf "${target:?}/${dir}/filament"
        mkdir -p "${target}/${dir}"

        if cp -R "$source" "${target}/${dir}/"; then
            echo "entrypoint: published ${dir}/filament"
        else
            echo "entrypoint: ERROR could not publish ${dir}/filament — the admin panel will be unstyled" >&2
        fi
    done
}

echo "entrypoint: warming framework caches"

php artisan config:cache
php artisan route:cache
php artisan event:cache

publish_filament_assets

echo "entrypoint: ready, starting: $*"

exec "$@"
