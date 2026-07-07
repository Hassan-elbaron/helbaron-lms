"use client";

import { useState } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { Headset, Send } from "lucide-react";
import { applyApiFieldErrors, errorMessage } from "@/lib/api/errors";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useConsulting, useRequestConsulting } from "@/lib/org/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { ConsultingCard } from "@/components/org/consulting-card";
import { SectionCard } from "@/components/org/section-card";
import { Field } from "@/components/auth/field";
import { FormAlert } from "@/components/auth/form-alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

type RequestValues = { subject: string; description?: string; organization?: string };

function RequestForm() {
  const { t } = useI18n();
  const req = useRequestConsulting();
  const [formError, setFormError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  const schema = z.object({
    subject: z.string().min(1, t("common.error")).max(255),
    description: z.string().max(5000).optional(),
    organization: z.string().optional(),
  });

  const {
    register,
    handleSubmit,
    setError,
    reset,
    formState: { errors },
  } = useForm<RequestValues>({ resolver: zodResolver(schema), defaultValues: { subject: "", description: "", organization: "" } });

  const onSubmit = handleSubmit((v) => {
    setFormError(null);
    setDone(false);
    const payload = {
      subject: v.subject,
      description: v.description?.trim() || undefined,
      organization: v.organization?.trim() || undefined,
    };
    req.mutate(payload, {
      onSuccess: () => {
        setDone(true);
        reset({ subject: "", description: "", organization: "" });
      },
      onError: (err) => {
        if (!applyApiFieldErrors(err, setError)) setFormError(errorMessage(err, t("org.error")));
      },
    });
  });

  return (
    <form onSubmit={onSubmit} className="space-y-4" noValidate>
      {done ? <FormAlert variant="success">{t("org.consulting.success")}</FormAlert> : null}
      {formError ? <FormAlert>{formError}</FormAlert> : null}
      <Field id="c-subject" label={t("org.consulting.subject")} error={errors.subject?.message}>
        <Input id="c-subject" placeholder={t("org.consulting.subjectPlaceholder")} {...register("subject")} />
      </Field>
      <Field id="c-description" label={t("org.consulting.description")} error={errors.description?.message}>
        <textarea
          id="c-description"
          rows={4}
          placeholder={t("org.consulting.descriptionPlaceholder")}
          className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          {...register("description")}
        />
      </Field>
      <Field id="c-organization" label={t("org.consulting.organization")} error={errors.organization?.message}>
        <Input id="c-organization" placeholder={t("org.consulting.organizationPlaceholder")} {...register("organization")} />
      </Field>
      <Button type="submit" disabled={req.isPending} className="w-full">
        <Send className="size-4" aria-hidden />
        {req.isPending ? t("org.consulting.submitting") : t("org.consulting.submit")}
      </Button>
    </form>
  );
}

export default function ConsultingPage() {
  const { t } = useI18n();
  const query = useConsulting();

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="CONSULTING" icon="Headset" title={t("org.consulting.title")} subtitle={t("org.consulting.subtitle")} />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          <h2 className="text-sm font-semibold text-muted-foreground">{t("org.consulting.listTitle")}</h2>
          <QueryState
            query={query}
            isEmpty={(d) => d.length === 0}
            empty={<EmptyState icon={<Headset className="size-8" />} title={t("org.consulting.empty")} />}
          >
            {(list) => (
              <div className="space-y-3">
                {list.map((r) => (
                  <ConsultingCard key={r.id} request={r} />
                ))}
              </div>
            )}
          </QueryState>
        </div>

        <div className="lg:col-span-1">
          <SectionCard title={t("org.consulting.newRequest")}>
            <RequestForm />
          </SectionCard>
        </div>
      </div>
    </div>
  );
}
