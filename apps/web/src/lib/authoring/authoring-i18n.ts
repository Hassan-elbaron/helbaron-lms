/**
 * Course Builder — module-local i18n.
 *
 * Reuses the app's locale + direction from `useI18n`, but keeps the builder's (many, specialised)
 * strings self-contained here instead of bloating the global dictionary. Same `t(key, vars)` shape
 * and RTL behaviour as the rest of the app. Missing keys fall back to the key (dev-visible).
 */
"use client";

import { useI18n } from "@/lib/i18n/i18n-context";

type Dict = Record<string, string>;

const en: Dict = {
  "builder.saving": "Saving…",
  "builder.saved": "All changes saved",
  "builder.dirty": "Unsaved changes",
  "builder.error": "Couldn't save",
  "builder.idle": "Up to date",
  "builder.loadError": "Couldn't load the curriculum.",
  "builder.retry": "Retry",

  "tree.curriculum": "Curriculum",
  "tree.search": "Search sections & lessons",
  "tree.addSection": "Add section",
  "tree.addBlock": "Add content",
  "tree.expandAll": "Expand all",
  "tree.collapseAll": "Collapse all",
  "tree.empty.title": "No sections yet",
  "tree.empty.desc": "Start by adding your first section, then fill it with lessons.",
  "tree.emptySection": "No lessons — add content",
  "tree.lessons.one": "1 lesson",
  "tree.lessons.other": "{count} lessons",
  "tree.noResults": "No matches for “{q}”",

  "group.content": "Content",
  "group.media": "Media",
  "group.interactive": "Interactive",
  "group.package": "Packages",
  "group.engagement": "Engagement",

  "action.rename": "Rename",
  "action.duplicate": "Duplicate",
  "action.delete": "Delete",
  "action.publish": "Publish",
  "action.unpublish": "Unpublish",
  "action.moveUp": "Move up",
  "action.moveDown": "Move down",
  "action.preview": "Toggle preview",
  "action.edit": "Edit",
  "action.dragHandle": "Reorder",

  "status.draft": "Draft",
  "status.published": "Published",
  "status.preview": "Free preview",

  "node.course": "Course",
  "node.section": "Section",
  "node.subsection": "Sub-section",
  "node.lesson": "Lesson",

  "editor.empty.title": "Select something to edit",
  "editor.empty.desc": "Pick a section or lesson from the curriculum, or add a new one.",
  "editor.course.title": "Course overview",
  "editor.course.desc": "This is the top of your curriculum. Add sections on the left, then lessons inside them.",
  "editor.section.titleLabel": "Section title",
  "editor.section.summaryLabel": "Section summary",
  "editor.section.summaryHint": "Shown to learners above the section's lessons (optional).",
  "editor.block.titleLabel": "Lesson title",
  "editor.unsupported.title": "Coming soon",
  "editor.unsupported.desc": "The {kind} block is designed and ready in the builder, but saving it needs backend support that isn't available yet. You can still place it to plan your curriculum.",

  "field.article.body": "Article content",
  "field.article.hint": "Rich lesson text. Sanitised on render.",
  "field.link.url": "URL",
  "field.link.label": "Link label",
  "field.quiz.note": "Author note",
  "field.quiz.hint": "A placeholder until the full quiz builder ships.",
  "field.media.title": "Media",
  "field.media.hint": "Upload / attach handled by the media pipeline (Mux / S3).",
  "field.media.none": "No media attached yet.",

  "inspector.title": "Inspector",
  "inspector.empty": "Select a section or lesson to see its properties.",
  "inspector.group.publishing": "Publishing",
  "inspector.group.visibility": "Visibility",
  "inspector.group.access": "Access",
  "inspector.group.schedule": "Schedule",
  "inspector.group.completion": "Completion",
  "inspector.group.seo": "SEO",
  "inspector.group.resources": "Resources & attachments",
  "inspector.group.metadata": "Metadata",
  "inspector.publishState": "Publish state",
  "inspector.freePreview": "Free preview",
  "inspector.freePreviewHint": "Let non-enrolled learners view this lesson.",
  "inspector.duration": "Estimated duration",
  "inspector.durationValue": "{min} min",
  "inspector.durationUnknown": "Not set",
  "inspector.prereqs": "Prerequisites",
  "inspector.prereqsNone": "None",
  "inspector.todo": "Backend integration pending",

  "toolbar.undo": "Undo",
  "toolbar.redo": "Redo",
  "toolbar.preview": "Preview",
  "toolbar.version": "v{n}",
  "toolbar.backToCourse": "Back to course",

  "confirm.deleteSection.title": "Delete this section?",
  "confirm.deleteSection.desc": "“{title}” and its lessons will be permanently removed.",
  "confirm.deleteBlock.title": "Delete this lesson?",
  "confirm.deleteBlock.desc": "“{title}” will be permanently removed.",
  "confirm.delete": "Delete",
  "confirm.cancel": "Cancel",

  "toast.saved": "Changes saved",
  "toast.saveError": "Couldn't save your changes",
  "toast.created": "Added",
  "toast.deleted": "Deleted",
  "toast.reordered": "Order updated",
  "toast.published": "Published",
  "toast.unpublished": "Moved to draft",
  "toast.unsupported": "That block type isn't available to save yet",

  "validation.sectionTitle": "Section needs a title",
  "validation.blockTitle": "Lesson needs a title",
  "validation.linkUrl": "Enter a valid URL",
  "validation.emptyCourse": "Add at least one section",
  "validation.emptySection": "Section “{title}” has no lessons",

  "richtext.toolbar": "Formatting",
  "richtext.h1": "Heading 1", "richtext.h2": "Heading 2", "richtext.h3": "Heading 3",
  "richtext.bold": "Bold", "richtext.italic": "Italic", "richtext.underline": "Underline", "richtext.strike": "Strikethrough",
  "richtext.bulletList": "Bulleted list", "richtext.orderedList": "Numbered list", "richtext.blockquote": "Quote",
  "richtext.inlineCode": "Inline code", "richtext.codeBlock": "Code block", "richtext.hr": "Divider",
  "richtext.link": "Add link", "richtext.unlink": "Remove link",
  "richtext.undo": "Undo", "richtext.redo": "Redo", "richtext.clearFormatting": "Clear formatting",
  "richtext.words": "{n} words", "richtext.characters": "{n} characters", "richtext.readingTime": "~{n} min read",
  "richtext.linkDialog.title": "Add a link",
  "richtext.linkDialog.desc": "Only http and https links are allowed.",
  "richtext.linkDialog.apply": "Apply",

  "field.audio.transcript": "Transcript",
  "field.audio.transcriptHint": "Plain text only — transcripts are stored unsanitised, so HTML is not accepted.",

  "media.title": "Media",
  "media.desc": "Reference an already-uploaded asset. Uploading files from the builder needs a backend upload endpoint that does not exist yet.",
  "media.playbackId": "Mux playback ID",
  "media.storageKey": "Storage key (S3)",
  "media.mimeType": "MIME type",
  "media.duration": "Duration (seconds)",
  "media.filesize": "File size (bytes)",
  "media.attached": "Attached",
  "media.none": "No media attached yet.",
  "media.remove": "Remove media",
  "media.save": "Save media",
  "media.needsSource": "Provide a Mux playback ID or a storage key.",
  "media.numberInvalid": "Enter a whole number of 0 or more.",

  "link.test": "Test link",
  "link.unsafe": "Only http and https links are allowed.",

  "new.section": "Untitled section",
  "new.block": "Untitled {kind}",

  // Block labels / descriptions
  "block.article.label": "Article", "block.article.desc": "Rich text lesson",
  "block.pdf.label": "PDF", "block.pdf.desc": "Document viewer",
  "block.download.label": "Download", "block.download.desc": "Downloadable file",
  "block.external_link.label": "External link", "block.external_link.desc": "Link out to a resource",
  "block.video.label": "Video", "block.video.desc": "Streamed video lesson",
  "block.audio.label": "Audio", "block.audio.desc": "Audio lesson",
  "block.live_session.label": "Live session", "block.live_session.desc": "Scheduled live class",
  "block.quiz_placeholder.label": "Quiz (basic)", "block.quiz_placeholder.desc": "Placeholder quiz block",
  "block.quiz.label": "Quiz", "block.quiz.desc": "Graded questions",
  "block.assignment.label": "Assignment", "block.assignment.desc": "Submitted & graded work",
  "block.survey.label": "Survey", "block.survey.desc": "Ungraded feedback",
  "block.scorm.label": "SCORM", "block.scorm.desc": "SCORM package",
  "block.xapi.label": "xAPI", "block.xapi.desc": "xAPI activity",
  "block.cmi5.label": "cmi5", "block.cmi5.desc": "cmi5 assignable unit",
  "block.discussion.label": "Discussion", "block.discussion.desc": "Threaded discussion",
  "block.certificate.label": "Certificate", "block.certificate.desc": "Completion certificate",
};

const ar: Dict = {
  "builder.saving": "جارٍ الحفظ…",
  "builder.saved": "تم حفظ كل التغييرات",
  "builder.dirty": "تغييرات غير محفوظة",
  "builder.error": "تعذّر الحفظ",
  "builder.idle": "محدّث",
  "builder.loadError": "تعذّر تحميل المنهج.",
  "builder.retry": "إعادة المحاولة",

  "tree.curriculum": "المنهج",
  "tree.search": "ابحث في الأقسام والدروس",
  "tree.addSection": "إضافة قسم",
  "tree.addBlock": "إضافة محتوى",
  "tree.expandAll": "توسيع الكل",
  "tree.collapseAll": "طي الكل",
  "tree.empty.title": "لا توجد أقسام بعد",
  "tree.empty.desc": "ابدأ بإضافة أول قسم، ثم املأه بالدروس.",
  "tree.emptySection": "لا دروس — أضف محتوى",
  "tree.lessons.one": "درس واحد",
  "tree.lessons.other": "{count} دروس",
  "tree.noResults": "لا نتائج لـ “{q}”",

  "group.content": "محتوى",
  "group.media": "وسائط",
  "group.interactive": "تفاعلي",
  "group.package": "حزم",
  "group.engagement": "تفاعل",

  "action.rename": "إعادة تسمية",
  "action.duplicate": "تكرار",
  "action.delete": "حذف",
  "action.publish": "نشر",
  "action.unpublish": "إلغاء النشر",
  "action.moveUp": "تحريك لأعلى",
  "action.moveDown": "تحريك لأسفل",
  "action.preview": "تبديل المعاينة",
  "action.edit": "تحرير",
  "action.dragHandle": "إعادة الترتيب",

  "status.draft": "مسودة",
  "status.published": "منشور",
  "status.preview": "معاينة مجانية",

  "node.course": "الدورة",
  "node.section": "قسم",
  "node.subsection": "قسم فرعي",
  "node.lesson": "درس",

  "editor.empty.title": "اختر عنصرًا لتحريره",
  "editor.empty.desc": "اختر قسمًا أو درسًا من المنهج، أو أضف جديدًا.",
  "editor.course.title": "نظرة عامة على الدورة",
  "editor.course.desc": "هذا رأس المنهج. أضف الأقسام على اليسار، ثم الدروس داخلها.",
  "editor.section.titleLabel": "عنوان القسم",
  "editor.section.summaryLabel": "ملخص القسم",
  "editor.section.summaryHint": "يظهر للمتعلّمين فوق دروس القسم (اختياري).",
  "editor.block.titleLabel": "عنوان الدرس",
  "editor.unsupported.title": "قريبًا",
  "editor.unsupported.desc": "بلوك {kind} جاهز في الواجهة، لكن حفظه يحتاج دعمًا من الخادم غير متوفر بعد. يمكنك وضعه لتخطيط المنهج.",

  "field.article.body": "محتوى المقال",
  "field.article.hint": "نص ثريّ للدرس. يُعقّم عند العرض.",
  "field.link.url": "الرابط",
  "field.link.label": "نص الرابط",
  "field.quiz.note": "ملاحظة المؤلّف",
  "field.quiz.hint": "عنصر نائب ريثما يتوفر منشئ الاختبارات الكامل.",
  "field.media.title": "الوسائط",
  "field.media.hint": "الرفع / الإرفاق عبر خط الوسائط (Mux / S3).",
  "field.media.none": "لا توجد وسائط بعد.",

  "inspector.title": "الخصائص",
  "inspector.empty": "اختر قسمًا أو درسًا لعرض خصائصه.",
  "inspector.group.publishing": "النشر",
  "inspector.group.visibility": "الظهور",
  "inspector.group.access": "الوصول",
  "inspector.group.schedule": "الجدولة",
  "inspector.group.completion": "الإكمال",
  "inspector.group.seo": "تحسين الظهور",
  "inspector.group.resources": "الموارد والمرفقات",
  "inspector.group.metadata": "بيانات وصفية",
  "inspector.publishState": "حالة النشر",
  "inspector.freePreview": "معاينة مجانية",
  "inspector.freePreviewHint": "اسمح لغير المسجلين بمشاهدة هذا الدرس.",
  "inspector.duration": "المدة التقديرية",
  "inspector.durationValue": "{min} دقيقة",
  "inspector.durationUnknown": "غير محددة",
  "inspector.prereqs": "المتطلبات السابقة",
  "inspector.prereqsNone": "لا يوجد",
  "inspector.todo": "بانتظار تكامل الخادم",

  "toolbar.undo": "تراجع",
  "toolbar.redo": "إعادة",
  "toolbar.preview": "معاينة",
  "toolbar.version": "إصدار {n}",
  "toolbar.backToCourse": "العودة للدورة",

  "confirm.deleteSection.title": "حذف هذا القسم؟",
  "confirm.deleteSection.desc": "سيُحذف “{title}” ودروسه نهائيًا.",
  "confirm.deleteBlock.title": "حذف هذا الدرس؟",
  "confirm.deleteBlock.desc": "سيُحذف “{title}” نهائيًا.",
  "confirm.delete": "حذف",
  "confirm.cancel": "إلغاء",

  "toast.saved": "تم حفظ التغييرات",
  "toast.saveError": "تعذّر حفظ التغييرات",
  "toast.created": "تمت الإضافة",
  "toast.deleted": "تم الحذف",
  "toast.reordered": "تم تحديث الترتيب",
  "toast.published": "تم النشر",
  "toast.unpublished": "نُقل إلى المسودة",
  "toast.unsupported": "هذا النوع غير متاح للحفظ بعد",

  "validation.sectionTitle": "القسم يحتاج عنوانًا",
  "validation.blockTitle": "الدرس يحتاج عنوانًا",
  "validation.linkUrl": "أدخل رابطًا صحيحًا",
  "validation.emptyCourse": "أضف قسمًا واحدًا على الأقل",
  "validation.emptySection": "القسم “{title}” بلا دروس",

  "richtext.toolbar": "التنسيق",
  "richtext.h1": "عنوان 1", "richtext.h2": "عنوان 2", "richtext.h3": "عنوان 3",
  "richtext.bold": "عريض", "richtext.italic": "مائل", "richtext.underline": "تسطير", "richtext.strike": "يتوسطه خط",
  "richtext.bulletList": "قائمة نقطية", "richtext.orderedList": "قائمة مرقّمة", "richtext.blockquote": "اقتباس",
  "richtext.inlineCode": "كود مضمّن", "richtext.codeBlock": "كتلة كود", "richtext.hr": "فاصل",
  "richtext.link": "إضافة رابط", "richtext.unlink": "إزالة الرابط",
  "richtext.undo": "تراجع", "richtext.redo": "إعادة", "richtext.clearFormatting": "مسح التنسيق",
  "richtext.words": "{n} كلمة", "richtext.characters": "{n} حرف", "richtext.readingTime": "~{n} دقيقة قراءة",
  "richtext.linkDialog.title": "إضافة رابط",
  "richtext.linkDialog.desc": "يُسمح بروابط http و https فقط.",
  "richtext.linkDialog.apply": "تطبيق",

  "field.audio.transcript": "النص المكتوب",
  "field.audio.transcriptHint": "نص عادي فقط — لا تُعقّم النصوص المكتوبة، لذا لا يُقبل HTML.",

  "media.title": "الوسائط",
  "media.desc": "أشر إلى ملف مرفوع مسبقًا. رفع الملفات من المنشئ يحتاج نقطة رفع في الخادم غير متوفرة بعد.",
  "media.playbackId": "معرّف التشغيل (Mux)",
  "media.storageKey": "مفتاح التخزين (S3)",
  "media.mimeType": "نوع الملف",
  "media.duration": "المدة (بالثواني)",
  "media.filesize": "الحجم (بايت)",
  "media.attached": "مرفق",
  "media.none": "لا توجد وسائط بعد.",
  "media.remove": "إزالة الوسائط",
  "media.save": "حفظ الوسائط",
  "media.needsSource": "أدخل معرّف تشغيل Mux أو مفتاح تخزين.",
  "media.numberInvalid": "أدخل رقمًا صحيحًا 0 أو أكثر.",

  "link.test": "اختبار الرابط",
  "link.unsafe": "يُسمح بروابط http و https فقط.",

  "new.section": "قسم بلا عنوان",
  "new.block": "{kind} بلا عنوان",

  "block.article.label": "مقال", "block.article.desc": "درس نصي ثري",
  "block.pdf.label": "PDF", "block.pdf.desc": "عارض مستندات",
  "block.download.label": "تنزيل", "block.download.desc": "ملف قابل للتنزيل",
  "block.external_link.label": "رابط خارجي", "block.external_link.desc": "رابط لمصدر خارجي",
  "block.video.label": "فيديو", "block.video.desc": "درس فيديو",
  "block.audio.label": "صوت", "block.audio.desc": "درس صوتي",
  "block.live_session.label": "جلسة مباشرة", "block.live_session.desc": "حصة مباشرة مجدولة",
  "block.quiz_placeholder.label": "اختبار (مبسّط)", "block.quiz_placeholder.desc": "بلوك اختبار نائب",
  "block.quiz.label": "اختبار", "block.quiz.desc": "أسئلة مُقيّمة",
  "block.assignment.label": "تكليف", "block.assignment.desc": "عمل يُسلّم ويُقيّم",
  "block.survey.label": "استبيان", "block.survey.desc": "تغذية راجعة غير مُقيّمة",
  "block.scorm.label": "SCORM", "block.scorm.desc": "حزمة SCORM",
  "block.xapi.label": "xAPI", "block.xapi.desc": "نشاط xAPI",
  "block.cmi5.label": "cmi5", "block.cmi5.desc": "وحدة cmi5",
  "block.discussion.label": "نقاش", "block.discussion.desc": "نقاش تفاعلي",
  "block.certificate.label": "شهادة", "block.certificate.desc": "شهادة إتمام",
};

const DICTS: Record<string, Dict> = { en, ar };

/** Exported for the EN/AR key-parity test — the builder ships bilingual, so drift is a defect. */
export const AUTHORING_DICTS: Readonly<Record<string, Dict>> = DICTS;

export type AuthoringT = (key: string, vars?: Record<string, string | number>) => string;

export interface UseAuthoringI18n {
  t: AuthoringT;
  locale: string;
  dir: "ltr" | "rtl";
}

/** Builder-scoped translator; reuses the app locale + direction. */
export function useAuthoringI18n(): UseAuthoringI18n {
  const { locale, dir } = useI18n();
  const dict = DICTS[locale] ?? en;
  const t: AuthoringT = (key, vars) => {
    let s = dict[key] ?? en[key] ?? key;
    if (vars) {
      for (const [k, v] of Object.entries(vars)) s = s.replace(`{${k}}`, String(v));
    }
    return s;
  };
  return { t, locale, dir };
}
