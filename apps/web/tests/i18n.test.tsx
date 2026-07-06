import { describe, expect, it } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { I18nProvider, useI18n } from "@/lib/i18n/i18n-context";

describe("i18n", () => {
  it("resolves dot-path keys and falls back to the key", () => {
    const { result } = renderHook(() => useI18n(), { wrapper: I18nProvider });
    expect(result.current.t("common.loading")).toBe("Loading…");
    expect(result.current.t("does.not.exist")).toBe("does.not.exist");
    expect(result.current.dir).toBe("ltr");
  });

  it("switches to Arabic and flips direction to rtl", () => {
    const { result } = renderHook(() => useI18n(), { wrapper: I18nProvider });
    act(() => result.current.setLocale("ar"));
    expect(result.current.locale).toBe("ar");
    expect(result.current.dir).toBe("rtl");
    expect(result.current.t("common.loading")).toBe("جارٍ التحميل…");
  });
});
