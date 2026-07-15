"use client";

import { useParams, useRouter } from "next/navigation";
import { useState, type FormEvent } from "react";
import { BadgeCheck, Search, ShieldX } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { ApiRequestError } from "@/lib/api/client";
import { errorMessage } from "@/lib/api/errors";
import { useVerifyCertificate } from "@/lib/certification/hooks";
import type { CertificateVerification } from "@/lib/certification/api";
import { Reveal } from "@/components/landing/reveal";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { LoadingState } from "@/components/states/loading-state";
import { ErrorState } from "@/components/states/error-state";

function formatDate(iso: string | null, locale: string): string {
  if (!iso) return "—";
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString(locale === "ar" ? "ar" : "en", { dateStyle: "medium" });
}

function DetailRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-border/60 py-2 last:border-0">
      <dt className="text-sm text-muted-foreground">{label}</dt>
      <dd className="text-sm font-medium">{value}</dd>
    </div>
  );
}

function ResultCard({ data }: { data: CertificateVerification }) {
  const { t, locale } = useI18n();
  const isValid = data.valid && data.status !== "revoked";

  return (
    <Card className={isValid ? "border-primary/30" : "border-destructive/40"}>
      <CardContent className="p-6">
        <div className="flex items-start gap-4">
          <span
            className={
              isValid
                ? "flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                : "flex size-12 shrink-0 items-center justify-center rounded-2xl bg-destructive/10 text-destructive"
            }
          >
            {isValid ? <BadgeCheck className="size-6" aria-hidden /> : <ShieldX className="size-6" aria-hidden />}
          </span>
          <div>
            <h2 className={isValid ? "font-serif text-xl font-semibold text-primary" : "font-serif text-xl font-semibold text-destructive"}>
              {isValid ? t("verify.validTitle") : t("verify.invalidTitle")}
            </h2>
            <p className="mt-1 text-sm text-muted-foreground">
              {isValid ? t("verify.validDescription") : t("verify.invalidDescription")}
            </p>
          </div>
        </div>

        <dl className="mt-6">
          {data.holder_name ? <DetailRow label={t("verify.holder")} value={data.holder_name} /> : null}
          {data.course_title ? <DetailRow label={t("verify.course")} value={data.course_title} /> : null}
          <DetailRow label={t("verify.number")} value={data.number} />
          <DetailRow
            label={t("verify.status")}
            value={data.status === "revoked" ? t("verify.statusRevoked") : t("verify.statusIssued")}
          />
          <DetailRow label={t("verify.issued")} value={formatDate(data.issued_at, locale)} />
          {data.revoked_at ? <DetailRow label={t("verify.revoked")} value={formatDate(data.revoked_at, locale)} /> : null}
        </dl>
      </CardContent>
    </Card>
  );
}

function VerifyResult({ code }: { code: string }) {
  const { t } = useI18n();
  const query = useVerifyCertificate(code);

  if (query.isPending) return <LoadingState />;

  if (query.isError) {
    if (query.error instanceof ApiRequestError && query.error.status === 404) {
      return (
        <Card className="border-destructive/40">
          <CardContent className="p-6">
            <div className="flex items-start gap-4">
              <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-destructive/10 text-destructive">
                <ShieldX className="size-6" aria-hidden />
              </span>
              <div>
                <h2 className="font-serif text-xl font-semibold text-destructive">{t("verify.notFoundTitle")}</h2>
                <p className="mt-1 text-sm text-muted-foreground">{t("verify.notFoundDescription")}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      );
    }
    return <ErrorState message={errorMessage(query.error, t("common.error"))} onRetry={() => query.refetch()} />;
  }

  return <ResultCard data={query.data} />;
}

export function VerifyClient() {
  const { t, locale } = useI18n();
  const router = useRouter();
  const params = useParams<{ code?: string }>();
  const code = typeof params.code === "string" ? decodeURIComponent(params.code) : undefined;
  const [input, setInput] = useState("");

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    const next = input.trim();
    if (next) router.push(`/verify/${encodeURIComponent(next)}`);
  };

  return (
    <Reveal className="mx-auto max-w-2xl py-6">
      <div className="mb-3 inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-copper">
        <span className="h-px w-8 bg-copper/50" aria-hidden />
        {t("verify.eyebrow")}
      </div>
      <h1 className="font-serif text-3xl font-semibold leading-[1.05] tracking-tight sm:text-4xl">
        {t("verify.title")} <span className="italic text-copper">{t("verify.emphasis")}</span>
      </h1>
      <p className="mt-4 text-muted-foreground">{t("verify.subtitle")}</p>

      {code ? (
        <div className="mt-8">
          <VerifyResult code={code} />
        </div>
      ) : null}

      <form onSubmit={onSubmit} className="mt-8 flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute inset-y-0 start-3 my-auto size-4 text-muted-foreground" aria-hidden />
          <Input
            className="ps-9"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder={t("verify.codePlaceholder")}
            aria-label={t("verify.codeLabel")}
            dir={locale === "ar" ? "rtl" : "ltr"}
          />
        </div>
        <Button type="submit" disabled={!input.trim()}>
          {code ? t("verify.verifyAnother") : t("verify.submit")}
        </Button>
      </form>
    </Reveal>
  );
}
