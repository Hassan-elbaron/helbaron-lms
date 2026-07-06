"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import type { OrganizationMember } from "@/lib/org/api";
import { Badge } from "@/components/ui/badge";

const roleVariant: Record<string, "default" | "secondary" | "outline"> = {
  owner: "default",
  admin: "default",
  manager: "secondary",
  member: "outline",
};
const statusVariant: Record<string, "success" | "warning" | "outline"> = {
  active: "success",
  invited: "warning",
  removed: "outline",
};

export function MemberRow({ member }: { member: OrganizationMember }) {
  const { t } = useI18n();
  return (
    <div className="card-hover flex flex-wrap items-center justify-between gap-2 rounded-md border p-3 hover:border-primary/30 hover:shadow-sm">
      <div className="min-w-0">
        <p className="truncate text-sm font-medium">{member.email}</p>
        {member.invited_at ? (
          <p className="text-xs text-muted-foreground">
            {t("org.members.invitedAt")}: {new Date(member.invited_at).toLocaleDateString()}
          </p>
        ) : null}
      </div>
      <div className="flex items-center gap-2">
        <Badge variant={roleVariant[member.role] ?? "outline"}>{t(`org.roles.${member.role}`)}</Badge>
        <Badge variant={statusVariant[member.status] ?? "outline"}>{t(`org.memberStatus.${member.status}`)}</Badge>
      </div>
    </div>
  );
}
