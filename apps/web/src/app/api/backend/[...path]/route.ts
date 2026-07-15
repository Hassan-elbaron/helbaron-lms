import { NextRequest, NextResponse } from "next/server";

/**
 * Same-origin BFF proxy to the Laravel REST API. Attaches the Sanctum token from the
 * httpOnly session cookie server-side, so browser JS never handles the credential.
 * Non-GET requests are rejected when the Origin header does not match (CSRF guard on
 * top of SameSite=Lax).
 */
const SESSION_COOKIE = "helbaron_session";

const API_BASE =
  process.env.API_INTERNAL_URL ??
  process.env.NEXT_PUBLIC_API_BASE_URL ??
  "http://localhost:8000/api/v1";

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

function crossOrigin(req: NextRequest): boolean {
  const origin = req.headers.get("origin");
  if (!origin) return false;
  try {
    return new URL(origin).host !== req.nextUrl.host;
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
  // Re-encode each decoded segment to prevent path injection into the upstream URL.
  const target = `${API_BASE}/${path.map(encodeURIComponent).join("/")}${req.nextUrl.search}`;

  const headers: Record<string, string> = { Accept: "application/json" };
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
    });
  } catch {
    return NextResponse.json(
      { error: { code: "UPSTREAM_UNAVAILABLE", message: "The API is unreachable." } },
      { status: 502 },
    );
  }

  const resBody = res.status === 204 ? null : await res.arrayBuffer();
  const out = new NextResponse(resBody, { status: res.status });
  for (const name of FORWARD_RESPONSE_HEADERS) {
    const value = res.headers.get(name);
    if (value) out.headers.set(name, value);
  }
  return out;
}

export { proxy as GET, proxy as POST, proxy as PUT, proxy as PATCH, proxy as DELETE };
