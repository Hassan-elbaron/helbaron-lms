import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "Settings" };

export default function AccountSettingsPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Account" title="Settings" subtitle="Manage your account preferences." icon="Settings" />
      <p className="text-sm text-muted-foreground">Account settings will appear here.</p>
    </div>
  );
}