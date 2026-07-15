"use client";

import Link from "next/link";
import { Sparkles } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { PageHeader } from "@/components/student/page-header";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

/**
 * Polished, localized "coming soon" surface for areas whose backend context is not yet built.
 * Honest placeholder — never presents unfinished functionality as available.
 */
export function ComingSoon({ eyebrow, title, icon }: { eyebrow?: string; title: string; icon?: string }) {
  const { t } = useI18n();
  return (
    <div className="space-y-6">
      <PageHeader eyebrow={eyebrow} title={title} icon={icon} />
      <div className="flex flex-col items-center justify-center gap-4 rounded-3xl border bg-card/50 p-10 text-center">
        <span className="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
          <Sparkles className="size-7" aria-hidden />
        </span>
        <Badge variant="secondary">{t("common.comingSoon.badge")}</Badge>
        <div className="space-y-1">
          <p className="font-serif text-xl font-semibold">{t("common.comingSoon.title")}</p>
          <p className="mx-auto max-w-md text-sm text-muted-foreground">{t("common.comingSoon.description")}</p>
        </div>
        <Button asChild variant="outline" className="mt-1">
          <Link href="/dashboard">{t("common.comingSoon.back")}</Link>
        </Button>
      </div>
    </div>
  );
}
