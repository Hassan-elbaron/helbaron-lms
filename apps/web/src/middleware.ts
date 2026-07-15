import { NextRequest, NextResponse } from "next/server";

/**
 * Server-side route protection: authenticated areas redirect to /login (preserving the
 * intended destination) when no session cookie is present. This complements — not replaces —
 * the client-side guards and the API's own authorization: the middleware only checks cookie
 * presence; token validity is enforced by the API on every proxied request.
 */
const SESSION_COOKIE = "helbaron_session";

const PROTECTED_PREFIXES = [
  "/account",
  "/dashboard",
  "/my-learning",
  "/continue-learning",
  "/certificates",
  "/learn",
  "/lessons",
  "/teach",
  "/crm",
  "/org",
  "/orders",
  "/checkout",
  "/contracts",
  "/analytics",
  "/reports",
  "/dashboards",
];

export function middleware(req: NextRequest): NextResponse {
  const { pathname } = req.nextUrl;

  const isProtected = PROTECTED_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`),
  );
  if (!isProtected) return NextResponse.next();

  if (req.cookies.get(SESSION_COOKIE)?.value) return NextResponse.next();

  const login = req.nextUrl.clone();
  login.pathname = "/login";
  login.search = "";
  login.searchParams.set("redirect", pathname + req.nextUrl.search);
  return NextResponse.redirect(login);
}

export const config = {
  // Skip static assets, Next internals, and the BFF API routes themselves.
  matcher: ["/((?!_next|api|favicon.ico|.*\\..*).*)"],
};
