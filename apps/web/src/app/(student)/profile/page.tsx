"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { errorMessage } from "@/lib/api/errors";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useProfile, useUpdateProfile } from "@/lib/student/hooks";
import type { UserProfile } from "@/lib/student/api";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { Field } from "@/components/auth/field";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { toast } from "@/components/ui/toast";
import { cn } from "@/lib/utils";

type Values = {
  name: string;
  first_name: string;
  last_name: string;
  bio: string;
  gender: "" | "male" | "female" | "unspecified";
  date_of_birth: string;
  locale: "en" | "ar";
};

const controlClass =
  "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2";

function ProfileForm({ data }: { data: UserProfile }) {
  const { t } = useI18n();
  const update = useUpdateProfile();

  const schema = z.object({
    name: z.string().optional(),
    first_name: z.string().optional(),
    last_name: z.string().optional(),
    bio: z.string().max(1000).optional(),
    gender: z.enum(["", "male", "female", "unspecified"]).optional(),
    date_of_birth: z.string().optional(),
    locale: z.enum(["en", "ar"]),
  });

  const { register, handleSubmit, reset } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: data.name ?? "",
      first_name: data.profile?.first_name ?? "",
      last_name: data.profile?.last_name ?? "",
      bio: data.profile?.bio ?? "",
      gender: (data.profile?.gender as Values["gender"]) ?? "",
      date_of_birth: data.profile?.date_of_birth ?? "",
      locale: data.locale ?? "en",
    },
  });

  useEffect(() => {
    reset({
      name: data.name ?? "",
      first_name: data.profile?.first_name ?? "",
      last_name: data.profile?.last_name ?? "",
      bio: data.profile?.bio ?? "",
      gender: (data.profile?.gender as Values["gender"]) ?? "",
      date_of_birth: data.profile?.date_of_birth ?? "",
      locale: data.locale ?? "en",
    });
  }, [data, reset]);

  const onSubmit = handleSubmit((v) => {
    update.mutate(
      {
        name: v.name || undefined,
        first_name: v.first_name || null,
        last_name: v.last_name || null,
        bio: v.bio || null,
        gender: v.gender ? v.gender : null,
        date_of_birth: v.date_of_birth || null,
        locale: v.locale,
      },
      {
        onSuccess: () => toast.success(t("student.profile.saved")),
        onError: (e) => toast.error(errorMessage(e, t("common.error"))),
      },
    );
  });

  return (
    <Card>
      <CardContent className="p-6">
        <form onSubmit={onSubmit} className="space-y-4">
          <Field id="name" label={t("student.profile.name")}>
            <Input id="name" {...register("name")} />
          </Field>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field id="first_name" label={t("student.profile.firstName")}>
              <Input id="first_name" {...register("first_name")} />
            </Field>
            <Field id="last_name" label={t("student.profile.lastName")}>
              <Input id="last_name" {...register("last_name")} />
            </Field>
          </div>
          <Field id="bio" label={t("student.profile.bio")}>
            <textarea id="bio" rows={3} className={cn(controlClass, "h-auto")} {...register("bio")} />
          </Field>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field id="gender" label={t("student.profile.gender")}>
              <select id="gender" className={controlClass} {...register("gender")}>
                <option value="">—</option>
                <option value="male">{t("student.profile.genderMale")}</option>
                <option value="female">{t("student.profile.genderFemale")}</option>
                <option value="unspecified">{t("student.profile.genderUnspecified")}</option>
              </select>
            </Field>
            <Field id="date_of_birth" label={t("student.profile.dob")}>
              <Input id="date_of_birth" type="date" {...register("date_of_birth")} />
            </Field>
          </div>
          <Field id="locale" label={t("student.profile.language")}>
            <select id="locale" className={controlClass} {...register("locale")}>
              <option value="en">English</option>
              <option value="ar">العربية</option>
            </select>
          </Field>
          <Button type="submit" loading={update.isPending}>
            {t("student.profile.save")}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

export default function ProfilePage() {
  const { t } = useI18n();
  const query = useProfile();
  return (
    <div className="max-w-2xl">
      <PageHeader eyebrow="ACCOUNT" icon="User" title={t("student.profile.title")} subtitle={t("student.profile.subtitle")} />
      <QueryState query={query}>{(data) => <ProfileForm data={data} />}</QueryState>
    </div>
  );
}
