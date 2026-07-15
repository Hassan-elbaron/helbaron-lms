import Link from "next/link";
import { cookies } from "next/headers";
import { defaultLocale, isLocale, localeCookieName, localeDirection, type Locale } from "@/lib/i18n/config";
import { dictionaries } from "@/lib/i18n/dictionaries";

/** Root 404. Server component — resolves the locale from the cookie so it is localized (EN/AR/RTL). */
export default async function NotFound() {
  const cookieStore = await cookies();
  const cookieLocale = cookieStore.get(localeCookieName)?.value;
  const locale: Locale = isLocale(cookieLocale) ? cookieLocale : defaultLocale;
  const t = dictionaries[locale].common.notFound;

  return (
    <div
      dir={localeDirection[locale]}
      className="flex min-h-dvh flex-col items-center justify-center gap-4 p-8 text-center"
    >
      <p className="font-serif text-2xl font-semibold">{t.title}</p>
      <p className="text-sm text-muted-foreground">{t.description}</p>
      <div className="flex flex-wrap justify-center gap-3">
        <Link href="/" className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
          {t.home}
        </Link>
        <Link href="/courses" className="rounded-md border px-4 py-2 text-sm font-medium">
          {t.browse}
        </Link>
      </div>
    </div>
  );
}
