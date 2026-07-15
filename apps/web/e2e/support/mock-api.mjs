// Deterministic, dependency-free mock of the Laravel REST API for E2E.
//
// Why this exists: the marketing shell (root layout) awaits getBranding() + getFeatureFlags()
// server-side on every request, the homepage awaits getHomepage(), and /sitemap.xml (prerendered
// at build) awaits getSeoSitemap() + listPublishedPages(). Those helpers fall back to built-in
// defaults on failure, but only AFTER apiFetch's 20s timeout — so with no API reachable, every SSR
// render stalls 13-40s and Playwright navigations abort, and the sitemap prerender fails. This
// server answers those public, unauthenticated endpoints INSTANTLY with contract-correct payloads
// (see mock-routes.mjs) so SSR resolves immediately and the production build succeeds.
//
// It is E2E-only tooling: never imported by, or shipped in, the app. Authenticated end-to-end
// journeys are NOT served here (they stay skipped unless E2E creds + a real API are supplied).

import { createServer } from "node:http";
import { resolveMock } from "./mock-routes.mjs";

const PORT = Number(process.env.MOCK_API_PORT ?? 8787);
const HOST = process.env.MOCK_API_HOST ?? "127.0.0.1";

const server = createServer((req, res) => {
  const url = new URL(req.url ?? "/", `http://${HOST}:${PORT}`);
  const { status, body } = resolveMock(url.pathname, req.method ?? "GET");
  res.statusCode = status;
  res.setHeader("Content-Type", "application/json; charset=utf-8");
  res.setHeader("Cache-Control", "no-store");
  res.end(JSON.stringify(body));
});

server.listen(PORT, HOST, () => {
  // eslint-disable-next-line no-console
  console.log(`[mock-api] listening on http://${HOST}:${PORT} (E2E only)`);
});

for (const sig of ["SIGINT", "SIGTERM"]) {
  process.on(sig, () => server.close(() => process.exit(0)));
}
