import { afterEach, describe, expect, it, vi } from "vitest";

/**
 * D3 — the `Secure` attribute on the session cookie.
 *
 * It was `process.env.NODE_ENV === "production"`. NODE_ENV describes how the BUNDLE WAS COMPILED; it
 * says nothing about how the site is reached. A staging or preview deployment built with any other
 * value emitted the credential cookie — an httpOnly cookie holding a real Sanctum token — WITHOUT
 * Secure, over HTTPS, to real users. Any plaintext request to the same host then leaks it.
 *
 * It is now tied to the scheme of the operator's canonical URL, which is the thing that actually
 * determines whether the cookie will travel over TLS.
 */
const ORIGINAL_ENV = { ...process.env };

afterEach(() => {
  process.env = { ...ORIGINAL_ENV };
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

/** Reads the Set-Cookie the DELETE (sign-out) handler writes — the simplest path that sets both. */
async function signOutCookies(env: Record<string, string | undefined>): Promise<string> {
  vi.resetModules();
  process.env = { ...ORIGINAL_ENV, ...env } as NodeJS.ProcessEnv;

  const { DELETE } = await import("@/app/api/session/route");
  const { NextRequest } = await import("next/server");

  const res = await DELETE(
    new NextRequest("https://academy.test/api/session", { method: "DELETE" }) as never,
  );

  return res.headers.getSetCookie().join("\n");
}

describe("session cookie Secure attribute", () => {
  it("is set for an https deployment even when NODE_ENV is not production", async () => {
    // The exact staging shape that leaked: HTTPS in front, a non-production build behind.
    const cookies = await signOutCookies({
      NODE_ENV: "development",
      NEXT_PUBLIC_SITE_URL: "https://staging.academy.test",
    });

    expect(cookies.toLowerCase()).toContain("secure");
  });

  it("is not set for a plain http dev server", async () => {
    const cookies = await signOutCookies({
      NODE_ENV: "development",
      NEXT_PUBLIC_SITE_URL: "http://localhost:3000",
    });

    // Marking it Secure over http would mean the browser silently drops it and nobody can sign in
    // locally — the failure this guard must not cause while fixing the other one.
    expect(cookies.toLowerCase()).not.toContain("secure");
  });

  it("falls back to NODE_ENV when the canonical URL is absent", async () => {
    const cookies = await signOutCookies({
      NODE_ENV: "production",
      NEXT_PUBLIC_SITE_URL: undefined,
    });

    expect(cookies.toLowerCase()).toContain("secure");
  });

  it("falls back to NODE_ENV when the canonical URL is malformed", async () => {
    const cookies = await signOutCookies({
      NODE_ENV: "production",
      NEXT_PUBLIC_SITE_URL: "not a url",
    });

    // A typo in one variable must not silently strip Secure from a production cookie.
    expect(cookies.toLowerCase()).toContain("secure");
  });
});
