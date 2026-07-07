"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { applyApiFieldErrors, errorMessage } from "@/lib/api/errors";
import { forgotPassword } from "@/lib/auth/api";
import { useI18n } from "@/lib/i18n/i18n-context";
import { AuthCard } from "@/components/auth/auth-card";
import { Field } from "@/components/auth/field";
import { FormAlert } from "@/components/auth/form-alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

type Values = { email: string };

export default function ForgotPasswordPage() {
  const { t } = useI18n();
  const [sent, setSent] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const schema = z.object({
    email: z.string().min(1, t("auth.validation.required")).email(t("auth.validation.email")),
  });

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { email: "" } });

  const mutation = useMutation({
    mutationFn: (v: Values) => forgotPassword(v.email),
    onSuccess: () => setSent(true),
    onError: (err) => {
      if (!applyApiFieldErrors(err, setError)) setFormError(errorMessage(err, t("auth.genericError")));
    },
  });

  const onSubmit = handleSubmit((v) => {
    setFormError(null);
    mutation.mutate(v);
  });

  return (
    <AuthCard
      title={t("auth.forgot.title")}
      subtitle={t("auth.forgot.subtitle")}
      footer={
        <Link className="font-medium text-primary hover:underline" href="/login">
          {t("auth.forgot.back")}
        </Link>
      }
    >
      {sent ? (
        <FormAlert variant="success">{t("auth.forgot.sent")}</FormAlert>
      ) : (
        <form onSubmit={onSubmit} className="space-y-4" noValidate>
          {formError ? <FormAlert>{formError}</FormAlert> : null}
          <Field id="email" label={t("auth.email")} error={errors.email?.message}>
            <Input id="email" type="email" autoComplete="email" placeholder={t("auth.emailPlaceholder")} {...register("email")} />
          </Field>
          <Button type="submit" className="w-full" loading={mutation.isPending}>
            {t("auth.forgot.submit")}
          </Button>
        </form>
      )}
    </AuthCard>
  );
}
