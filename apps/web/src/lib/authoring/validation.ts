/**
 * Course Builder — validation. Pure functions used for inline validation + the toolbar's issue
 * count. No side effects; message keys resolve against authoring-i18n.
 */
import { blockDef } from "./block-registry";
import type { Block, Curriculum, Section, ValidationIssue } from "./types";

function isValidUrl(value: string): boolean {
  try {
    const u = new URL(value);
    return u.protocol === "http:" || u.protocol === "https:";
  } catch {
    return false;
  }
}

export function validateBlock(block: Block): ValidationIssue[] {
  const issues: ValidationIssue[] = [];
  if (!block.title.trim()) {
    issues.push({ level: "error", target: `lesson:${block.id}`, message: "validation.blockTitle" });
  }
  if (block.kind === "external_link") {
    const url = typeof block.content.url === "string" ? block.content.url : "";
    if (!isValidUrl(url)) issues.push({ level: "error", target: `lesson:${block.id}`, message: "validation.linkUrl" });
  }
  return issues;
}

export function validateSection(section: Section): ValidationIssue[] {
  const issues: ValidationIssue[] = [];
  if (!section.title.trim()) {
    issues.push({ level: "error", target: `section:${section.id}`, message: "validation.sectionTitle" });
  }
  if (section.blocks.length === 0) {
    issues.push({ level: "warning", target: `section:${section.id}`, message: "validation.emptySection" });
  }
  for (const b of section.blocks) issues.push(...validateBlock(b));
  return issues;
}

export function validateCurriculum(curriculum: Curriculum): ValidationIssue[] {
  const issues: ValidationIssue[] = [];
  if (curriculum.sections.length === 0) {
    issues.push({ level: "warning", target: "course", message: "validation.emptyCourse" });
  }
  for (const s of curriculum.sections) issues.push(...validateSection(s));
  return issues;
}

/** True when this block kind is fully editable/persistable in the current backend. */
export function blockIsPersistable(block: Block): boolean {
  return blockDef(block.kind).supported;
}

export function errorCount(issues: ValidationIssue[]): number {
  return issues.filter((i) => i.level === "error").length;
}
