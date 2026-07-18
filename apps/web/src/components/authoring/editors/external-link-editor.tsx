"use client";

import { ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { isSafeUrl, readString, withValue } from "@/lib/authoring/block-content";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";
import type { BlockContent } from "@/lib/authoring/types";

/**
 * External-link lesson editor.
 *
 * Fields are limited to what the schema actually carries: `url` (the only key the learner player
 * reads — `components/learning/lesson-content.tsx`) and `label`, the existing author-facing name
 * from the block registry's `defaultContent`. No title/description/"open in new tab" controls:
 * the learner renderer ignores them and always opens in a new tab, so those inputs would silently
 * discard the author's intent.
 *
 * Only http/https are accepted; javascript:, data: and file: are rejected here and by the
 * backend sanitizer's allowed-schemes list.
 */
export function ExternalLinkEditor({
  content,
  onChange,
}: {
  content: BlockContent;
  onChange: (next: BlockContent) => void;
}) {
  const { t } = useAuthoringI18n();
  const url = readString(content, "url");
  const unsafe = url.trim() !== "" && !isSafeUrl(url);

  return (
    <div className="space-y-4">
      <FormField label={t("field.link.url")} required error={unsafe ? t("link.unsafe") : undefined}>
        <Input
          type="url"
          inputMode="url"
          value={url}
          onChange={(e) => onChange(withValue(content, "url", e.target.value))}
          placeholder="https://"
        />
      </FormField>

      <FormField label={t("field.link.label")}>
        <Input
          value={readString(content, "label")}
          onChange={(e) => onChange(withValue(content, "label", e.target.value))}
        />
      </FormField>

      {isSafeUrl(url) ? (
        <Button asChild variant="outline" size="sm">
          <a href={url} target="_blank" rel="noopener noreferrer">
            <ExternalLink className="size-4" aria-hidden />
            {t("link.test")}
          </a>
        </Button>
      ) : (
        <Button variant="outline" size="sm" disabled>
          <ExternalLink className="size-4" aria-hidden />
          {t("link.test")}
        </Button>
      )}
    </div>
  );
}
