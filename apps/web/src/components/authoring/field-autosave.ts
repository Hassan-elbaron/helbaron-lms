"use client";

import { useEffect, useRef, useState } from "react";

/**
 * Local text state that autosaves (debounced) and on blur, without ever calling setState inside an
 * effect. `commit` should be idempotent for unchanged values (the store actions early-return).
 */
export function useFieldAutosave(initial: string, commit: (value: string) => void | Promise<void>, delay = 600) {
  const [value, setValue] = useState(initial);
  const committed = useRef(initial);

  useEffect(() => {
    if (value === committed.current) return;
    const id = setTimeout(() => {
      committed.current = value;
      void commit(value);
    }, delay);
    return () => clearTimeout(id);
  }, [value, commit, delay]);

  const flush = () => {
    if (value !== committed.current) {
      committed.current = value;
      void commit(value);
    }
  };

  return { value, setValue, flush };
}
