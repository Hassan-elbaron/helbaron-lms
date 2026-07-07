"use client";

import { errorMessage } from "@/lib/api/errors";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useAcceptContract, useContracts } from "@/lib/commerce/hooks";
import { RequireAuth } from "@/lib/auth/guards";
import { PageHero } from "@/components/marketing/page-hero";
import { QueryState } from "@/components/student/query-state";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/states/empty-state";
import { toast } from "@/components/ui/toast";

export default function ContractsPage() {
  const { t } = useI18n();
  const query = useContracts();
  const accept = useAcceptContract();

  return (
    <RequireAuth>
      <PageHero page="contracts" />
      <QueryState query={query} isEmpty={(d) => d.length === 0} empty={<EmptyState title={t("commerce.contracts.empty")} />}>
        {(contracts) => (
          <div className="space-y-4">
            {contracts.map((c) => {
              const accepted = c.status === "accepted" || Boolean(c.accepted_at);
              return (
                <Card key={c.id}>
                  <CardHeader className="flex-row items-center justify-between gap-2">
                    <CardTitle className="text-base">{c.template?.title ?? t("commerce.contracts.title")}</CardTitle>
                    <Badge variant={accepted ? "success" : "warning"}>
                      {accepted ? t("commerce.contracts.accepted") : t("commerce.contracts.pending")}
                    </Badge>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {c.template?.body ? (
                      <div className="max-h-48 overflow-y-auto whitespace-pre-line rounded-md border bg-muted/30 p-4 text-sm text-muted-foreground">
                        {c.template.body}
                      </div>
                    ) : null}
                    {!accepted ? (
                      <Button
                        loading={accept.isPending && accept.variables === c.id}
                        onClick={() =>
                          accept.mutate(c.id, {
                            onSuccess: () => toast.success(t("commerce.contracts.accepted")),
                            onError: (e) => toast.error(errorMessage(e, t("common.error"))),
                          })
                        }
                      >
                        {t("commerce.contracts.accept")}
                      </Button>
                    ) : null}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}
      </QueryState>
    </RequireAuth>
  );
}
