import type { Metadata } from "next";
import { Inter, Fraunces } from "next/font/google";
import { defaultLocale, localeDirection } from "@/lib/i18n/config";
import { siteConfig } from "@/config/site";
import { Providers } from "./providers";
import "./globals.css";

const inter = Inter({ subsets: ["latin"], variable: "--font-inter", display: "swap" });
const fraunces = Fraunces({
  subsets: ["latin"],
  variable: "--font-fraunces",
  display: "swap",
  style: ["normal", "italic"],
  axes: ["opsz"],
});

export const metadata: Metadata = {
  title: { default: siteConfig.name, template: `%s · ${siteConfig.name}` },
  description: siteConfig.description,
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang={defaultLocale} dir={localeDirection[defaultLocale]} suppressHydrationWarning>
      <body className={`${inter.variable} ${fraunces.variable} font-sans antialiased`}>
        <Providers initialLocale={defaultLocale}>{children}</Providers>
      </body>
    </html>
  );
}
