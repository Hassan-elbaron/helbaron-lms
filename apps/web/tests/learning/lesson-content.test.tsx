import { describe, expect, it } from "vitest";
import { renderWithI18n } from "../render";
import { LessonContent } from "@/components/learning/lesson-content";
import type { LessonPayload } from "@/lib/learning/api";

const articleLesson = (html: string): LessonPayload => ({
  id: "les1",
  title: "Article Lesson",
  type: "article",
  content: { html },
  is_preview: false,
  playback: null,
  progress: { status: "in_progress", position_seconds: null },
  bookmarked: false,
  note: null,
  navigation: { previous: null, next: null },
});

describe("LessonContent article sanitization", () => {
  it("strips <script> tags and inline event handlers but keeps formatting markup", () => {
    const malicious =
      '<p>Safe <strong>bold</strong> text</p>' +
      '<script>window.__pwned = true;</script>' +
      '<img src="x" onerror="window.__pwned = true" alt="pic">' +
      '<a href="javascript:alert(1)" onclick="window.__pwned = true">link</a>' +
      '<iframe src="https://evil.example"></iframe>' +
      "<style>body{display:none}</style>";

    const { container } = renderWithI18n(<LessonContent lesson={articleLesson(malicious)} />);
    const prose = container.querySelector(".prose");
    expect(prose).not.toBeNull();
    const rendered = prose!.innerHTML;

    // Formatting is preserved.
    expect(prose!.querySelector("p")).not.toBeNull();
    expect(prose!.querySelector("strong")).not.toBeNull();
    expect(prose!.querySelector("strong")!.textContent).toBe("bold");
    expect(prose!.querySelector("img")).not.toBeNull();
    expect(prose!.querySelector("a")).not.toBeNull();

    // Dangerous content is removed.
    expect(prose!.querySelector("script")).toBeNull();
    expect(prose!.querySelector("iframe")).toBeNull();
    expect(prose!.querySelector("style")).toBeNull();
    expect(rendered).not.toContain("onerror");
    expect(rendered).not.toContain("onclick");
    expect(rendered).not.toContain("javascript:");
    expect((window as unknown as { __pwned?: boolean }).__pwned).toBeUndefined();
  });

  it("renders plain-text article bodies untouched", () => {
    const lesson: LessonPayload = { ...articleLesson(""), content: { body: "Hello <world>" } };
    const { getByText } = renderWithI18n(<LessonContent lesson={lesson} />);
    expect(getByText("Hello <world>")).toBeInTheDocument();
  });
});
