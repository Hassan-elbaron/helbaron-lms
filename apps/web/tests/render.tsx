import { Suspense, type ReactElement, type ReactNode } from "react";
import { act, render } from "@testing-library/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";

const Wrapper = ({ children }: { children: ReactNode }) => (
  <I18nProvider>
    <Suspense fallback={null}>{children}</Suspense>
  </I18nProvider>
);

/** Wrap components that read i18n so tests get real strings. */
export function renderWithI18n(ui: ReactElement) {
  return render(ui, { wrapper: Wrapper });
}

/**
 * Same as renderWithI18n but flushes microtasks under act, so pages that unwrap route params via
 * React `use(params)` resolve their Suspense before assertions run.
 */
export async function renderWithI18nAsync(ui: ReactElement) {
  let result!: ReturnType<typeof render>;
  await act(async () => {
    result = render(ui, { wrapper: Wrapper });
  });
  return result;
}
