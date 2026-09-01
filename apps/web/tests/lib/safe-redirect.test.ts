import { describe, expect, it } from "vitest";
import { safeRedirect } from "@/lib/utils";

/**
 * safeRedirect() is the open-redirect guard (CWE-601) and had NO test at all.
 *
 * It decides where a freshly-authenticated or freshly-verified session is sent, from a value an
 * attacker fully controls via a crafted link. A regression here hands a logged-in user to another
 * origin at the exact moment their session is most valuable.
 *
 * Every case asserts the FALLBACK is returned, not merely that the hostile value is absent — a guard
 * that returned "" or undefined would satisfy the weaker assertion and still break the redirect.
 */
describe("safeRedirect", () => {
  const FALLBACK = "/dashboard";

  it("returns the fallback for empty input", () => {
    expect(safeRedirect(null, FALLBACK)).toBe(FALLBACK);
    expect(safeRedirect(undefined, FALLBACK)).toBe(FALLBACK);
    expect(safeRedirect("", FALLBACK)).toBe(FALLBACK);
  });

  it("allows a plain same-origin path", () => {
    expect(safeRedirect("/courses/abc", FALLBACK)).toBe("/courses/abc");
    expect(safeRedirect("/", FALLBACK)).toBe("/");
  });

  it("keeps a query string and fragment on an allowed path", () => {
    expect(safeRedirect("/courses?page=2", FALLBACK)).toBe("/courses?page=2");
    expect(safeRedirect("/courses#section", FALLBACK)).toBe("/courses#section");
  });

  it.each([
    ["absolute https URL", "https://evil.example/steal"],
    ["absolute http URL", "http://evil.example/steal"],
    ["protocol-relative", "//evil.example/steal"],
    ["backslash-escaped", "/\\evil.example"],
    ["javascript scheme", "javascript:alert(1)"],
    ["scheme smuggled after slash", "/javascript:alert(1)"],
    ["data scheme", "data:text/html,<script>alert(1)</script>"],
    ["no leading slash", "evil.example"],
    ["tab-obfuscated", "/\t/evil.example"],
    ["newline-obfuscated", "/\n/evil.example"],
  ])("refuses %s", (_label, target) => {
    expect(safeRedirect(target, FALLBACK)).toBe(FALLBACK);
  });

  it("allows a colon that is not a scheme", () => {
    // A path segment may legitimately contain a colon; only a scheme-looking FIRST segment is
    // dangerous. Rejecting this would break real links rather than protect anyone.
    expect(safeRedirect("/foo/bar:baz", FALLBACK)).toBe("/foo/bar:baz");
  });

  it("defaults to the site root when no fallback is given", () => {
    expect(safeRedirect("https://evil.example")).toBe("/");
  });
});
