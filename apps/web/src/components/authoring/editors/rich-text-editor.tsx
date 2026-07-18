"use client";

import { useState, type ReactNode } from "react";
import { EditorContent, useEditor } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import Link from "@tiptap/extension-link";
import Underline from "@tiptap/extension-underline";
import {
  Bold,
  Code,
  Heading1,
  Heading2,
  Heading3,
  Italic,
  Link2,
  Link2Off,
  List,
  ListOrdered,
  Minus,
  Quote,
  Redo2,
  RemoveFormatting,
  Square,
  Strikethrough,
  Underline as UnderlineIcon,
  Undo2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { isSafeUrl, readingStats } from "@/lib/authoring/block-content";
import { useAuthoringI18n } from "@/lib/authoring/authoring-i18n";

/**
 * Rich-text lesson editor (TipTap / ProseMirror).
 *
 * Output is plain HTML restricted to tags the BACKEND sanitizer allows
 * (`HtmlSanitizer`: p, br, hr, strong, em, u, s, ul, ol, li, blockquote, pre, code, h1-h6, a[href]).
 * Text alignment is intentionally NOT offered: the backend allow-list permits no `style`
 * attribute, so alignment could never persist — offering it would silently lose the author's work.
 */
export function RichTextEditor({
  value,
  onChange,
  ariaLabel,
}: {
  value: string;
  onChange: (html: string) => void;
  ariaLabel: string;
}) {
  const { t, dir } = useAuthoringI18n();
  const [stats, setStats] = useState(() => readingStats(""));
  const [linkOpen, setLinkOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState("");

  const editor = useEditor({
    // Next.js App Router renders on the server first; TipTap must not render immediately.
    immediatelyRender: false,
    extensions: [
      StarterKit.configure({ heading: { levels: [1, 2, 3] } }),
      Underline,
      Link.configure({
        openOnClick: false,
        autolink: true,
        protocols: ["http", "https"],
        HTMLAttributes: { rel: "noopener noreferrer", target: "_blank" },
      }),
    ],
    content: value,
    editorProps: {
      attributes: {
        // `prose` styling comes from globals.css; dir keeps Arabic authoring RTL-correct.
        class: "min-h-[16rem] max-w-none px-3 py-2 focus:outline-none",
        dir,
        "aria-label": ariaLabel,
      },
    },
    onCreate: ({ editor: created }) => setStats(readingStats(created.getText())),
    onUpdate: ({ editor: updated }) => {
      setStats(readingStats(updated.getText()));
      onChange(updated.getHTML());
    },
  });

  if (!editor) {
    return <div className="h-64 animate-pulse rounded-md border border-border bg-muted/40" aria-hidden />;
  }

  const openLinkDialog = () => {
    setLinkUrl(String(editor.getAttributes("link").href ?? ""));
    setLinkOpen(true);
  };

  const applyLink = () => {
    if (!isSafeUrl(linkUrl)) return;
    editor.chain().focus().extendMarkRange("link").setLink({ href: linkUrl.trim() }).run();
    setLinkOpen(false);
  };

  return (
    <div className="rounded-md border border-border bg-background focus-within:ring-2 focus-within:ring-ring">
      <div
        role="toolbar"
        aria-label={t("richtext.toolbar")}
        aria-orientation="horizontal"
        className="flex flex-wrap items-center gap-0.5 border-b border-border p-1"
      >
        <ToolButton label={t("richtext.h1")} active={editor.isActive("heading", { level: 1 })} onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}><Heading1 className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.h2")} active={editor.isActive("heading", { level: 2 })} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}><Heading2 className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.h3")} active={editor.isActive("heading", { level: 3 })} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}><Heading3 className="size-4" aria-hidden /></ToolButton>

        <Divider />

        <ToolButton label={t("richtext.bold")} active={editor.isActive("bold")} onClick={() => editor.chain().focus().toggleBold().run()}><Bold className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.italic")} active={editor.isActive("italic")} onClick={() => editor.chain().focus().toggleItalic().run()}><Italic className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.underline")} active={editor.isActive("underline")} onClick={() => editor.chain().focus().toggleUnderline().run()}><UnderlineIcon className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.strike")} active={editor.isActive("strike")} onClick={() => editor.chain().focus().toggleStrike().run()}><Strikethrough className="size-4" aria-hidden /></ToolButton>

        <Divider />

        <ToolButton label={t("richtext.bulletList")} active={editor.isActive("bulletList")} onClick={() => editor.chain().focus().toggleBulletList().run()}><List className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.orderedList")} active={editor.isActive("orderedList")} onClick={() => editor.chain().focus().toggleOrderedList().run()}><ListOrdered className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.blockquote")} active={editor.isActive("blockquote")} onClick={() => editor.chain().focus().toggleBlockquote().run()}><Quote className="size-4" aria-hidden /></ToolButton>

        <Divider />

        <ToolButton label={t("richtext.inlineCode")} active={editor.isActive("code")} onClick={() => editor.chain().focus().toggleCode().run()}><Code className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.codeBlock")} active={editor.isActive("codeBlock")} onClick={() => editor.chain().focus().toggleCodeBlock().run()}><Square className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.hr")} onClick={() => editor.chain().focus().setHorizontalRule().run()}><Minus className="size-4" aria-hidden /></ToolButton>

        <Divider />

        <ToolButton label={t("richtext.link")} active={editor.isActive("link")} onClick={openLinkDialog}><Link2 className="size-4" aria-hidden /></ToolButton>
        <ToolButton
          label={t("richtext.unlink")}
          disabled={!editor.isActive("link")}
          onClick={() => editor.chain().focus().extendMarkRange("link").unsetLink().run()}
        >
          <Link2Off className="size-4" aria-hidden />
        </ToolButton>

        <Divider />

        <ToolButton label={t("richtext.undo")} disabled={!editor.can().undo()} onClick={() => editor.chain().focus().undo().run()}><Undo2 className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.redo")} disabled={!editor.can().redo()} onClick={() => editor.chain().focus().redo().run()}><Redo2 className="size-4" aria-hidden /></ToolButton>
        <ToolButton label={t("richtext.clearFormatting")} onClick={() => editor.chain().focus().unsetAllMarks().clearNodes().run()}><RemoveFormatting className="size-4" aria-hidden /></ToolButton>
      </div>

      <EditorContent editor={editor} />

      <div className="flex flex-wrap items-center justify-end gap-3 border-t border-border px-3 py-1.5 text-xs text-muted-foreground">
        <span>{t("richtext.words", { n: stats.words })}</span>
        <span>{t("richtext.characters", { n: stats.characters })}</span>
        <span>{t("richtext.readingTime", { n: stats.minutes })}</span>
      </div>

      <LinkDialog
        open={linkOpen}
        onOpenChange={setLinkOpen}
        url={linkUrl}
        onUrlChange={setLinkUrl}
        onApply={applyLink}
      />
    </div>
  );
}

function Divider() {
  return <span aria-hidden className="mx-0.5 h-5 w-px shrink-0 bg-border" />;
}

function ToolButton({
  label,
  active,
  disabled,
  onClick,
  children,
}: {
  label: string;
  active?: boolean;
  disabled?: boolean;
  onClick: () => void;
  children: ReactNode;
}) {
  return (
    <Button
      type="button"
      variant={active ? "secondary" : "ghost"}
      size="icon"
      className="size-8"
      aria-label={label}
      aria-pressed={active === undefined ? undefined : active}
      disabled={disabled}
      onClick={onClick}
    >
      {children}
    </Button>
  );
}

function LinkDialog({
  open,
  onOpenChange,
  url,
  onUrlChange,
  onApply,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  url: string;
  onUrlChange: (url: string) => void;
  onApply: () => void;
}) {
  const { t } = useAuthoringI18n();
  const invalid = url.trim() !== "" && !isSafeUrl(url);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>{t("richtext.linkDialog.title")}</DialogTitle>
          <DialogDescription>{t("richtext.linkDialog.desc")}</DialogDescription>
        </DialogHeader>
        <FormField label={t("field.link.url")} error={invalid ? t("validation.linkUrl") : undefined}>
          <Input
            type="url"
            inputMode="url"
            value={url}
            onChange={(e) => onUrlChange(e.target.value)}
            placeholder="https://"
          />
        </FormField>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            {t("confirm.cancel")}
          </Button>
          <Button disabled={!isSafeUrl(url)} onClick={onApply}>
            {t("richtext.linkDialog.apply")}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
