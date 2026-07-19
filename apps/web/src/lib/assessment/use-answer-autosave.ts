"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { saveAnswer } from "./api";
import type { AnswerResponse } from "./types";

const DEBOUNCE_MS = 700;
const RETRY_DELAY_MS = 3_000;
const MAX_AUTO_RETRIES = 2;

export type SaveState = "idle" | "dirty" | "saving" | "saved" | "error";

/**
 * Debounced answer autosave for the learner player.
 *
 * Everything is written through the real `PUT /attempts/{id}/answers` endpoint — there is no local
 * answer store that could diverge from the server, and no optimistic "saved" state: the indicator
 * only reads `saved` once the request resolved.
 *
 * Three things this deliberately handles, because a learner losing work is the worst failure mode
 * this feature has:
 *
 *   • Coalescing — typing into a short-answer box fires one request when they pause, not per key.
 *   • Ordering  — a queued save always sends the LATEST value for that question, so a slow request
 *                 can never overwrite a newer answer with an older one.
 *   • Retry     — a transient failure retries automatically twice before surfacing, since a blip
 *                 mid-exam should not make a learner think their answer was lost.
 *
 * A 4xx is NOT retried: those mean the attempt is closed or the question was not served, and
 * retrying would just fail again while hiding the real message.
 */
export function useAnswerAutosave(attemptId: string | null, enabled: boolean) {
  const [state, setState] = useState<SaveState>("idle");
  const [error, setError] = useState<string | null>(null);

  /** Latest pending value per question — the queue collapses to one entry per question. */
  const pending = useRef(new Map<string, AnswerResponse | null>());
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const inFlight = useRef(false);
  const retries = useRef(0);

  /**
   * Points at the current `flush`. The retry below is scheduled from inside `flush` itself, and a
   * callback cannot close over its own binding without capturing a stale one — so the timer calls
   * through this ref and always re-enters the latest closure.
   */
  const flushRef = useRef<() => Promise<void>>(async () => {});

  const flush = useCallback(async () => {
    if (!attemptId || inFlight.current || pending.current.size === 0) return;

    // Snapshot and clear: anything typed during the request re-queues for the next flush rather
    // than being dropped.
    const batch = [...pending.current.entries()];
    pending.current.clear();
    inFlight.current = true;
    setState("saving");

    try {
      for (const [questionId, response] of batch) {
        await saveAnswer(attemptId, questionId, response);
      }
      retries.current = 0;
      setError(null);
      setState(pending.current.size > 0 ? "dirty" : "saved");
    } catch (e) {
      // Put the batch back so nothing is lost, unless a newer value already superseded it.
      for (const [questionId, response] of batch) {
        if (!pending.current.has(questionId)) pending.current.set(questionId, response);
      }

      const status = (e as { status?: number }).status;
      const retriable = status === undefined || status >= 500;

      if (retriable && retries.current < MAX_AUTO_RETRIES) {
        retries.current += 1;
        setState("dirty");
        timer.current = setTimeout(() => void flushRef.current(), RETRY_DELAY_MS);
      } else {
        setError(e instanceof Error ? e.message : String(e));
        setState("error");
      }
    } finally {
      inFlight.current = false;
    }
  }, [attemptId]);

  useEffect(() => {
    flushRef.current = flush;
  }, [flush]);

  const queue = useCallback(
    (questionId: string, response: AnswerResponse | null) => {
      if (!enabled) return;

      pending.current.set(questionId, response);
      setState("dirty");

      if (timer.current) clearTimeout(timer.current);
      timer.current = setTimeout(() => void flush(), DEBOUNCE_MS);
    },
    [enabled, flush],
  );

  /** Force-write everything queued. Used before submitting so no answer is left behind. */
  const flushNow = useCallback(async () => {
    if (timer.current) clearTimeout(timer.current);
    await flush();
  }, [flush]);

  useEffect(() => () => {
    if (timer.current) clearTimeout(timer.current);
  }, []);

  return { state, error, queue, flushNow, hasPending: () => pending.current.size > 0 };
}
