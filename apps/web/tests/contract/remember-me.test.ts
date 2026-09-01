import { NextRequest } from "next/server";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { MARKER_COOKIE, SESSION_COOKIE } from "@/lib/auth/session-cookies";

/**
 * E1 — "remember me" is a security promise the UI made and the system did not keep.
 *
 * The checkbox was registered on the form, validated by its schema, and then dropped: the submit
 * handler never passed it, the auth context had no such parameter, and the session route applied a
 * hardcoded fourteen-day Max-Age unconditionally.
 *
 * The dangerous direction is the UNTICKED one. Somebody deliberately declining to be remembered on a
 * shared or public machine still walked away leaving a fortnight-long credential in that browser.
 * A cookie with no Max-Age is what they asked for; the browser discards it when it closes.
 */
const ORIGINAL_ENV = { ...process.env };

type Cookie = { name: string; value: string; maxAge?: number; secure?: boolean };

async function login(body: Record<string, unknown>, token = "tok_abc"): Promise<Cookie[]> {
  vi.resetModules();
  process.env = { ...ORIGINAL_ENV, NEXT_PUBLIC_SITE_URL: "https://academy.test" } as NodeJS.ProcessEnv;

  vi.stubGlobal(
    "fetch",
    vi.fn(async () =>
      new Response(JSON.stringify({ data: { token, user: { id: 1 } } }), {
        status: 200,
        headers: { "content-type": "application/json" },
      }),
    ),
  );

  const { POST } = await import("@/app/api/session/route");
  const res = await POST(
    new NextRequest("https://academy.test/api/session", {
      method: "POST",
      body: JSON.stringify(body),
      headers: { "content-type": "application/json" },
    }) as never,
  );

  return res.cookies.getAll().map((c) => ({
    name: c.name,
    value: c.value,
    maxAge: c.maxAge,
    secure: c.secure,
  }));
}

function cookie(cookies: Cookie[], name: string): Cookie {
  const found = cookies.find((c) => c.name === name);
  expect(found, `${name} was not set`).toBeDefined();

  return found as Cookie;
}

/** What the BFF actually forwarded to the API. */
async function forwardedBody(body: Record<string, unknown>): Promise<Record<string, unknown>> {
  vi.resetModules();
  process.env = { ...ORIGINAL_ENV, NEXT_PUBLIC_SITE_URL: "https://academy.test" } as NodeJS.ProcessEnv;

  const spy = vi.fn(async () =>
    new Response(JSON.stringify({ data: { token: "t", user: {} } }), {
      status: 200,
      headers: { "content-type": "application/json" },
    }),
  );
  vi.stubGlobal("fetch", spy);

  const { POST } = await import("@/app/api/session/route");
  await POST(
    new NextRequest("https://academy.test/api/session", {
      method: "POST",
      body: JSON.stringify(body),
      headers: { "content-type": "application/json" },
    }) as never,
  );

  // The stub is typed with no parameters, so its recorded call args need widening before the body
  // can be read back.
  const call = spy.mock.calls[0] as unknown as [string, RequestInit] | undefined;

  expect(call, "the BFF did not call the API").toBeDefined();

  return JSON.parse(String(call?.[1].body)) as Record<string, unknown>;
}

beforeEach(() => {
  process.env = { ...ORIGINAL_ENV } as NodeJS.ProcessEnv;
});

afterEach(() => {
  process.env = { ...ORIGINAL_ENV } as NodeJS.ProcessEnv;
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

describe("unticked — the case that was unsafe", () => {
  it("issues a session-only cookie with no Max-Age", async () => {
    const cookies = await login({ email: "a@b.test", password: "x", remember: false });

    // No Max-Age at all. NOT `maxAge: 0` — that would expire the cookie immediately and sign the
    // user straight back out.
    expect(cookie(cookies, SESSION_COOKIE).maxAge).toBeUndefined();
  });

  it("treats an absent flag as not remembered", async () => {
    const cookies = await login({ email: "a@b.test", password: "x" });

    // Defaulting the other way would restore the defect for any client that omits the field.
    expect(cookie(cookies, SESSION_COOKIE).maxAge).toBeUndefined();
  });

  it.each([["false"], [0], [null], ["no"]])(
    "does not accept %s as consent to be remembered",
    async (value) => {
      const cookies = await login({ email: "a@b.test", password: "x", remember: value });

      // Only a real boolean true counts. A truthy string from a sloppy client must not silently
      // grant a month-long credential.
      expect(cookie(cookies, SESSION_COOKIE).maxAge).toBeUndefined();
    },
  );

  it("gives the marker cookie the same lifetime as the credential", async () => {
    const cookies = await login({ email: "a@b.test", password: "x", remember: false });

    // A persistent marker beside a session-scoped token would leave the client believing it is
    // signed in after the browser dropped the credential: the authenticated shell renders, then
    // 401s on its first request.
    expect(cookie(cookies, MARKER_COOKIE).maxAge).toBeUndefined();
  });
});

describe("ticked", () => {
  it("issues a persistent cookie", async () => {
    const cookies = await login({ email: "a@b.test", password: "x", remember: true });

    expect(cookie(cookies, SESSION_COOKIE).maxAge).toBeGreaterThan(60 * 60 * 24 * 7);
    expect(cookie(cookies, MARKER_COOKIE).maxAge).toBe(cookie(cookies, SESSION_COOKIE).maxAge);
  });

  it("still never exposes the token to browser JS", async () => {
    const cookies = await login({ email: "a@b.test", password: "x", remember: true }, "tok_secret");

    expect(cookie(cookies, SESSION_COOKIE).value).toBe("tok_secret");
    // The marker exists so the client can know a session exists WITHOUT holding the credential.
    expect(cookie(cookies, MARKER_COOKIE).value).toBe("1");
  });
});

describe("the flag reaches the API", () => {
  it("forwards the choice so the token itself is bounded", async () => {
    // The server-side half. A cookie the browser discards is not the same as a credential the
    // server has stopped accepting: LoginAction issues a short-lived token when this is false.
    expect(await forwardedBody({ email: "a@b.test", password: "x", remember: true })).toMatchObject({
      remember: true,
    });
    expect(await forwardedBody({ email: "a@b.test", password: "x", remember: false })).toMatchObject({
      remember: false,
    });
  });
});

describe("logout", () => {
  it("clears both cookies whichever way they were set", async () => {
    vi.resetModules();
    process.env = {
      ...ORIGINAL_ENV,
      NEXT_PUBLIC_SITE_URL: "https://academy.test",
    } as NodeJS.ProcessEnv;
    vi.stubGlobal("fetch", vi.fn(async () => new Response(null, { status: 204 })));

    const { DELETE } = await import("@/app/api/session/route");
    const res = await DELETE(
      new NextRequest("https://academy.test/api/session", { method: "DELETE" }) as never,
    );

    const cookies = res.cookies.getAll();

    // maxAge 0 here is correct and is the opposite case from login: this actively expires them.
    for (const name of [SESSION_COOKIE, MARKER_COOKIE]) {
      const found = cookies.find((c) => c.name === name);
      expect(found?.maxAge).toBe(0);
      expect(found?.value).toBe("");
    }
  });
});

describe("cookie names", () => {
  it("carries no vendor name", () => {
    // These are visible in the devtools of every customer academy's browser.
    expect(SESSION_COOKIE).not.toContain("helbaron");
    expect(MARKER_COOKIE).not.toContain("helbaron");
  });
});
