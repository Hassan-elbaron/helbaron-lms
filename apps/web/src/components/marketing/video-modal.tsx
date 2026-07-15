"use client";

import { useEffect, useRef } from "react";
import { X } from "lucide-react";

/** Canonical YouTube video ids are exactly 11 chars of [A-Za-z0-9_-]. */
const YOUTUBE_ID_PATTERN = /^[A-Za-z0-9_-]{11}$/;

export function VideoModal({ videoId, onClose }: { videoId: string | null; onClose: () => void }) {
  const closeRef = useRef<HTMLButtonElement>(null);
  const dialogRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!videoId) return;
    // Remember what was focused so we can restore it when the dialog closes.
    const previouslyFocused = document.activeElement as HTMLElement | null;

    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        onClose();
        return;
      }
      // Focus trap: keep Tab focus inside the dialog.
      if (e.key === "Tab" && dialogRef.current) {
        const focusable = dialogRef.current.querySelectorAll<HTMLElement>(
          'button, a[href], iframe, [tabindex]:not([tabindex="-1"])',
        );
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    document.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    // Move focus into the dialog (initial focus on the close button).
    closeRef.current?.focus();

    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
      previouslyFocused?.focus?.();
    };
  }, [videoId, onClose]);

  if (!videoId) return null;
  // Reject anything that is not a canonical YouTube id before it reaches the embed URL.
  if (!YOUTUBE_ID_PATTERN.test(videoId)) return null;
  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-label="Course preview video"
    >
      <div ref={dialogRef} className="relative w-full max-w-3xl" onClick={(e) => e.stopPropagation()}>
        <button
          ref={closeRef}
          onClick={onClose}
          aria-label="Close"
          className="absolute -top-11 end-0 flex size-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20"
        >
          <X className="size-5" aria-hidden />
        </button>
        <div className="aspect-video overflow-hidden rounded-2xl border border-white/10 shadow-2xl">
          <iframe
            className="size-full"
            src={`https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?autoplay=1&rel=0&modestbranding=1`}
            title="Course preview"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowFullScreen
          />
        </div>
      </div>
    </div>
  );
}
