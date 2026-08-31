import { NextRequest, NextResponse } from "next/server";
import { SESSION_COOKIE } from "@/lib/auth/session-cookies";

/**
 * Same-origin BFF proxy to the Laravel REST API. Attaches the Sanctum token from the
 * httpOnly session cookie server-side, so browser JS never handles the credential.
 * Non-GET requests are rejected when the Origin header does not match (CSRF guard on
 * top of SameSite=Lax).
 */

const API_BASE =
  process.env.API_INTERNAL_URL ??
  process.env.NEXT_PUBLIC_API_BASE_URL ??
  "http://localhost:8000/api/v1";

/**
 * How long to wait for the API before giving up.
 *
 * Without an explicit signal, fetch falls back to undici's default of 300 seconds. A single hung
 * upstream therefore pins a Next.js handler for five minutes; a handful of them exhaust the
 * concurrency of the whole node while the site appears merely slow. 25s sits under the usual 30s
 * edge/CDN idle timeout, so the client sees OUR 504 with a JSON body rather than the proxy's HTML.
 */
const UPSTREAM_TIMEOUT_MS = 25_000;

/**
 * A path segment that is only dots — `.`, `..`, and any longer run.
 *
 * encodeURIComponent does NOT encode `.`, so `..` survives it untouched; nginx then decodes and
 * NORMALISES the resulting URI before proxying. `/api/backend/..%2F..%2Fadmin` therefore had a live
 * path to `/admin` — with the user's bearer token attached by this very handler, because the token
 * is added before the target is fetched. Rejecting the segment is the fix; escaping it is not,
 * because the normalisation happens downstream of anything this process can encode.
 */
const TRAVERSAL_SEGMENT = /^\.+$/;

/** Request headers forwarded to the API. */
const FORWARD_REQUEST_HEADERS = ["content-type", "accept-language", "x-correlation-id"] as const;
/** Response headers passed back to the browser. */
const FORWARD_RESPONSE_HEADERS = [
  "content-type",
  "x-correlation-id",
  "retry-after",
  "x-ratelimit-limit",
  "x-ratelimit-remaining",
] as const;

/**
 * Internal Docker requests would otherwise arrive at Laravel with `Host: nginx` and `http` as the
 * scheme. Production host validation correctly rejects that host, and generated pagination links
 * would use the wrong scheme. Derive both values only from the operator-controlled canonical URL.
 */
function canonicalUpstreamHeaders(): Record<string, string> {
  const site = process.env.NEXT_PUBLIC_SITE_URL;
  if (!site) return {};

  try {
    const url = new URL(site);
    const protocol = url.protocol.replace(/:$/, "");
    return {
      Host: url.host,
      "X-Forwarded-Host": url.host,
      "X-Forwarded-Proto": protocol,
      "X-Forwarded-Port": url.port || (protocol === "https" ? "443" : "80"),
    };
  } catch {
    return {};
  }
}

/**
 * CSRF origin check. Accepts an Origin only when it matches a host this deployment serves: the host
 * Next resolved for the request, or the configured canonical site host. `nextUrl.host` alone is not
 * enough — behind a proxy that rewrites Host, or on a dev server reached by a hostname other than
 * the one it bound to, it differs from the browser's Origin and every mutation would 403. Adding
 * only the operator's own configured origin leaves third-party origins refused as before.
 */
function allowedHosts(req: NextRequest): Set<string> {
  const hosts = new Set([req.nextUrl.host]);
  const site = process.env.NEXT_PUBLIC_SITE_URL;
  if (site) {
    try {
      hosts.add(new URL(site).host);
    } catch {
      // A malformed NEXT_PUBLIC_SITE_URL simply contributes no additional host.
    }
  }
  return hosts;
}

function crossOrigin(req: NextRequest): boolean {
  const origin = req.headers.get("origin");
  if (!origin) return false;
  try {
    return !allowedHosts(req).has(new URL(origin).host);
  } catch {
    return true;
  }
}

async function proxy(
  req: NextRequest,
  ctx: { params: Promise<{ path: string[] }> },
): Promise<NextResponse> {
  if (req.method !== "GET" && req.method !== "HEAD" && crossOrigin(req)) {
    return NextResponse.json(
      { error: { code: "CSRF_ORIGIN_MISMATCH", message: "Cross-origin request rejected." } },
      { status: 403 },
    );
  }

  const { path } = await ctx.params;

  // Rejected, not sanitised: see TRAVERSAL_SEGMENT. A request that contains one is never a mistake
  // a legitimate client makes, so there is nothing to recover.
  if (path.some((segment) => TRAVERSAL_SEGMENT.test(segment))) {
    return NextResponse.json(
      { error: { code: "INVALID_PATH", message: "Invalid API path." } },
      { status: 400 },
    );
  }

  // Re-encode each decoded segment to prevent path injection into the upstream URL.
  const target = `${API_BASE}/${path.map(encodeURIComponent).join("/")}${req.nextUrl.search}`;

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...canonicalUpstreamHeaders(),
  };
  for (const name of FORWARD_REQUEST_HEADERS) {
    const value = req.headers.get(name);
    if (value) headers[name] = value;
  }

  const token = req.cookies.get(SESSION_COOKIE)?.value;
  if (token) headers.Authorization = `Bearer ${token}`;

  const rawBody =
    req.method === "GET" || req.method === "HEAD" ? undefined : await req.arrayBuffer();

  let res: Response;
  try {
    res = await fetch(target, {
      method: req.method,
      headers,
      body: rawBody && rawBody.byteLength > 0 ? rawBody : undefined,
      cache: "no-store",
      redirect: "manual",
      signal: AbortSignal.timeout(UPSTREAM_TIMEOUT_MS),
    });
  } catch (error) {
    // A timeout is a DIFFERENT condition from an unreachable API and deserves its own status: 504
    // tells a client (and an uptime check, and a CDN) that the request may still be executing
    // upstream, which matters for a non-idempotent call. 502 would claim it never started.
    const timedOut = error instanceof Error && error.name === "TimeoutError";
    return NextResponse.json(
      timedOut
        ? { error: { code: "UPSTREAM_TIMEOUT", message: "The API did not respond in time." } }
        : { error: { code: "UPSTREAM_UNAVAILABLE", message: "The API is unreachable." } },
      { status: timedOut ? 504 : 502 },
    );
  }

  const resBody = res.status === 204 ? null : await res.arrayBuffer();

  // An error page generated by nginx (502, 504, 413) is HTML. Forwarded verbatim it reaches a client
  // that has already committed to `await response.json()`, so the parse throws and the real status
  // is lost behind a SyntaxError — the caller reports "unexpected token <" instead of "the API is
  // down". Successful responses are passed through untouched; only error statuses that are not JSON
  // are rewritten, and the upstream status is preserved.
  if (!res.ok && !isJson(res)) {
    return NextResponse.json(
      {
        error: {
          code: "UPSTREAM_ERROR",
          message: "The API returned an error.",
          status: res.status,
        },
      },
      { status: res.status },
    );
  }

  const out = new NextResponse(resBody, { status: res.status });
  for (const name of FORWARD_RESPONSE_HEADERS) {
    const value = res.headers.get(name);
    if (value) out.headers.set(name, value);
  }
  return out;
}

function isJson(res: Response): boolean {
  const type = res.headers.get("content-type") ?? "";
  return type.includes("json");
}

export { proxy as GET, proxy as POST, proxy as PUT, proxy as PATCH, proxy as DELETE };
