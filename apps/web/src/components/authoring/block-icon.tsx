"use client";

import { Icon } from "@/components/ui/icon";
import { blockDef } from "@/lib/authoring/block-registry";
import type { BlockKind } from "@/lib/authoring/types";

type IconSize = "xs" | "sm" | "md" | "lg" | "xl";

/** Renders the registry icon for a block kind. */
export function BlockIcon({ kind, size = "sm", label }: { kind: BlockKind; size?: IconSize; label?: string }) {
  return <Icon icon={blockDef(kind).icon} size={size} label={label} />;
}
