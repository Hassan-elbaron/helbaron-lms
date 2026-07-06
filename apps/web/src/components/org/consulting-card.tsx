"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import type { ConsultingRequest } from "@/lib/org/api";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const statusVariant: Record<string, "default" | "secondary" | "success" | "warning" | "outline"> = {
  new: "warning",
  triaged: "secondary",
  in_progress: "default",
  resolved: "success",
  closed: "outline",
};

export function ConsultingCard({ request }: { request: ConsultingRequest }) {
  const { t } = useI18n();
  return (
    <Card className="card-hover hover:border-primary/30 hover:shadow-md">
      <CardHeader className="flex-row items-start justify-between gap-2">
        <CardTitle className="text-base">{request.subject}</CardTitle>
        <Badge variant={statusVariant[request.status] ?? "outline"}>{t(`org.consultingStatus.${request.status}`)}</Badge>
      </CardHeader>
      <CardContent className="space-y-2">
        {request.description ? (
          <p className="line-clamp-3 text-sm text-muted-foreground">{request.description}</p>
        ) : null}
        <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
          {request.created_at ? (
            <span>{t("org.consulting.created")}: {new Date(request.created_at).toLocaleDateString()}</span>
          ) : null}
          {request.sla_due_at ? (
            <span>{t("org.consulting.slaDue")}: {new Date(request.sla_due_at).toLocaleDateString()}</span>
          ) : null}
        </div>
      </CardContent>
    </Card>
  );
}
