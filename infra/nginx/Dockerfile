# syntax=docker/dockerfile:1
# Pinned to an exact patch, not the floating 1.27-alpine tag. nginx.conf uses `server ... resolve;`
# inside an upstream block, which open-source nginx only supports from 1.27.3 — on 1.27.0-1.27.2 the
# config fails to load and the proxy will not start. A floating minor tag is free to resolve to
# either, so the same commit could build a working image today and a dead one tomorrow.
FROM nginx:1.27.4-alpine

# Bake routing into the release image. Dokploy replaces its Git checkout atomically, so bind mounts
# can otherwise remain attached to an older checkout when Compose keeps the proxy container alive.
COPY infra/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Filament's browser assets are NOT copied in here any more. Baking them into the proxy image put
# the admin panel's CSS/JS on a different release cadence from the PHP that references them: a
# `composer update filament/filament` without an nginx rebuild shipped STALE assets — not a 404, so
# nothing failed and nobody noticed. They now arrive on a shared volume that the API container
# populates from its own image at startup (see apps/api/infra/php/docker-entrypoint.sh), which ties
# them to the PHP they belong to.

RUN nginx -t
