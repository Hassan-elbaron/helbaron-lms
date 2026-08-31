import { readdirSync, readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";
import { gradebookPath, gradebookExportPath } from "@/lib/gradebook/gradebook-api";

/**
 * Regression guard for the W09 "double /api/v1" defect.
 *
 * The BFF proxy base (src/app/api/backend/[...path]/route.ts) and the SSR base
 * (siteConfig.apiBaseUrl) BOTH already end in `/api/v1`, and apiFetch() joins
 * `${base}/${path}`. Therefore any fetcher that passes a path beginning with
 * `v1/` (or `/v1/`) produces `/api/v1/v1/...` → 404. The media, assignments,
 * versioning, gradebook and learning-player modules previously did exactly this,
 * silently breaking those entire surfaces in production while unit tests (which
 * only assert the built string) stayed green.
 *
 * These files must pass BARE resource paths (e.g. `media/assets`, `admin/...`).
 */

/**
 * DISCOVERED, not listed.
 *
 * The list used to be hardcoded, which meant the guard covered exactly the five modules that had
 * already been caught and nothing written afterwards — a regression test that stops growing with the
 * code it guards is a regression test that expires. Every `*-api.ts` fetcher under src/lib is now
 * swept automatically, plus the handful of components that call the backend directly.
 *
 * The explicit extras stay explicit: they are NOT `*-api.ts`, so a glob would miss them, and each
 * one is a place a bare path was previously got wrong.
 */
const EXTRA_MODULES = [
  "src/lib/learning/player-api.ts",
  "src/components/assignments/grading/SubmissionFileList.tsx",
  "src/components/assignments/submission/upload/uploadClient.ts",
];

/** Every `*-api.ts` fetcher under src/lib, with separators normalised for Windows. */
function discoverApiModules(): string[] {
  return readdirSync(resolve(process.cwd(), "src/lib"), { recursive: true, encoding: "utf8" })
    .map((entry) => entry.split("\\").join("/"))
    .filter((entry) => entry.endsWith("-api.ts"))
    .map((entry) => `src/lib/${entry}`);
}

const API_MODULES = [...discoverApiModules(), ...EXTRA_MODULES].sort();

// Matches a fetch-path string literal that starts with the version prefix:
//   "v1/...   'v1/...   `v1/...   "/v1/...   `/v1/...   etc.
const VERSION_PREFIXED_PATH = /["'`]\/?v1\//;

describe("no double /api/v1 prefix in client fetchers", () => {
  it("discovers the fetchers rather than trusting a hardcoded list", () => {
    // If the glob ever silently matches nothing, every assertion below would vacuously pass.
    expect(API_MODULES.length).toBeGreaterThan(EXTRA_MODULES.length);
    expect(API_MODULES).toContain("src/lib/gradebook/gradebook-api.ts");
  });

  it.each(API_MODULES)("%s passes bare paths (no v1/ prefix)", (rel) => {
    const src = readFileSync(resolve(process.cwd(), rel), "utf8");
    // Strip line comments and block-comment lines so doc examples don't trip the guard.
    const code = src
      .split("\n")
      .filter((line) => {
        const t = line.trim();
        return !t.startsWith("*") && !t.startsWith("//") && !t.startsWith("/*");
      })
      .join("\n");
    const offenders = code
      .split("\n")
      .map((line, i) => ({ line, n: i + 1 }))
      .filter(({ line }) => VERSION_PREFIXED_PATH.test(line));
    expect(
      offenders,
      `found version-prefixed fetch path(s) that would double to /api/v1/v1/...:\n${offenders
        .map((o) => `  ${rel}:${o.n}  ${o.line.trim()}`)
        .join("\n")}`,
    ).toEqual([]);
  });

  it("gradebook path builders are backend-relative and bare", () => {
    expect(gradebookPath("crs_1")).toBe("admin/courses/crs_1/gradebook");
    expect(gradebookPath("crs_1")).not.toMatch(/^\/?v1\//);
    expect(gradebookExportPath("crs_1")).toBe("/api/backend/admin/courses/crs_1/gradebook/export");
    expect(gradebookExportPath("crs_1")).not.toContain("/api/backend/v1/");
  });
});
