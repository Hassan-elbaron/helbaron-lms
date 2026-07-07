"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { applyApiFieldErrors, errorMessage } from "@/lib/api/errors";
import { registerUser } from "@/lib/auth/api";
import { useAuth } from "@/lib/auth/auth-context";
import { useI18n } from "@/lib/i18n/i18n-context";
import { AuthCard } from "@/components/auth/auth-card";
import { Field } from "@/components/auth/field";
import { FormAlert } from "@/components/auth/form-alert";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";

type Values = {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
  terms: boolean;
};

export default function RegisterPage() {
  const { t, locale } = useI18n();
  const auth = useAuth();
  const [formError, setFormError] = useState<string | null>(null);

  const schema = z
    .object({
      name: z.string().min(1, t("auth.validation.required")),
      email: z.string().min(1, t("auth.validation.required")).email(t("auth.validation.email")),
      phone: z.string().optional(),
      password: z.string().min(8, t("auth.validation.min8")),
      password_confirmation: z.string().min(1, t("auth.validation.required")),
      terms: z.boolean(),
    })
    .refine((d) => d.password === d.password_confirmation, {
      path: ["password_confirmation"],
      message: t("auth.validation.passwordsMatch"),
    })
    .refine((d) => d.terms === true, { path: ["terms"], message: t("auth.validation.terms") });

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: "", email: "", phone: "", password: "", password_confirmation: "", terms: false },
  });

  const mutation = useMutation({
    mutationFn: async (v: Values) => {
      await registerUser({
        name: v.name,
        email: v.email,
        phone: v.phone?.trim() ? v.phone.trim() : undefined,
        password: v.password,
        password_confirmation: v.password_confirmation,
        locale,
      });
      // Best-effort sign-in so the (authenticated) email-verification step works immediately.
      try {
        await auth.login(v.email, v.password);
      } catch {
        /* verification page prompts sign-in if this fails */
      }
    },
    onSuccess: () => window.location.assign("/verify-email"),
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
      title={t("auth.register.title")}
      subtitle={t("auth.register.subtitle")}
      footer={
        <span>
          {t("auth.register.haveAccount")}{" "}
          <Link className="font-medium text-primary hover:underline" href="/login">
            {t("auth.register.login")}
          </Link>
        </span>
      }
    >
      <form onSubmit={onSubmit} className="space-y-4" noValidate>
        {formError ? <FormAlert>{formError}</FormAlert> : null}
        <Field id="name" label={t("auth.name")} error={errors.name?.message}>
          <Input id="name" autoComplete="name" {...register("name")} />
        </Field>
        <Field id="email" label={t("auth.email")} error={errors.email?.message}>
          <Input id="email" type="email" autoComplete="email" placeholder={t("auth.emailPlaceholder")} {...register("email")} />
        </Field>
        <Field id="phone" label={t("auth.phone")} error={errors.phone?.message}>
          <Input id="phone" type="tel" autoComplete="tel" {...register("phone")} />
        </Field>
        <Field id="password" label={t("auth.password")} error={errors.password?.message}>
          <Input id="password" type="password" autoComplete="new-password" {...register("password")} />
        </Field>
        <Field id="password_confirmation" label={t("auth.confirmPassword")} error={errors.password_confirmation?.message}>
          <Input id="password_confirmation" type="password" autoComplete="new-password" {...register("password_confirmation")} />
        </Field>
        <div className="space-y-1.5">
          <label className="flex items-start gap-2 text-sm text-muted-foreground">
            <Checkbox className="mt-0.5" {...register("terms")} />
            <span>{t("auth.register.terms")}</span>
          </label>
          {errors.terms?.message ? (
            <p role="alert" className="text-xs font-medium text-destructive">
              {errors.terms.message}
            </p>
          ) : null}
        </div>
        <Button type="submit" className="w-full" loading={mutation.isPending}>
          {t("auth.register.submit")}
        </Button>
      </form>
    </AuthCard>
  );
}
