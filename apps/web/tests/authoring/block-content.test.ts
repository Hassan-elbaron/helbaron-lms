import { describe, expect, it } from "vitest";
import { AUTHORING_DICTS } from "@/lib/authoring/authoring-i18n";
import {
  isSafeUrl,
  readBoolean,
  readString,
  readingStats,
  withValue,
} from "@/lib/authoring/block-content";

describe("isSafeUrl", () => {
  it("accepts http and https", () => {
    expect(isSafeUrl("https://example.com/a?b=c")).toBe(true);
    expect(isSafeUrl("http://example.com")).toBe(true);
    expect(isSafeUrl("  https://example.com  ")).toBe(true);
  });

  // These are the payloads the editor must never turn into an href.
  it.each([
    "javascript:alert(1)",
    "JavaScript:alert(1)",
    "data:text/html;base64,PHNjcmlwdD4=",
    "file:///etc/passwd",
    "vbscript:msgbox(1)",
    "mailto:someone@example.com",
    "example.com",
    "",
    "   ",
  ])("rejects %j", (value) => {
    expect(isSafeUrl(value)).toBe(false);
  });
});

describe("content accessors", () => {
  it("reads only strings and booleans, never coercing", () => {
    expect(readString({ html: "<p>hi</p>" }, "html")).toBe("<p>hi</p>");
    expect(readString({ html: 42 }, "html")).toBe("");
    expect(readString({}, "missing")).toBe("");
    expect(readBoolean({ new_tab: true }, "new_tab")).toBe(true);
    expect(readBoolean({ new_tab: "true" }, "new_tab")).toBe(false);
  });

  it("drops empty values instead of persisting blanks", () => {
    expect(withValue({ url: "https://a.test" }, "url", "")).toEqual({});
    expect(withValue({ new_tab: true }, "new_tab", false)).toEqual({});
    expect(withValue({}, "url", "https://a.test")).toEqual({ url: "https://a.test" });
  });

  it("does not mutate the input", () => {
    const original = { url: "https://a.test" };
    withValue(original, "url", "https://b.test");
    expect(original).toEqual({ url: "https://a.test" });
  });
});

describe("readingStats", () => {
  it("counts words and characters from plain text", () => {
    expect(readingStats("")).toEqual({ characters: 0, words: 0, minutes: 0 });
    expect(readingStats("hello world")).toEqual({ characters: 11, words: 2, minutes: 1 });
  });

  it("rounds to at least one minute for any non-empty text", () => {
    expect(readingStats("one").minutes).toBe(1);
  });

  it("uses 200 wpm", () => {
    expect(readingStats(Array.from({ length: 400 }, () => "word").join(" ")).minutes).toBe(2);
  });
});

describe("authoring i18n", () => {
  it("keeps the Arabic dictionary in sync with English", () => {
    const en = Object.keys(AUTHORING_DICTS.en).sort();
    const ar = Object.keys(AUTHORING_DICTS.ar).sort();
    expect(ar).toEqual(en);
  });

  it("translates the new lesson-editor keys in both locales", () => {
    for (const key of ["richtext.toolbar", "media.title", "link.test", "field.audio.transcript"]) {
      expect(AUTHORING_DICTS.en[key]).toBeTruthy();
      expect(AUTHORING_DICTS.ar[key]).toBeTruthy();
      expect(AUTHORING_DICTS.ar[key]).not.toBe(AUTHORING_DICTS.en[key]);
    }
  });
});
