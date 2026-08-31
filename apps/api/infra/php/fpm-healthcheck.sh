#!/bin/sh
# Container healthcheck for the API image.
#
# WHY THIS REPLACES THE OLD PROBE
#
# The previous check was:
#     SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000
#
# `ping.path` is a php-fpm POOL setting and it is not enabled anywhere in this repository, so FPM has
# no /ping endpoint to answer. cgi-fcgi still exits 0: it successfully opened the socket, sent a
# request and received a response — the response just happened to be "Primary script unknown". The
# probe therefore reported HEALTHY for any FPM master that was accepting connections, including one
# whose worker pool was entirely wedged on a stuck database call.
#
# That is not a cosmetic problem. `web` declares `depends_on: api: condition: service_healthy`, so a
# dead API was reported healthy and the frontend started against it; and a rolling deploy would
# happily retire the old container in favour of a new one that could not serve a request.
#
# This asks the application the same question a load balancer would: it executes public/index.php
# through FPM for the dependency-free liveness route and requires the app's own answer in the body.
# It exercises the socket, the worker, the PHP runtime, the compiled config and the router.
set -e

RESPONSE=$(
    SCRIPT_NAME=/index.php \
    SCRIPT_FILENAME=/var/www/html/public/index.php \
    DOCUMENT_ROOT=/var/www/html/public \
    REQUEST_METHOD=GET \
    REQUEST_URI=/api/v1/health \
    QUERY_STRING= \
    SERVER_PROTOCOL=HTTP/1.1 \
    cgi-fcgi -bind -connect 127.0.0.1:9000 2>/dev/null
) || exit 1

# The status line the liveness route returns. Matching the BODY rather than the exit code is the
# whole point: an exit code only proves something answered.
echo "$RESPONSE" | grep -q '"status":"ok"' || exit 1
