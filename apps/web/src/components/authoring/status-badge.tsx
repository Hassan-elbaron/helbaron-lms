"use client";

import { Badge } from "@/components/ui/badge";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import type { PublishState } from "@/lib/authoring/types";

/** Draft / Published pill, reused across the tree, editors and inspector. */
export function StatusBadge({ state }: { state: PublishState }) {
  const { t } = useAuthoringI18n();
  return (
    <Badge variant={state === "published" ? "success" : "secondary"}>
      {t(state === "published" ? "status.published" : "status.draft")}
    </Badge>
  );
}
