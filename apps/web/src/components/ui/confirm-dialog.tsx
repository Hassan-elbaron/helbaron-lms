"use client";

import { useState, type ReactNode } from "react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { Button, type ButtonProps } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

export interface ConfirmDialogProps {
  /** Controlled open state. */
  open: boolean;
  /** Called when the dialog requests to open/close. */
  onOpenChange: (open: boolean) => void;
  /** Dialog title. Falls back to a bilingual default. */
  title?: string;
  /** Consequence / description. Rendered inside the dialog body. */
  description?: ReactNode;
  /** Confirm button label. Falls back to a bilingual default. */
  confirmLabel?: string;
  /** Cancel button label. Falls back to a bilingual default. */
  cancelLabel?: string;
  /** Confirm button variant — defaults to `destructive` for irreversible actions. */
  confirmVariant?: ButtonProps["variant"];
  /** External loading state (e.g. a mutation's `isPending`). */
  loading?: boolean;
  /**
   * Confirm handler. May be async — the confirm button shows a spinner while it
   * resolves and the dialog stays locked (cannot be dismissed) until it settles.
   */
  onConfirm: () => void | Promise<void>;
}

/**
 * Reusable confirmation dialog for destructive / irreversible actions.
 * Built on the Radix-backed `ui/dialog` (focus trap + Escape handled by the primitive).
 */
export function ConfirmDialog({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel,
  cancelLabel,
  confirmVariant = "destructive",
  loading,
  onConfirm,
}: ConfirmDialogProps) {
  const { t } = useI18n();
  const [pending, setPending] = useState(false);
  const isBusy = loading ?? pending;

  const handleConfirm = async () => {
    try {
      setPending(true);
      await onConfirm();
    } finally {
      setPending(false);
    }
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        // Prevent dismissing while the confirm action is in flight.
        if (isBusy) return;
        onOpenChange(next);
      }}
    >
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>{title ?? t("common.confirm.title")}</DialogTitle>
          {description ? <DialogDescription>{description}</DialogDescription> : null}
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isBusy}>
            {cancelLabel ?? t("common.confirm.cancel")}
          </Button>
          <Button variant={confirmVariant} loading={isBusy} onClick={handleConfirm}>
            {confirmLabel ?? t("common.confirm.confirm")}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
