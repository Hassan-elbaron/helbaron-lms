"use client";

import { useTheme } from "next-themes";
import { Toaster as Sonner, toast } from "sonner";
import { useI18n } from "@/lib/i18n/i18n-context";

type ToasterProps = React.ComponentProps<typeof Sonner>;

/** Theme + direction aware toast host. Mount once in the app providers. */
export function Toaster(props: ToasterProps) {
  const { theme = "system" } = useTheme();
  const { dir } = useI18n();
  return (
    <Sonner
      theme={theme as ToasterProps["theme"]}
      dir={dir}
      className="toaster group"
      position={dir === "rtl" ? "bottom-left" : "bottom-right"}
      toastOptions={{
        classNames: {
          toast: "group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:rounded-lg group-[.toaster]:[box-shadow:var(--elevation-4)]",
          description: "group-[.toast]:text-muted-foreground",
          actionButton: "group-[.toast]:bg-primary group-[.toast]:text-primary-foreground",
          cancelButton: "group-[.toast]:bg-muted group-[.toast]:text-muted-foreground",
        },
      }}
      {...props}
    />
  );
}

export { toast };
