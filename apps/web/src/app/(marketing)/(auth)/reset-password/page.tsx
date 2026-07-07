"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { applyApiFieldErrors, errorMessage } from "@/lib/api/errors";
import { resetPassword } from "@/lib/auth/api";
import { useI18n } from "@/lib/i18n/i18n-context";
import { AuthCard } from "@/components/auth/auth-card";
import { Field } from "@/components/auth/field";
import { FormAlert } from "@/components/auth/form-alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

type Values = { password: string; password_confirmation: string };

function ResetForm() {
  const { t } = useI18n();
  const router = useRouter();
  const params = useSearchParams();
  const token = params.get("token") ?? "";
  const email = params.get("email") ?? "";
  const [done, setDone] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const schema = z
    .object({
      password: z.string().min(8, t("auth.validation.min8")),
      password_confirmation: z.string().min(1, t("auth.validation.required")),
    })
    .refine((d) => d.password === d.password_confirmation, {
      path: ["password_confirmation"],
      message: t("auth.validation.passwordsMatch"),
    });

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { password: "", password_confirmation: "" } });

  const mutation = useMutation({
    mutationFn: (v: Values) =>
      resetPassword({ token, email, password: v.password, password_confirmation: v.password_confirmation }),
    onSuccess: () => {
      setDone(true);
      setTimeout(() => router.replace("/login"), 1500);
    },
    onError: (err) => {
      if (!applyApiFieldErrors(err, setError)) setFormError(errorMessage(err, t("auth.genericError")));
    },
  });

  const onSubmit = handleSubmit((v) => {
    setFormError(null);
    mutation.mutate(v);
  });

  if (!token || !email) {
    return (
      <AuthCard
        title={t("auth.reset.title")}
        footer={
          <Link className="font-medium text-primary hover:underline" href="/forgot-password">
            {t("auth.forgot.title")}
          </Link>
        }
      >
        <FormAlert>{t("auth.reset.invalidLink")}</FormAlert>
      </AuthCard>
    );
  }

  return (
    <AuthCard
      title={t("auth.reset.title")}
      subtitle={t("auth.reset.subtitle")}
      footer={
        <Link className="font-medium text-primary hover:underline" href="/login">
          {t("auth.forgot.back")}
        </Link>
      }
    >
      {done ? (
        <FormAlert variant="success">{t("auth.reset.success")}</FormAlert>
      ) : (
        <form onSubmit={onSubmit} className="space-y-4" noValidate>
          {formError ? <FormAlert>{formError}</FormAlert> : null}
          <Field id="reset-email" label={t("auth.email")}>
            <Input id="reset-email" type="email" value={email} readOnly disabled />
          </Field>
          <Field id="password" label={t("auth.password")} error={errors.password?.message}>
            <Input id="password" type="password" autoComplete="new-password" {...register("password")} />
          </Field>
          <Field id="password_confirmation" label={t("auth.confirmPassword")} error={errors.password_confirmation?.message}>
            <Input id="password_confirmation" type="password" autoComplete="new-password" {...register("password_confirmation")} />
          </Field>
          <Button type="submit" className="w-full" loading={mutation.isPending}>
            {t("auth.reset.submit")}
          </Button>
        </form>
      )}
    </AuthCard>
  );
}

export default function ResetPasswordPage() {
  return (
    <Suspense>
      <ResetForm />
    </Suspense>
  );
}
