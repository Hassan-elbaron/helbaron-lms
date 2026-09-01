"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useCallback, useEffect, useRef, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { hasSession } from "@/lib/api/client";
import { useHydrated } from "@/hooks/use-hydrated";
import { applyApiFieldErrors, errorMessage, isRateLimited } from "@/lib/api/errors";
import { resendEmailOtp, verifyEmail } from "@/lib/auth/api";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { safeRedirect } from "@/lib/utils";
import { AuthCard } from "@/components/auth/auth-card";
import { Field } from "@/components/auth/field";
import { FormAlert } from "@/components/auth/form-alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { PageLoading } from "@/components/states/loading-state";

type Values = { code: string };

/** Seconds the resend control stays disabled after a successful request. */
const RESEND_COOLDOWN_SECONDS = 60;

function VerifyEmailInner() {
  const { t } = useI18n();
  const router = useRouter();
  const searchParams = useSearchParams();
  const auth = useAuth();
  // The session marker is a client-only cookie, so resolve it after hydration (never during SSR)
  // to avoid a hydration mismatch. `ready` gates the loader exactly as the previous mount effect did.
  const ready = useHydrated();
  const authed = ready && hasSession();
  const [done, setDone] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [resendNotice, setResendNotice] = useState<string | null>(null);
  const [cooldown, setCooldown] = useState(0);

  /*
   * Where to send the user once they are verified.
   *
   * `?redirect=` is threaded here by the auth guard, the course purchase panel and the sign-in flow,
   * and was then dropped on the floor: this page hardcoded "/" on success and "/dashboard" when the
   * account was already verified. A learner who clicked "Enroll" on a course, verified, and landed
   * on the homepage had to find the course again to finish what they started.
   *
   * safeRedirect() is the open-redirect guard — it accepts only a root-relative path on this origin
   * and falls back otherwise, so a crafted `?redirect=https://evil.example` cannot bounce a
   * freshly-verified session off-site.
   */
  const target = safeRedirect(searchParams.get("redirect"), "/dashboard");

  const schema = z.object({ code: z.string().min(1, t("auth.validation.code")) });
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { code: "" } });

  const mutation = useMutation({
    mutationFn: async (v: Values) => {
      await verifyEmail(v.code);
      await auth.refresh();
    },
    onSuccess: () => {
      setDone(true);
      setTimeout(() => router.replace(target), 1200);
    },
    onError: (err) => {
      if (!applyApiFieldErrors(err, setError)) setFormError(errorMessage(err, t("auth.genericError")));
    },
  });

  const resend = useMutation({
    mutationFn: resendEmailOtp,
    onSuccess: () => {
      setFormError(null);
      // The API answers generically on purpose (it never confirms whether an address is still
      // pending), so the wording here must not promise more than the response actually said.
      setResendNotice(t("auth.verify.resent"));
      setCooldown(RESEND_COOLDOWN_SECONDS);
    },
    onError: (err) => {
      setResendNotice(null);
      setFormError(errorMessage(err, t("auth.genericError")));
      // Cool down ONLY for a real rate limit. Starting the cooldown on any failure meant a dropped
      // connection or a transient 500 locked the button for a minute, when retrying immediately
      // would have worked — the opposite of what someone locked out of the product needs.
      if (isRateLimited(err)) setCooldown(RESEND_COOLDOWN_SECONDS);
    },
  });

  useEffect(() => {
    if (cooldown <= 0) return;
    const id = setTimeout(() => setCooldown((n) => n - 1), 1000);
    return () => clearTimeout(id);
  }, [cooldown]);

  const onSubmit = handleSubmit((v) => {
    setFormError(null);
    setResendNotice(null);
    mutation.mutate(v);
  });

  // Nothing left to verify. The page is no longer guest-guarded, so it has to send an already
  // verified account on itself rather than showing it a code form it cannot use. `done` is excluded
  // so the success message still gets its moment before the page's own redirect fires.
  const alreadyVerified = auth.status === "authenticated" && auth.user?.email_verified === true;
  useEffect(() => {
    if (alreadyVerified && !done) router.replace(target);
  }, [alreadyVerified, done, router, target]);

  if (!ready) return <PageLoading />;

  return (
    <AuthCard
      title={t("auth.verify.title")}
      subtitle={t("auth.verify.subtitle")}
      footer={
        <Link className="font-medium text-primary hover:underline" href="/login">
          {t("auth.forgot.back")}
        </Link>
      }
    >
      {!authed ? (
        <FormAlert>{t("auth.verify.needLogin")}</FormAlert>
      ) : done ? (
        <FormAlert variant="success">{t("auth.verify.success")}</FormAlert>
      ) : (
        <form onSubmit={onSubmit} className="space-y-4" noValidate>
          {formError ? <FormAlert>{formError}</FormAlert> : null}
          {resendNotice ? <FormAlert variant="success">{resendNotice}</FormAlert> : null}
          <Field id="code" label={t("auth.code")} error={errors.code?.message}>
            <Input id="code" inputMode="numeric" autoComplete="one-time-code" {...register("code")} />
          </Field>
          <Button type="submit" className="w-full" loading={mutation.isPending}>
            {t("auth.verify.submit")}
          </Button>
          <div className="text-center text-sm text-muted-foreground">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              loading={resend.isPending}
              disabled={cooldown > 0 || resend.isPending}
              onClick={() => resend.mutate()}
            >
              {cooldown > 0
                ? t("auth.verify.resendIn").replace("{seconds}", String(cooldown))
                : t("auth.verify.resend")}
            </Button>
          </div>
        </form>
      )}
    </AuthCard>
  );
}

export default function VerifyEmailPage() {
  // useSearchParams needs a Suspense boundary during prerender.
  return (
    <Suspense fallback={<PageLoading />}>
      <VerifyEmailInner />
    </Suspense>
  );
}
