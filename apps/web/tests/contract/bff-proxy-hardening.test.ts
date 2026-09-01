import { NextRequest } from "next/server";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

/**
 * D3 — the BFF proxy. It is the one process that holds the user's Sanctum token, so its failure
 * modes are not ordinary bugs: a path it forwards is a path forwarded WITH the credential attached.
 */

const ORIGINAL_ENV = { ...process.env };

async function loadProxy() {
  vi.resetModules();
  return import("@/app/api/backend/[...path]/route");
}

/**
 * A NextRequest, not a bare Request: the handler reads nextUrl for the query string and host.
 *
 * `RequestInit` here is Next's own, whose `signal` is nullable; the DOM lib's is not. Taking the
 * constructor's parameter type directly keeps the two from disagreeing.
 */
type NextRequestInit = ConstructorParameters<typeof NextRequest>[1];

function request(path: string, init: NextRequestInit = {}): NextRequest {
  return new NextRequest(`https://academy.test/api/backend/${path}`, { method: "GET", ...init });
}

function params(segments: string[]) {
  return { params: Promise.resolve({ path: segments }) };
}

beforeEach(() => {
  process.env.API_INTERNAL_URL = "http://nginx/api/v1";
  process.env.NEXT_PUBLIC_SITE_URL = "https://academy.test";
});

afterEach(() => {
  process.env = { ...ORIGINAL_ENV };
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

/*
 * The traversal. encodeURIComponent does NOT encode `.`, so `..` passes through it unchanged, and
 * nginx decodes and NORMALISES the URI before proxying — `/api/backend/..%2F..%2Fadmin` could
 * normalise to `/admin`, reached with the user's bearer token because this handler attaches it
 * before fetching. Escaping cannot fix that; the normalisation happens downstream. So it is refused.
 */
describe("path traversal", () => {
  it.each([[".."], ["."], ["..."], ["....."]])("refuses the segment %s", async (segment) => {
    const { GET } = await loadProxy();
    const fetchSpy = vi.fn();
    vi.stubGlobal("fetch", fetchSpy);

    const res = await GET(request("x") as never, params(["courses", segment, "admin"]) as never);

    expect(res.status).toBe(400);
    // The upstream must never be contacted at all: the credential is attached before the fetch.
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it("allows a dot inside an otherwise normal segment", async () => {
    const { GET } = await loadProxy();
    const fetchSpy = vi.fn(async () =>
      new Response(JSON.stringify({ ok: true }), {
        status: 200,
        headers: { "content-type": "application/json" },
      }),
    );
    vi.stubGlobal("fetch", fetchSpy);

    // Filenames and version strings legitimately contain dots; only an ALL-dots segment is a
    // traversal, and over-blocking would break real routes.
    const res = await GET(request("x") as never, params(["files", "report.v2.pdf"]) as never);

    expect(res.status).toBe(200);
    expect(fetchSpy).toHaveBeenCalledOnce();
  });
});

/*
 * Without an explicit signal, fetch waits out undici's 300-second default. One hung upstream pins a
 * Next handler for five minutes; a few of them exhaust the node's concurrency while the site merely
 * looks slow.
 */
describe("upstream timeout", () => {
  it("gives up rather than waiting out the undici default", async () => {
    const { GET } = await loadProxy();
    vi.stubGlobal(
      "fetch",
      vi.fn(async (_url: string, init: RequestInit) => {
        expect(init.signal).toBeInstanceOf(AbortSignal);
        const error = new Error("The operation was aborted due to timeout");
        error.name = "TimeoutError";
        throw error;
      }),
    );

    const res = await GET(request("x") as never, params(["courses"]) as never);

    // 504, not 502: the request may still be running upstream, which matters for a non-idempotent
    // call. 502 would claim it never started.
    expect(res.status).toBe(504);
    expect((await res.json()).error.code).toBe("UPSTREAM_TIMEOUT");
  });

  it("still reports an unreachable API as 502", async () => {
    const { GET } = await loadProxy();
    vi.stubGlobal("fetch", vi.fn(async () => {
      throw new TypeError("fetch failed");
    }));

    const res = await GET(request("x") as never, params(["courses"]) as never);

    expect(res.status).toBe(502);
    expect((await res.json()).error.code).toBe("UPSTREAM_UNAVAILABLE");
  });
});

/*
 * nginx generates HTML for 502/504/413. Forwarded verbatim, it reaches a client that has already
 * committed to `await response.json()` — the parse throws and the real status is lost behind a
 * SyntaxError, so the caller reports "unexpected token <" rather than "the API is down".
 */
describe("non-JSON upstream errors", () => {
  it("normalises an HTML error page into the error envelope", async () => {
    const { GET } = await loadProxy();
    vi.stubGlobal("fetch", vi.fn(async () =>
      new Response("<html><body><h1>502 Bad Gateway</h1></body></html>", {
        status: 502,
        headers: { "content-type": "text/html" },
      }),
    ));

    const res = await GET(request("x") as never, params(["courses"]) as never);
    const body = await res.json();

    expect(res.status).toBe(502);
    expect(body.error.code).toBe("UPSTREAM_ERROR");
    expect(body.error.status).toBe(502);
  });

  it("passes a JSON error through untouched", async () => {
    const { GET } = await loadProxy();
    vi.stubGlobal("fetch", vi.fn(async () =>
      new Response(JSON.stringify({ error: { code: "VALIDATION_FAILED", message: "Nope." } }), {
        status: 422,
        headers: { "content-type": "application/json" },
      }),
    ));

    const res = await GET(request("x") as never, params(["courses"]) as never);

    // The API's own error envelope carries field-level detail the UI renders; rewriting it would
    // turn every validation failure into a generic message.
    expect((await res.json()).error.code).toBe("VALIDATION_FAILED");
  });

  it("passes a successful non-JSON response through untouched", async () => {
    const { GET } = await loadProxy();
    vi.stubGlobal("fetch", vi.fn(async () =>
      new Response("id,name\n1,Alice", {
        status: 200,
        headers: { "content-type": "text/csv" },
      }),
    ));

    const res = await GET(request("x") as never, params(["exports", "learners.csv"]) as never);

    // Only ERROR statuses are rewritten. A CSV export is a legitimate non-JSON 200.
    expect(res.status).toBe(200);
    expect(await res.text()).toContain("Alice");
  });
});
