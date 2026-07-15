"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api/client";
import type { HomepagePayload } from "./api";

type State =
  | { status: "loading"; data: null; error: null }
  | { status: "success"; data: HomepagePayload; error: null }
  | { status: "error"; data: null; error: string };

/**
 * Client-side homepage fetch (used by interactive/preview surfaces). The public marketing page
 * renders server-side via getHomepage(); this hook is for client contexts that need the same
 * content with loading/error states. `preview` requests the admin draft.
 */
export function useHomepage(preview = false): State {
  const [state, setState] = useState<State>({ status: "loading", data: null, error: null });

  useEffect(() => {
    let active = true;
    const path = preview ? "homepage/preview" : "homepage";
    api
      .data<HomepagePayload>(path, { auth: !preview ? false : undefined })
      .then((data) => active && setState({ status: "success", data, error: null }))
      .catch((e: unknown) =>
        active && setState({ status: "error", data: null, error: e instanceof Error ? e.message : "Failed to load" }),
      );
    return () => {
      active = false;
    };
  }, [preview]);

  return state;
}
