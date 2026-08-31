/**
 * Session cookie names and lifetimes — the single place both are defined.
 *
 * TWO CHANGES LIVE HERE, deliberately together.
 *
 * 1. THE NAMES ARE NEUTRAL. They were `helbaron_session` / `helbaron_authed`, hardcoded in six
 *    files. This product ships as one deployed instance per customer academy, so the vendor's name
 *    was visible in the devtools of every customer's browser. Renaming invalidates every existing
 *    session, which is why it was deferred out of the white-label pass and folded in HERE: the
 *    remember-me change is the other thing that forces users to sign in again, so the two together
 *    cost one forced logout instead of two.
 *
 * 2. "REMEMBER ME" IS HONOURED. The login form has always offered the checkbox and the system has
 *    never read it: `SESSION_MAX_AGE` was applied unconditionally. The dangerous direction is the
 *    unticked one — somebody deliberately declining to be remembered on a shared machine still left
 *    a fourteen-day credential in that browser. Unticked now produces a cookie with NO Max-Age,
 *    which the browser discards when it closes.
 *
 * The token's own lifetime is bounded on the API side by `config('identity.session.*')`; these are
 * the browser half. Both halves have to exist: a cookie the browser throws away is not the same as
 * a credential the server has stopped accepting.
 */

/** httpOnly, holds the Sanctum token. Never readable from browser JS. */
export const SESSION_COOKIE = "lms_session";

/** Not httpOnly: lets the client know a session exists without exposing the credential. */
export const MARKER_COOKIE = "lms_authed";

/**
 * How long a "remember me" session lasts, in seconds.
 *
 * Kept in step with `identity.session.remembered_days` on the API by intent, not by mechanism — the
 * cookie may safely outlive the token (the user is asked to sign in again) but must never be the
 * only thing keeping a session alive past it.
 */
export const REMEMBERED_MAX_AGE = 60 * 60 * 24 * 30;

/**
 * Whether the session cookie carries `Secure`.
 *
 * Tied to the SCHEME THIS DEPLOYMENT SERVES, not to NODE_ENV. A staging or preview build compiled
 * with any other NODE_ENV emitted the credential cookie without `Secure` — over HTTPS, to real
 * users. NODE_ENV describes how the bundle was built; it says nothing about how the site is reached.
 */
export function secureCookies(): boolean {
  const site = process.env.NEXT_PUBLIC_SITE_URL;

  if (site) {
    try {
      return new URL(site).protocol === "https:";
    } catch {
      // A malformed URL is not evidence either way; fall through.
    }
  }

  return process.env.NODE_ENV === "production";
}

type CookieOptions = {
  httpOnly: boolean;
  secure: boolean;
  sameSite: "lax";
  path: string;
  maxAge?: number;
};

/**
 * Cookie attributes for a freshly-issued session.
 *
 * `maxAge` is OMITTED entirely when the user did not ask to be remembered. That is the whole point:
 * a cookie with no Max-Age is a session cookie, which the browser drops when it closes. Passing
 * `maxAge: 0` instead would expire it immediately and log the user straight back out, and passing
 * `undefined` explicitly is not the same as omitting the key in every cookie serialiser — hence the
 * conditional spread rather than a ternary on the value.
 */
export function sessionCookieOptions(remember: boolean, httpOnly: boolean): CookieOptions {
  return {
    httpOnly,
    secure: secureCookies(),
    sameSite: "lax",
    path: "/",
    ...(remember ? { maxAge: REMEMBERED_MAX_AGE } : {}),
  };
}

/** Attributes that clear a cookie, whichever way it was set. */
export function clearedCookieOptions(httpOnly: boolean): CookieOptions {
  return {
    httpOnly,
    secure: secureCookies(),
    sameSite: "lax",
    path: "/",
    maxAge: 0,
  };
}
